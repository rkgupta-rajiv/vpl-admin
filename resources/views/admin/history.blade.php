@extends('layouts.admin')
@section('title', 'Registration & Approval History')
@section('content')
<div class="flex flex-wrap gap-2">
    @foreach(['' => 'All', 'registration' => 'Registration', 'payment' => 'Payment', 'document' => 'Documents'] as $k => $v)
        <a href="{{ route('admin.history', $k ? ['type' => $k] : []) }}" class="rounded-full px-4 py-1.5 text-sm font-semibold {{ request('type','') === $k ? 'bg-pitch-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">{{ $v }}</a>
    @endforeach
</div>
<x-card>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>When</th><th>Player</th><th>Type</th><th>Change</th><th>Remarks</th><th>By</th></tr></thead>
            <tbody>
            @forelse($items as $h)
                <tr>
                    <td class="whitespace-nowrap text-slate-500">{{ $h->created_at?->format('d M Y, h:i A') }}</td>
                    <td>@if($h->registration)<a href="{{ route('admin.players.show', $h->registration->player_id) }}" class="font-medium hover:text-pitch-600">{{ $h->registration->player?->full_name }}</a>@endif</td>
                    <td class="capitalize">{{ $h->type }}</td>
                    <td class="whitespace-nowrap">@if($h->from_status)<x-badge :status="$h->from_status" /> → @endif<x-badge :status="$h->to_status" /></td>
                    <td class="text-xs text-slate-600">{{ $h->remarks }}</td>
                    <td>{{ $h->actor?->name ?? 'Player' }}</td>
                </tr>
            @empty <tr><td colspan="6" class="text-center text-slate-500">No history yet.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
</x-card>
@endsection
