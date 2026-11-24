@props(['status', 'label' => null])
@php
    $map = [
        'pending' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'verified' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'payment_approved' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'completed' => 'bg-emerald-600 text-white ring-emerald-600',
        'rejected' => 'bg-red-100 text-red-700 ring-red-200',
        'payment_rejected' => 'bg-red-100 text-red-700 ring-red-200',
        'proof_submitted' => 'bg-sky-100 text-sky-800 ring-sky-200',
        'under_verification' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
        'payment_pending' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'incomplete' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'open' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'closed' => 'bg-slate-200 text-slate-700 ring-slate-300',
        'not_submitted' => 'bg-slate-100 text-slate-500 ring-slate-200',
    ];
    $text = $label ?? \Illuminate\Support\Str::of($status ?? 'not_submitted')->replace('payment_', 'payment ')->replace('_', ' ')->title();
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset whitespace-nowrap '.($map[$status ?? 'not_submitted'] ?? 'bg-slate-100 text-slate-700 ring-slate-200')]) }}>{{ $text }}</span>
