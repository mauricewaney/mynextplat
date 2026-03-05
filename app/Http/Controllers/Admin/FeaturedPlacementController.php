<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Models\FeaturedPlacement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeaturedPlacementController extends Controller
{
    public function index(): JsonResponse
    {
        $placements = FeaturedPlacement::with(['game:id,title,slug,cover_url'])
            ->orderBy('position')
            ->get();

        return response()->json($placements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'label' => 'nullable|string|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $maxPosition = FeaturedPlacement::max('position') ?? 0;

        $placement = FeaturedPlacement::create([
            'game_id' => $validated['game_id'],
            'label' => $validated['label'] ?? 'Indie Spotlight',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'position' => $maxPosition + 1,
        ]);

        GameController::bustGameCache();

        return response()->json(
            $placement->load('game:id,title,slug,cover_url'),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $placement = FeaturedPlacement::findOrFail($id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $placement->update($validated);

        GameController::bustGameCache();

        return response()->json($placement->load('game:id,title,slug,cover_url'));
    }

    public function destroy(int $id): JsonResponse
    {
        $placement = FeaturedPlacement::findOrFail($id);
        $placement->delete();

        GameController::bustGameCache();

        return response()->json(['message' => 'Featured placement removed.']);
    }
}
