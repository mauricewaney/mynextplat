<x-mail::message>
# New Trophy Guides Available!

Hey {{ $user->name }},

Great news! New trophy guides have been added for {{ $games->count() === 1 ? 'a game' : 'games' }} in your list:

@foreach($games as $game)
<x-mail::panel>
**[{{ $game->title }}]({{ config('app.url') }}/game/{{ $game->slug }})**

@php
$stats = [];
if ($game->difficulty) $stats[] = "Difficulty: {$game->difficulty}/10";
if ($game->time_min) {
    $time = $game->time_min . ($game->time_max && $game->time_max != $game->time_min ? "-{$game->time_max}" : '');
    $stats[] = "Time: {$time} hours";
}
@endphp
@if(count($stats) > 0)
{{ implode(' | ', $stats) }}
@endif
</x-mail::panel>
@endforeach

<x-mail::button :url="config('app.url') . '/my-games'">
View My Games
</x-mail::button>

Happy trophy hunting!

<x-mail::subcopy>
Don't want these emails? [Unsubscribe]({{ $unsubscribeUrl }}) or manage your notification preferences in your account settings.
</x-mail::subcopy>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
