@extends('layouts.app')

@section('title', 'Admin Panel | StudentTools')

@section('content')
<section class="hero-section active" style="margin-top: 20px;">
    <div class="hero-text" style="margin-bottom: 30px; text-align: left;">
        <h1 style="font-family: 'Outfit'; font-size: 3rem; font-weight: 800; color: white;"><i class="fas fa-user-shield" style="color: #6366f1;"></i> Panel de Control Administrativo</h1>
        <p style="color: var(--text-dim); font-size: 1rem;">Administra las cuentas de los alumnos, monitorea el uso del sistema y califica sus actividades de IA.</p>
    </div>

    <!-- Core Metrics Showcase Cards (3 Column) -->
    <div class="diagram-layout" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="glass-card" style="display: flex; align-items: center; gap: 20px; padding: 25px;">
            <div style="font-size: 2.2rem; color: #6366f1; width: 60px; height: 60px; background: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <span style="color: var(--text-dim); font-size: 0.8rem; text-transform: uppercase; font-weight: 600;">Total Alumnos</span>
                <h3 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: white; margin-top: 2px;">{{ $totalStudents }}</h3>
            </div>
        </div>

        <div class="glass-card" style="display: flex; align-items: center; gap: 20px; padding: 25px;">
            <div style="font-size: 2.2rem; color: #a855f7; width: 60px; height: 60px; background: rgba(168, 85, 247, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-magic"></i>
            </div>
            <div>
                <span style="color: var(--text-dim); font-size: 0.8rem; text-transform: uppercase; font-weight: 600;">Recursos de IA Generados</span>
                <h3 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: white; margin-top: 2px;">{{ $totalCreations }}</h3>
            </div>
        </div>

        <div class="glass-card" style="display: flex; align-items: center; gap: 20px; padding: 25px;">
            <div style="font-size: 2.2rem; color: #10b981; width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <span style="color: var(--text-dim); font-size: 0.8rem; text-transform: uppercase; font-weight: 600;">Promedio por Alumno</span>
                <h3 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: white; margin-top: 2px;">{{ $averageCreations }}</h3>
            </div>
        </div>
    </div>

    <!-- Student Accounts CRUD Table -->
    <div class="glass-card" style="padding: 30px; overflow-x: auto;">
        <h3 style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 600; color: white; margin-bottom: 25px;"><i class="fas fa-user-friends"></i> Alumnos Registrados</h3>

        @if($users->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-dim); font-style: italic;">
                No hay alumnos registrados en el sistema actualmente.
            </div>
        @else
            <table style="width: 100%; border-collapse: collapse; min-width: 800px; color: #e2e8f0; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: var(--text-dim);">
                        <th style="padding: 15px 10px; text-align: left;">Alumno</th>
                        <th style="padding: 15px 10px; text-align: left;">Correo Electrónico</th>
                        <th style="padding: 15px 10px; text-align: left;">Fecha de Registro</th>
                        <th style="padding: 15px 10px; text-align: center;">Creaciones de IA</th>
                        <th style="padding: 15px 10px; text-align: center; width: 220px;">Límite Cuota Diaria</th>
                        <th style="padding: 15px 10px; text-align: center; width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 20px 10px; font-weight: 600; color: white;">
                                <i class="fas fa-user" style="color: #6366f1; margin-right: 8px;"></i> {{ $user->name }}
                            </td>
                            <td style="padding: 20px 10px;">{{ $user->email }}</td>
                            <td style="padding: 20px 10px; color: var(--text-dim);">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding: 20px 10px; text-align: center; font-weight: 700; color: #a855f7;">
                                <span style="background: rgba(168, 85, 247, 0.1); padding: 4px 12px; border-radius: 12px;">{{ $user->generations_count }}</span>
                            </td>
                            <td style="padding: 20px 10px;">
                                <!-- Inline Quota Update Form -->
                                <form action="{{ route('admin.users.quota', $user->id) }}" method="POST" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="quota" value="{{ $user->quota }}" min="0" max="999" required style="width: 70px; padding: 6px 10px; border-radius: 8px; background: rgba(0,0,0,0.3); text-align: center; font-family: monospace; font-weight: bold; border-color: rgba(255,255,255,0.05);">
                                    <button type="submit" class="primary-btn" style="width: auto; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; box-shadow: none;" title="Actualizar Límite">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                            <td style="padding: 20px 10px; text-align: center;">
                                <!-- Suspend Student Form -->
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Estás absolutamente seguro de que deseas eliminar permanentemente la cuenta de este alumno? Esta acción borrará todas sus creaciones.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="nav-btn logout-btn" style="padding: 8px 12px; border-radius: 8px;" title="Suspender Alumno">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Custom Pagination -->
            <div style="margin-top: 30px; display: flex; justify-content: center;">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
