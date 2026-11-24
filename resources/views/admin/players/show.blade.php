@extends('layouts.admin')
@section('title', $player->full_name)
@php use App\Models\Player; $r = $registration; $photo = $player->document('photo'); @endphp
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="h-20 bg-gradient-to-r from-pitch-900 to-pitch-600"></div>
            <div class="-mt-10 flex flex-wrap items-end gap-4 px-6 pb-5">
                @if($photo)<a href="{{ route('admin.documents.file', $photo) }}" target="_blank"><img src="{{ route('admin.documents.file', $photo) }}" class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white" alt=""></a>
                @else<div class="grid h-24 w-24 place-items-center rounded-2xl bg-pitch-100 text-2xl font-bold text-pitch-700 ring-4 ring-white">{{ mb_substr($player->first_name,0,1) }}</div>@endif
                <div class="min-w-[220px] flex-1 pt-12">
                    <h2 class="text-xl font-extrabold">{{ $player->full_name }}</h2>
                    <div class="text-sm text-slate-500">{{ Player::label(Player::ROLES, $player->playing_role) }} · {{ $player->village }} · {{ $player->user->is_active ? 'Active account' : 'Deactivated' }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($r)
                        <x-badge :status="$r->isCompleted() ? 'completed' : $r->status" :label="$r->isCompleted() ? 'Registration Completed' : 'Registration '.$r->statusLabel()" class="!text-sm" />
                        <x-badge :status="$r->payment_status" class="!text-sm" />
                    @else
                        <x-badge status="not_submitted" label="Not registered for this tournament" />
                    @endif
                </div>
            </div>
            @if($r?->registration_no)
                <div class="border-t border-slate-100 bg-emerald-50 px-6 py-3 text-sm">Registration ID: <span class="font-mono font-bold text-emerald-800">{{ $r->registration_no }}</span> · confirmed {{ $r->completed_at?->format('d M Y, h:i A') }}</div>
            @endif
            @if($r?->status === 'rejected')
                <div class="border-t border-rose-100 bg-rose-50 px-6 py-3 text-sm text-rose-700"><b>Rejection remarks:</b> {{ $r->rejection_remarks }}</div>
            @endif
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <x-card title="Personal information">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    @foreach(['First name'=>$player->first_name,'Surname'=>$player->surname,'Age'=>$player->age,'Gender'=>Player::label(Player::GENDERS,$player->gender),'Mobile'=>$player->user->mobile,'Email'=>$player->user->email,'Village'=>$player->village,'Joined'=>$player->created_at->format('d M Y')] as $k=>$v)
                        <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="break-words font-medium">{{ $v ?? '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-card>
            <x-card title="Cricket information">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    @foreach(['Playing role'=>Player::label(Player::ROLES,$player->playing_role),'Batting'=>Player::label(Player::BATTING,$player->batting_style),'Bowling'=>Player::label(Player::BOWLING,$player->bowling_style),'T-shirt'=>$player->tshirt_size,'Jersey number'=>$r?->jersey_number,'Jersey name'=>$r?->jersey_name] as $k=>$v)
                        <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-medium">{{ $v ?? '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-card>
        </div>

        <x-card title="Documents">
            <x-slot:actions><x-badge :status="$player->documentStatus()" /></x-slot:actions>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach(Player::DOC_TYPES as $type => $label)
                    @php $d = $player->document($type); @endphp
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold">{{ $label }}</span>@if($d)<x-badge :status="$d->status" />@endif</div>
                        @if($d)
                            <a href="{{ route('admin.documents.file', $d) }}" target="_blank" class="block overflow-hidden rounded-lg bg-slate-100">
                                <img src="{{ route('admin.documents.file', $d) }}" class="h-36 w-full object-contain" alt="{{ $label }}" loading="lazy">
                            </a>
                            @if($d->remarks)<p class="mt-2 text-xs text-rose-600">{{ $d->remarks }}</p>@endif
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <a href="{{ route('admin.documents.file', [$d, 'download']) }}" class="btn btn-light !px-2 !py-1 text-xs">⬇ Download</a>
                                @if($d->status !== 'verified')
                                    <form method="POST" action="{{ route('admin.documents.verify', $d) }}">@csrf<input type="hidden" name="status" value="verified"><button class="btn btn-primary !px-2 !py-1 text-xs">✓ Verify</button></form>
                                @endif
                                @if($d->status !== 'rejected')
                                    <button class="btn btn-danger !px-2 !py-1 text-xs" onclick="openModal({action:'{{ route('admin.documents.verify', $d) }}',title:'Reject {{ $label }}',text:'The player will be asked to upload it again.',required:true,danger:true,button:'Reject document',placeholder:'Reason, e.g. image is blurry',extra:'<input type=hidden name=status value=rejected>'})">✕ Reject</button>
                                @endif
                            </div>
                        @else
                            <div class="grid h-36 place-items-center rounded-lg border-2 border-dashed border-slate-200 text-xs text-slate-400">Not uploaded</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>

        @if($r)
        <x-card title="Payments">
            <div class="-m-5 overflow-x-auto">
                <table class="data w-full">
                    <thead><tr><th>#</th><th>Method</th><th>Amount</th><th>UTR / Ref</th><th>Date</th><th>Status</th><th>Remarks</th><th></th></tr></thead>
                    <tbody>
                    @forelse($r->payments as $p)
                        <tr>
                            <td>{{ $p->id }}</td><td class="uppercase">{{ $p->method }}</td>
                            <td>₹{{ number_format($p->amount, 2) }} @unless($p->amountMatches())<span class="text-xs font-semibold text-rose-600">(expected ₹{{ number_format($p->expected_amount,2) }})</span>@endunless</td>
                            <td class="font-mono text-xs">{{ $p->transaction_id ?? '—' }}</td><td>{{ $p->payment_date?->format('d M Y') }}</td>
                            <td><x-badge :status="$p->status" /></td><td class="text-xs text-slate-500">{{ $p->admin_remarks }}</td>
                            <td><a href="{{ route('admin.payments.show', $p) }}" class="text-xs font-semibold text-pitch-600">Open →</a></td>
                        </tr>
                    @empty <tr><td colspan="8" class="text-center text-slate-500">No payment submitted yet. Fee for this registration: ₹{{ number_format($r->fee_amount, 2) }}</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif
    </div>

    {{-- Right column: actions & history --}}
    <div class="space-y-6">
        <x-card title="Actions">
            <div class="space-y-2">
                @if($r)
                    @if($r->status !== 'approved')
                        <form method="POST" action="{{ route('admin.registrations.approve', $r) }}">@csrf<button class="btn btn-primary w-full justify-center">✓ Approve registration</button></form>
                    @endif
                    @if($r->status !== 'rejected' && ! $r->isCompleted())
                        <button class="btn btn-danger w-full justify-center" onclick="openModal({action:'{{ route('admin.registrations.reject', $r) }}',title:'Reject registration',text:'The player will see these remarks in the app and can correct & resubmit.',required:true,danger:true,button:'Reject registration',placeholder:'e.g. Aadhaar photo is not readable'})">✕ Reject with remarks</button>
                    @endif
                    @if(! $r->isCompleted() && $r->status !== 'rejected')
                        <button class="btn btn-light w-full justify-center" onclick="openModal({action:'{{ route('admin.registrations.offline-payment', $r) }}',title:'Record cash / offline payment',text:'This marks the payment approved and confirms the registration.',button:'Record payment',extra:'<div class=\'grid grid-cols-2 gap-3 mb-1\'><div><label class=label>Amount (₹)</label><input name=amount type=number step=0.01 required value=\'{{ $r->fee_amount }}\' class=field></div><div><label class=label>Payment date</label><input name=payment_date type=date required value=\'{{ now()->toDateString() }}\' class=field></div><div class=col-span-2><label class=label>Receipt / reference (optional)</label><input name=reference class=field></div></div>'})">₹ Record offline payment</button>
                    @endif
                @endif
                <a href="{{ route('admin.players.edit', $player) }}" class="btn btn-light w-full justify-center">✎ Edit player details</a>
                <form method="POST" action="{{ route('admin.players.destroy', $player) }}" onsubmit="return confirm('Permanently delete {{ $player->full_name }} with all documents and payments? This cannot be undone.')">
                    @csrf @method('DELETE')<button class="btn w-full justify-center text-rose-600 hover:bg-rose-50">🗑 Delete player</button>
                </form>
            </div>
        </x-card>

        @if($r)
        <x-card title="Registration & approval history">
            <ol class="relative space-y-4 border-l-2 border-slate-100 pl-5">
                @forelse($r->histories as $h)
                    <li>
                        <span class="absolute -left-[7px] mt-1 h-3 w-3 rounded-full {{ str_contains($h->to_status,'reject') ? 'bg-rose-500' : (str_contains($h->to_status,'approv') || $h->to_status==='verified' ? 'bg-emerald-500' : 'bg-amber-400') }}"></span>
                        <div class="text-xs uppercase tracking-wide text-slate-400">{{ $h->type }} · {{ $h->created_at?->format('d M Y, h:i A') }}</div>
                        <div class="text-sm"><span class="text-slate-500">{{ $h->from_status ? str_replace('_',' ',$h->from_status).' → ' : '' }}</span><b>{{ str_replace('_',' ',$h->to_status) }}</b></div>
                        @if($h->remarks)<div class="text-xs text-slate-600">{{ $h->remarks }}</div>@endif
                        <div class="text-[11px] text-slate-400">by {{ $h->actor?->name ?? 'Player' }}</div>
                    </li>
                @empty <li class="text-sm text-slate-500">No history yet.</li> @endforelse
            </ol>
        </x-card>
        @endif

        @if($player->registrations->count() > 1)
        <x-card title="Other seasons">
            <ul class="space-y-2 text-sm">
                @foreach($player->registrations as $reg)
                    <li class="flex justify-between"><span>{{ $reg->tournament->title }}</span><x-badge :status="$reg->status" /></li>
                @endforeach
            </ul>
        </x-card>
        @endif
    </div>
</div>
@endsection
