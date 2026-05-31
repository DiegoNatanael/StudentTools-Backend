<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    
    public function index()
    {
        $subjects = Auth::user()->subjects()
            ->withCount('generations')
            ->orderBy('name')
            ->get();

        return view('subjects.index', compact('subjects'));
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        Auth::user()->subjects()->create($validated);

        return redirect()->route('subjects.index')
            ->with('success', '¡Asignatura creada correctamente!');
    }

    
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        if ($subject->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.index')
            ->with('success', '¡Asignatura actualizada correctamente!');
    }

    
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        if ($subject->user_id !== Auth::id()) {
            abort(403);
        }

        $subject->delete();

        return redirect()->route('subjects.index')
            ->with('success', '¡Asignatura eliminada correctamente! Las creaciones asociadas han sido conservadas.');
    }
}