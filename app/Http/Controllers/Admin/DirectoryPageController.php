<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Models\DirectoryPage;
use App\Models\DirectorySection;
use App\Models\Genre;
use App\Models\Platform;
use App\Services\DirectorySectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryPageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = DirectoryPage::withCount('sections')
            ->orderBy('directory_type')
            ->orderBy('slug')
            ->get();

        return response()->json($pages);
    }

    public function availableSlugs(): JsonResponse
    {
        $genres = Genre::orderBy('name')->pluck('slug', 'name');
        $platforms = Platform::orderBy('name')->get()->map(fn ($p) => [
            'slug' => $p->slug,
            'name' => $p->short_name ?? $p->name,
        ]);

        $presets = collect([
            'fast-and-easy' => 'Fast & Easy Platinum Trophies',
            'must-play' => 'Must Play Trophy Games',
            'no-stress' => 'No Stress Platinum Trophies',
            'easy-platinums' => 'Easy Platinum Trophies',
            'quick-platinums' => 'Quick Platinum Trophies',
            'offline-only' => 'Offline Only Platinum Trophies',
            'no-missables' => 'No Missable Trophies',
            'hidden-gems' => 'Hidden Gem Trophy Games',
            'quality-epics' => 'Quality Epic Trophy Games',
        ]);

        return response()->json([
            'genre' => $genres->map(fn ($slug, $name) => ['slug' => $slug, 'name' => $name])->values(),
            'platform' => $platforms,
            'preset' => $presets->map(fn ($name, $slug) => ['slug' => $slug, 'name' => $name])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'directory_type' => 'required|in:genre,platform,preset',
            'slug' => 'required|string|max:100',
            'intro_text' => 'nullable|string',
            'featured_game_ids' => 'nullable|array|max:10',
            'featured_game_ids.*' => 'integer|exists:games,id',
            'related_pages' => 'nullable|array',
            'related_pages.*.type' => 'required_with:related_pages|in:genre,platform,preset',
            'related_pages.*.slug' => 'required_with:related_pages|string|max:100',
            'related_pages.*.label' => 'required_with:related_pages|string|max:200',
        ]);

        $page = DirectoryPage::updateOrCreate(
            ['directory_type' => $validated['directory_type'], 'slug' => $validated['slug']],
            $validated
        );

        GameController::bustGameCache();

        return response()->json($page->load('sections'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $page = DirectoryPage::with('sections')->findOrFail($id);

        return response()->json($page);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = DirectoryPage::findOrFail($id);

        $validated = $request->validate([
            'intro_text' => 'nullable|string',
            'featured_game_ids' => 'nullable|array|max:10',
            'featured_game_ids.*' => 'integer|exists:games,id',
            'related_pages' => 'nullable|array',
            'related_pages.*.type' => 'required_with:related_pages|in:genre,platform,preset',
            'related_pages.*.slug' => 'required_with:related_pages|string|max:100',
            'related_pages.*.label' => 'required_with:related_pages|string|max:200',
        ]);

        $page->update($validated);

        GameController::bustGameCache();

        return response()->json($page->load('sections'));
    }

    public function destroy(int $id): JsonResponse
    {
        $page = DirectoryPage::findOrFail($id);
        $page->delete();

        GameController::bustGameCache();

        return response()->json(['message' => 'Deleted']);
    }

    // ── Section endpoints ──

    public function addSection(Request $request, int $id): JsonResponse
    {
        $page = DirectoryPage::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'sort_order' => 'integer|min:0',
            'filter_definition' => 'nullable|array',
            'game_ids' => 'nullable|array',
            'game_ids.*' => 'integer|exists:games,id',
            'limit' => 'integer|min:3|max:12',
        ]);

        $section = $page->sections()->create($validated);

        GameController::bustGameCache();

        return response()->json($section, 201);
    }

    public function updateSection(Request $request, int $id, int $sectionId): JsonResponse
    {
        $section = DirectorySection::where('directory_page_id', $id)->findOrFail($sectionId);

        $validated = $request->validate([
            'title' => 'string|max:200',
            'sort_order' => 'integer|min:0',
            'filter_definition' => 'nullable|array',
            'game_ids' => 'nullable|array',
            'game_ids.*' => 'integer|exists:games,id',
            'limit' => 'integer|min:3|max:12',
        ]);

        $section->update($validated);

        GameController::bustGameCache();

        return response()->json($section);
    }

    public function deleteSection(int $id, int $sectionId): JsonResponse
    {
        $section = DirectorySection::where('directory_page_id', $id)->findOrFail($sectionId);
        $section->delete();

        GameController::bustGameCache();

        return response()->json(['message' => 'Deleted']);
    }

    public function reorderSections(Request $request, int $id): JsonResponse
    {
        $page = DirectoryPage::findOrFail($id);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($validated['order'] as $index => $sectionId) {
            DirectorySection::where('id', $sectionId)
                ->where('directory_page_id', $id)
                ->update(['sort_order' => $index]);
        }

        GameController::bustGameCache();

        return response()->json($page->load('sections'));
    }

    public function previewSection(Request $request, int $id, int $sectionId): JsonResponse
    {
        $section = DirectorySection::where('directory_page_id', $id)->findOrFail($sectionId);
        $sectionService = app(DirectorySectionService::class);

        $games = $sectionService->resolveSection($section);

        return response()->json($games->map(fn ($g) => [
            'id' => $g->id,
            'title' => $g->title,
            'slug' => $g->slug,
            'cover_url' => $g->cover_url,
            'difficulty' => $g->difficulty,
            'time_range' => $g->time_range,
            'critic_score' => $g->critic_score,
        ]));
    }
}
