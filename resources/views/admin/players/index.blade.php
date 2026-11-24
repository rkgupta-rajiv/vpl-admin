@extends('layouts.admin')
@section('title', 'Players')
@php use App\Models\Player; use App\Models\Registration; @endphp
@section('content')
<x-card>
    <form method="GET" class="grid gap-3 md:grid-cols-4 xl:grid-cols-8">
        <input name="q" value="{{ request('q') }}" placeholder="Search name, mobile, village, Reg ID…" class="field md:col-span-2">
        <select name="gender" class="field"><option value="">All genders</option>@foreach(Player::GENDERS as $k=>$v)<option value="{{ $k }}" @selected(request('gender')===$k)>{{ $v }}</option>@endforeach</select>
        <select name="playing_role" class="field"><option value="">All roles</option>@foreach(Player::ROLES as $k=>$v)<option value="{{ $k }}" @selected(request('playing_role')===$k)>{{ $v }}</option>@endforeach</select>
        <select name="village" class="field"><option value="">All villages</option>@foreach($villages as $v)<option @selected(request('village')===$v)>{{ $v }}</option>@endforeach</select>
        <select name="status" class="field">
            <option value="">All registrations</option>
            @foreach(Registration::STATUSES as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach
            <option value="completed" @selected(request('status')==='completed')>Completed (paid)</option>
            <option value="not_submitted" @selected(request('status')==='not_submitted')>Account only – not submitted</option>
        </select>
        <select name="payment_status" class="field"><option value="">All payments</option>@foreach(Registration::PAYMENT_STATUSES as $k=>$v)<option value="{{ $k }}" @selected(request('payment_status')===$k)>{{ $v }}</option>@endforeach</select>
        <div class="flex gap-2"><button class="btn btn-primary flex-1 justify-center">Filter</button><a href="{{ route('admin.players.index') }}" class="btn btn-light">Reset</a></div>
    </form>
</x-card>

<x-card :title="number_format($players->total()).' players'">
    <x-slot:actions>
        <a href="{{ route('admin.players.export', request()->query()) }}" class="btn btn-gold !py-1.5 text-xs">⬇ Export Excel/CSV</a>
    </x-slot:actions>
    <div class="-m-5 overflow-x-auto">
        <table class="data w-full">
            <thead><tr><th>Player</th><th>Mobile</th><th>Village</th><th>Role</th><th>Jersey</th><th>Docs</th><th>Registration</th><th>Payment</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            @forelse($players as $p)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @php $photo = $p->documents->firstWhere('type','photo'); @endphp
                            @if($photo)<img src="{{ route('admin.documents.file', $photo) }}" class="h-9 w-9 rounded-full object-cover ring-2 ring-white" loading="lazy" alt="">
                            @else<div class="grid h-9 w-9 place-items-center rounded-full bg-pitch-100 text-xs font-bold text-pitch-700">{{ mb_substr($p->first_name,0,1) }}{{ mb_substr($p->surname,0,1) }}</div>@endif
                            <div><a href="{{ route('admin.players.show', $p) }}" class="font-semibold hover:text-pitch-600">{{ $p->full_name }}</a>
                                <div class="text-xs text-slate-500">{{ $p->registration_no ?? Player::label(Player::GENDERS, $p->gender).' · '.$p->age.' yrs' }}</div></div>
                        </div>
                    </td>
                    <td>{{ $p->mobile }}</td>
                    <td>{{ $p->village }}</td>
                    <td>{{ Player::label(Player::ROLES, $p->playing_role) }}</td>
                    <td>{{ $p->jersey_number ? '#'.$p->jersey_number.' '.$p->jersey_name : '—' }}</td>
                    <td><x-badge :status="$p->documentStatus()" /></td>
                    <td>@if($p->reg_id)<x-badge :status="$p->completed_at ? 'completed' : $p->reg_status" :label="$p->completed_at ? 'Completed' : null" />@else<x-badge status="not_submitted" label="Not submitted" />@endif</td>
                    <td>@if($p->payment_status)<x-badge :status="$p->payment_status" />@endif</td>
                    <td class="whitespace-nowrap text-right">
                        <a href="{{ route('admin.players.show', $p) }}" class="btn btn-light !px-2.5 !py-1 text-xs">View</a>
                        <a href="{{ route('admin.players.edit', $p) }}" class="btn btn-light !px-2.5 !py-1 text-xs">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-10 text-center text-slate-500">No players match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $players->links() }}</div>
</x-card>
@endsection
