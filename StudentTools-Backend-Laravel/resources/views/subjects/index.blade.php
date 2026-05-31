@extends('layouts.app')

@section('title', 'Asignaturas | StudentTools')

@section('content')
<section class="hero-section active" style="margin-top: 20px;">
    <div class="hero-text" style="margin-bottom: 30px; text-align: left;">
        <h1 style="font-family: 'Outfit'; font-size: 3rem; font-weight: 800; color: white;">Carpetas y Asignaturas</h1>
        <p style="color: var(--text-dim); font-size: 1rem;">Administra tus carpetas de estudio (materias) para organizar tus creaciones generadas con Inteligencia Artificial.</p>
    </div>

    <div class="diagram-layout" style="grid-template-columns: 350px 1fr; gap: 30px;">
        
        <!-- A. CREATE / EDIT SUBJECT CARD -->
        <div class="glass-card" style="height: fit-content;">
            <h3 id="formTitle" style="font-family: 'Outfit'; font-size: 1.4rem; font-weight: 600; color: white; margin-bottom: 25px;"><i class="fas fa-folder-plus"></i> Nueva Asignatura</h3>
            
            <form action="{{ route('subjects.store') }}" method="POST" id="subjectForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="input-group">
                    <label for="name">Nombre de la Asignatura</label>
                    <input type="text" id="name" name="name" placeholder="Ej: Física, Historia, Química" required style="padding: 12px 16px; border-radius: 12px; background: rgba(0,0,0,0.2);">
                    @error('name')
                        <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group" style="margin-bottom: 30px;">
                    <label for="color">Color de Categorización</label>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <input type="color" id="color" name="color" value="#6366f1" style="width: 50px; height: 50px; padding: 0; border: none; border-radius: 10px; background: transparent; cursor: pointer;">
                        <input type="text" id="colorText" value="#6366f1" style="flex: 1; padding: 12px; border-radius: 12px; background: rgba(0,0,0,0.2); font-family: monospace;" readonly>
                    </div>
                    @error('color')
                        <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="primary-btn" id="submitBtn" style="padding: 12px;">
                        <span id="btnText">Crear Carpeta</span>
                    </button>
                    <button type="button" class="nav-btn auth-btn hidden" id="cancelEditBtn" onclick="cancelEdit()" style="padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

        <!-- B. FOLDERS / SUBJECTS GALLERY -->
        <div>
            @if($subjects->isEmpty())
                <div class="glass-card" style="text-align: center; padding: 80px 40px; border-style: dashed; border-color: rgba(255,255,255,0.15); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-folder-open" style="font-size: 3.5rem; color: var(--text-dim); margin-bottom: 25px;"></i>
                    <h3 style="font-size: 1.5rem; font-family: 'Outfit'; color: white; margin-bottom: 10px;">No tienes asignaturas creadas</h3>
                    <p style="color: var(--text-dim); max-width: 500px; margin-bottom: 0;">
                        Crea tu primera asignatura en el formulario lateral para empezar a organizar tus documentos, diapositivas y diagramas generados.
                    </p>
                </div>
            @else
                <div class="diagram-layout" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px;">
                    @foreach($subjects as $sub)
                        <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 180px; border-top: 4px solid {{ $sub->color }}; transition: transform 0.2s ease;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div style="font-size: 1.5rem; color: {{ $sub->color }};"><i class="fas fa-folder"></i></div>
                                    <span style="background: rgba(255,255,255,0.05); color: var(--text-dim); font-size: 0.8rem; padding: 3px 10px; border-radius: 12px; font-weight: 500;">
                                        {{ $sub->generations_count }} recursos
                                    </span>
                                </div>
                                <h3 style="font-family: 'Outfit'; font-size: 1.3rem; font-weight: 600; color: white; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $sub->name }}
                                </h3>
                            </div>

                            <div style="display: flex; gap: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px;">
                                <!-- Edit Button triggers JS fill -->
                                <button type="button" onclick="startEdit({{ $sub->id }}, '{{ $sub->name }}', '{{ $sub->color }}')" class="nav-btn auth-btn" style="flex: 1; padding: 8px; font-size: 0.8rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                
                                <form action="{{ route('subjects.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar la asignatura \'{{ $sub->name }}\'? Las creaciones guardadas en esta carpeta se desvincularán pero NO serán eliminadas.');" style="flex: 1;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="nav-btn logout-btn" style="width: 100%; padding: 8px; font-size: 0.8rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 5px;">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>
    // Live color picker syncing
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('colorText');
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', (e) => {
            colorText.value = e.target.value.toUpperCase();
        });
    }

    // Toggle edit mode
    function startEdit(id, name, color) {
        const form = document.getElementById('subjectForm');
        const title = document.getElementById('formTitle');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const cancelBtn = document.getElementById('cancelEditBtn');
        const methodInput = document.getElementById('formMethod');

        // Populate fields
        document.getElementById('name').value = name;
        document.getElementById('color').value = color;
        document.getElementById('colorText').value = color.toUpperCase();

        // Adjust form actions
        form.action = `/subjects/${id}`;
        methodInput.value = 'PUT';
        title.innerHTML = `<i class="fas fa-edit"></i> Editar Asignatura`;
        btnText.textContent = "Guardar Cambios";
        cancelBtn.classList.remove('hidden');
    }

    function cancelEdit() {
        const form = document.getElementById('subjectForm');
        const title = document.getElementById('formTitle');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const cancelBtn = document.getElementById('cancelEditBtn');
        const methodInput = document.getElementById('formMethod');

        // Clear fields
        document.getElementById('name').value = '';
        document.getElementById('color').value = '#6366f1';
        document.getElementById('colorText').value = '#6366F1';

        // Restore form defaults
        form.action = "{{ route('subjects.store') }}";
        methodInput.value = 'POST';
        title.innerHTML = `<i class="fas fa-folder-plus"></i> Nueva Asignatura`;
        btnText.textContent = "Crear Carpeta";
        cancelBtn.classList.add('hidden');
    }
</script>
@endsection
