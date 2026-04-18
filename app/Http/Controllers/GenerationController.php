<?php
namespace App\Http\Controllers;
use App\Models\Generation;
use Illuminate\Http\Request;
use Carbon\Carbon;
class GenerationController extends Controller
{
    public function checkQuota(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return response()->json([
                'allowed' => true,
                'remaining' => 'unlimited',
                'quota' => $user->quota,
            ]);
        }
        $todayCount = Generation::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();
        $remaining = max(0, $user->quota - $todayCount);
        return response()->json([
            'allowed' => $remaining > 0,
            'remaining' => $remaining,
            'quota' => $user->quota,
            'used' => $todayCount,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:document,presentation,diagram',
            'topic' => 'required|string|max:255',
        ]);
        $user = $request->user();
        if ($user->role !== 'admin') {
            $todayCount = Generation::where('user_id', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->count();
            if ($todayCount >= $user->quota) {
                return response()->json([
                    'message' => 'Daily quota exceeded. Come back tomorrow!',
                ], 429);
            }
        }
        $generation = $user->generations()->create($validated);
        return response()->json($generation, 201);
    }
    public function index(Request $request)
    {
        $generations = $request->user()
            ->generations()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($generations);
    }
}