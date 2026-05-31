@extends('layouts.app')

@section('title', 'StudentTools | AI Workspace Pro')

@section('content')
<section id="welcome" class="hero-section active" style="margin-top: 50px;">
    <div class="hero-text" style="margin-bottom: 60px;">
        <h1 style="font-size: 4.5rem; line-height: 1.1; margin-bottom: 20px;">Estudia Inteligente.</h1>
        <p style="font-size: 1.4rem; max-width: 700px; margin: 0 auto; line-height: 1.6;">
            Genera documentos académicos, diapositivas para exposiciones y diagramas conceptuales interactivos con la velocidad de NVIDIA NIM.
        </p>
    </div>

    <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 80px;">
        @auth
            <a href="{{ route('dashboard') }}" class="primary-btn" style="max-width: 250px; text-decoration: none;">
                <span>Ir al Workspace</span> <i class="fas fa-arrow-right"></i>
            </a>
        @else
            <a href="{{ route('login') }}" class="primary-btn" style="max-width: 250px; text-decoration: none;">
                <span>Comenzar Ahora</span> <i class="fas fa-arrow-right"></i>
            </a>
            <a href="{{ route('register') }}" class="nav-btn auth-btn" style="padding: 18px 30px; border-radius: 12px; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                <span>Registrarse</span>
            </a>
        @endauth
    </div>

    <!-- Features Showcase -->
    <div class="diagram-layout" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px;">
        <div class="glass-card" style="text-align: center; padding: 40px 30px;">
            <i class="fas fa-file-signature" style="font-size: 3rem; color: #6366f1; margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 15px; font-family: 'Outfit';">Documentos Académicos</h3>
            <p style="color: var(--text-dim); line-height: 1.5; font-size: 0.95rem;">
                Crea ensayos y guías estructuradas en Markdown y descárgalas en formato DOCX de Word listas para entregar.
            </p>
        </div>

        <div class="glass-card" style="text-align: center; padding: 40px 30px;">
            <i class="fas fa-images" style="font-size: 3rem; color: #a855f7; margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 15px; font-family: 'Outfit';">Presentaciones Reveal.js</h3>
            <p style="color: var(--text-dim); line-height: 1.5; font-size: 0.95rem;">
                Diseña diapositivas interactivas en modo oscuro premium con animaciones cinematográficas de GSAP.
            </p>
        </div>

        <div class="glass-card" style="text-align: center; padding: 40px 30px;">
            <i class="fas fa-project-diagram" style="font-size: 3rem; color: #10b981; margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 15px; font-family: 'Outfit';">Diagramas de Flujo</h3>
            <p style="color: var(--text-dim); line-height: 1.5; font-size: 0.95rem;">
                Genera mapas mentales, diagramas de secuencia, líneas de tiempo y diagramas de flujo con colores personalizados.
            </p>
        </div>
    </div>
</section>
@endsection
