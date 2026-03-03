@php
    $guides = array_filter([
        $game->psnprofiles_url ? ['label' => 'PSNP', 'url' => $game->psnprofiles_url, 'class' => 'guide-badge-psnp'] : null,
        $game->playstationtrophies_url ? ['label' => 'PST', 'url' => $game->playstationtrophies_url, 'class' => 'guide-badge-pst'] : null,
        $game->powerpyx_url ? ['label' => 'PPX', 'url' => $game->powerpyx_url, 'class' => 'guide-badge-ppx'] : null,
    ]);

    $diffClass = match(true) {
        !$game->difficulty => 'text-gray-300 dark:text-gray-600',
        $game->difficulty <= 2 => 'text-emerald-600',
        $game->difficulty <= 4 => 'text-green-600',
        $game->difficulty <= 6 => 'text-yellow-600',
        $game->difficulty <= 8 => 'text-orange-600',
        default => 'text-red-600',
    };

    $userScoreClass = match(true) {
        !$game->user_score => 'bg-gray-200 dark:bg-slate-700 text-gray-400 dark:text-gray-500',
        $game->user_score >= 75 => 'bg-emerald-500 text-white',
        $game->user_score >= 50 => 'bg-yellow-500 text-white',
        default => 'bg-red-500 text-white',
    };

    $criticScoreClass = match(true) {
        !$game->critic_score => 'border-gray-200 dark:border-slate-600 text-gray-400 dark:text-gray-500',
        $game->critic_score >= 75 => 'border-emerald-500 text-emerald-600 dark:text-emerald-400',
        $game->critic_score >= 50 => 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
        default => 'border-red-500 text-red-600 dark:text-red-400',
    };
@endphp
<div class="list-game border-b border-gray-200 dark:border-slate-800 relative">
    <div class="flex gap-3 flex-1 min-w-0 items-center">
        @if($game->cover_url)
            <img class="w-[60px] h-[80px] object-cover rounded flex-shrink-0" src="{{ $game->cover_url }}" alt="{{ $game->title }}" loading="lazy">
        @else
            <div class="w-[60px] h-[80px] bg-gray-200 dark:bg-slate-700 rounded flex-shrink-0 flex items-center justify-center text-gray-400 dark:text-slate-500 text-[10px]">No cover</div>
        @endif
        <div class="flex-1 min-w-0">
            {{-- Title + Scores --}}
            <div class="flex items-start gap-2 mb-1">
                <span class="font-bold text-primary-600 dark:text-primary-400 text-sm leading-tight truncate flex-1"><a href="/game/{{ $game->slug }}" class="after:absolute after:inset-0">{{ $game->title }}</a></span>
                <div class="flex items-center gap-1 shrink-0">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold {{ $game->user_score ? 'text-sm' : 'text-[9px]' }} {{ $userScoreClass }}">{{ $game->user_score ?? '--' }}</div>
                    <div class="w-6 h-6 rounded-lg flex items-center justify-center font-bold border {{ $game->critic_score ? 'text-xs' : 'text-[9px]' }} {{ $criticScoreClass }}">{{ $game->critic_score ?? '--' }}</div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] mb-3">
                @if($game->difficulty)
                    <span><span class="font-bold {{ $diffClass }}">{{ $game->difficulty }}/10</span> <span class="text-gray-500">Diff</span></span>
                @endif
                @if($game->time_min)
                    <span><span class="font-bold text-gray-700 dark:text-gray-300">{{ $game->time_range }}</span></span>
                @endif
                @if($game->missable_trophies === false)
                    <span class="font-bold text-primary-600 dark:text-primary-400">No Missables</span>
                @elseif($game->missable_trophies === true)
                    <span class="font-bold text-red-600 dark:text-red-400">Missables</span>
                @endif
                @if($game->has_online_trophies === false)
                    <span class="font-bold text-primary-600 dark:text-primary-400">No Online</span>
                @elseif($game->has_online_trophies === true)
                    <span class="font-bold text-red-600 dark:text-red-400">Online</span>
                @endif
            </div>

            {{-- Guide links --}}
            @if(!empty($guides))
                <div class="relative z-10 flex items-center gap-1 px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700/50 rounded-lg border border-gray-200 dark:border-slate-600 w-fit">
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    @foreach($guides as $guide)
                        <a href="{{ $guide['url'] }}" target="_blank" rel="noopener" class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $guide['class'] }} hover:opacity-80">{{ $guide['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
