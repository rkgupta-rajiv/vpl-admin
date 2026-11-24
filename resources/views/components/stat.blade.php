@props(['label', 'value', 'tone' => 'slate', 'icon' => null, 'href' => null])
@php
    $tones = [
        'green' => 'from-emerald-600 to-emerald-700 text-white',
        'amber' => 'from-amber-400 to-amber-500 text-amber-950',
        'red' => 'from-rose-500 to-rose-600 text-white',
        'blue' => 'from-sky-500 to-sky-600 text-white',
        'slate' => 'from-slate-800 to-slate-900 text-white',
        'white' => 'from-white to-white text-slate-800 ring-1 ring-slate-200',
    ];
@endphp
<a @if($href) href="{{ $href }}" @endif class="block rounded-2xl bg-gradient-to-br {{ $tones[$tone] ?? $tones['slate'] }} p-4 shadow-sm transition hover:shadow-md">
    <div class="text-xs font-medium uppercase tracking-wide opacity-80">{{ $label }}</div>
    <div class="mt-1 text-2xl font-bold">{{ $value }}</div>
    @if($slot->isNotEmpty())<div class="mt-1 text-xs opacity-80">{{ $slot }}</div>@endif
</a>
