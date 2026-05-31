@extends('layouts.app')

@section('title', $generation->topic . ' | StudentTools')

@section('styles')
@if($generation->type === 'presentation')
<style>
    /* Premium Slide Viewer styles */
    .reveal-container {
        width: 100%;
        height: 600px;
        background: #050505;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    
    .reveal, .reveal-viewport {
        background: transparent !important;
        color: white !important;
    }
    
    .reveal h1, .reveal h2, .reveal h3, .reveal p, .reveal li, .reveal blockquote { 
        margin: 0; 
        font-family: 'Outfit', sans-serif;
    }
    
    .reveal h1, .reveal h2 {
        color: white !important;
        text-shadow: 0 5px 15px rgba(0,0,0,0.5);
    }
    
    .reveal h1 { font-weight: 800; text-transform: uppercase; font-size: 3.5em !important; letter-spacing: -0.04em; line-height: 0.9; }
    .reveal h2 { font-weight: 600; text-transform: uppercase; font-size: 2em !important; margin-bottom: 20px; }
    .reveal h3 { font-weight: 400; color: #a1a1aa !important; text-transform: uppercase; font-size: 0.8em !important; letter-spacing: 0.5em; margin-bottom: 15px; }
    .reveal p { color: #d4d4d8 !important; font-weight: 300; line-height: 1.4; font-size: 1.1em !important; }

    .divider { width: 60px; height: 3px; background: #6366f1; margin: 30px 0; }
    
    blockquote { border-left: 4px solid #6366f1; background: rgba(255,255,255,0.05); padding: 30px; font-style: italic; border-radius: 0 12px 12px 0; color: white !important; font-size: 1.3em !important; margin: 20px 0; text-align: left; }

    .slide-container { display: flex; align-items: center; justify-content: space-between; gap: 60px; width: 100%; text-align: left; }
    
    table { border-collapse: collapse; width: 100%; color: #d4d4d8 !important; font-size: 0.8em; margin-top: 20px; }
    th { color: white !important; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 15px; text-transform: uppercase; font-size: 0.7em; text-align: left; }
    td { color: #d4d4d8 !important; padding: 18px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); line-height: 1.2; }

    .slide-bullets { list-style: none; padding: 0; margin: 0; text-align: left; }
    .slide-bullets li { color: #d4d4d8 !important; font-weight: 300; font-size: 1.1em !important; padding: 8px 0; padding-left: 20px; position: relative; line-height: 1.4; }
    .slide-bullets li::before { content: '▸'; color: #6366f1; position: absolute; left: 0; font-size: 1em; }
</style>
@endif
@endsection

@section('content')
<section class="hero-section active" style="margin-top: 10px;">
    <!-- Visor Control Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('generations.index') }}" class="nav-btn auth-btn" style="text-decoration: none; padding: 10px 15px; border-radius: 8px;"><i class="fas fa-chevron-left"></i> Volver</a>
            <div>
                <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-dim); font-size: 0.8rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; text-transform: uppercase;">
                    @if($generation->type === 'document') Documento @elseif($generation->type === 'presentation') Diapositiva @else Diagrama @endif
                </span>
                <h2 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: white; margin-top: 5px;">{{ $generation->topic }}</h2>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <!-- Save as PDF / Export Button -->
            @if($generation->type === 'document')
                <button onclick="downloadAsDocx()" class="primary-btn" style="width: auto; padding: 10px 20px; font-size: 0.95rem; border-radius: 8px;"><i class="fas fa-file-word"></i> Descargar DOCX</button>
            @elseif($generation->type === 'presentation')
                <button id="downloadPdfBtn" class="primary-btn" style="width: auto; padding: 10px 20px; font-size: 0.95rem; border-radius: 8px; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4);"><i class="fas fa-file-pdf"></i> Guardar PDF</button>
            @endif

            <!-- Edit Creation -->
            <a href="{{ route('generations.edit', $generation->id) }}" class="nav-btn auth-btn" style="text-decoration: none; padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); display: flex; align-items: center;"><i class="fas fa-edit"></i> Editar</a>
        </div>
    </div>

    <!-- MAIN RENDER VISORS -->
    <div class="glass-card" style="padding: 30px; min-height: 400px; display: flex; align-items: center; justify-content: center;">
        
        <!-- A. DOCUMENT MARKDOWN VISOR -->
        @if($generation->type === 'document')
            <div style="width: 100%; max-width: 800px; text-align: left; line-height: 1.8; color: #e2e8f0;" id="markdownContent" class="markdown-body">
                <!-- Content will be rendered dynamically here -->
            </div>
            
            <!-- Marked.js Markdown renderer CDN -->
            <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
            <script>
                document.getElementById('markdownContent').innerHTML = marked.parse({!! json_encode($generation->content) !!});
            </script>

        <!-- B. PRESENTATION VISOR -->
        @elseif($generation->type === 'presentation')
            <div id="revealViewer" class="reveal-container">
                <div class="reveal">
                    <div class="slides">
                        @foreach($slides['slides'] as $slide)
                            <section>
                                @php $layout = $slide['layout'] ?? 'text'; @endphp
                                
                                @if($layout === 'intro')
                                    <div style="text-align: center; display: flex; flex-direction: column; align-items: center; width: 100%;">
                                        <h3>{{ $slide['section'] ?? 'PRESENTACIÓN' }}</h3>
                                        <div class="divider" style="margin: 30px auto;"></div>
                                        <h1 style="text-align: center; width: 100%;">{{ $slide['h1'] ?? $generation->topic }}</h1>
                                        <p style="text-align: center; max-width: 800px; margin: 0 auto;">{{ $slide['p'] ?? '' }}</p>
                                    </div>
                                    
                                @elseif($layout === 'bullets' && isset($slide['bullets']))
                                    <div class="container slide-container">
                                        <div class="col-text">
                                            <h3>{{ $slide['section'] ?? '' }}</h3>
                                            <h2>{{ $slide['h2'] ?? '' }}</h2>
                                            <div class="divider"></div>
                                            <ul class="slide-bullets">
                                                @foreach($slide['bullets'] as $bullet)
                                                    <li>{{ $bullet }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    
                                @elseif($layout === 'quote')
                                    <div class="center-content" style="text-align: center;">
                                        <h3>{{ $slide['section'] ?? 'REFLEXIÓN' }}</h3>
                                        <div class="divider" style="margin: 30px auto;"></div>
                                        <blockquote style="text-align: center; border-left: none; border-top: 6px solid var(--accent, #6366f1); border-radius: 12px; padding: 40px;">
                                            {{ $slide['quote'] ?? ($slide['p'] ?? '') }}
                                        </blockquote>
                                        @if(isset($slide['source']))
                                            <div style="margin-top: 20px;">
                                                <span class="source-link" style="color: #6366f1; text-decoration: none; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">{{ $slide['source'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                @elseif($layout === 'table' && isset($slide['table']))
                                    <h3>{{ $slide['section'] ?? 'DATOS' }}</h3>
                                    <h2 style="text-align: center;">{{ $slide['h2'] ?? '' }}</h2>
                                    <div class="divider" style="margin: 30px auto;"></div>
                                    <table>
                                        <thead>
                                            <tr>
                                                @foreach($slide['table']['headers'] as $header)
                                                    <th>{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($slide['table']['rows'] as $row)
                                                <tr>
                                                    @foreach($row as $cell)
                                                        <td>{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    
                                @elseif($layout === 'conclusion')
                                    <div class="center-content">
                                        <h3>{{ $slide['section'] ?? 'CONCLUSIÓN' }}</h3>
                                        <h1>{{ $slide['h1'] ?? 'Conclusión' }}</h1>
                                        <div class="divider"></div>
                                        <p>{{ $slide['p'] ?? '' }}</p>
                                    </div>
                                    
                                @else
                                    <div class="container slide-container">
                                        <div class="col-text">
                                            <h3>{{ $slide['section'] ?? '' }}</h3>
                                            <h2>{{ $slide['h2'] ?? '' }}</h2>
                                            <div class="divider"></div>
                                            <p>{{ $slide['p'] ?? '' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>

        <!-- C. DIAGRAM VISOR -->
        @elseif($generation->type === 'diagram')
            <div id="mermaidOutput" class="mermaid-container" style="width: 100%;">
                {!! $generation->content !!}
            </div>
        @endif

    </div>
</section>
@endsection

@section('scripts')
<!-- Document DOCX Downloader -->
@if($generation->type === 'document')
<script>
    async function downloadAsDocx() {
        const statusLog = document.createElement('div');
        statusLog.className = 'status-log';
        statusLog.innerHTML = `<div class="log-content"><div class="log-header"><div class="loader"></div><span>Compilando archivo DOCX...</span></div></div>`;
        document.body.appendChild(statusLog);

        try {
            // Laravel downloads the docx by hitting the FastAPI docx endpoint directly!
            const response = await fetch('http://127.0.0.1:8000/api/generate/docx', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ topic: {!! json_encode($generation->topic) !!} })
            });

            if (!response.ok) throw new Error("La compilación falló");

            const blob = await response.blob();
            const safeName = {!! json_encode($generation->topic) !!}.replace(/[^a-zA-Z0-9áéíóúñÁÉÍÓÚÑ ]/g, '').replace(/ /g, '_');
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${safeName}.docx`;
            a.click();
            window.URL.revokeObjectURL(url);
            statusLog.remove();
        } catch (e) {
            statusLog.innerHTML = `<div class="log-content"><div class="log-header" style="color: #ef4444;"><i class="fas fa-times-circle"></i><span>Error en la descarga</span></div></div>`;
            setTimeout(() => statusLog.remove(), 2000);
        }
    }
</script>
@endif

<!-- Presentation Slideshow scripts -->
@if($generation->type === 'presentation')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('revealViewer');
        const deck = new Reveal(container.querySelector('.reveal'), {
            embedded: true,
            controls: false,
            progress: false,
            center: true,
            hash: false,
            transition: 'fade',
            transitionSpeed: 'slow',
            width: 1200,
            height: 900
        });

        deck.initialize().then(() => {
            animateSlide(deck.getCurrentSlide());
            deck.on('slidechanged', event => animateSlide(event.currentSlide));
        });

        // Left click to advance
        container.querySelector('.reveal').addEventListener('mousedown', (e) => {
            if (e.button === 0) {
                e.preventDefault();
                deck.next();
            }
        });

        window.currentDeck = deck;
    });

    function animateSlide(slide) {
        const elements = slide.querySelectorAll('h1, h2, h3, p, .divider, blockquote, table, .slide-bullets li');
        gsap.fromTo(elements,
            { opacity: 0, y: 40 },
            { opacity: 1, y: 0, duration: 1.2, stagger: 0.15, ease: "power3.out" }
        );
    }

    // PDF Printing script
    document.getElementById('downloadPdfBtn').addEventListener('click', () => {
        const printWindow = window.open('', '_blank');
        
        // Assemble slide container content
        const slidesHtml = document.querySelector('.slides').innerHTML;
        
        printWindow.document.write(`<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>${{ json_encode($generation->topic) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #050505;
            --text-primary: #ffffff;
            --text-secondary: #d4d4d8;
            --accent: #6366f1;
            --card-border: rgba(255, 255, 255, 0.1);
        }
        @page { size: 1200px 900px; margin: 0; }
        
        body, html { 
            margin: 0; padding: 0; 
            background-color: var(--bg-color) !important; 
            font-family: 'Inter', sans-serif; 
            color: var(--text-primary) !important;
            width: 100%; height: auto;
        }

        /* Slide Layout */
        .slides { width: 100%; display: flex; flex-direction: column; }
        section { 
            width: 100%; height: 900px; 
            display: flex !important; flex-direction: column !important; 
            justify-content: center !important; align-items: center !important; 
            page-break-after: always !important; break-after: page !important;
            page-break-inside: avoid !important; break-inside: avoid !important;
            padding: 40px; box-sizing: border-box;
            position: relative;
        }
        
        /* Typography & Colors (Overriding all print defaults) */
        h1, h2, h3, p, li, blockquote, th, td { color: var(--text-primary) !important; font-family: 'Outfit', sans-serif; margin: 0; }
        h1 { font-weight: 800; text-transform: uppercase; font-size: 3.5em !important; letter-spacing: -0.04em; margin-bottom: 20px; text-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        h2 { font-weight: 600; text-transform: uppercase; font-size: 2.2em !important; margin-bottom: 20px; text-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        h3 { font-weight: 400; color: #a1a1aa !important; text-transform: uppercase; font-size: 1.1em !important; letter-spacing: 0.5em; margin-bottom: 15px; }
        p { color: var(--text-secondary) !important; font-weight: 300; line-height: 1.5; font-size: 1.4em !important; font-family: 'Inter', sans-serif; text-align: center; }
        
        .divider { width: 60px; height: 4px; background: var(--accent) !important; margin: 30px 0; border-radius: 2px; }
        blockquote { border-left: 6px solid var(--accent) !important; background: rgba(255,255,255,0.05) !important; padding: 30px; font-style: italic; font-size: 1.6em !important; text-align: left; border-radius: 0 12px 12px 0; }
        
        /* Layouts */
        .slide-container { display: flex; align-items: center; justify-content: space-between; gap: 80px; width: 85%; margin: 0 auto; text-align: left; }
        .col-text { flex: 1; }
        .center-content { text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 80%; }
        
        /* Tables & Lists */
        table { border-collapse: collapse; width: 85%; font-size: 1.1em; margin-top: 30px; font-family: 'Inter', sans-serif; }
        th { color: white !important; border-bottom: 2px solid rgba(255,255,255,0.2) !important; padding: 20px; text-transform: uppercase; font-size: 0.9em; text-align: left; }
        td { color: var(--text-secondary) !important; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1) !important; }
        
        .slide-bullets { list-style: none; padding: 0; margin: 0; text-align: left; font-family: 'Inter', sans-serif; }
        .slide-bullets li { color: var(--text-secondary) !important; font-weight: 300; font-size: 1.4em !important; padding: 12px 0; padding-left: 30px; position: relative; line-height: 1.5; }
        .slide-bullets li::before { content: '▸'; color: var(--accent) !important; position: absolute; left: 0; top: 12px; font-size: 1.2em; }

        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
        }
    </style>
</head>
<body>
    <div class="slides">${slidesHtml}</div>
    <script>
        // No Reveal.js JS needed! Pure CSS rendering guarantees perfect PDF export!
        setTimeout(() => { window.print(); }, 1000);
    <\/script>
</body>
</html>`);
        printWindow.document.close();
    });
</script>
@endif

<!-- Diagram Renderer scripts -->
@if($generation->type === 'diagram')
<script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('mermaidOutput');
        mermaid.run({ nodes: [container] }).then(() => {
            // Apply slight transition/smoothness after SVG generation
            const svg = container.querySelector('svg');
            if (svg) {
                svg.style.width = '100%';
                svg.style.height = '600px';
                
                // Initialize SVG Pan/Zoom interactivity
                svgPanZoom(svg, {
                    zoomEnabled: true,
                    controlIconsEnabled: true,
                    fit: true,
                    center: true,
                    minZoom: 0.5,
                    maxZoom: 15
                });
            }
        }).catch(err => {
            container.innerHTML = '<div style="color: #ef4444;">Error de Renderizado. Edita el código del diagrama para corregirlo.</div>';
        });
    });
</script>
@endif
@endsection
