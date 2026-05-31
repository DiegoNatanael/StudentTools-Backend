@extends('layouts.app')

@section('title', 'Workspace | StudentTools')

@section('content')
<section class="hero-section active" style="margin-top: 20px;">
    <div class="hero-text" style="margin-bottom: 30px;">
        <h1 style="font-family: 'Outfit'; font-size: 3rem; font-weight: 800; color: white;">Workspace Inteligente</h1>
        <p style="color: var(--text-dim); font-size: 1rem;">Usa el poder de NVIDIA NIM para crear y guardar tus recursos de estudio al instante.</p>
    </div>

    <form action="{{ route('generations.generate') }}" method="POST" id="generateForm" onsubmit="showLoadingOverlay()">
        @csrf

        <div class="diagram-layout">
            <!-- Sidebar / Control Panel -->
            <div class="glass-card sidebar">
                <!-- Select Resource Type -->
                <div class="input-group">
                    <label>Tipo de Recurso</label>
                    <select id="typeSelect" name="type" onchange="toggleFormFields()" required>
                        <option value="document" {{ old('type') === 'document' ? 'selected' : '' }}>Documento Académico</option>
                        <option value="presentation" {{ old('type') === 'presentation' ? 'selected' : '' }}>Presentación Diapositivas</option>
                        <option value="diagram" {{ old('type') === 'diagram' ? 'selected' : '' }}>Diagrama Visual</option>
                    </select>
                </div>

                <!-- Select Subject (Relational CRUD Folder) -->
                <div class="input-group">
                    <label>Asignatura / Carpeta</label>
                    <select name="subject_id">
                        <option value="">-- Sin Carpeta --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <small style="color: var(--text-dim); font-size: 0.8rem; margin-top: 5px; display: block;">
                        Organiza tu creación en una carpeta. Puedes crearlas en la pestaña <a href="{{ route('subjects.index') }}" style="color: #6366f1; text-decoration: none;">Asignaturas</a>.
                    </small>
                </div>

                <!-- Diagram Sub-type grid (Toggled via JS) -->
                <div class="input-group hidden" id="diagramTypeGroup">
                    <label>Tipo de Diagrama</label>
                    <input type="hidden" name="diagram_type" id="diagramTypeVal" value="{{ old('diagram_type', 'Flowchart') }}">
                    <div class="diagram-type-grid">
                        <div class="diag-type-card active" data-type="Flowchart" onclick="selectDiagType(this)">
                            <i class="fas fa-sitemap"></i>
                            <span>Flowchart</span>
                        </div>
                        <div class="diag-type-card" data-type="Timeline" onclick="selectDiagType(this)">
                            <i class="fas fa-clock"></i>
                            <span>Timeline</span>
                        </div>
                        <div class="diag-type-card" data-type="Sequence Diagram" onclick="selectDiagType(this)">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Secuencia</span>
                        </div>
                        <div class="diag-type-card" data-type="Venn Diagram" onclick="selectDiagType(this)">
                            <i class="fas fa-circle-nodes"></i>
                            <span>Venn</span>
                        </div>
                        <div class="diag-type-card" data-type="Mindmap" onclick="selectDiagType(this)">
                            <i class="fas fa-brain"></i>
                            <span>Mapa Mental</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workspace Topic Details -->
            <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 380px;">
                <div class="input-group" style="height: 100%;">
                    <label id="topicLabel">Tema del Documento</label>
                    <textarea id="topicInput" name="topic" placeholder="Describe lo que quieres generar... Ej: 'El impacto del calentamiento global en la biodiversidad polar'" style="height: calc(100% - 40px); min-height: 200px;" required>{{ old('topic') }}</textarea>
                </div>

                <button type="submit" class="primary-btn" style="margin-top: 20px;">
                    <span id="btnText">Generar Recurso</span>
                    <i class="fas fa-magic"></i>
                </button>
            </div>
        </div>
    </form>
</section>

<!-- Loader Overlay for Generation waiting time -->
<div id="loadingOverlay" class="status-log hidden" style="position: fixed; bottom: 30px; right: 30px; width: 320px; z-index: 9999;">
    <div class="log-content">
        <div class="log-header">
            <div class="loader" style="margin-right: 10px;"></div>
            <span>Generando recurso con NIM de NVIDIA...</span>
        </div>
        <div class="log-messages">
            <div class="msg">> La inteligencia artificial está redactando tu recurso de estudio...</div>
            <div class="msg">> Esto puede tardar unos segundos. Por favor no recargues la página...</div>
            <div class="msg" id="timerMsg">> Tiempo transcurrido: 0s</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleFormFields() {
        const type = document.getElementById('typeSelect').value;
        const diagGroup = document.getElementById('diagramTypeGroup');
        const label = document.getElementById('topicLabel');
        const input = document.getElementById('topicInput');

        if (type === 'diagram') {
            diagGroup.classList.remove('hidden');
            label.textContent = "Proceso o Concepto del Diagrama";
            input.placeholder = "Ingresa el tema o proceso a diagramar... Ej: 'El proceso digestivo humano'";
        } else {
            diagGroup.classList.add('hidden');
            if (type === 'document') {
                label.textContent = "Tema del Documento";
                input.placeholder = "Describe lo que quieres generar... Ej: 'El impacto del calentamiento global en la biodiversidad polar'";
            } else {
                label.textContent = "Tema de la Presentación";
                input.placeholder = "Ingresa el tema de tu diapositiva... Ej: 'Las Leyes de Newton explicadas de forma didáctica'";
            }
        }
    }

    function selectDiagType(card) {
        document.querySelectorAll('.diag-type-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        document.getElementById('diagramTypeVal').value = card.dataset.type;
    }

    let seconds = 0;
    let timerInterval = null;

    function showLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.remove('hidden');
        
        // Defer disabling form submit button to prevent double-click without interfering with form serialization
        setTimeout(() => {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            }
        }, 0);
        
        timerInterval = setInterval(() => {
            seconds++;
            document.getElementById('timerMsg').textContent = `> Tiempo transcurrido: ${seconds}s`;
        }, 1000);
    }

    // Call toggleFormFields on DOM load to align fields with old() value if redirected back
    document.addEventListener('DOMContentLoaded', toggleFormFields);
</script>
@endsection
