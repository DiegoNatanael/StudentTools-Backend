"""
council.py — The Council of Models Pipeline (V2 — Direct LaTeX)

Instead of fighting AI models to output a rigid JSON schema,
we now let the AI write LaTeX directly. This eliminates:
  - JSON schema mismatches (Spanish keys, wrong structure)
  - The Polisher stage entirely
  - Pydantic validation failures on AI output

Pipeline:
  1. Architect: Classifies scale (light/medium/deep) — quick call
  2. Writer: Generates COMPLETE, compilable LaTeX code — one big call
  
That's it. No JSON middleman. No Polisher. Direct to PDF.
"""

import re
import json
from nim_client import NIMClient

# --- The Council of Models ---
# Architect: Llama 3.1 8B — ultra high-speed, instant structured JSON & diagram outputs
nim_architect = NIMClient(model="meta/llama-3.1-8b-instruct")
# Writer: Llama 3.1 8B — ultra high-speed, instant Markdown & LaTeX generation
nim_writer = NIMClient(model="meta/llama-3.1-8b-instruct")


LATEX_TEMPLATE_INSTRUCTIONS = r"""
You are an elite LaTeX document generator. Output a COMPLETE, COMPILABLE LaTeX document.

REQUIRED PREAMBLE (copy exactly):
\documentclass[12pt, a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[spanish, es-tabla]{babel}
\usepackage{amsmath, amsfonts, amssymb, amsthm}
\usepackage{geometry}
\usepackage{setspace}
\usepackage{fancyhdr}
\usepackage{hyperref}
\usepackage{xcolor}
\usepackage{titlesec}
\usepackage{array}
\usepackage{booktabs}
\usepackage{enumitem}

\definecolor{primary}{HTML}{003366}
\geometry{left=2.5cm, right=2.5cm, top=2.5cm, bottom=2.5cm}
\onehalfspacing

RULES:
1. Output ONLY LaTeX code. No markdown. No explanations. Start with \documentclass
2. The document MUST compile with pdflatex — do NOT use packages that aren't standard
3. Write ALL content in Spanish
4. Use \section{}, \subsection{}, \subsubsection{} for structure
5. Use equation environments for math: \begin{equation} ... \end{equation}
6. Use itemize/enumerate for lists
7. Use tabular for tables with \hline and \textbf for headers
8. Use \fbox{\parbox{...}} for definitions and theorems
9. End with \end{document}
10. DO NOT use \includegraphics or reference external files
"""


def classify_document_scale(topic: str) -> dict:
    """Quick classification of user intent to determine document scale."""
    print("[Scale Analysis] Determining document complexity...")
    
    import json
    classifier_prompt = (
        f"Analyze this document request and respond with ONLY a JSON object:\n"
        f"Topic: {topic}\n\n"
        "Classify:\n"
        '- Short summary/cheat sheet: {"pages": 3, "depth": "light"}\n'
        '- Standard report/essay: {"pages": 7, "depth": "medium"}\n'
        '- Deep investigation/research: {"pages": 15, "depth": "deep"}\n'
        '- If user mentions page count, respect it.\n\n'
        "Output ONLY the JSON."
    )
    
    try:
        res = nim_architect.generate_chat(
            [{"role": "user", "content": classifier_prompt}],
            temperature=0.1, max_tokens=100,
            response_format={"type": "json_object"}
        )
        raw = res['choices'][0]['message']['content'].strip()
        raw = raw.replace("```json", "").replace("```", "").strip()
        first, last = raw.find("{"), raw.rfind("}")
        if first != -1 and last != -1:
            raw = raw[first:last+1]
        classification = json.loads(raw)
    except Exception as e:
        print(f"[Scale Analysis] Classification failed, defaulting to medium: {e}")
        classification = {"pages": 7, "depth": "medium"}
    
    pages = classification.get("pages", 7)
    depth = classification.get("depth", "medium")
    
    token_map = {
        "light":  {"writer_tokens": 3000,  "cover": False, "toc": False},
        "medium": {"writer_tokens": 4000,  "cover": True,  "toc": True},
        "deep":   {"writer_tokens": 4096,  "cover": True,  "toc": True},
    }
    
    config = token_map.get(depth, token_map["medium"])
    config["pages"] = pages
    config["depth"] = depth
    
    print(f"[Scale Analysis] Result: {pages} pages, depth={depth}, tokens={config['writer_tokens']}")
    return config


