<?php

namespace App\Http\Controllers;

use App\Models\GuideClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuideClickController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'guide_source' => ['required', Rule::in(['psnprofiles', 'playstationtrophies', 'powerpyx'])],
        ]);

        GuideClick::create([
            'user_id' => $request->user()?->id,
            'game_id' => $validated['game_id'],
            'guide_source' => $validated['guide_source'],
        ]);

        return response()->json(['status' => 'ok'], 201);
    }
}
