<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Tournament;
use App\Services\FileStorage;
use App\Services\RegistrationWorkflow;
use App\Support\AdminContext;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(private RegistrationWorkflow $workflow) {}

    private function query(Request $request)
    {
        $q = Payment::with('registration.player.user', 'verifier')
            ->whereHas('registration', fn ($r) => $r->where('tournament_id', AdminContext::tournamentId()));

        if ($st = $request->query('status')) {
            $q->where('status', $st);
        }
        if ($m = $request->query('method')) {
            $q->where('method', $m);
        }
        if ($from = $request->query('from')) {
            $q->whereDate('payment_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->whereDate('payment_date', '<=', $to);
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('transaction_id', 'like', '%'.$s.'%')
                    ->orWhereHas('registration.player', fn ($p) => $p->where('first_name', 'like', '%'.$s.'%')->orWhere('surname', 'like', '%'.$s.'%')->orWhere('village', 'like', '%'.$s.'%'))
                    ->orWhereHas('registration.player.user', fn ($u) => $u->where('mobile', 'like', '%'.$s.'%'));
            });
        }

        return $q->latest('id');
    }

    public function index(Request $request)
    {
        $payments = $this->query($request)->paginate(20)->withQueryString();
        $counts = Payment::whereHas('registration', fn ($r) => $r->where('tournament_id', AdminContext::tournamentId()))
            ->select('status', DB::raw('count(*) c'))->groupBy('status')->pluck('c', 'status');

        return view('admin.payments.index', compact('payments', 'counts'));
    }

    public function show(Payment $payment)
    {
        $payment->load('registration.player.user', 'registration.player.documents', 'registration.tournament', 'verifier');
        $this->workflow->markUnderVerification($payment);
        $payment->refresh();
        $others = Payment::where('registration_id', $payment->registration_id)->where('id', '!=', $payment->id)->latest('id')->get();
        $dupes = $payment->transaction_id
            ? Payment::with('registration.player')->where('transaction_id', $payment->transaction_id)->where('id', '!=', $payment->id)->get()
            : collect();

        return view('admin.payments.show', compact('payment', 'others', 'dupes'));
    }

    public function approve(Request $request, Payment $payment)
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:255']]);
        $this->workflow->approvePayment($payment->load('registration.player.user', 'registration.tournament'), $data['remarks'] ?? null);

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment approved. Registration is now confirmed.');
    }

    public function reject(Request $request, Payment $payment)
    {
        $data = $request->validate(['remarks' => ['required', 'string', 'min:5', 'max:500']]);
        $this->workflow->rejectPayment($payment->load('registration.player.user', 'registration.tournament'), $data['remarks']);

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment rejected. The player can resubmit proof.');
    }

    public function screenshot(Payment $payment, string $mode = 'view')
    {
        abort_unless($payment->screenshot_path && Storage::disk(FileStorage::DISK)->exists($payment->screenshot_path), 404);

        return $mode === 'download'
            ? Storage::disk(FileStorage::DISK)->download($payment->screenshot_path, 'payment-'.$payment->id.'.'.pathinfo($payment->screenshot_path, PATHINFO_EXTENSION))
            : Storage::disk(FileStorage::DISK)->response($payment->screenshot_path, null, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->cursor()->map(fn (Payment $p) => [
            $p->id, $p->registration->registration_no, $p->registration->player->full_name, $p->registration->player->user->mobile,
            $p->registration->player->village, strtoupper($p->method), $p->expected_amount, $p->amount, $p->transaction_id,
            $p->payment_date?->toDateString(), $p->statusLabel(), $p->admin_remarks, $p->verifier?->name,
            $p->verified_at?->toDateTimeString(), $p->created_at?->toDateTimeString(),
        ]);
        ActivityLog::log('payments.export', 'Exported payments');

        return CsvExport::stream('payments-'.now()->format('Ymd-His').'.csv', [
            'Payment ID', 'Registration ID', 'Player', 'Mobile', 'Village', 'Method', 'Expected', 'Paid', 'UTR / Txn ID',
            'Payment Date', 'Status', 'Remarks', 'Verified By', 'Verified At', 'Submitted At',
        ], $rows);
    }

    public function reports(Request $request)
    {
        $tid = AdminContext::tournamentId();

        $tournaments = Tournament::orderByDesc('id')->get()->map(function (Tournament $t) {
            $regs = Registration::where('tournament_id', $t->id);

            return [
                'tournament' => $t,
                'registrations' => (clone $regs)->count(),
                'expected' => (float) (clone $regs)->where('status', '!=', 'rejected')->sum('fee_amount'),
                'received' => (float) Payment::whereHas('registration', fn ($q) => $q->where('tournament_id', $t->id))->where('status', 'payment_approved')->sum('amount'),
                'pending' => (clone $regs)->whereIn('payment_status', ['proof_submitted', 'under_verification'])->count(),
                'unpaid' => (clone $regs)->where('payment_status', 'payment_pending')->count(),
                'rejected' => Payment::whereHas('registration', fn ($q) => $q->where('tournament_id', $t->id))->where('status', 'payment_rejected')->count(),
            ];
        });

        $daily = Payment::whereHas('registration', fn ($q) => $q->where('tournament_id', $tid))
            ->where('status', 'payment_approved')
            ->select(DB::raw('DATE(payment_date) as d'), DB::raw('count(*) as c'), DB::raw('sum(amount) as total'))
            ->groupBy('d')->orderByDesc('d')->limit(60)->get();

        return view('admin.payments.reports', compact('tournaments', 'daily'));
    }
}
