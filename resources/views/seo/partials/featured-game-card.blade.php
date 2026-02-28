<div class="featured-game bg-slate-800/60 rounded-lg">
    @if($game->cover_url)
        <img class="w-[140px] h-[187px] object-cover rounded-md flex-shrink-0" src="{{ $game->cover_url }}" alt="{{ $game->title }}" loading="lazy">
    @else
        <div class="w-[140px] h-[187px] bg-slate-700 rounded-md flex-shrink-0 flex items-center justify-center text-slate-500 text-xs">No cover</div>
    @endif
    <div class="flex-1 min-w-0">
        <div class="text-lg font-bold mb-1">
            <a href="/game/{{ $game->slug }}" class="text-primary-400 hover:text-primary-300 hover:underline">{{ $game->title }}</a>
        </div>
        <div class="featured-stats flex flex-wrap gap-2 text-sm text-gray-400 mb-1">
            @if($game->difficulty)
                <span class="bg-slate-900/80 px-2 py-0.5 rounded text-xs">Difficulty: {{ $game->difficulty }}/10</span>
            @endif
            @if($game->time_min)
                <span class="bg-slate-900/80 px-2 py-0.5 rounded text-xs">Time: {{ $game->time_range }}</span>
            @endif
            @if($game->critic_score)
                <span class="bg-slate-900/80 px-2 py-0.5 rounded text-xs">Critic: {{ $game->critic_score }}</span>
            @endif
            @if($game->user_score)
                <span class="bg-slate-900/80 px-2 py-0.5 rounded text-xs">User: {{ $game->user_score }}</span>
            @endif
        </div>
        @if($game->description)
            <p class="text-sm text-gray-500 my-2 leading-relaxed">{{ Str::limit($game->description, 200) }}</p>
        @endif
        <div class="badges flex flex-wrap gap-1.5 mt-2">
            @foreach($game->platforms as $platform)
                <span class="bg-slate-700 text-gray-300 px-2 py-0.5 rounded text-xs">{{ $platform->short_name ?? $platform->name }}</span>
            @endforeach
            @foreach($game->genres as $genre)
                <span class="bg-slate-700 text-gray-300 px-2 py-0.5 rounded text-xs">{{ $genre->name }}</span>
            @endforeach
        </div>
    </div>
</div>
