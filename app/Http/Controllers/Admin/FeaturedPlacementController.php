<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Models\FeaturedPlacement;
use App\Models\Game;
use App\Models\PsnTitle;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeaturedPlacementController extends Controller
{
    public function index(): JsonResponse
    {
        $placements = FeaturedPlacement::with(['game:id,title,slug,cover_url,has_platinum,bronze_count,silver_count,gold_count,platinum_count,psn_store_url', 'game.psnTitles:id,game_id,psn_title,platform,np_communication_id'])
            ->withCount('clicks')
            ->orderBy('position')
            ->get();

        return response()->json([
            'label' => SiteSetting::get('featured_label', 'Featured'),
            'placements' => $placements,
        ]);
    }

    public function updateLabel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
        ]);

        SiteSetting::set('featured_label', $validated['label']);

        GameController::bustGameCache();

        return response()->json(['label' => $validated['label']]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'tagline' => 'nullable|string|max:120',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $maxPosition = FeaturedPlacement::max('position') ?? 0;

        $placement = FeaturedPlacement::create([
            'game_id' => $validated['game_id'],
            'tagline' => $validated['tagline'] ?? null,
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
            'tagline' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $placement->update($validated);

        GameController::bustGameCache();

        return response()->json($placement->load('game:id,title,slug,cover_url'));
    }

    public function searchPsnTitles(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $titles = PsnTitle::where('psn_title', 'like', '%' . $request->q . '%')
            ->orderByRaw('game_id IS NOT NULL, times_seen DESC')
            ->limit(15)
            ->get(['id', 'psn_title', 'platform', 'np_communication_id', 'game_id', 'has_platinum', 'bronze_count', 'silver_count', 'gold_count', 'times_seen']);

        return response()->json($titles);
    }

    public function linkPsnTitle(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
        ]);

        $placement = FeaturedPlacement::findOrFail($id);
        $game = Game::findOrFail($placement->game_id);
        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);

        $psnTitle->linkToGame($game);

        GameController::bustGameCache();

        return response()->json([
            'message' => "Linked \"{$psnTitle->psn_title}\" to \"{$game->title}\"",
            'game' => $game->fresh(['psnTitles:id,game_id,psn_title,platform,np_communication_id']),
        ]);
    }

    public function addNpId(Request $request, int $id): JsonResponse
    {
        $placement = FeaturedPlacement::findOrFail($id);

        $validated = $request->validate([
            'np_communication_id' => ['required', 'string', 'regex:/^NPWR\d{5}_\d{2}$/'],
        ]);

        $npId = $validated['np_communication_id'];
        $game = Game::findOrFail($placement->game_id);

        // Find existing or create new PSN title record
        $psnTitle = PsnTitle::firstOrCreate(
            ['np_communication_id' => $npId],
            [
                'psn_title' => $game->title,
                'platform' => null,
                'discovered_from' => 'manual',
                'times_seen' => 0,
            ]
        );

        // If already linked to a different game, reject
        if ($psnTitle->game_id && $psnTitle->game_id !== $game->id) {
            return response()->json([
                'message' => "This NP ID is already linked to \"{$psnTitle->game->title}\"",
            ], 422);
        }

        $psnTitle->linkToGame($game);

        GameController::bustGameCache();

        return response()->json([
            'message' => "Linked NP ID {$npId} to \"{$game->title}\"",
            'game' => $game->fresh(['psnTitles:id,game_id,psn_title,platform,np_communication_id']),
        ]);
    }

    public function unlinkPsnTitle(Request $request, int $id): JsonResponse
    {
        $placement = FeaturedPlacement::findOrFail($id);

        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $psnTitle->unlinkFromGame();

        GameController::bustGameCache();

        $game = Game::findOrFail($placement->game_id);

        return response()->json([
            'message' => "Unlinked \"{$psnTitle->psn_title}\"",
            'game' => $game->fresh(['psnTitles:id,game_id,psn_title,platform,np_communication_id']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $placement = FeaturedPlacement::findOrFail($id);
        $placement->delete();

        GameController::bustGameCache();

        return response()->json(['message' => 'Featured placement removed.']);
    }
}
