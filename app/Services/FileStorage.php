<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Aadhaar documents, photos and payment screenshots are stored on the PRIVATE
 * "local" disk (storage/app/private) with random file names. They are never
 * web-accessible directly — only via authorised controller routes.
 */
class FileStorage
{
    public const DISK = 'local';

    public function storeDocument(Player $player, string $type, UploadedFile $file): PlayerDocument
    {
        $existing = $player->documents()->where('type', $type)->first();
        $path = $file->storeAs(
            'players/'.$player->id,
            $type.'_'.Str::random(32).'.'.$file->extension(),
            self::DISK
        );

        if ($existing) {
            Storage::disk(self::DISK)->delete($existing->path);
        }

        return PlayerDocument::updateOrCreate(
            ['player_id' => $player->id, 'type' => $type],
            [
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'status' => 'pending',        // any re-upload needs fresh verification
                'remarks' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );
    }

    public function storePaymentScreenshot(UploadedFile $file, Registration $reg): string
    {
        return $file->storeAs(
            'payments/'.$reg->id,
            'proof_'.Str::random(32).'.'.$file->extension(),
            self::DISK
        );
    }

    public function deletePlayerFiles(Player $player): void
    {
        Storage::disk(self::DISK)->deleteDirectory('players/'.$player->id);
        foreach ($player->registrations as $reg) {
            Storage::disk(self::DISK)->deleteDirectory('payments/'.$reg->id);
        }
    }
}
