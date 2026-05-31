<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class GenerationController extends Controller
{
    
    public function dashboard()
    {
        $user = Auth::user();
        $subjects = $user->subjects()->orderBy('name')->get();

        $todayCount = Generation::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $remaining = $user->role === 'admin' ? 'unlimited' : max(0, $user->quota - $todayCount);

        return view('dashboard', compact('subjects', 'remaining', 'todayCount'));
    }

    
    public function generate(Request $request)
    {
        $request->validate([
            'type'          => 'required|in:document,presentation,diagram',
            'topic'         => 'required|string|max:255',
            'subject_id'    => 'nullable|exists:subjects,id',
            'diagram_type'  => 'nullable|string|in:Flowchart,Timeline,Sequence Diagram,Venn Diagram,Mindmap',
        ]);

        $user = Auth::user();

        if ($user->role !== 'admin') {
            $todayCount = Generation::where('user_id', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($todayCount >= $user->quota) {
                return back()->with('error', 'Has superado tu límite de uso diario. ¡Vuelve mañana!');
            }
        }

        $pythonUrl = 'http://127.0.0.1:8000/api/generate';
        $content = '';

        try {
            if ($request->type === 'document') {
                $response = Http::timeout(180)->post("{$pythonUrl}/markdown", [
                    'topic' => $request->topic,
                ]);

                if ($response->failed()) {
                    throw new \Exception($response->json('detail') ?? 'Falló el motor de IA para documentos.');
                }

                $content = $response->json('markdown');

            } elseif ($request->type === 'presentation') {
                $response = Http::timeout(180)->post("{$pythonUrl}/plan", [
                    'topic' => $request->topic,
                    'type'  => 'presentation',
                ]);

                if ($response->failed()) {
                    throw new \Exception($response->json('detail') ?? 'Falló el motor de IA para presentaciones.');
                }

                $content = json_encode($response->json());

            } elseif ($request->type === 'diagram') {
                $diagType = $request->diagram_type ?? 'Flowchart';
                $response = Http::timeout(180)->post("{$pythonUrl}/diagram", [
                    'topic' => $request->topic,
                    'type'  => $diagType,
                ]);

                if ($response->failed()) {
                    throw new \Exception($response->json('detail') ?? 'Falló el motor de IA para diagramas.');
                }

                $content = $response->json('code');
            }

            $generation = Generation::create([
                'user_id'    => $user->id,
                'subject_id' => $request->subject_id,
                'type'       => $request->type,
                'topic'      => $request->topic,
                'content'    => $content,
            ]);

            return redirect()->route('generations.show', $generation->id)
                ->with('success', '¡Recurso de IA generado y guardado exitosamente!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error en la Generación: ' . $e->getMessage())->withInput();
        }
    }

    
    public function index(Request $request)
    {
        $user = Auth::user();
        $subjects = $user->subjects()->orderBy('name')->get();

        $query = Generation::where('user_id', $user->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('search')) {
            $query->where('topic', 'like', '%' . $request->search . '%');
        }

        $generations = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('generations.index', compact('generations', 'subjects'));
    }

    
    public function show($id)
    {
        $generation = Generation::findOrFail($id);

        if ($generation->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'No tienes permiso para ver esta creación.');
        }

        $slides = null;
        if ($generation->type === 'presentation') {
            $slides = json_decode($generation->content, true);
        }

        return view('generations.show', compact('generation', 'slides'));
    }

    
    public function edit($id)
    {
        $generation = Generation::findOrFail($id);

        if ($generation->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta creación.');
        }

        $subjects = Auth::user()->subjects()->orderBy('name')->get();

        return view('generations.edit', compact('generation', 'subjects'));
    }

    
    public function update(Request $request, $id)
    {
        $generation = Generation::findOrFail($id);

        if ($generation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'topic'      => 'required|string|max:255',
            'content'    => 'required|string',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $generation->update([
            'topic'      => $request->topic,
            'content'    => $request->content,
            'subject_id' => $request->subject_id,
        ]);

        return redirect()->route('generations.show', $generation->id)
            ->with('success', '¡Recurso de IA actualizado exitosamente!');
    }

    
    public function destroy($id)
    {
        $generation = Generation::findOrFail($id);

        if ($generation->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $generation->delete();

        return redirect()->route('generations.index')
            ->with('success', '¡Recurso de IA eliminado de tu galería!');
    }
}