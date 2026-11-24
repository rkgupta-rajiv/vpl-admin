@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
        <x-card title="Application settings">
            <div class="space-y-4">
                <div><label class="label">App name</label><input name="app_name" value="{{ old('app_name', $s['app_name']) }}" class="field" required></div>
                <div><label class="label">Organiser name</label><input name="organizer_name" value="{{ old('organizer_name', $s['organizer_name']) }}" class="field"></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Support phone</label><input name="support_phone" value="{{ old('support_phone', $s['support_phone']) }}" class="field"></div>
                    <div><label class="label">Support email</label><input name="support_email" type="email" value="{{ old('support_email', $s['support_email']) }}" class="field"></div>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_notifications" value="1" @checked($s['email_notifications'] === '1') class="rounded"> Email players on approval / rejection (in-app notifications are always sent)</label>
                <button class="btn btn-primary">Save settings</button>
            </div>
        </x-card>
    </form>
    <form method="POST" action="{{ route('admin.settings.password') }}">@csrf @method('PUT')
        <x-card title="Change admin password">
            <div class="space-y-4">
                <div><label class="label">Current password</label><input name="current_password" type="password" class="field" required></div>
                <div><label class="label">New password</label><input name="password" type="password" class="field" required autocomplete="new-password"></div>
                <div><label class="label">Confirm new password</label><input name="password_confirmation" type="password" class="field" required></div>
                <button class="btn btn-primary">Change password</button>
            </div>
        </x-card>
    </form>
</div>
@endsection