def build_cover_instructions(scale: dict) -> str:
    """Generate cover page instructions based on scale."""
    if scale["cover"]:
        return r"""
COVER PAGE: Include a professional title page with:
\begin{titlepage}
    \centering
    \vspace*{2cm}
    {\Huge \bfseries \color{primary} TITLE} \\[1cm]
    \rule{\linewidth}{1pt} \\[0.5cm]
    {\Large Subtitle} \\[3cm]
    {\large \today}
\end{titlepage}
And include \tableofcontents\newpage after it.
"""
    else:
        return r"""
HEADER: Use a simple centered header (no title page):
\begin{center}
    {\Huge \bfseries \color{primary} TITLE} \\[0.3cm]
\end{center}
\vspace{0.5cm}\hrule\vspace{0.8cm}
Do NOT include \tableofcontents.
"""


def sanitize_latex(latex: str) -> str:
    """Clean up common AI mistakes in LaTeX output."""
    # Remove markdown fences
    latex = re.sub(r"```(?:latex|tex)?", "", latex).replace("```", "").strip()
    
    # Ensure it starts with \documentclass
    doc_start = latex.find(r"\documentclass")
    if doc_start > 0:
        latex = latex[doc_start:]
    elif doc_start == -1:
        # AI didn't include preamble — this is bad but we'll try to wrap it
        print("[Sanitizer] WARNING: No \\documentclass found, wrapping content...")
        latex = (
            r"\documentclass[12pt, a4paper]{article}" + "\n"
            r"\usepackage[utf8]{inputenc}" + "\n"
            r"\usepackage[T1]{fontenc}" + "\n"
            r"\usepackage[spanish, es-tabla]{babel}" + "\n"
            r"\usepackage{amsmath, amsfonts, amssymb}" + "\n"
            r"\usepackage{geometry}" + "\n"
            r"\usepackage{xcolor}" + "\n"
            r"\usepackage{hyperref}" + "\n"
            r"\definecolor{primary}{HTML}{003366}" + "\n"
            r"\geometry{left=2.5cm, right=2.5cm, top=2.5cm, bottom=2.5cm}" + "\n"
            r"\begin{document}" + "\n"
            + latex + "\n"
            r"\end{document}"
        )
    
    # Ensure it ends with \end{document}
    if r"\end{document}" not in latex:
        latex += "\n" + r"\end{document}"
    
    # Remove duplicate \end{document}
    parts = latex.split(r"\end{document}")
    latex = parts[0] + r"\end{document}"
    
    return latex


def run_document_pipeline(topic: str) -> str:
    """
    Runs the V2 Direct LaTeX pipeline:
    1. Classify scale
    2. Generate complete LaTeX document
    
    Returns: str (complete, compilable LaTeX code)
    """
    print(f"\n{'='*60}")
    print(f"  COUNCIL V2 (Direct LaTeX) — Topic: {topic}")
    print(f"{'='*60}")
    
    # --- STEP 0: CLASSIFY ---
    scale = classify_document_scale(topic)
    pages = scale["pages"]
    writer_tokens = scale["writer_tokens"]
    cover_instructions = build_cover_instructions(scale)
    
    # --- STEP 1: GENERATE COMPLETE LATEX ---
    print(f"\n[Writer] Generating {pages}-page LaTeX document ({writer_tokens} tokens)...")
    
    writer_prompt = (
        f"Create a {pages}-page professional academic document about:\n"
        f"{topic}\n\n"
        f"{LATEX_TEMPLATE_INSTRUCTIONS}\n"
        f"{cover_instructions}\n"
        f"TARGET: {pages} pages of dense, high-quality academic content in Spanish.\n"
        f"Each section must have at least 3-4 substantial paragraphs.\n"
        "Include equations, definitions, and tables where appropriate.\n"
        "Output the COMPLETE LaTeX document from \\documentclass to \\end{document}.\n"
    )
    
    writer_res = nim_writer.generate_chat(
        [
            {"role": "system", "content": "You are a LaTeX document generator. Output ONLY compilable LaTeX code. No explanations. No markdown."},
            {"role": "user", "content": writer_prompt}
        ],
        temperature=0.7, max_tokens=writer_tokens
    )
    
    raw_latex = writer_res['choices'][0]['message']['content']
    print(f"[Writer] Raw output: {len(raw_latex)} chars")
    print(f"[Writer] Preview: {raw_latex[:200]}...\n")
    
    # --- STEP 2: SANITIZE ---
    clean_latex = sanitize_latex(raw_latex)
    print(f"[Sanitizer] Clean output: {len(clean_latex)} chars")
    
    # Quick validation
    begin_count = clean_latex.count(r"\begin{")
    end_count = clean_latex.count(r"\end{")
    print(f"[Validator] \\begin count: {begin_count}, \\end count: {end_count}")
    if begin_count != end_count:
        print(f"[Validator] WARNING: Mismatched environments ({begin_count} vs {end_count})")
    
    print(f"{'='*60}")
    print(f"  PIPELINE COMPLETE — {len(clean_latex)} chars of LaTeX")
    print(f"{'='*60}\n")
    
    return clean_latex


