<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'StudentTools | AI Workspace')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <!-- Icons, Reveal.js, Mermaid.js & GSAP -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.css">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>

    <!-- Custom Premium Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    
    @yield('styles')
</head>

<body class="dark-mode">
    <!-- Premium Animated backgrounds -->
    <div class="bg-vignette"></div>
    <div class="blob-gradient blob-1"></div>
    <div class="blob-gradient blob-2"></div>
    <div class="blob-gradient blob-3"></div>

    <!-- Reusable Navbar -->
    <nav class="glass-nav">
        <div class="nav-content">
            <a href="{{ route('welcome') }}" class="logo" style="text-decoration: none;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
                <span>StudentTools</span>
            </a>
            <div class="nav-links">
                @auth
                    <a href="{{ route('dashboard') }}" class="nav-btn {{ Route::is('dashboard') ? 'active' : '' }}">Workspace</a>
                    <a href="{{ route('generations.index') }}" class="nav-btn {{ Route::is('generations.*') ? 'active' : '' }}">Mis Creaciones</a>
                    <a href="{{ route('subjects.index') }}" class="nav-btn {{ Route::is('subjects.*') ? 'active' : '' }}">Asignaturas</a>
                    @if(Auth::user()->role === 'admin')
                        <a href="{{ route('admin.users.index') }}" class="nav-btn {{ Route::is('admin.users.*') ? 'active' : '' }}"><i class="fas fa-user-shield"></i> Control Panel</a>
                    @endif
                @endauth
            </div>
            <div class="nav-user">
                @auth
                    <div id="userInfo" class="user-info">
                        <span id="quotaBadge" class="quota-badge {{ Auth::user()->role === 'admin' ? '' : (Auth::user()->quota <= 1 ? 'low' : '') }}">
                            @if(Auth::user()->role === 'admin') ∞ @else {{ Auth::user()->quota }} @endif
                        </span>
                        <span id="userName" class="user-name">{{ Auth::user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="nav-btn logout-btn" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-btn auth-btn" style="text-decoration: none;"><i class="fas fa-user"></i> Iniciar Sesión</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Views Container -->
    <main class="container" style="padding-top: 120px;">
        @yield('content')
    </main>

    <!-- Dynamic Status Logs & Alerts Overlay -->
    @if(session('success') || session('error') || $errors->any())
        <div id="statusLog" class="status-log">
            <div class="log-content">
                <div class="log-header">
                    <i class="fas fa-bell"></i>
                    <span>Notificaciones</span>
                </div>
                <div id="logMessages" class="log-messages">
                    @if(session('success'))
                        <div class="msg" style="border-left-color: #10b981;">> {{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="msg" style="border-left-color: #ef4444;">> {{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        @foreach($errors->all() as $error)
                            <div class="msg" style="border-left-color: #ef4444;">> {{ $error }}</div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const log = document.getElementById('statusLog');
                if (log) {
                    log.style.transition = 'opacity 0.5s ease-out';
                    log.style.opacity = '0';
                    setTimeout(() => log.remove(), 500);
                }
            }, 4000);
        </script>
    @endif

    <!-- Scripts Section -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        // Init Mermaid with a sleek dark theme
        mermaid.initialize({ startOnLoad: false, theme: 'dark' });
    </script>
    
    @yield('scripts')
</body>

</html>
