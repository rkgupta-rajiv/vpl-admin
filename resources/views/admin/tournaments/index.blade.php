@extends('layouts.admin')
@section('title', 'Tournaments & Seasons')
@section('content')
<div class="flex justify-end"><a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary">+ New tournament / season</a></div>
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($tournaments as $t)
        <section class="flex flex-col rounded-2xl bg-white shadow-sm ring-1 {{ $t->is_active ? 'ring-2 ring-amber-400' : 'ring-slate-200' }}">
            <div class="flex items-start justify-between gap-2 border-b border-slate-100 p-5">
                <div><div class="text-xs font-semibold uppercase tracking-wide text-pitch-600">{{ $t->season }} · {{ $t->code }}</div><h3 class="text-lg font-bold">{{ $t->name }}</h3></div>
                <div class="flex flex-col items-end gap-1">@if($t->is_active)<span class="rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950">★ Active in app</span>@endif<x-badge :status="$t->registration_status" :label="'Registration '.ucfirst($t->registration_status)" /></div>
            </div>
            <dl class="grid flex-1 grid-cols-2 gap-3 p-5 text-sm">
                <div><dt class="text-xs text-slate-500">Opens</dt><dd>{{ $t->registration_start_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Closes</dt><dd>{{ $t->registration_end_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Fee</dt><dd class="font-semibold">₹{{ number_format($t->paymentSetting?->registration_fee ?? 0, 2) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Registrations</dt><dd class="font-semibold">{{ $t->registrations_count }}</dd></div>
            </dl>
            <div class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
                <a href="{{ route('admin.tournaments.edit', $t) }}" class="btn btn-light !py-1 text-xs">Edit</a>
                <a href="{{ route('admin.tournaments.payment', $t) }}" class="btn btn-light !py-1 text-xs">Payment settings</a>
                <form method="POST" action="{{ route('admin.tournaments.toggle', $t) }}">@csrf<button class="btn btn-light !py-1 text-xs">{{ $t->registration_status === 'open' ? 'Close' : 'Open' }} registration</button></form>
                @unless($t->is_active)<form method="POST" action="{{ route('admin.tournaments.activate', $t) }}">@csrf<button class="btn btn-gold !py-1 text-xs">Make active</button></form>@endunless
                @if(! $t->registrations_count)<form method="POST" action="{{ route('admin.tournaments.destroy', $t) }}" onsubmit="return confirm('Delete this tournament?')">@csrf @method('DELETE')<button class="btn !py-1 text-xs text-rose-600">Delete</button></form>@endif
            </div>
        </section>
    @empty
        <p class="text-slate-500">No tournaments yet.</p>
    @endforelse
</div>
<p class="text-xs text-slate-500">★ The <b>active</b> tournament is the one players see and register for in the app. Previous seasons and their data stay available here and in the top-bar switcher.</p>
@endsection
