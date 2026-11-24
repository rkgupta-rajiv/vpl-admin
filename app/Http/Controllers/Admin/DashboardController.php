<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Tournament;
use App\Support\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $t = AdminContext::tournament();
        $tid = $t?->id ?? 0;
        $regs = Registration::where('tournament_id', $tid);

        $statusCounts = (clone $regs)->select('status', DB::raw('count(*) c'))->groupBy('status')->pluck('c', 'status');
        $base = fn () => Registration::query()->join('players', 'players.id', '=', 'registrations.player_id')->where('registrations.tournament_id', $tid);

        $stats = [
            'total' => (clone $regs)->count(),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
            'completed' => (clone $regs)->whereNotNull('completed_at')->count(),
            'gender' => $base()->select('players.gender', DB::raw('count(*) c'))->groupBy('players.gender')->pluck('c', 'players.gender'),
            'villages' => $base()->select('players.village', DB::raw('count(*) c'))->groupBy('players.village')->orderByDesc('c')->limit(12)->pluck('c', 'players.village'),
            'roles' => $base()->select('players.playing_role', DB::raw('count(*) c'))->groupBy('players.playing_role')->pluck('c', 'players.playing_role'),
        ];

        $pay = [
            'expected' => (float) (clone $regs)->where('status', '!=', 'rejected')->sum('fee_amount'),
            'received' => (float) Payment::whereHas('registration', fn ($q) => $q->where('tournament_id', $tid))->where('status', 'payment_approved')->sum('amount'),
            'pending_count' => (clone $regs)->whereIn('payment_status', ['proof_submitted', 'under_verification'])->count(),
            'unpaid_count' => (clone $regs)->where('payment_status', 'payment_pending')->count(),
            'rejected_count' => Payment::whereHas('registration', fn ($q) => $q->where('tournament_id', $tid))->where('status', 'payment_rejected')->count(),
        ];

        $recent = Registration::with('player.user', 'player.documents')->where('tournament_id', $tid)->latest('id')->limit(8)->get();
        $toVerify = Payment::with('registration.player')->whereHas('registration', fn ($q) => $q->where('tournament_id', $tid))
            ->whereIn('status', ['proof_submitted', 'under_verification'])->oldest()->limit(6)->get();

        return view('admin.dashboard', compact('t', 'stats', 'pay', 'recent', 'toVerify'));
    }

    public function switchTournament(Request $request)
    {
        $data = $request->validate(['tournament_id' => ['required', 'exists:tournaments,id']]);
        session(['admin_tournament_id' => (int) $data['tournament_id']]);

        return back();
    }
}
