<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameCorrection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameCorrectionController extends Controller
{
    /**
     * List corrections with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GameCorrection::with(['game:id,title,slug,cover_url', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Search in description
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $corrections = $query->paginate($request->get('per_page', 20));

        return response()->json($corrections);
    }

    /**
     * Get stats for the admin dashboard.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'pending' => GameCorrection::where('status', 'pending')->count(),
            'in_review' => GameCorrection::where('status', 'in_review')->count(),
            'applied' => GameCorrection::where('status', 'applied')->count(),
            'rejected' => GameCorrection::where('status', 'rejected')->count(),
            'total' => GameCorrection::count(),
            'by_category' => GameCorrection::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
        ];

        return response()->json($stats);
    }

    /**
     * Get a single correction with full game data for comparison.
     */
    public function show(int $id): JsonResponse
    {
        $correction = GameCorrection::with([
            'game:id,title,slug,cover_url,banner_url,developer,publisher,difficulty,time_min,time_max,playthroughs_required,has_online_trophies,missable_trophies,psnprofiles_url,playstationtrophies_url,powerpyx_url',
            'game.platforms:id,name,short_name',
            'game.genres:id,name',
            'user:id,name,email',
            'resolvedBy:id,name',
        ])->findOrFail($id);

        return response()->json($correction);
    }

    /**
     * Update correction status/notes.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $correction = GameCorrection::findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(GameCorrection::STATUSES))],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (isset($validated['status'])) {
            $correction->status = $validated['status'];

            // Mark as resolved if applied or rejected
            if (in_array($validated['status'], ['applied', 'rejected'])) {
                $correction->resolved_at = now();
                $correction->resolved_by = $request->user()->id;
            } else {
                $correction->resolved_at = null;
                $correction->resolved_by = null;
            }
        }

        if (array_key_exists('admin_notes', $validated)) {
            $correction->admin_notes = $validated['admin_notes'];
        }

        $correction->save();

        return response()->json([
            'message' => 'Correction updated.',
            'correction' => $correction->fresh(['game:id,title,slug', 'user:id,name', 'resolvedBy:id,name']),
        ]);
    }

    /**
     * Delete a correction.
     */
    public function destroy(int $id): JsonResponse
    {
        $correction = GameCorrection::findOrFail($id);
        $correction->delete();

        return response()->json(['message' => 'Correction deleted.']);
    }

    /**
     * Bulk update status.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:game_corrections,id'],
            'status' => ['required', Rule::in(array_keys(GameCorrection::STATUSES))],
        ]);

        $updateData = ['status' => $validated['status']];

        if (in_array($validated['status'], ['applied', 'rejected'])) {
            $updateData['resolved_at'] = now();
            $updateData['resolved_by'] = $request->user()->id;
        }

        GameCorrection::whereIn('id', $validated['ids'])->update($updateData);

        return response()->json([
            'message' => count($validated['ids']) . ' corrections updated.',
        ]);
    }
}