def run_diagram_pipeline(topic: str, diagram_type: str) -> str:
    """
    Runs the advanced visual diagram generation pipeline.
    1. Architect: Extrapolates detailed processes & structures (especially for low-context topics)
    2. Coder: Generates beautifully themed and styled Mermaid.js code matching our dark glassmorphism theme
    """
    print(f"\n[Diagram Pipeline] Type: {diagram_type}, Topic: {topic}")
    
    planner_system = (
        "You are an expert academic and educational visual architect. "
        "Your task is to plan a highly detailed, comprehensive, and advanced diagram structure.\n"
        "CRITICAL RULES:\n"
        "1. NEVER tell the user that the topic is too broad, simple, or request more information. "
        "If they write a simple word or low-context phrase (e.g. 'célula', 'ciclo del agua', 'fotosíntesis'), "
        "use your deep general knowledge to extrapolate and guess a highly educational, comprehensive, step-by-step diagram.\n"
        "2. A professional diagram MUST have depth. Avoid boring linear paths of 3 boxes. Plan multiple branches, "
        "decision diamonds (yes/no paths), loops, or sub-processes where relevant to make the diagram look visually advanced, complex, and beautiful.\n"
        f"3. DYNAMIC TYPE ADAPTATION (CRITICAL):\n"
        f"   You MUST adapt the topic '{topic}' to fit the specific structural logic of the diagram type '{diagram_type}':\n"
        f"   - If 'Timeline': A timeline is chronological. Represent the topic '{topic}' as a sequential chronological progression over time (e.g., 'El viaje temporal de una gota de agua: Día 1 Evaporación, Día 2 Condensación...' or the historical evolution of scientific discoveries related to the topic).\n"
        f"   - If 'Sequence Diagram': A sequence diagram shows actor communication. Identify 3-4 active components in the topic '{topic}' as ACTORS (e.g., 'Sol', 'Océano', 'Atmósfera', 'Tierra' for the water cycle) and plan a sequence of interactive messages sent between them (e.g., Sol sends heat to Océano, Océano evaporates water to Atmósfera).\n"
        f"   - If 'Venn Diagram': A Venn diagram compares groups. Identify distinct phases, states, or categories within the topic '{topic}' to compare (e.g., 'Sólido vs Líquido vs Gaseoso') and plan a comparative set of unique characteristics and their overlapping intersection area.\n"
        f"   - If 'Mindmap': A mindmap is a conceptual hierarchy. Organize the main topic '{topic}' into clear levels of sub-topics, branches, and specific details.\n"
        f"   - If 'Flowchart': A flowchart maps operational process logic. Plan a step-by-step flow of actions, decisions, cycles, and feedback loops.\n"
        "4. Write all node titles, labels, and explanations completely in Spanish.\n"
        "5. Avoid generic text or placeholders. All content must be factual and educational.\n"
        "6. Output your plan as a structured description of nodes, relations, and node styles (e.g. Process, Decision, Storage, Start/End)."
    )

    plan_res = nim_architect.generate_chat(
        [
            {"role": "system", "content": planner_system},
            {"role": "user", "content": f"Plan a highly detailed, advanced, and comprehensive {diagram_type} diagram for the topic: {topic}"}
        ],
        temperature=0.6, max_tokens=1024
    )
    diagram_plan = plan_res['choices'][0]['message']['content']
    print(f"[Diagram Architect] Plan ready: {len(diagram_plan)} chars")

    # Use architect for Mermaid too (it's a reasoning task)
    rules = (
        "You are a Mermaid.js master. Convert the provided diagram plan into flawless, high-fidelity Mermaid.js code.\n"
        "Output ONLY the raw Mermaid code. Do NOT wrap it in markdown fences (```). Do NOT include any explanations or intro text.\n\n"
        "SYNTAX & STYLE RULES PER DIAGRAM TYPE:\n"
        "1. Flowchart:\n"
        "   - Syntax: Start directly with `flowchart TD` or `flowchart LR`.\n"
        "   - Shapes: Use `([Stadium])` for Start/End, `[Process]` or `(Round process)` for standard actions, `{Decision}` for diamonds (e.g. A{Texto}), `[(Database)]` for data/storage, `[[Subprocess]]` for complex steps.\n"
        "   - Styling (MANDATORY): You MUST append these exact classDefs at the bottom of the flowchart and apply them to nodes:\n"
        "     classDef startEnd fill:#4f46e5,stroke:#818cf8,stroke-width:2px,color:#fff;\n"
        "     classDef process fill:#1e1b4b,stroke:#4f46e5,stroke-width:1px,color:#e0e7ff;\n"
        "     classDef decision fill:#0f172a,stroke:#fda4af,stroke-width:2px,color:#f43f5e;\n"
        "     classDef accent fill:#311042,stroke:#d946ef,stroke-width:1.5px,color:#f5d0fe;\n"
        "     classDef storage fill:#064e3b,stroke:#10b981,stroke-width:1px,color:#d1fae5;\n"
        "     Apply class styling using double colons (e.g. nodeName:::process, nodeName:::decision).\n"
        "     Example:\n"
        "       flowchart TD\n"
        "           A([Inicio]):::startEnd --> B{¿Es válido?}:::decision\n"
        "           B -- Sí --> C[Procesar]:::process\n"
        "           B -- No --> D([Fin]):::startEnd\n"
        "           classDef startEnd fill:#4f46e5,stroke:#818cf8,stroke-width:2px,color:#fff;\n"
        "           classDef process fill:#1e1b4b,stroke:#4f46e5,stroke-width:1px,color:#e0e7ff;\n"
        "           classDef decision fill:#0f172a,stroke:#fda4af,stroke-width:2px,color:#f43f5e;\n\n"
        "2. Timeline:\n"
        "   - Syntax: Start directly with `timeline`.\n"
        "   - CRITICAL: Do NOT use flowchart shapes (`-->`, `[]`, `()`, classDef, `:::`, etc.) in a timeline! Timelines only accept `section` followed by `Period : Event` format.\n"
        "   - Example:\n"
        "     timeline\n"
        "         title Historia del Ciclo de Agua\n"
        "         section Antigüedad\n"
        "             Siglo IV AC : Aristóteles describe la evaporación\n"
        "         section Modernidad\n"
        "             1674 : Perrault mide la lluvia\n\n"
        "3. Sequence Diagram:\n"
        "   - Syntax: Start directly with `sequenceDiagram`.\n"
        "   - CRITICAL: Do NOT use flowchart shapes, classDefs, or styling in a sequence diagram!\n"
        "   - Include `autonumber` on the second line.\n"
        "   - Use notes: `Note over Actor: Explicación` or `Note left of Actor: ...`.\n"
        "   - Example:\n"
        "     sequenceDiagram\n"
        "         autonumber\n"
        "         Usuario->>Servidor: Petición de datos\n"
        "         Note over Servidor: Procesa en DB\n"
        "         Servidor-->>Usuario: Respuesta con datos\n\n"
        "4. Venn Diagram:\n"
        "   - Syntax: Represent this using `flowchart TD` or `flowchart LR` where overlapping regions are styled subgraphs.\n"
        "   - Create a central 'Intersección' node connected to outer concept nodes, styled nicely with overlapping groups.\n"
        "   - Append the same classDefs as standard Flowcharts to make it beautiful.\n"
        "   - Example:\n"
        "       flowchart TD\n"
        "           A[Concepto A]:::process --> AB[Intersección]:::accent\n"
        "           B[Concepto B]:::process --> AB[Intersección]:::accent\n"
        "           classDef process fill:#1e1b4b,stroke:#4f46e5,stroke-width:1px,color:#e0e7ff;\n"
        "           classDef accent fill:#311042,stroke:#d946ef,stroke-width:1.5px,color:#f5d0fe;\n\n"
        "5. Mindmap:\n"
        "   - Syntax: Start directly with `mindmap`.\n"
        "   - CRITICAL: Do NOT use flowchart syntax (`-->`, classDef, `:::`, etc.) in a mindmap!\n"
        "   - Use shape wrappers for hierarchy level visual distinction:\n"
        "     - Root: `((Concepto Principal))` (double circle)\n"
        "     - Level 1: `{{Tema Clave}}` (hexagon)\n"
        "     - Level 2: `)Subtema(` (cloud) or `[Detalle]` (square)\n"
        "   - Example:\n"
        "     mindmap\n"
        "         root((Fotosíntesis))\n"
        "             Luz{{Fase Lumínica}}\n"
        "                 Clorofila)Cloroplastos(\n"
        "             Calvin{{Ciclo de Calvin}}\n"
        "                 Fijacion)Fijación de Carbono(\n"
    )
    
    response = nim_architect.generate_chat([
        {"role": "system", "content": rules},
        {"role": "user", "content": f"Convert this plan into a {diagram_type} using flawless, styled Mermaid code: {diagram_plan}"}
    ], temperature=0.3, max_tokens=2048)
    
    code = response['choices'][0]['message']['content'].strip()
    code = re.sub(r"```(?:mermaid)?", "", code).replace("```", "").strip()
    
    print(f"[Diagram Coder] Mermaid code ready: {len(code)} chars")
    return code


