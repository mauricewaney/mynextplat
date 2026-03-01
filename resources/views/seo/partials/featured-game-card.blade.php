<a href="/game/{{ $game->slug }}" class="flex gap-3 bg-slate-800/60 rounded-lg p-3 hover:bg-slate-800 transition-colors">
    @if($game->cover_url)
        <img class="w-16 h-[85px] object-cover rounded-md flex-shrink-0" src="{{ $game->cover_url }}" alt="{{ $game->title }}" loading="lazy">
    @else
        <div class="w-16 h-[85px] bg-slate-700 rounded-md flex-shrink-0 flex items-center justify-center text-slate-500 text-[10px]">No cover</div>
    @endif
    <div class="flex-1 min-w-0">
        <h3 class="font-bold text-primary-400 text-sm leading-tight mb-1 truncate">{{ $game->title }}</h3>
        <div class="flex flex-wrap gap-1.5 text-xs text-gray-400 mb-1.5">
            @if($game->difficulty)
                <span class="bg-slate-900/80 px-1.5 py-0.5 rounded">{{ $game->difficulty }}/10</span>
            @endif
            @if($game->time_min)
                <span class="bg-slate-900/80 px-1.5 py-0.5 rounded">{{ $game->time_range }}</span>
            @endif
            @if($game->critic_score)
                <span class="bg-slate-900/80 px-1.5 py-0.5 rounded">{{ $game->critic_score }}%</span>
            @endif
        </div>
        @if($game->description)
            <p class="text-xs text-gray-500 leading-relaxed line-clamp-2">{{ Str::limit($game->description, 100) }}</p>
        @endif
    </div>
</a>
