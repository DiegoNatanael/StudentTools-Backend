@extends('layouts.app')

@section('title', 'Mis Creaciones | StudentTools')

@section('content')
<section class="hero-section active" style="margin-top: 20px;">
    <div class="hero-text" style="margin-bottom: 30px; text-align: left; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 style="font-family: 'Outfit'; font-size: 3rem; font-weight: 800; color: white;">Mi Galería</h1>
            <p style="color: var(--text-dim); font-size: 1rem;">Administra, visualiza y edita tus documentos, diapositivas y diagramas generados.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="primary-btn" style="max-width: 200px; text-decoration: none; padding: 12px 20px;">
            <i class="fas fa-plus"></i> <span>Crear Nuevo</span>
        </a>
    </div>

    <!-- Filters Toolbar (Glassmorphic) -->
    <div class="glass-card" style="padding: 20px; margin-bottom: 40px;">
        <form action="{{ route('generations.index') }}" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1.5; min-width: 250px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por tema..." style="padding: 12px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
            </div>
            
            <div style="flex: 1; min-width: 180px;">
                <select name="type" style="padding: 12px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
                    <option value="">-- Todos los Tipos --</option>
                    <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>Documentos</option>
                    <option value="presentation" {{ request('type') === 'presentation' ? 'selected' : '' }}>Presentaciones</option>
                    <option value="diagram" {{ request('type') === 'diagram' ? 'selected' : '' }}>Diagramas</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 180px;">
                <select name="subject_id" style="padding: 12px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
                    <option value="">-- Todas las Carpetas --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="primary-btn" style="padding: 12px 20px; border-radius: 12px; width: auto; font-size: 0.95rem; box-shadow: none;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="{{ route('generations.index') }}" class="nav-btn auth-btn" style="padding: 12px 20px; border-radius: 12px; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: center;">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Creations Grid Layout -->
    @if($generations->isEmpty())
        <div class="glass-card" style="text-align: center; padding: 80px 40px; border-style: dashed; border-color: rgba(255,255,255,0.15);">
            <i class="fas fa-search" style="font-size: 3.5rem; color: var(--text-dim); margin-bottom: 25px; display: block;"></i>
            <h3 style="font-size: 1.5rem; font-family: 'Outfit'; color: white; margin-bottom: 10px;">No se encontraron creaciones</h3>
            <p style="color: var(--text-dim); max-width: 500px; margin: 0 auto 30px;">
                Parece que no tienes creaciones guardadas que coincidan con estos filtros. ¡Intenta expandir tus términos de búsqueda o genera algo nuevo!
            </p>
            <a href="{{ route('dashboard') }}" class="primary-btn" style="max-width: 220px; margin: 0 auto; text-decoration: none;">
                <span>Crear recurso ahora</span>
            </a>
        </div>
    @else
        <div class="diagram-layout" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px;">
            @foreach($generations as $i => $gen)
                <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 240px; position: relative; transition: all 0.3s ease; animation: cardFade 0.4s ease forwards; animation-delay: {{ $i * 0.05 }}s; opacity: 0; transform: translateY(20px);">
                    
                    <!-- Subject Folder Badge -->
                    @if($gen->subject)
                        <span style="position: absolute; top: 15px; right: 15px; background: {{ $gen->subject->color }}1b; border: 1px solid {{ $gen->subject->color }}; color: {{ $gen->subject->color }}; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                            <i class="fas fa-folder"></i> {{ $gen->subject->name }}
                        </span>
                    @endif

                    <div>
                        <div style="font-size: 1.8rem; color: {{ $gen->type === 'document' ? '#6366f1' : ($gen->type === 'presentation' ? '#a855f7' : '#10b981') }}; margin-bottom: 15px;">
                            @if($gen->type === 'document')
                                <i class="fas fa-file-signature"></i>
                            @elseif($gen->type === 'presentation')
                                <i class="fas fa-images"></i>
                            @else
                                <i class="fas fa-project-diagram"></i>
                            @endif
                        </div>

                        <h3 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 600; color: white; line-height: 1.4; margin-bottom: 10px; text-overflow: ellipsis; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            {{ $gen->topic }}
                        </h3>
                        <span style="color: var(--text-dim); font-size: 0.8rem; display: block; margin-bottom: 20px;">
                            {{ $gen->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <div style="display: flex; gap: 10px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                        <a href="{{ route('generations.show', $gen->id) }}" class="primary-btn" style="flex: 2; padding: 10px; font-size: 0.85rem; border-radius: 8px; box-shadow: none; text-decoration: none;">
                            <i class="fas fa-eye"></i> Abrir
                        </a>
                        <a href="{{ route('generations.edit', $gen->id) }}" class="nav-btn auth-btn" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('generations.destroy', $gen->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este recurso permanente de tu galería?');" style="flex: 1; display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-btn logout-btn" style="width: 100%; height: 100%; padding: 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Custom Pagination Links -->
        <div style="margin-top: 40px; display: flex; justify-content: center;">
            {{ $generations->appends(request()->query())->links() }}
        </div>
    @endif
</section>

<style>
    @keyframes cardFade {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection
