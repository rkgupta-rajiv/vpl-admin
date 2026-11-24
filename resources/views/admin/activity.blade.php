@extends('layouts.admin')
@section('title', 'Activity Log')
@section('content')
<x-card>
    <form method="GET" class="flex flex-wrap gap-2">
        <select name="action" class="field !w-auto"><option value="">All actions</option>@foreach($actions as $a)<option @selected(request('action')===$a)>{{ $a }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>
</x-card>
<x-card>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $l)
                <tr><td class="whitespace-nowrap text-slate-500">{{ $l->created_at?->format('d M Y, h:i A') }}</td><td>{{ $l->user?->name ?? 'System' }}</td><td><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $l->action }}</code></td><td>{{ $l->description }}</td><td class="text-xs text-slate-400">{{ $l->ip_address }}</td></tr>
            @empty <tr><td colspan="5" class="text-center text-slate-500">No activity yet.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $logs->links() }}</div>
</x-card>
@endsection