def run_document_markdown_pipeline(topic: str) -> str:
    """
    Generates a rich Markdown document — fast and reliable.
    The Markdown is then converted to .docx by the endpoint.
    """
    print(f"\n{'='*60}")
    print(f"  MARKDOWN DOCUMENT PIPELINE — Topic: {topic}")
    print(f"{'='*60}")
    
    # Step 1: Classify scale (reuse existing function)
    scale = classify_document_scale(topic)
    pages = scale["pages"]
    
    # Map pages to approximate word count
    word_targets = {3: 1500, 5: 2500, 7: 3500, 10: 5000, 15: 7500}
    target_words = word_targets.get(pages, pages * 500)
    
    writer_prompt = (
        f"Write a professional academic document about: {topic}\n\n"
        f"TARGET LENGTH: approximately {target_words} words ({pages} pages).\n\n"
        "FORMAT: Output the document as rich Markdown with:\n"
        "- A main title as # heading\n"
        "- Sections as ## headings\n"
        "- Subsections as ### headings\n"
        "- Flowing paragraphs of prose (3-5 sentences each)\n"
        "- Bullet point lists where appropriate\n"
        "- Numbered lists for steps/processes\n"
        "- **Bold** for key terms and *italics* for emphasis\n"
        "- Tables using Markdown table syntax where data comparison is relevant\n"
        "- > Blockquotes for important definitions or quotes\n\n"
        "RULES:\n"
        "1. Write ALL content in Spanish\n"
        "2. Write in academic, formal prose — connected paragraphs, smooth transitions\n"
        "3. Include concrete examples, data, and real-world applications\n"
        "4. Use transition phrases: 'Además...', 'Sin embargo...', 'En consecuencia...'\n"
        "5. Output ONLY the Markdown document. No explanations. No code fences around the whole document.\n"
        "6. Start with the # title immediately.\n"
    )
    
    print(f"[Markdown Writer] Generating ~{target_words}-word document...")
    
    writer_res = nim_writer.generate_chat(
        [
            {"role": "system", "content": "You are a professional academic document writer. Output ONLY Markdown text. No code fences wrapping the document. Start with the # title."},
            {"role": "user", "content": writer_prompt}
        ],
        temperature=0.7, max_tokens=scale.get("writer_tokens", 4096)
    )
    
    markdown_text = writer_res['choices'][0]['message']['content'].strip()
    
    # Clean: remove any wrapping ```markdown ... ``` fences
    markdown_text = re.sub(r"^```(?:markdown|md)?\n?", "", markdown_text)
    markdown_text = re.sub(r"\n?```$", "", markdown_text).strip()
    
    print(f"[Markdown Writer] Output: {len(markdown_text)} chars, ~{len(markdown_text.split())} words")
    print(f"{'='*60}")
    print(f"  MARKDOWN PIPELINE COMPLETE")
    print(f"{'='*60}\n")
    
    return markdown_text


