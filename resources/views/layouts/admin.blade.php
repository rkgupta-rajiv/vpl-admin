<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ \App\Models\Setting::get('app_name') }} Admin</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak]{display:none}
        .field{display:block;width:100%;border-radius:.6rem;border:1px solid #cbd5e1;padding:.55rem .75rem;font-size:.875rem;background:#fff}
        .field:focus{outline:none;border-color:#13704a;box-shadow:0 0 0 3px rgba(19,112,74,.15)}
        .btn{display:inline-flex;align-items:center;gap:.4rem;border-radius:.6rem;padding:.5rem .9rem;font-size:.85rem;font-weight:600;transition:.15s}
        .btn-primary{background:#13704a;color:#fff}.btn-primary:hover{background:#0f5a3c}
        .btn-gold{background:#f5b301;color:#3b2a00}.btn-gold:hover{background:#e0a400}
        .btn-danger{background:#e11d48;color:#fff}.btn-danger:hover{background:#be123c}
        .btn-light{background:#fff;color:#334155;border:1px solid #cbd5e1}.btn-light:hover{background:#f8fafc}
        .label{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.3rem}
        table.data th{font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:600;text-align:left;padding:.6rem .75rem;background:#f8fafc;white-space:nowrap}
        table.data td{padding:.65rem .75rem;border-top:1px solid #f1f5f9;font-size:.85rem;vertical-align:middle}
        table.data tr:hover td{background:#fafcfb}
        .seam{background-image:repeating-linear-gradient(90deg,transparent 0 10px,rgba(255,255,255,.12) 10px 14px)}
    </style>
    @stack('head')
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
@php $ctx = \App\Support\AdminContext::tournament(); $allT = \App\Models\Tournament::orderByDesc('id')->get(['id','name','season','is_active']); @endphp
<div class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-pitch-950 text-pitch-100 transition-transform lg:static lg:translate-x-0">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
            <div class="grid h-9 w-9 place-items-center rounded-full bg-red-600 shadow-inner ring-2 ring-white/20"><span class="h-full w-0.5 bg-white/70"></span></div>
            <div>
                <div class="text-sm font-bold text-white">{{ \App\Models\Setting::get('app_name') }}</div>
                <div class="text-[11px] text-pitch-100/60">Main Admin</div>
            </div>
        </div>
        @php
            $nav = [
                ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
                ['Players', 'admin.players.*', 'M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0z', 'admin.players.index'],
                ['Payment Verification', 'admin.payments.index|admin.payments.show', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'admin.payments.index'],
                ['Payment Reports', 'admin.payments.reports', 'M9 17v-6m4 6V7m4 10v-3M4 21h16', 'admin.payments.reports'],
                ['Tournaments', 'admin.tournaments.*', 'M8 21h8m-4-4v4M7 4h10v5a5 5 0 01-10 0V4zM5 4H3v2a3 3 0 003 3M19 4h2v2a3 3 0 01-3 3', 'admin.tournaments.index'],
                ['Approval History', 'admin.history', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'admin.history'],
                ['Activity Log', 'admin.activity', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'admin.activity'],
                ['Settings', 'admin.settings', 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1.1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001 2.5 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.5-1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1.1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001.1-2.5 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.5-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'admin.settings'],
            ];
            $pending = $ctx ? \App\Models\Registration::where('tournament_id', $ctx->id)->whereIn('payment_status', ['proof_submitted','under_verification'])->count() : 0;
        @endphp
        <nav class="space-y-1 p-3">
            @foreach($nav as $n)
                @php $active = collect(explode('|', $n[1]))->contains(fn($p) => request()->routeIs($p)); @endphp
                <a href="{{ route($n[3] ?? $n[1]) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ $active ? 'bg-white/10 text-white' : 'text-pitch-100/70 hover:bg-white/5 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $n[2] }}"/></svg>
                    <span class="flex-1">{{ $n[0] }}</span>
                    @if($n[0] === 'Payment Verification' && $pending)<span class="rounded-full bg-amber-400 px-2 text-xs font-bold text-amber-950">{{ $pending }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="absolute inset-x-0 bottom-0 border-t border-white/10 p-4 text-xs text-pitch-100/60">
            Signed in as <span class="font-semibold text-white">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-2">@csrf<button class="text-amber-300 hover:text-amber-200">Log out →</button></form>
        </div>
    </aside>
    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden" onclick="toggleSidebar()"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-8">
            <button class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" onclick="toggleSidebar()" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="truncate text-lg font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-2">
                @if($allT->count())
                <form method="POST" action="{{ route('admin.switch-tournament') }}">
                    @csrf
                    <select name="tournament_id" onchange="this.form.submit()" class="field !w-auto !py-1.5 text-sm font-medium" title="Tournament you are viewing">
                        @foreach($allT as $tt)
                            <option value="{{ $tt->id }}" @selected($ctx?->id === $tt->id)>{{ $tt->name }} – {{ $tt->season }}{{ $tt->is_active ? ' ★' : '' }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>
        </header>

        <main class="flex-1 space-y-6 p-4 lg:p-8">
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">✓ {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @if(! $ctx)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">No tournament yet. <a class="font-semibold underline" href="{{ route('admin.tournaments.create') }}">Create your first tournament</a>.</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

{{-- Simple modal used for reject / remarks forms --}}
<dialog id="modal" class="w-full max-w-lg rounded-2xl p-0 backdrop:bg-black/40">
    <form method="POST" id="modal-form" class="space-y-4 p-6">
        @csrf
        <h3 id="modal-title" class="text-lg font-bold"></h3>
        <p id="modal-text" class="text-sm text-slate-600"></p>
        <div id="modal-extra"></div>
        <textarea name="remarks" id="modal-remarks" rows="3" class="field" placeholder="Remarks"></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-light" onclick="document.getElementById('modal').close()">Cancel</button>
            <button class="btn" id="modal-submit">Submit</button>
        </div>
    </form>
</dialog>

<script>
    function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('backdrop').classList.toggle('hidden'); }
    function openModal(opts){
        const m = document.getElementById('modal');
        document.getElementById('modal-form').action = opts.action;
        document.getElementById('modal-title').textContent = opts.title;
        document.getElementById('modal-text').textContent = opts.text || '';
        document.getElementById('modal-extra').innerHTML = opts.extra || '';
        const r = document.getElementById('modal-remarks');
        r.required = !!opts.required; r.value=''; r.placeholder = opts.placeholder || 'Remarks (optional)';
        r.style.display = opts.noRemarks ? 'none' : '';
        const b = document.getElementById('modal-submit');
        b.textContent = opts.button || 'Submit'; b.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
        m.showModal();
    }
</script>
@stack('scripts')
</body>
</html>
