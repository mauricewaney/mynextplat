@component('mail::message')
# New {{ $type === 'contact' ? 'contact message' : 'game correction' }}

@if ($capReached)
@component('mail::panel')
**Daily cap of {{ $cap }} reached.** Further submissions today are still being saved in the admin inbox, but won't trigger more emails until midnight. Worth a look — could be normal volume, could be a bot.
@endcomponent
@endif

**{{ $headline }}**

@if ($type === 'contact')
- **From:** {{ $item->name }} ({{ $item->email }})
- **Category:** {{ \App\Models\ContactMessage::CATEGORIES[$item->category] ?? $item->category }}
- **IP:** {{ $item->ip_address ?? 'unknown' }}
- **Submitted:** {{ $item->created_at->format('Y-m-d H:i') }}

**Subject:** {{ $item->subject }}

> {{ $item->message }}
@else
- **Game:** {{ optional($item->game)->title ?? 'Unknown' }}
- **Category:** {{ \App\Models\GameCorrection::CATEGORIES[$item->category] ?? $item->category }}
- **From:** {{ $item->email ?? optional($item->user)->email ?? 'anonymous' }}
- **IP:** {{ $item->ip_address ?? 'unknown' }}
- **Submitted:** {{ $item->created_at->format('Y-m-d H:i') }}
@if ($item->source_url)
- **Source:** {{ $item->source_url }}
@endif

> {{ $item->description }}
@endif

@component('mail::button', ['url' => $adminUrl])
Open admin inbox
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
