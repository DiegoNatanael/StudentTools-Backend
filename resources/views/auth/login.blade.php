@extends('layouts.app')

@section('title', 'Iniciar Sesión | StudentTools')

@section('content')
<section class="active" style="display: flex; justify-content: center; align-items: center; min-height: 70vh; width: 100%;">
    <div class="glass-card" style="width: 100%; max-width: 450px; padding: 45px 35px; box-shadow: 0 30px 60px rgba(0,0,0,0.6);">
        <div style="text-align: center; margin-bottom: 35px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 60px; margin-bottom: 15px;">
            <h2 style="font-family: 'Outfit'; font-size: 2rem; font-weight: 800; color: white;">Iniciar Sesión</h2>
            <p style="color: var(--text-dim); font-size: 0.9rem; margin-top: 5px;">Ingresa tus credenciales para continuar al workspace</p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="input-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autofocus>
                @error('email')
                    <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="input-group" style="margin-bottom: 15px;">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                @error('password')
                    <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text-dim); font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="remember" style="width: auto; height: auto; accent-color: #6366f1;">
                    Recordarme en este dispositivo
                </label>
            </div>

            <button type="submit" class="primary-btn" style="padding: 16px;">
                <span>Ingresar</span> <i class="fas fa-sign-in-alt"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: var(--text-dim);">
            ¿Aún no tienes cuenta? 
            <a href="{{ route('register') }}" style="color: #6366f1; text-decoration: none; font-weight: 600;">Regístrate aquí</a>
        </div>
    </div>
</section>
@endsection
