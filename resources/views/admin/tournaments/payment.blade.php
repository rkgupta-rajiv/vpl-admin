@extends('layouts.admin')
@section('title', 'Payment Settings · '.$tournament->title)
@section('content')
<form method="POST" action="{{ route('admin.tournaments.payment.update', $tournament) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
    @csrf @method('PUT')
    <x-card title="Fee & UPI details" class="lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Registration fee (₹) *</label><input name="registration_fee" type="number" step="1" min="0" value="{{ old('registration_fee', (float) $setting->registration_fee) }}" class="field text-lg font-bold" required>
                <p class="mt-1 text-xs text-slate-500">Changing the fee only affects <b>new</b> registrations; existing ones keep their fee.</p></div>
            <label class="flex items-center gap-2 text-sm sm:mt-6"><input type="hidden" name="online_payment_enabled" value="0"><input type="checkbox" name="online_payment_enabled" value="1" @checked(old('online_payment_enabled', $setting->online_payment_enabled)) class="rounded"> Enable online QR / UPI payment</label>
            <div><label class="label">UPI ID</label><input name="upi_id" value="{{ old('upi_id', $setting->upi_id) }}" class="field" placeholder="vplcommittee@okicici"></div>
            <div><label class="label">Payee name</label><input name="payee_name" value="{{ old('payee_name', $setting->payee_name) }}" class="field" placeholder="VPL Committee"></div>
            <div class="sm:col-span-2"><label class="label">Payment instructions (shown to players)</label><textarea name="instructions" rows="5" class="field" placeholder="1. Scan the QR with any UPI app&#10;2. Pay the exact amount&#10;3. Note down the UTR / transaction ID&#10;4. Upload the screenshot here">{{ old('instructions', $setting->instructions) }}</textarea></div>
        </div>
    </x-card>
    <x-card title="UPI QR code">
        @if($setting->qr_code_path)
            <img src="{{ $setting->qrCodeUrl() }}" class="mx-auto mb-3 w-52 rounded-xl border border-slate-200 p-2" alt="QR">
            <label class="mb-3 flex items-center gap-2 text-sm text-rose-600"><input type="hidden" name="remove_qr" value="0"><input type="checkbox" name="remove_qr" value="1" class="rounded"> Remove current QR</label>
        @else
            <div class="mb-3 grid h-40 place-items-center rounded-xl border-2 border-dashed border-slate-200 text-sm text-slate-400">No QR uploaded</div>
        @endif
        <label class="label">{{ $setting->qr_code_path ? 'Replace' : 'Upload' }} QR image (PNG/JPG, ≤2 MB)</label>
        <input type="file" name="qr_code" accept="image/png,image/jpeg,image/webp" class="field" onchange="if(this.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('qrprev').src=e.target.result;document.getElementById('qrprev').classList.remove('hidden')};r.readAsDataURL(this.files[0])}">
        <img id="qrprev" class="mx-auto mt-3 hidden w-40 rounded-lg" alt="">
        <p class="mt-3 text-xs text-slate-500">This QR appears automatically on every player's payment page.</p>
    </x-card>
    <div class="lg:col-span-3"><button class="btn btn-primary">Save payment settings</button> <a href="{{ route('admin.tournaments.index') }}" class="btn btn-light">Back</a></div>
</form>
@endsection
