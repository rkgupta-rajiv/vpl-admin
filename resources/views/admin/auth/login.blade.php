<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login · {{ \App\Models\Setting::get('app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:Inter,system-ui,sans-serif}.field{display:block;width:100%;border-radius:.7rem;border:1px solid #cbd5e1;padding:.7rem .9rem;font-size:.9rem}.field:focus{outline:none;border-color:#13704a;box-shadow:0 0 0 3px rgba(19,112,74,.15)}</style>
</head>
<body class="min-h-screen bg-[#062419]">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden overflow-hidden lg:block">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0f5a3c] via-[#0a3a28] to-[#062419]"></div>
        <div class="absolute -right-24 top-1/2 h-[520px] w-[520px] -translate-y-1/2 rounded-full border-[18px] border-white/5"></div>
        <div class="absolute left-1/2 top-0 h-full w-40 -translate-x-1/2 bg-[#c9a66b]/10"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-3"><div class="h-10 w-10 rounded-full bg-red-600 ring-4 ring-white/10"></div><span class="font-bold">{{ \App\Models\Setting::get('app_name') }}</span></div>
            <div>
                <h1 class="text-4xl font-extrabold leading-tight">Every player.<br>Every payment.<br><span class="text-amber-400">One dashboard.</span></h1>
                <p class="mt-4 max-w-md text-white/70">Verify registrations, Aadhaar documents and UPI payments for your tournament from a single, secure place.</p>
            </div>
            <p class="text-xs text-white/40">{{ \App\Models\Setting::get('organizer_name') }}</p>
        </div>
    </div>
    <div class="flex items-center justify-center bg-slate-50 p-6">
        <form method="POST" action="{{ route('admin.login.submit') }}" class="w-full max-w-sm space-y-5">
            @csrf
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Main Admin Login</h2>
                <p class="text-sm text-slate-500">Sign in to manage registrations.</p>
            </div>
            @if($errors->any())<div class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Email</label><input name="email" type="email" value="{{ old('email') }}" required autofocus class="field"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Password</label><input name="password" type="password" required class="field"></div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded"> Remember me</label>
            <button class="w-full rounded-xl bg-[#13704a] py-3 font-semibold text-white hover:bg-[#0f5a3c]">Sign in</button>
            <p class="text-center text-xs text-slate-400">Forgot password? Run <code class="rounded bg-slate-200 px-1">php artisan vpl:admin you@email --password=New@1234</code> on the server.</p>
        </form>
    </div>
</div>
</body>
</html>
