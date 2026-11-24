<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\Registration;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Optional demo data: php artisan db:seed --class=DemoPlayersSeeder */
class DemoPlayersSeeder extends Seeder
{
    public function run(): void
    {
        $t = Tournament::active();
        $villages = ['Rampura', 'Sanand', 'Bavla', 'Dholka', 'Viramgam', 'Kalol'];
        $first = ['Rahul', 'Kiran', 'Mehul', 'Jignesh', 'Priya', 'Nilesh', 'Hetal', 'Bhavesh', 'Ravi', 'Asha', 'Ketan', 'Paresh'];
        $roles = array_keys(Player::ROLES);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        foreach ($first as $i => $fn) {
            $user = User::firstOrCreate(['email' => 'player'.($i + 1).'@example.com'], [
                'name' => $fn.' Vankar', 'mobile' => '98765'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'password' => 'Player@123', 'role' => User::ROLE_PLAYER,
            ]);
            $player = Player::firstOrCreate(['user_id' => $user->id], [
                'first_name' => $fn, 'surname' => 'Vankar', 'age' => 18 + $i, 'gender' => in_array($fn, ['Priya', 'Hetal', 'Asha']) ? 'female' : 'male',
                'village' => $villages[$i % count($villages)], 'playing_role' => $roles[$i % 4], 'batting_style' => $i % 3 ? 'right' : 'left',
                'bowling_style' => ['right_arm', 'left_arm', 'na'][$i % 3], 'tshirt_size' => ['M', 'L', 'XL'][$i % 3],
            ]);
            foreach (array_keys(Player::DOC_TYPES) as $type) {
                $path = 'players/'.$player->id.'/'.$type.'_demo.png';
                Storage::disk('local')->put($path, $png);
                PlayerDocument::updateOrCreate(['player_id' => $player->id, 'type' => $type], ['path' => $path, 'mime' => 'image/png', 'size' => strlen($png), 'original_name' => $type.'.png']);
            }
            if ($t && $i < 10) {
                Registration::firstOrCreate(['tournament_id' => $t->id, 'player_id' => $player->id], [
                    'jersey_number' => (string) (7 + $i), 'jersey_name' => strtoupper($fn), 'status' => 'pending',
                    'payment_status' => 'payment_pending', 'fee_amount' => $t->currentFee(), 'submitted_at' => now()->subHours(12 - $i),
                ]);
            }
        }
    }
}
