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
    
    .reveal h1, .reveal h2, .reveal h3 { 
        color: white !important; 
        margin: 0; 
        text-shadow: 0 10px 30px rgba(0,0,0,0.5);
        font-family: 'Outfit', sans-serif;
    }
    
    .reveal h1 { font-weight: 800; text-transform: uppercase; font-size: 3.5em !important; letter-spacing: -0.04em; line-height: 0.9; }
    .reveal h2 { font-weight: 600; text-transform: uppercase; font-size: 2em !important; margin-bottom: 20px; }
    .reveal h3 { font-weight: 400; color: #888 !important; text-transform: uppercase; font-size: 0.8em !important; letter-spacing: 0.5em; margin-bottom: 15px; }
    .reveal p { color: #888; font-weight: 300; line-height: 1.4; font-size: 1.1em !important; }

    .divider { width: 60px; height: 3px; background: #6366f1; margin: 30px 0; }
    
    blockquote { border-left: 4px solid #6366f1; background: rgba(255,255,255,0.05); padding: 30px; font-style: italic; border-radius: 0 12px 12px 0; color: white !important; font-size: 1.3em !important; margin: 20px 0; text-align: left; }

    .slide-container { display: flex; align-items: center; justify-content: space-between; gap: 60px; width: 100%; text-align: left; }
    
    table { border-collapse: collapse; width: 100%; color: #888; font-size: 0.8em; margin-top: 20px; }
    th { color: white; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 15px; text-transform: uppercase; font-size: 0.7em; text-align: left; }
    td { padding: 18px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); line-height: 1.2; }

    .slide-bullets { list-style: none; padding: 0; margin: 0; text-align: left; }
    .slide-bullets li { color: #888; font-weight: 300; font-size: 1em; padding: 8px 0; padding-left: 20px; position: relative; line-height: 1.4; }
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
                                    <h3>{{ $slide['section'] ?? 'PRESENTACIÓN' }}</h3>
                                    <div class="divider"></div>
                                    <h1>{{ $slide['h1'] ?? $generation->topic }}</h1>
                                    <p>{{ $slide['p'] ?? '' }}</p>
                                    
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
                                    <div class="center-content">
                                        <h3>{{ $slide['section'] ?? 'REFLEXIÓN' }}</h3>
                                        <div class="divider"></div>
                                        <blockquote>{{ $slide['quote'] ?? '' }}</blockquote>
                                        @if(isset($slide['source']))
                                            <a href="#" class="source-link" style="color: #6366f1; text-decoration: none; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;">{{ $slide['source'] }}</a>
                                        @endif
                                    </div>
                                    
                                @elseif($layout === 'table' && isset($slide['table']))
                                    <h3>{{ $slide['section'] ?? 'DATOS' }}</h3>
                                    <h2>{{ $slide['h2'] ?? '' }}</h2>
                                    <div class="divider"></div>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #050505;
            --text-primary: #ffffff;
            --text-secondary: #888888;
            --accent: #6366f1;
            --card-border: rgba(255, 255, 255, 0.1);
        }
        body, .reveal { background-color: var(--bg-color) !important; font-family: 'Inter', sans-serif; color: var(--text-primary); }
        .reveal .slides section { display: flex !important; flex-direction: column !important; justify-content: center !important; align-items: center !important; height: 100%; }
        .reveal h1, .reveal h2, .reveal h3 { color: var(--text-primary) !important; margin: 0; text-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .reveal h1 { font-weight: 600; text-transform: uppercase; font-size: 3.8em !important; letter-spacing: -0.04em; }
        .reveal h2 { font-weight: 400; text-transform: uppercase; font-size: 2.2em !important; margin-bottom: 20px; }
        .reveal h3 { font-weight: 300; color: var(--text-secondary) !important; text-transform: uppercase; font-size: 0.85em !important; letter-spacing: 0.5em; margin-bottom: 15px; }
        .reveal p { color: var(--text-secondary); font-weight: 300; line-height: 1.4; font-size: 1.2em !important; }
        .divider { width: 60px; height: 3px; background: var(--accent); margin: 30px 0; }
        blockquote { border-left: 4px solid var(--accent); background: rgba(255,255,255,0.05); padding: 30px; font-style: italic; color: var(--text-primary) !important; font-size: 1.4em !important; text-align: left; }
        .slide-container { display: flex; align-items: center; justify-content: space-between; gap: 80px; width: 100%; text-align: left; }
        table { border-collapse: collapse; width: 100%; color: var(--text-secondary); font-size: 0.85em; margin-top: 20px; }
        th { color: white; border-bottom: 2px solid var(--card-border); padding: 15px; text-transform: uppercase; font-size: 0.7em; text-align: left; }
        td { padding: 18px 15px; border-bottom: 1px solid var(--card-border); }
        .slide-bullets { list-style: none; padding: 0; margin: 0; text-align: left; }
        .slide-bullets li { color: var(--text-secondary); font-weight: 300; font-size: 1.1em; padding: 8px 0; padding-left: 20px; position: relative; }
        .slide-bullets li::before { content: '▸'; color: var(--accent); position: absolute; left: 0; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="reveal"><div class="slides">${slidesHtml}</div></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.js"><\/script>
    <script>
        Reveal.initialize({ controls: false, progress: false, hash: false, center: true, width: 1200, height: 900 });
        Reveal.on('ready', () => { setTimeout(() => { window.print(); }, 1000); });
    <\/script>
</body>
</html>`);
        printWindow.document.close();
    });
</script>
@endif

<!-- Diagram Renderer scripts -->
@if($generation->type === 'diagram')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('mermaidOutput');
        mermaid.run({ nodes: [container] }).then(() => {
            // Apply slight transition/smoothness after SVG generation
            const svg = container.querySelector('svg');
            if (svg) {
                svg.style.maxWidth = '100%';
                svg.style.height = 'auto';
            }
        }).catch(err => {
            container.innerHTML = '<div style="color: #ef4444;">Error de Renderizado. Edita el código del diagrama para corregirlo.</div>';
        });
    });
</script>
@endif
@endsection
