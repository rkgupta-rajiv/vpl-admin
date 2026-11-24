@extends('layouts.admin')
@section('title', 'Payment #'.$payment->id)
@php $reg = $payment->registration; $pl = $reg->player; $open = in_array($payment->status, ['proof_submitted','under_verification']); @endphp
@section('content')
<div class="grid gap-6 xl:grid-cols-5">
    <x-card title="Payment screenshot" class="xl:col-span-2">
        @if($payment->screenshot_path)
            <a href="{{ route('admin.payments.screenshot', $payment) }}" target="_blank" class="block overflow-hidden rounded-xl bg-slate-900">
                <img src="{{ route('admin.payments.screenshot', $payment) }}" class="mx-auto max-h-[640px] object-contain" alt="Payment screenshot">
            </a>
            <div class="mt-3 flex gap-2"><a href="{{ route('admin.payments.screenshot', $payment) }}" target="_blank" class="btn btn-light text-xs">Open full size</a><a href="{{ route('admin.payments.screenshot', [$payment, 'download']) }}" class="btn btn-light text-xs">⬇ Download</a></div>
        @else
            <p class="text-sm text-slate-500">No screenshot (offline payment recorded by admin).</p>
        @endif
    </x-card>

    <div class="space-y-6 xl:col-span-3">
        <x-card title="Verification">
            <x-slot:actions><x-badge :status="$payment->status" class="!text-sm" /></x-slot:actions>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4"><div class="text-xs text-slate-500">Expected fee (locked at registration)</div><div class="text-2xl font-bold">₹{{ number_format($payment->expected_amount, 2) }}</div></div>
                <div class="rounded-xl p-4 {{ $payment->amountMatches() ? 'bg-emerald-50' : 'bg-rose-50' }}"><div class="text-xs text-slate-500">Amount entered by player</div><div class="text-2xl font-bold {{ $payment->amountMatches() ? 'text-emerald-700' : 'text-rose-700' }}">₹{{ number_format($payment->amount, 2) }} {{ $payment->amountMatches() ? '✓' : '✕' }}</div></div>
                <div><div class="text-xs text-slate-500">UPI Transaction ID / UTR</div><div class="font-mono text-lg font-bold tracking-wide">{{ $payment->transaction_id ?? '—' }}</div></div>
                <div><div class="text-xs text-slate-500">Payment date</div><div class="font-semibold">{{ $payment->payment_date?->format('d M Y') }}</div></div>
                <div><div class="text-xs text-slate-500">Submitted</div><div class="font-semibold">{{ $payment->created_at->format('d M Y, h:i A') }}</div></div>
                <div><div class="text-xs text-slate-500">Method</div><div class="font-semibold uppercase">{{ $payment->method }}</div></div>
            </div>
            @if($dupes->count())
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">⚠ This UTR was also used in: @foreach($dupes as $d)<a class="font-semibold underline" href="{{ route('admin.payments.show', $d) }}">#{{ $d->id }} ({{ $d->registration->player->full_name }}, {{ $d->statusLabel() }})</a> @endforeach</div>
            @endif
            @if($payment->admin_remarks)<div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm"><b>Remarks:</b> {{ $payment->admin_remarks }}</div>@endif
            @if($payment->verifier)<p class="mt-2 text-xs text-slate-500">{{ $payment->statusLabel() }} by {{ $payment->verifier->name }} on {{ $payment->verified_at?->format('d M Y, h:i A') }}</p>@endif

            @if($open)
                <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                    <button class="btn btn-primary" onclick="openModal({action:'{{ route('admin.payments.approve', $payment) }}',title:'Approve payment of ₹{{ number_format($payment->amount,2) }}?',text:'Confirm you have checked UTR {{ $payment->transaction_id }} in your bank/UPI account. The registration will be marked COMPLETED and a Registration ID generated.',button:'Approve payment'})">✓ Approve payment</button>
                    <button class="btn btn-danger" onclick="openModal({action:'{{ route('admin.payments.reject', $payment) }}',title:'Reject payment',text:'The player will see the remarks and can submit proof again.',required:true,danger:true,button:'Reject payment',placeholder:'e.g. UTR not found in bank statement'})">✕ Reject</button>
                </div>
            @endif
        </x-card>

        <x-card title="Player">
            <x-slot:actions><a href="{{ route('admin.players.show', $pl) }}" class="text-xs font-semibold text-pitch-600">Full profile →</a></x-slot:actions>
            <div class="flex items-center gap-4">
                @if($photo = $pl->document('photo'))<img src="{{ route('admin.documents.file', $photo) }}" class="h-16 w-16 rounded-xl object-cover" alt="">@endif
                <div class="grid flex-1 grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                    <div><div class="text-xs text-slate-500">Name</div><b>{{ $pl->full_name }}</b></div>
                    <div><div class="text-xs text-slate-500">Mobile</div>{{ $pl->user->mobile }}</div>
                    <div><div class="text-xs text-slate-500">Village</div>{{ $pl->village }}</div>
                    <div><div class="text-xs text-slate-500">Registration</div><x-badge :status="$reg->status" /> @if($reg->registration_no)<div class="font-mono text-xs">{{ $reg->registration_no }}</div>@endif</div>
                </div>
            </div>
        </x-card>

        @if($others->count())
        <x-card title="Previous attempts by this player">
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach($others as $o)
                    <li class="flex items-center justify-between py-2"><span>#{{ $o->id }} · {{ $o->transaction_id }} · ₹{{ number_format($o->amount,2) }} · {{ $o->created_at->format('d M') }}</span><span class="flex items-center gap-2"><x-badge :status="$o->status" /><a href="{{ route('admin.payments.show', $o) }}" class="text-xs text-pitch-600">Open</a></span></li>
                @endforeach
            </ul>
        </x-card>
        @endif
    </div>
</div>
@endsection
