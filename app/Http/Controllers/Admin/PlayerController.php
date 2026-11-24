<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\Registration;
use App\Models\StatusHistory;
use App\Services\FileStorage;
use App\Services\RegistrationWorkflow;
use App\Support\AdminContext;
use App\Support\CsvExport;
use App\Support\PlayerRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PlayerController extends Controller
{
    public function __construct(private RegistrationWorkflow $workflow) {}

    private function query(Request $request)
    {
        $tid = AdminContext::tournamentId();
        $q = Player::query()
            ->join('users', 'users.id', '=', 'players.user_id')
            ->leftJoin('registrations as r', function ($j) use ($tid) {
                $j->on('r.player_id', '=', 'players.id')->where('r.tournament_id', '=', $tid);
            })
            ->select('players.*', 'users.email', 'users.mobile', 'users.is_active',
                'r.id as reg_id', 'r.status as reg_status', 'r.payment_status', 'r.registration_no',
                'r.jersey_number', 'r.jersey_name', 'r.fee_amount', 'r.submitted_at', 'r.completed_at');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $like = '%'.$s.'%';
                $full = DB::connection()->getDriverName() === 'mysql'
                    ? "CONCAT(players.first_name, ' ', players.surname)"
                    : "(players.first_name || ' ' || players.surname)";
                $w->whereRaw($full.' like ?', [$like])
                    ->orWhere('players.first_name', 'like', $like)->orWhere('players.surname', 'like', $like)
                    ->orWhere('users.mobile', 'like', $like)->orWhere('players.village', 'like', $like)
                    ->orWhere('users.email', 'like', $like)->orWhere('r.registration_no', 'like', $like);
            });
        }
        foreach (['gender', 'playing_role', 'village'] as $f) {
            if ($v = $request->query($f)) {
                $q->where('players.'.$f, $v);
            }
        }
        match ($request->query('status')) {
            'not_submitted' => $q->whereNull('r.id'),
            'completed' => $q->whereNotNull('r.completed_at'),
            'pending', 'approved', 'rejected' => $q->where('r.status', $request->query('status')),
            default => $request->query('scope') === 'all' ? null : $q->whereNotNull('r.id'),
        };
        if ($ps = $request->query('payment_status')) {
            $q->where('r.payment_status', $ps);
        }

        return $q->orderByRaw('r.submitted_at IS NULL')->orderByDesc('r.submitted_at')->orderByDesc('players.id');
    }

    public function index(Request $request)
    {
        $players = $this->query($request)->with('documents')->paginate(20)->withQueryString();
        $villages = Player::whereNotNull('village')->distinct()->orderBy('village')->pluck('village');

        return view('admin.players.index', compact('players', 'villages'));
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->cursor()->map(fn ($p) => [
            $p->registration_no, $p->first_name, $p->surname, $p->age, $p->mobile, $p->email,
            Player::label(Player::GENDERS, $p->gender), $p->village,
            Player::label(Player::ROLES, $p->playing_role), Player::label(Player::BATTING, $p->batting_style),
            Player::label(Player::BOWLING, $p->bowling_style), $p->tshirt_size, $p->jersey_number, $p->jersey_name,
            $p->reg_status ? (Registration::STATUSES[$p->reg_status] ?? $p->reg_status) : 'Not submitted',
            $p->payment_status ? (Registration::PAYMENT_STATUSES[$p->payment_status] ?? '') : '',
            $p->fee_amount, $p->submitted_at, $p->completed_at,
        ]);
        ActivityLog::log('players.export', 'Exported player list');

        return CsvExport::stream('players-'.now()->format('Ymd-His').'.csv', [
            'Registration ID', 'First Name', 'Surname', 'Age', 'Mobile', 'Email', 'Gender', 'Village', 'Playing Role',
            'Batting Style', 'Bowling Style', 'T-shirt', 'Jersey No', 'Jersey Name', 'Registration Status', 'Payment Status',
            'Fee', 'Submitted At', 'Completed At',
        ], $rows);
    }

    public function show(Player $player)
    {
        $player->load('user', 'documents', 'registrations.tournament');
        $registration = Registration::with(['payments.verifier', 'histories.actor', 'tournament'])
            ->where('tournament_id', AdminContext::tournamentId())->where('player_id', $player->id)->first();

        return view('admin.players.show', compact('player', 'registration'));
    }

    public function edit(Player $player)
    {
        $player->load('user');
        $registration = Registration::where('tournament_id', AdminContext::tournamentId())->where('player_id', $player->id)->first();

        return view('admin.players.edit', compact('player', 'registration'));
    }

    public function update(Request $request, Player $player)
    {
        $data = $request->validate(
            PlayerRules::personal($player->user_id) + PlayerRules::cricket() + [
                'jersey_number' => ['nullable', 'regex:/^[0-9]{1,3}$/'],
                'jersey_name' => ['nullable', 'string', 'max:15'],
                'is_active' => ['boolean'],
                'new_password' => ['nullable', Password::min(8)],
            ],
            PlayerRules::messages()
        );

        DB::transaction(function () use ($player, $data, $request) {
            $before = $player->only(['first_name', 'surname', 'age', 'gender', 'village', 'playing_role', 'batting_style', 'bowling_style', 'tshirt_size']);
            $player->update(collect($data)->only(array_keys($before))->all());
            $user = $player->user;
            $user->fill(['name' => $player->full_name, 'email' => strtolower($data['email']), 'mobile' => $data['mobile'], 'is_active' => $request->boolean('is_active')]);
            if (! empty($data['new_password'])) {
                $user->password = $data['new_password'];
                $user->tokens()->delete();
            }
            $user->save();

            if ($reg = Registration::where('tournament_id', AdminContext::tournamentId())->where('player_id', $player->id)->first()) {
                $reg->update(['jersey_number' => $data['jersey_number'] ?? null, 'jersey_name' => isset($data['jersey_name']) ? mb_strtoupper($data['jersey_name']) : null]);
            }
            ActivityLog::log('player.updated', 'Edited player '.$player->full_name, $player, ['before' => $before, 'after' => $player->only(array_keys($before))]);
        });

        return redirect()->route('admin.players.show', $player)->with('success', 'Player details updated.');
    }

    public function destroy(Player $player, FileStorage $files)
    {
        $name = $player->full_name;
        DB::transaction(function () use ($player, $files) {
            $files->deletePlayerFiles($player->load('registrations'));
            $player->user->tokens()->delete();
            $player->user->delete(); // cascades to player, documents, registrations, payments
        });
        ActivityLog::log('player.deleted', 'Deleted player '.$name);

        return redirect()->route('admin.players.index')->with('success', 'Player '.$name.' deleted.');
    }

    public function approve(Request $request, Registration $registration)
    {
        $this->workflow->approveRegistration($registration->load('player.user', 'tournament'), $request->input('remarks'));

        return back()->with('success', 'Registration approved.');
    }

    public function reject(Request $request, Registration $registration)
    {
        $data = $request->validate(['remarks' => ['required', 'string', 'min:5', 'max:500']]);
        $this->workflow->rejectRegistration($registration->load('player.user', 'tournament'), $data['remarks']);

        return back()->with('success', 'Registration rejected. The player can see your remarks.');
    }

    public function offlinePayment(Request $request, Registration $registration)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:35'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);
        $this->workflow->recordOfflinePayment($registration->load('player.user', 'tournament'), $data);

        return back()->with('success', 'Offline payment recorded and registration confirmed.');
    }

    public function verifyDocument(Request $request, PlayerDocument $document)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['verified', 'rejected', 'pending'])],
            'remarks' => ['nullable', 'required_if:status,rejected', 'string', 'max:255'],
        ]);
        $from = $document->status;
        $document->update(['status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'verified_by' => auth()->id(), 'verified_at' => now()]);

        $reg = Registration::where('tournament_id', AdminContext::tournamentId())->where('player_id', $document->player_id)->first();
        StatusHistory::record($reg, 'document', $from, $data['status'], $document->label.(! empty($data['remarks']) ? ': '.$data['remarks'] : ''));
        ActivityLog::log('document.'.$data['status'], ucfirst($data['status']).' '.$document->label.' of '.$document->player->full_name, $document);

        return back()->with('success', $document->label.' marked '.$data['status'].'.');
    }

    /** Authorised access to private documents (view inline or download). */
    public function document(PlayerDocument $document, string $mode = 'view')
    {
        abort_unless(Storage::disk(FileStorage::DISK)->exists($document->path), 404);
        ActivityLog::log('document.accessed', ($mode === 'download' ? 'Downloaded ' : 'Viewed ').$document->label.' of '.$document->player->full_name, $document);
        $name = str($document->player->full_name)->slug().'-'.$document->type.'.'.pathinfo($document->path, PATHINFO_EXTENSION);

        return $mode === 'download'
            ? Storage::disk(FileStorage::DISK)->download($document->path, $name)
            : Storage::disk(FileStorage::DISK)->response($document->path, $name, ['Cache-Control' => 'private, no-store']);
    }
}
