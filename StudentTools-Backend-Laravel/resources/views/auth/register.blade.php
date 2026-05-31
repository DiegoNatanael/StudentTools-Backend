@extends('layouts.app')

@section('title', 'Registro | StudentTools')

@section('content')
<section class="active" style="display: flex; justify-content: center; align-items: center; min-height: 75vh; width: 100%;">
    <div class="glass-card" style="width: 100%; max-width: 450px; padding: 45px 35px; box-shadow: 0 30px 60px rgba(0,0,0,0.6);">
        <div style="text-align: center; margin-bottom: 35px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 60px; margin-bottom: 15px;">
            <h2 style="font-family: 'Outfit'; font-size: 2rem; font-weight: 800; color: white;">Crear Cuenta</h2>
            <p style="color: var(--text-dim); font-size: 0.9rem; margin-top: 5px;">Regístrate de forma gratuita para empezar a crear</p>
        </div>

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="input-group">
                <label for="name">Nombre Completo</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Tu Nombre" required autofocus>
                @error('name')
                    <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="input-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required>
                @error('email')
                    <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                @error('password')
                    <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="input-group" style="margin-bottom: 30px;">
                <label for="password_confirmation">Confirmar Contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit" class="primary-btn" style="padding: 16px;">
                <span>Registrarse</span> <i class="fas fa-user-plus"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: var(--text-dim);">
            ¿Ya tienes cuenta? 
            <a href="{{ route('login') }}" style="color: #6366f1; text-decoration: none; font-weight: 600;">Inicia sesión aquí</a>
        </div>
    </div>
</section>
@endsection
