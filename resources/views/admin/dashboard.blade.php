@extends('layouts.admin')
@section('title', 'Dashboard')
@php use App\Models\Player; $inr = fn($v) => '₹'.number_format($v, 0); @endphp
@section('content')
@if($t)
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-pitch-900 to-pitch-700 p-6 text-white shadow">
    <div class="seam absolute inset-y-0 right-24 w-10 opacity-60"></div>
    <div class="relative flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <div class="text-xs uppercase tracking-widest text-amber-300">{{ $t->season }}</div>
            <div class="text-2xl font-extrabold">{{ $t->name }}</div>
            <div class="mt-1 text-sm text-white/70">
                Registration {{ $t->registration_start_date?->format('d M') ?? '—' }} → {{ $t->registration_end_date?->format('d M Y') ?? '—' }}
                · Fee {{ $inr($t->currentFee()) }}
            </div>
        </div>
        <x-badge :status="$t->isRegistrationOpen() ? 'open' : 'closed'" :label="$t->isRegistrationOpen() ? 'Registration Open' : 'Registration Closed'" class="!text-sm" />
        <form method="POST" action="{{ route('admin.tournaments.toggle', $t) }}">@csrf<button class="btn btn-gold">{{ $t->registration_status === 'open' ? 'Close registration' : 'Open registration' }}</button></form>
    </div>
</div>

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    <x-stat label="Total registered" :value="$stats['total']" tone="slate" :href="route('admin.players.index')" />
    <x-stat label="Approved" :value="$stats['approved']" tone="green" :href="route('admin.players.index', ['status' => 'approved'])" />
    <x-stat label="Pending" :value="$stats['pending']" tone="amber" :href="route('admin.players.index', ['status' => 'pending'])" />
    <x-stat label="Rejected" :value="$stats['rejected']" tone="red" :href="route('admin.players.index', ['status' => 'rejected'])" />
    <x-stat label="Male players" :value="$stats['gender']['male'] ?? 0" tone="white" :href="route('admin.players.index', ['gender' => 'male'])" />
    <x-stat label="Female players" :value="$stats['gender']['female'] ?? 0" tone="white" :href="route('admin.players.index', ['gender' => 'female'])">Other: {{ $stats['gender']['other'] ?? 0 }}</x-stat>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat label="Expected collection" :value="$inr($pay['expected'])" tone="white">{{ $stats['total'] - $stats['rejected'] }} active registrations</x-stat>
    <x-stat label="Payment received" :value="$inr($pay['received'])" tone="white">{{ $stats['completed'] }} confirmed</x-stat>
    <x-stat label="Awaiting verification" :value="$pay['pending_count']" tone="blue" :href="route('admin.payments.index', ['status' => 'proof_submitted'])">{{ $pay['unpaid_count'] }} not paid yet</x-stat>
    <x-stat label="Rejected payments" :value="$pay['rejected_count']" tone="white" :href="route('admin.payments.index', ['status' => 'payment_rejected'])" />
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <x-card title="Village-wise players" class="xl:col-span-1">
        @php $maxV = max(1, $stats['villages']->max() ?? 1); @endphp
        <div class="space-y-2">
            @forelse($stats['villages'] as $v => $c)
                <a href="{{ route('admin.players.index', ['village' => $v]) }}" class="block">
                    <div class="flex justify-between text-xs"><span class="font-medium">{{ $v ?: '—' }}</span><span class="text-slate-500">{{ $c }}</span></div>
                    <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-pitch-600" style="width: {{ $c / $maxV * 100 }}%"></div></div>
                </a>
            @empty <p class="text-sm text-slate-500">No registrations yet.</p> @endforelse
        </div>
    </x-card>
    <x-card title="Role-wise players">
        @php $maxR = max(1, $stats['roles']->max() ?? 1); $colors = ['batsman'=>'bg-sky-500','bowler'=>'bg-rose-500','all_rounder'=>'bg-amber-500','wicket_keeper'=>'bg-violet-500']; @endphp
        <div class="space-y-3">
            @foreach(Player::ROLES as $k => $label)
                @php $c = $stats['roles'][$k] ?? 0; @endphp
                <a href="{{ route('admin.players.index', ['playing_role' => $k]) }}" class="block">
                    <div class="flex justify-between text-sm"><span>{{ $label }}</span><span class="font-semibold">{{ $c }}</span></div>
                    <div class="mt-1 h-2.5 rounded-full bg-slate-100"><div class="h-2.5 rounded-full {{ $colors[$k] }}" style="width: {{ $c / $maxR * 100 }}%"></div></div>
                </a>
            @endforeach
        </div>
    </x-card>
    <x-card title="Payments to verify">
        <x-slot:actions><a href="{{ route('admin.payments.index') }}" class="text-xs font-semibold text-pitch-600">View all →</a></x-slot:actions>
        <ul class="divide-y divide-slate-100">
            @forelse($toVerify as $p)
                <li class="flex items-center justify-between gap-2 py-2.5">
                    <div class="min-w-0"><div class="truncate text-sm font-medium">{{ $p->registration->player->full_name }}</div><div class="text-xs text-slate-500">UTR {{ $p->transaction_id }} · ₹{{ number_format($p->amount, 0) }}</div></div>
                    <a href="{{ route('admin.payments.show', $p) }}" class="btn btn-light !py-1 text-xs">Verify</a>
                </li>
            @empty <li class="py-2 text-sm text-slate-500">Nothing pending. 🎉</li> @endforelse
        </ul>
    </x-card>
</div>

<x-card title="Recent registrations">
    <x-slot:actions><a href="{{ route('admin.players.index') }}" class="text-xs font-semibold text-pitch-600">All players →</a></x-slot:actions>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>Player</th><th>Village</th><th>Role</th><th>Status</th><th>Payment</th><th>Submitted</th></tr></thead>
            <tbody>
            @forelse($recent as $r)
                <tr class="cursor-pointer" onclick="location.href='{{ route('admin.players.show', $r->player) }}'">
                    <td class="font-medium">{{ $r->player->full_name }}<div class="text-xs text-slate-500">{{ $r->player->user->mobile }}</div></td>
                    <td>{{ $r->player->village }}</td>
                    <td>{{ Player::label(Player::ROLES, $r->player->playing_role) }}</td>
                    <td><x-badge :status="$r->status" /></td>
                    <td><x-badge :status="$r->payment_status" /></td>
                    <td class="text-slate-500">{{ $r->submitted_at?->diffForHumans() }}</td>
                </tr>
            @empty <tr><td colspan="6" class="text-center text-slate-500">No registrations yet.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endif
@endsection
