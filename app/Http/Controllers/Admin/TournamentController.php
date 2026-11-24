<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PaymentSetting;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::withCount('registrations')->with('paymentSetting')->orderByDesc('id')->get();

        return view('admin.tournaments.index', compact('tournaments'));
    }

    public function create()
    {
        return view('admin.tournaments.form', ['tournament' => new Tournament(['registration_status' => 'closed'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $t = DB::transaction(function () use ($data, $request) {
            $t = Tournament::create($data);
            PaymentSetting::create(['tournament_id' => $t->id, 'registration_fee' => 0, 'online_payment_enabled' => true]);
            if ($request->boolean('is_active')) {
                $this->makeActive($t);
            }

            return $t;
        });
        ActivityLog::log('tournament.created', 'Created tournament '.$t->title, $t);

        return redirect()->route('admin.tournaments.payment', $t)->with('success', 'Tournament created. Now set the registration fee and QR code.');
    }

    public function edit(Tournament $tournament)
    {
        return view('admin.tournaments.form', compact('tournament'));
    }

    public function update(Request $request, Tournament $tournament)
    {
        $data = $this->validated($request, $tournament);
        $tournament->update($data);
        if ($request->boolean('is_active')) {
            $this->makeActive($tournament);
        }
        ActivityLog::log('tournament.updated', 'Updated tournament '.$tournament->title, $tournament, ['changes' => $tournament->getChanges()]);

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament updated.');
    }

    public function activate(Tournament $tournament)
    {
        $this->makeActive($tournament);
        session(['admin_tournament_id' => $tournament->id]);
        ActivityLog::log('tournament.activated', 'Set '.$tournament->title.' as the active tournament', $tournament);

        return back()->with('success', $tournament->title.' is now the active tournament shown in the app.');
    }

    public function toggleRegistration(Tournament $tournament)
    {
        $tournament->update(['registration_status' => $tournament->registration_status === 'open' ? 'closed' : 'open']);
        ActivityLog::log('tournament.registration_'.$tournament->registration_status, 'Registration '.$tournament->registration_status.' for '.$tournament->title, $tournament);

        return back()->with('success', 'Registration is now '.strtoupper($tournament->registration_status).'.');
    }

    public function destroy(Tournament $tournament)
    {
        if ($tournament->registrations()->exists()) {
            return back()->withErrors(['tournament' => 'This tournament has registrations and cannot be deleted. Close registration instead.']);
        }
        if ($tournament->paymentSetting?->qr_code_path) {
            Storage::disk('public')->delete($tournament->paymentSetting->qr_code_path);
        }
        ActivityLog::log('tournament.deleted', 'Deleted tournament '.$tournament->title);
        $tournament->delete();

        return back()->with('success', 'Tournament deleted.');
    }

    /* --------------------------- Payment settings --------------------------- */

    public function payment(Tournament $tournament)
    {
        $setting = $tournament->paymentSetting ?? PaymentSetting::create(['tournament_id' => $tournament->id]);

        return view('admin.tournaments.payment', compact('tournament', 'setting'));
    }

    public function updatePayment(Request $request, Tournament $tournament)
    {
        $data = $request->validate([
            'registration_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9.\-_]{2,256}@[A-Za-z]{2,64}$/'],
            'payee_name' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'online_payment_enabled' => ['boolean'],
            'qr_code' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_qr' => ['boolean'],
        ], ['upi_id.regex' => 'Enter a valid UPI ID, e.g. vpl@okicici']);

        $setting = $tournament->paymentSetting ?? new PaymentSetting(['tournament_id' => $tournament->id]);
        $oldFee = $setting->registration_fee;
        $setting->fill([
            'registration_fee' => $data['registration_fee'],
            'upi_id' => $data['upi_id'] ?? null,
            'payee_name' => $data['payee_name'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'online_payment_enabled' => $request->boolean('online_payment_enabled'),
        ]);
        if ($request->boolean('remove_qr') && $setting->qr_code_path) {
            Storage::disk('public')->delete($setting->qr_code_path);
            $setting->qr_code_path = null;
        }
        if ($request->hasFile('qr_code')) {
            if ($setting->qr_code_path) {
                Storage::disk('public')->delete($setting->qr_code_path);
            }
            $setting->qr_code_path = $request->file('qr_code')->store('qr-codes', 'public');
        }
        $setting->save();
        ActivityLog::log('payment_settings.updated', 'Updated payment settings for '.$tournament->title, $tournament,
            ['old_fee' => $oldFee, 'new_fee' => $setting->registration_fee]);

        // Existing registrations keep the fee they registered with (registrations.fee_amount).
        return back()->with('success', 'Payment settings saved. Existing registrations keep their original fee.');
    }

    private function validated(Request $request, ?Tournament $t = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'season' => ['required', 'string', 'max:50'],
            'code' => ['required', 'alpha_num', 'max:12', Rule::unique('tournaments', 'code')->ignore($t?->id)],
            'registration_start_date' => ['nullable', 'date'],
            'registration_end_date' => ['nullable', 'date', 'after_or_equal:registration_start_date'],
            'registration_status' => ['required', Rule::in(['open', 'closed'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'rules' => ['nullable', 'string', 'max:20000'],
            'instructions' => ['nullable', 'string', 'max:10000'],
        ]);
        $data['code'] = strtoupper($data['code']);

        return $data;
    }

    private function makeActive(Tournament $t): void
    {
        Tournament::where('id', '!=', $t->id)->update(['is_active' => false]);
        $t->update(['is_active' => true]);
    }
}
