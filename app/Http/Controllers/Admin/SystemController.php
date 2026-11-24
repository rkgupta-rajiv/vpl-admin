<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\StatusHistory;
use App\Support\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Application settings, admin password, activity log and approval history. */
class SystemController extends Controller
{
    public function settings()
    {
        return view('admin.settings', ['s' => collect(Setting::DEFAULTS)->mapWithKeys(fn ($v, $k) => [$k => Setting::get($k)])]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'organizer_name' => ['nullable', 'string', 'max:120'],
            'support_phone' => ['nullable', 'string', 'max:20'],
            'support_email' => ['nullable', 'email', 'max:120'],
        ]);
        $data['email_notifications'] = $request->boolean('email_notifications') ? '1' : '0';
        Setting::put($data);
        ActivityLog::log('settings.updated', 'Updated application settings', null, $data);

        return back()->with('success', 'Settings saved.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $data['password']]);
        ActivityLog::log('admin.password_changed', 'Admin changed password');

        return back()->with('success', 'Password changed.');
    }

    public function activity(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->latest('id')->paginate(30)->withQueryString();
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity', compact('logs', 'actions'));
    }

    public function history(Request $request)
    {
        $items = StatusHistory::with('actor', 'payment')
            ->with(['registration' => fn ($q) => $q->with('player')])
            ->whereHas('registration', fn ($q) => $q->where('tournament_id', AdminContext::tournamentId()))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.history', compact('items'));
    }
}
