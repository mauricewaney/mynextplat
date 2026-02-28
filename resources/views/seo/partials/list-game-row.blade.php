<div class="list-game border-b border-slate-800">
    @if($game->cover_url)
        <img class="w-[60px] h-[80px] object-cover rounded flex-shrink-0" src="{{ $game->cover_url }}" alt="{{ $game->title }}" loading="lazy">
    @else
        <div class="w-[60px] h-[80px] bg-slate-700 rounded flex-shrink-0"></div>
    @endif
    <div class="flex-1 min-w-0">
        <div class="font-bold text-sm mb-0.5">
            <a href="/game/{{ $game->slug }}" class="text-primary-400 hover:text-primary-300 hover:underline">{{ $game->title }}</a>
        </div>
        <div class="text-xs text-gray-500">
            @if($game->difficulty)
                Difficulty: {{ $game->difficulty }}/10
            @endif
            @if($game->difficulty && $game->time_min)
                &middot;
            @endif
            @if($game->time_min)
                Time: {{ $game->time_range }}
            @endif
            @if(($game->difficulty || $game->time_min) && $game->critic_score)
                &middot;
            @endif
            @if($game->critic_score)
                Critic: {{ $game->critic_score }}
            @endif
        </div>
        <div class="flex flex-wrap gap-1 mt-1">
            @foreach($game->platforms as $platform)
                <span class="bg-slate-700 text-gray-300 px-1.5 py-0.5 rounded text-[0.65rem]">{{ $platform->short_name ?? $platform->name }}</span>
            @endforeach
            @foreach($game->genres as $genre)
                <span class="bg-slate-700 text-gray-300 px-1.5 py-0.5 rounded text-[0.65rem]">{{ $genre->name }}</span>
            @endforeach
        </div>
    </div>
</div>
