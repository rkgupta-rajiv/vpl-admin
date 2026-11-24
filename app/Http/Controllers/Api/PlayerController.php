<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Tournament;
use App\Services\FileStorage;
use App\Support\PlayerPresenter;
use App\Support\PlayerRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Everything here is scoped to the authenticated player — no IDs from the client. */
class PlayerController extends Controller
{
    /** Public tournament info for the landing / registration screen. */
    public function tournament()
    {
        return response()->json(['success' => true, 'tournament' => PlayerPresenter::tournament(Tournament::active()?->load('paymentSetting'))]);
    }

    /** Dashboard payload: profile, documents, current registration, notifications count. */
    public function me(Request $request)
    {
        $user = $request->user();
        $player = $user->player()->with('documents')->firstOrFail();
        $tournament = Tournament::active()?->load('paymentSetting');
        $registration = $tournament
            ? Registration::where('tournament_id', $tournament->id)->where('player_id', $player->id)->first()
            : null;

        return response()->json([
            'success' => true,
            'player' => PlayerPresenter::player($player),
            'tournament' => PlayerPresenter::tournament($tournament),
            'registration' => PlayerPresenter::registration($registration),
            'unread_notifications' => $user->unreadNotifications()->count(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $player = $user->player;
        $this->ensureEditable($player);

        $data = $request->validate(
            PlayerRules::personal($user->id, partial: true) + PlayerRules::cricket(required: false),
            PlayerRules::messages()
        );

        DB::transaction(function () use ($user, $player, $data) {
            $player->update(collect($data)->only(['first_name', 'surname', 'age', 'gender', 'village', 'playing_role', 'batting_style', 'bowling_style', 'tshirt_size'])->all());
            $user->update(array_filter([
                'name' => $player->full_name,
                'email' => isset($data['email']) ? strtolower($data['email']) : null,
                'mobile' => $data['mobile'] ?? null,
            ]));
        });

        return response()->json(['success' => true, 'message' => 'Profile updated.', 'player' => PlayerPresenter::player($player->fresh())]);
    }

    public function uploadDocument(Request $request, string $type, FileStorage $files)
    {
        abort_unless(in_array($type, ['photo', 'aadhaar_front', 'aadhaar_back'], true), 404);
        $player = $request->user()->player;
        $this->ensureEditable($player);
        $request->validate(PlayerRules::document($type), ['file.dimensions' => 'Image is too small. Please upload a clear photo (min 200×200 px).']);

        $doc = $files->storeDocument($player, $type, $request->file('file'));

        return response()->json(['success' => true, 'message' => $doc->label.' uploaded.', 'document' => PlayerPresenter::document($doc), 'player' => PlayerPresenter::player($player->fresh())]);
    }

    /** Streams the player's OWN document (for preview in the app). */
    public function document(Request $request, string $type)
    {
        $doc = $request->user()->player->documents()->where('type', $type)->firstOrFail();
        abort_unless(Storage::disk(FileStorage::DISK)->exists($doc->path), 404);

        return Storage::disk(FileStorage::DISK)->response($doc->path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureEditable($player): void
    {
        $tournament = Tournament::active();
        if (! $tournament) {
            return;
        }
        $reg = Registration::where('tournament_id', $tournament->id)->where('player_id', $player->id)->first();
        if ($reg && $reg->isCompleted()) {
            throw ValidationException::withMessages(['profile' => 'Your registration is confirmed, so details are locked. Please contact the organiser for any correction.']);
        }
        if ($reg && in_array($reg->payment_status, ['proof_submitted', 'under_verification'], true) && $reg->status === 'approved') {
            throw ValidationException::withMessages(['profile' => 'Your payment is being verified. Details are locked until verification is complete.']);
        }
    }
}
