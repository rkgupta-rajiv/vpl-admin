@extends('layouts.admin')
@section('title', 'Payment Verification')
@php use App\Models\Payment; @endphp
@section('content')
<div class="flex flex-wrap gap-2">
    @php $tabs = ['' => 'All'] + Payment::STATUSES; @endphp
    @foreach($tabs as $k => $v)
        <a href="{{ route('admin.payments.index', array_merge(request()->except('page','status'), $k ? ['status'=>$k] : [])) }}"
           class="rounded-full px-4 py-1.5 text-sm font-semibold {{ request('status', '') === $k ? 'bg-pitch-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
            {{ $v }} <span class="opacity-70">{{ $k ? ($counts[$k] ?? 0) : $counts->sum() }}</span>
        </a>
    @endforeach
</div>
<x-card>
    <form method="GET" class="grid gap-3 md:grid-cols-6">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input name="q" value="{{ request('q') }}" placeholder="Search UTR, player, mobile, village" class="field md:col-span-2">
        <select name="method" class="field"><option value="">All methods</option><option value="upi" @selected(request('method')==='upi')>UPI</option><option value="cash" @selected(request('method')==='cash')>Cash / offline</option></select>
        <input type="date" name="from" value="{{ request('from') }}" class="field" title="Payment date from">
        <input type="date" name="to" value="{{ request('to') }}" class="field" title="Payment date to">
        <div class="flex gap-2"><button class="btn btn-primary flex-1 justify-center">Filter</button><a href="{{ route('admin.payments.export', request()->query()) }}" class="btn btn-gold">⬇ CSV</a></div>
    </form>
</x-card>
<x-card>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>Player</th><th>Village</th><th>Method</th><th>Amount</th><th>UTR / Txn ID</th><th>Payment date</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                @php $pl = $p->registration->player; @endphp
                <tr>
                    <td><a href="{{ route('admin.players.show', $pl) }}" class="font-semibold hover:text-pitch-600">{{ $pl->full_name }}</a><div class="text-xs text-slate-500">{{ $pl->user->mobile }}</div></td>
                    <td>{{ $pl->village }}</td>
                    <td class="uppercase">{{ $p->method }}</td>
                    <td>₹{{ number_format($p->amount, 2) }} @unless($p->amountMatches())<div class="text-xs font-semibold text-rose-600">≠ ₹{{ number_format($p->expected_amount,2) }}</div>@endunless</td>
                    <td class="font-mono text-xs">{{ $p->transaction_id ?? '—' }}</td>
                    <td>{{ $p->payment_date?->format('d M Y') }}</td>
                    <td class="text-slate-500">{{ $p->created_at->diffForHumans() }}</td>
                    <td><x-badge :status="$p->status" /></td>
                    <td><a href="{{ route('admin.payments.show', $p) }}" class="btn {{ in_array($p->status,['proof_submitted','under_verification']) ? 'btn-primary' : 'btn-light' }} !py-1 text-xs">{{ in_array($p->status,['proof_submitted','under_verification']) ? 'Verify' : 'View' }}</a></td>
                </tr>
            @empty <tr><td colspan="9" class="py-10 text-center text-slate-500">No payments found.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $payments->links() }}</div>
</x-card>
@endsection
