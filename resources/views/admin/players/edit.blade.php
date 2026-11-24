@extends('layouts.admin')
@section('title', 'Edit · '.$player->full_name)
@php use App\Models\Player; $sel = fn($name, $map, $val) => collect($map)->map(fn($v,$k) => '<option value="'.e($k).'" '.((string)old($name,$val)===(string)$k?'selected':'').'>'.e($v).'</option>')->implode(''); @endphp
@section('content')
<form method="POST" action="{{ route('admin.players.update', $player) }}" class="space-y-6">
    @csrf @method('PUT')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Personal information">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">First name *</label><input name="first_name" value="{{ old('first_name', $player->first_name) }}" class="field" required></div>
                <div><label class="label">Surname *</label><input name="surname" value="{{ old('surname', $player->surname) }}" class="field" required></div>
                <div><label class="label">Age *</label><input name="age" type="number" value="{{ old('age', $player->age) }}" class="field" required></div>
                <div><label class="label">Gender *</label><select name="gender" class="field">{!! $sel('gender', Player::GENDERS, $player->gender) !!}</select></div>
                <div><label class="label">Mobile *</label><input name="mobile" value="{{ old('mobile', $player->user->mobile) }}" class="field" required maxlength="10"></div>
                <div><label class="label">Email *</label><input name="email" type="email" value="{{ old('email', $player->user->email) }}" class="field" required></div>
                <div class="sm:col-span-2"><label class="label">Village *</label><input name="village" value="{{ old('village', $player->village) }}" class="field" required></div>
            </div>
        </x-card>
        <x-card title="Cricket information">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Playing role *</label><select name="playing_role" class="field">{!! $sel('playing_role', Player::ROLES, $player->playing_role) !!}</select></div>
                <div><label class="label">Batting style *</label><select name="batting_style" class="field">{!! $sel('batting_style', Player::BATTING, $player->batting_style) !!}</select></div>
                <div><label class="label">Bowling style *</label><select name="bowling_style" class="field">{!! $sel('bowling_style', Player::BOWLING, $player->bowling_style) !!}</select></div>
                <div><label class="label">T-shirt size *</label><select name="tshirt_size" class="field">{!! $sel('tshirt_size', Player::TSHIRT, $player->tshirt_size) !!}</select></div>
                <div><label class="label">Jersey number</label><input name="jersey_number" value="{{ old('jersey_number', $registration?->jersey_number) }}" class="field" maxlength="3" @disabled(!$registration)></div>
                <div><label class="label">Jersey name</label><input name="jersey_name" value="{{ old('jersey_name', $registration?->jersey_name) }}" class="field uppercase" maxlength="15" @disabled(!$registration)></div>
            </div>
            @unless($registration)<p class="mt-3 text-xs text-slate-500">Jersey details belong to a tournament registration — this player hasn't registered for the selected tournament.</p>@endunless
        </x-card>
    </div>
    <x-card title="Account">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $player->user->is_active)) class="rounded"> Account active (player can log in)</label>
            <div><label class="label">Set new password (optional)</label><input name="new_password" type="password" class="field" autocomplete="new-password" placeholder="Leave blank to keep current"></div>
        </div>
    </x-card>
    <div class="flex gap-2"><button class="btn btn-primary">Save changes</button><a href="{{ route('admin.players.show', $player) }}" class="btn btn-light">Cancel</a></div>
</form>
@endsection
