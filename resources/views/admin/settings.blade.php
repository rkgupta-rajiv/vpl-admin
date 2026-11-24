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
    <form method="POST" action="{{ route('admin.settings.qr') }}" enctype="multipart/form-data">@csrf
        <x-card title="Payment QR code{{ $tournament ? ' · '.$tournament->title : '' }}">
            @if(!$tournament)
                <p class="text-sm text-slate-500">Create a tournament first, then upload its QR here.</p>
            @else
                @if($payment->qr_code_path)
                    <img src="{{ $payment->qrCodeUrl() }}" class="mx-auto mb-3 w-52 rounded-xl border border-slate-200 p-2" alt="QR">
                    <label class="mb-3 flex items-center gap-2 text-sm text-rose-600"><input type="checkbox" name="remove_qr" value="1" class="rounded"> Remove current QR</label>
                @else
                    <div class="mb-3 grid h-40 place-items-center rounded-xl border-2 border-dashed border-slate-200 text-sm text-slate-400">No QR uploaded</div>
                @endif
                <label class="label">{{ $payment->qr_code_path ? 'Replace' : 'Upload' }} QR photo (PNG/JPG/WebP, up to 4 MB)</label>
                <input type="file" name="qr_code" accept="image/png,image/jpeg,image/webp" class="field">
                @error('qr_code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                <p class="mt-3 text-xs text-slate-500">Shown automatically on every player's payment screen in the app.</p>
                <button class="btn btn-primary mt-3">Save QR code</button>
            @endif
        </x-card>
    </form>
</div>
@endsection
