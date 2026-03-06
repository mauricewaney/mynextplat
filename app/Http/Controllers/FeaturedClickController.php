<?php

namespace App\Http\Controllers;

use App\Models\FeaturedClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeaturedClickController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'placement_id' => ['required', 'integer', 'exists:featured_placements,id'],
            'game_id' => ['required', 'integer', 'exists:games,id'],
        ]);

        FeaturedClick::create([
            'placement_id' => $validated['placement_id'],
            'game_id' => $validated['game_id'],
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['status' => 'ok'], 201);
    }
}