def run_presentation_pipeline(topic: str) -> dict:
    """Generates rich, structured presentation data with diverse slide layouts."""
    print(f"\n[Presentation Pipeline] Topic: {topic}")
    
    prompt = (
        f"Create a professional 10-slide presentation about: {topic}\n\n"
        "Return ONLY a valid JSON object. NO markdown fences. NO explanation.\n\n"
        "AVAILABLE LAYOUTS (you MUST use a mix of these):\n\n"
        "1. \"intro\" — Opening title slide:\n"
        '   {"layout": "intro", "section": "PRESENTACIÓN", "h1": "BIG TITLE", "p": "Subtitle line"}\n\n'
        "2. \"bullets\" — Bullet point slide (USE THIS FOR MOST SLIDES):\n"
        '   {"layout": "bullets", "section": "02 // TEMA", "h2": "Slide Title", "bullets": ["Point 1 with detail", "Point 2 with detail", "Point 3", "Point 4", "Point 5"]}\n\n'
        "3. \"text\" — Paragraph explanation slide:\n"
        '   {"layout": "text", "section": "03 // CONCEPTO", "h2": "Slide Title", "p": "A full paragraph of 2-3 sentences explaining a concept in detail."}\n\n'
        "4. \"quote\" — Inspirational quote slide (USE EXACTLY ONCE):\n"
        '   {"layout": "quote", "section": "REFLEXIÓN", "quote": "The quote text here", "source": "Author Name (Year)"}\n\n'
        "5. \"table\" — Data/comparison table slide:\n"
        '   {"layout": "table", "section": "05 // DATOS", "h2": "Table Title", "table": {"headers": ["Col1", "Col2", "Col3"], "rows": [["a","b","c"], ["d","e","f"], ["g","h","i"]]}}\n\n'
        "6. \"conclusion\" — Closing slide:\n"
        '   {"layout": "conclusion", "section": "CONCLUSIÓN", "h1": "Key Takeaway", "p": "Final summary sentence"}\n\n'
        "STRICT RULES:\n"
        "1. Write ALL text in Spanish.\n"
        "2. Use \"bullets\" layout for AT LEAST 4 slides. Each bullets slide must have 4-6 bullet points.\n"
        "3. Use \"quote\" layout for EXACTLY 1 slide.\n"
        "4. Use \"table\" layout for EXACTLY 1 slide with at least 3 rows.\n"
        "5. Use \"text\" layout for 1-2 slides.\n"
        "6. First slide MUST be \"intro\", last slide MUST be \"conclusion\".\n"
        "7. Every bullet point must be specific and informative (10-20 words), not vague.\n"
        "8. Output the JSON object with keys \"title\" (string) and \"slides\" (array).\n"
        "9. Output ONLY raw JSON. No markdown. No explanation."
    )
    
    try:
        res = nim_architect.generate_chat(
            [{"role": "user", "content": prompt}],
            temperature=0.5, max_tokens=3000,
            response_format={"type": "json_object"}
        )
        raw = res['choices'][0]['message']['content'].strip()
        raw = re.sub(r"```json\n?|```", "", raw).strip()
        # Find the JSON object boundaries
        first_brace = raw.find("{")
        last_brace = raw.rfind("}")
        if first_brace != -1 and last_brace != -1:
            raw = raw[first_brace:last_brace + 1]
            
        # Fix trailing commas (common LLM mistake)
        raw = re.sub(r',\s*([\]}])', r'\1', raw)
        
        try:
            data = json.loads(raw)
        except json.JSONDecodeError as jde:
            print(f"[Presentation Engine] JSON parse error: {jde}. Raw string: {raw}")
            # If it still fails, try to strip unescaped control chars
            raw = re.sub(r'[\x00-\x1f]', '', raw)
            data = json.loads(raw)
        
        # Validate and log slide layout distribution
        layouts = [s.get("layout", "text") for s in data.get("slides", [])]
        print(f"[Presentation Pipeline] Generated {len(layouts)} slides: {layouts}")
        
        return data
    except Exception as e:
        print(f"[Presentation Engine] Error: {e}")
        return {
            "title": topic,
            "slides": [{"id": "01", "layout": "intro", "section": "ERROR", "h1": "Generation Failed", "p": str(e)}]
        }
