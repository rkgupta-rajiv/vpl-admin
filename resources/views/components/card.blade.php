@props(['title' => null, 'actions' => null])
<section {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if($title)
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-800">{{ $title }}</h2>
            @if($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endif
        </header>
    @endif
    <div class="p-5">{{ $slot }}</div>
</section>
