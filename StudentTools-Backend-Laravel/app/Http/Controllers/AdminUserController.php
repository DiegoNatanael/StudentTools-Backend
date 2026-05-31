<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    
    protected function authorizeAdmin()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado. Se requieren privilegios de Administrador.');
        }
    }

    
    public function index()
    {
        $this->authorizeAdmin();

        $users = User::where('id', '!=', Auth::id())
            ->withCount('generations')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalStudents = User::where('role', 'student')->count();
        $totalCreations = Generation::count();
        $averageCreations = $totalStudents > 0 ? round($totalCreations / $totalStudents, 1) : 0;

        return view('admin.users.index', compact('users', 'totalStudents', 'totalCreations', 'averageCreations'));
    }

    
    public function updateQuota(Request $request, $id)
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        $request->validate([
            'quota' => 'required|integer|min:0|max:1000',
        ]);

        $user->update([
            'quota' => $request->quota,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "¡Límite de cuota para {$user->name} actualizado a {$request->quota} exitosamente!");
    }

    
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return back()->with('error', 'No puedes eliminar a otro administrador.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "La cuenta del alumno {$user->name} ha sido eliminada correctamente.");
    }
}