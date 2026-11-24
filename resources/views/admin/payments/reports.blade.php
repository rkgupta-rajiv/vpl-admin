@extends('layouts.admin')
@section('title', 'Payment Reports')
@php $inr = fn($v) => '₹'.number_format($v, 2); @endphp
@section('content')
<x-card title="Tournament-wise payment report">
    <x-slot:actions><a href="{{ route('admin.payments.export') }}" class="btn btn-gold !py-1.5 text-xs">⬇ Export payments (current tournament)</a></x-slot:actions>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>Tournament</th><th>Registrations</th><th>Expected</th><th>Received</th><th>Outstanding</th><th>Awaiting verification</th><th>Not paid</th><th>Rejected proofs</th></tr></thead>
            <tbody>
            @foreach($tournaments as $row)
                <tr>
                    <td class="font-semibold">{{ $row['tournament']->title }} @if($row['tournament']->is_active)<span class="text-amber-500">★</span>@endif</td>
                    <td>{{ $row['registrations'] }}</td><td>{{ $inr($row['expected']) }}</td>
                    <td class="font-semibold text-emerald-700">{{ $inr($row['received']) }}</td>
                    <td class="text-rose-600">{{ $inr(max(0, $row['expected'] - $row['received'])) }}</td>
                    <td>{{ $row['pending'] }}</td><td>{{ $row['unpaid'] }}</td><td>{{ $row['rejected'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
<x-card title="Date-wise collection (approved payments, selected tournament)">
    @php $max = max(1, (float) $daily->max('total')); @endphp
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>Payment date</th><th>Payments</th><th>Amount</th><th class="w-1/2"></th></tr></thead>
            <tbody>
            @forelse($daily as $d)
                <tr><td>{{ \Carbon\Carbon::parse($d->d)->format('d M Y, D') }}</td><td>{{ $d->c }}</td><td class="font-semibold">{{ $inr($d->total) }}</td>
                    <td><div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-pitch-600" style="width: {{ $d->total / $max * 100 }}%"></div></div></td></tr>
            @empty <tr><td colspan="4" class="text-center text-slate-500">No approved payments yet.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-6 text-xs text-slate-500">For player-wise payment details use <a class="font-semibold text-pitch-600" href="{{ route('admin.payments.index') }}">Payment Verification</a> (filter + CSV export).</p>
</x-card>
@endsection
