@extends('layouts.admin')
@section('title', $tournament->exists ? 'Edit '.$tournament->name : 'New tournament')
@section('content')
<form method="POST" action="{{ $tournament->exists ? route('admin.tournaments.update', $tournament) : route('admin.tournaments.store') }}" class="space-y-6">
    @csrf @if($tournament->exists) @method('PUT') @endif
    <x-card title="Tournament details">
        <div class="grid gap-4 md:grid-cols-4">
            <div class="md:col-span-2"><label class="label">Tournament name *</label><input name="name" value="{{ old('name', $tournament->name) }}" class="field" required placeholder="Vankar Premier League"></div>
            <div><label class="label">Season *</label><input name="season" value="{{ old('season', $tournament->season) }}" class="field" required placeholder="Season 2"></div>
            <div><label class="label">Code (Registration ID prefix) *</label><input name="code" value="{{ old('code', $tournament->code) }}" class="field uppercase" required maxlength="12" placeholder="VPL2"></div>
            <div><label class="label">Registration start date</label><input type="date" name="registration_start_date" value="{{ old('registration_start_date', $tournament->registration_start_date?->toDateString()) }}" class="field"></div>
            <div><label class="label">Registration end date</label><input type="date" name="registration_end_date" value="{{ old('registration_end_date', $tournament->registration_end_date?->toDateString()) }}" class="field"></div>
            <div><label class="label">Registration status *</label><select name="registration_status" class="field"><option value="open" @selected(old('registration_status',$tournament->registration_status)==='open')>Open</option><option value="closed" @selected(old('registration_status',$tournament->registration_status)==='closed')>Closed</option></select></div>
            <label class="mt-6 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tournament->is_active)) class="rounded"> Active tournament in the app</label>
            <div class="md:col-span-4"><label class="label">Description</label><textarea name="description" rows="3" class="field">{{ old('description', $tournament->description) }}</textarea></div>
            <div class="md:col-span-2"><label class="label">Tournament rules</label><textarea name="rules" rows="8" class="field" placeholder="One rule per line">{{ old('rules', $tournament->rules) }}</textarea></div>
            <div class="md:col-span-2"><label class="label">Registration instructions</label><textarea name="instructions" rows="8" class="field" placeholder="One instruction per line">{{ old('instructions', $tournament->instructions) }}</textarea></div>
        </div>
    </x-card>
    <div class="flex gap-2"><button class="btn btn-primary">{{ $tournament->exists ? 'Save tournament' : 'Create tournament' }}</button><a href="{{ route('admin.tournaments.index') }}" class="btn btn-light">Cancel</a></div>
</form>
@endsection
