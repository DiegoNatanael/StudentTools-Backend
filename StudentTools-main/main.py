import os
import io
import re
from fastapi import FastAPI, HTTPException, Body, Request
from fastapi.responses import StreamingResponse
from fastapi.middleware.cors import CORSMiddleware

from usage import is_allowed, is_admin, record_usage, total_stats
from latex_manager import LaTeXManager
from council import run_document_pipeline, run_diagram_pipeline, run_document_markdown_pipeline

app = FastAPI(title="Student Tools V2 - Powered by NVIDIA NIM & LaTeX")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Endpoints ---

@app.get("/health")
async def health_check(request: Request):
    return {"status": "ok", "engine": "NVIDIA NIM + TeXLive.net", "is_admin": is_admin(request)}

@app.get("/api/health")
async def api_health_check(request: Request):
    return {"status": "ok", "engine": "NVIDIA NIM + TeXLive.net", "is_admin": is_admin(request)}

@app.get("/api/stats")
async def get_stats():
    return total_stats

@app.post("/api/generate/document")
async def generate_document(request: Request, topic: str = Body(..., embed=True)):
    """
    V2 endpoint: Generates a complete PDF in one shot.
    No more plan→pdf two-step. AI writes LaTeX directly, we compile it.
    """
    if not is_allowed(request):
        raise HTTPException(status_code=429, detail="Daily limit reached. Come back tomorrow or use God Mode!")
    
    try:
        # Step 1: AI generates complete LaTeX
        latex_code = run_document_pipeline(topic)
        
        # Step 2: Compile to PDF
        print(f"[PDF Engine] Compiling {len(latex_code)} chars of LaTeX...")
        pdf_bytes = LaTeXManager.generate_pdf(latex_code)
        
        # Create safe filename from topic
        safe_filename = re.sub(r'[\\/*?:"<>|]', "", topic[:60]).replace(" ", "_")
        
        record_usage(request)
        
        return StreamingResponse(
            io.BytesIO(pdf_bytes),
            media_type="application/pdf",
            headers={"Content-Disposition": f"attachment; filename={safe_filename}.pdf"}
        )
    except Exception as e:
        print(f"[Document Engine] ERROR: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Generation Error: {str(e)}")

# Keep the old endpoints for backwards compatibility during transition
@app.post("/api/generate/plan")
async def generate_plan(request: Request, topic: str = Body(..., embed=True), type: str = Body("document")):
    """Legacy endpoint — redirects to the new unified document generator."""
    if not is_allowed(request):
        raise HTTPException(status_code=429, detail="Daily limit reached. Come back tomorrow or use God Mode!")
    
    try:
        if type == "document":
            latex_code = run_document_pipeline(topic)
            record_usage(request)
            return {"latex": latex_code, "topic": topic}
        else:
            from council import run_presentation_pipeline
            plan = run_presentation_pipeline(topic)
            record_usage(request)
            return plan
    except Exception as e:
        print(f"!!! [PIPELINE ERROR]: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Generation Error: {str(e)}")

@app.post("/api/generate/pdf")
async def generate_pdf(request: Request, latex: str = Body(..., embed=True)):
    """Compiles raw LaTeX code into a PDF."""
    try:
        print(f"[PDF Engine] Compiling {len(latex)} chars of LaTeX...")
        pdf_bytes = LaTeXManager.generate_pdf(latex)
        return StreamingResponse(
            io.BytesIO(pdf_bytes),
            media_type="application/pdf",
            headers={"Content-Disposition": "attachment; filename=document.pdf"}
        )
    except Exception as e:
        print(f"[PDF Engine] ERROR: {str(e)}")
        raise HTTPException(status_code=500, detail=f"LaTeX Error: {str(e)}")

@app.post("/api/generate/diagram")
async def generate_diagram(request: Request, topic: str = Body(..., embed=True), type: str = Body("Flowchart")):
    if not is_allowed(request):
        raise HTTPException(status_code=429, detail="Daily limit reached. Come back tomorrow or use God Mode!")

    try:
        code = run_diagram_pipeline(topic, type)
        
        mermaid_keywords = ["graph", "flowchart", "sequencediagram", "classdiagram", "statediagram", "erdiagram", "gantt", "pie", "mindmap"]
        if any(kw in code.lower() for kw in mermaid_keywords):
            record_usage(request)
            
        return {"code": code}
    except Exception as e:
        print(f"[Diagram Engine] ERROR: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/generate/markdown")
async def generate_markdown(request: Request, topic: str = Body(..., embed=True)):
    """Generates and returns the raw Markdown string for a topic."""
    if not is_allowed(request):
        raise HTTPException(status_code=429, detail="Daily limit reached. Come back tomorrow or use God Mode!")
    
    try:
        markdown_text = run_document_markdown_pipeline(topic)
        record_usage(request)
        return {"markdown": markdown_text, "topic": topic}
    except Exception as e:
        print(f"[Markdown Engine] ERROR: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/generate/docx")
async def generate_docx(request: Request, topic: str = Body(..., embed=True)):
    """
    Markdown → DOCX document generator.
    AI writes Markdown, we convert it to a professional .docx file.
    """
    if not is_allowed(request):
        raise HTTPException(status_code=429, detail="Daily limit reached. Come back tomorrow or use God Mode!")
    
    try:
        # Step 1: AI generates Markdown
        markdown_text = run_document_markdown_pipeline(topic)
        
        # Step 2: Convert Markdown to DOCX
        from docx_builder import markdown_to_docx
        docx_bytes = markdown_to_docx(markdown_text, topic)
        
        safe_filename = re.sub(r'[\\/*?:"<>|]', "", topic[:60]).replace(" ", "_")
        
        record_usage(request)
        
        return StreamingResponse(
            io.BytesIO(docx_bytes),
            media_type="application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            headers={"Content-Disposition": f"attachment; filename={safe_filename}.docx"}
        )
    except Exception as e:
        print(f"[DOCX Engine] ERROR: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Generation Error: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
