@extends('layouts.app')

@section('title', 'Editar Creación | StudentTools')

@section('content')
<section class="hero-section active" style="margin-top: 10px;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
        <a href="{{ route('generations.show', $generation->id) }}" class="nav-btn auth-btn" style="text-decoration: none; padding: 10px 15px; border-radius: 8px;"><i class="fas fa-chevron-left"></i> Cancelar</a>
        <div>
            <h2 style="font-family: 'Outfit'; font-size: 2rem; font-weight: 800; color: white;">Editar Creación</h2>
            <p style="color: var(--text-dim); font-size: 0.9rem;">Modifica los detalles o el contenido generado de tu recurso de IA</p>
        </div>
    </div>

    <div class="glass-card" style="padding: 40px 30px;">
        <form action="{{ route('generations.update', $generation->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="diagram-layout" style="grid-template-columns: 1fr 300px; gap: 30px;">
                <!-- Main Editor Area -->
                <div>
                    <div class="input-group">
                        <label for="topic">Tema / Título del Recurso</label>
                        <input type="text" id="topic" name="topic" value="{{ old('topic', $generation->topic) }}" required style="padding: 14px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
                        @error('topic')
                            <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="input-group" style="margin-bottom: 0; height: calc(100% - 100px); min-height: 400px;">
                        <label for="content">Contenido Generado</label>
                        <textarea id="content" name="content" style="font-family: 'Courier New', Courier, monospace; font-size: 0.95rem; height: 100%; min-height: 400px; padding: 20px; line-height: 1.5; background: rgba(0, 0, 0, 0.4); border-color: rgba(255, 255, 255, 0.05); color: #a5f3fc; resize: vertical;" required>{{ old('content', $generation->content) }}</textarea>
                        @error('content')
                            <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Sidebar settings -->
                <div style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div class="input-group">
                            <label>Tipo de Recurso</label>
                            <input type="text" value="{{ ucfirst($generation->type) }}" disabled style="padding: 14px 16px; border-radius: 12px; background: rgba(255,255,255,0.05); color: var(--text-dim); cursor: not-allowed;">
                        </div>

                        <div class="input-group">
                            <label for="subject_id">Mover a Asignatura / Carpeta</label>
                            <select name="subject_id" id="subject_id" style="padding: 14px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
                                <option value="">-- Sin Carpeta --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id', $generation->subject_id) == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; font-size: 0.85rem; color: var(--text-dim); line-height: 1.5;">
                            <strong><i class="fas fa-info-circle"></i> Consejos de Edición:</strong>
                            <ul style="padding-left: 20px; margin-top: 10px; list-style-type: square;">
                                @if($generation->type === 'document')
                                    <li>Edita libremente usando formato estándar de <strong>Markdown</strong>.</li>
                                    <li>Las tablas, listas y títulos se formatearán de inmediato al ver.</li>
                                @elseif($generation->type === 'presentation')
                                    <li>Este recurso contiene formato <strong>JSON estructurado</strong> de diapositivas.</li>
                                    <li>Ten especial cuidado con no corromper las llaves <code>{ }</code> ni las comillas dobles.</li>
                                @else
                                    <li>Modifica el código de **Mermaid.js** directamente para reestructurar el diagrama.</li>
                                    <li>Puedes añadir nuevos nodos utilizando flechas como <code>A --> B</code>.</li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="primary-btn" style="padding: 16px; margin-top: 30px;">
                        <span>Guardar Cambios</span> <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
