<?php

namespace App\Support;

use App\Models\Player;
use Illuminate\Validation\Rule;

/** Backend validation rules shared by the player API and the admin edit form. */
class PlayerRules
{
    public const NAME_REGEX = "/^[\pL\s.'-]+$/u";
    public const MOBILE_REGEX = '/^[6-9][0-9]{9}$/';

    public static function personal(?int $ignoreUserId = null, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return [
            'first_name' => [$req, 'string', 'min:2', 'max:60', 'regex:'.self::NAME_REGEX],
            'surname' => [$req, 'string', 'min:2', 'max:60', 'regex:'.self::NAME_REGEX],
            'age' => [$req, 'integer', 'min:10', 'max:80'],
            'mobile' => [$req, 'regex:'.self::MOBILE_REGEX, Rule::unique('users', 'mobile')->ignore($ignoreUserId)],
            'email' => [$req, 'email:rfc', 'max:120', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'gender' => [$req, Rule::in(array_keys(Player::GENDERS))],
            'village' => [$req, 'string', 'min:2', 'max:100'],
        ];
    }

    public static function cricket(bool $required = true): array
    {
        $req = $required ? 'required' : 'sometimes';

        return [
            'playing_role' => [$req, Rule::in(array_keys(Player::ROLES))],
            'batting_style' => [$req, Rule::in(array_keys(Player::BATTING))],
            'bowling_style' => [$req, Rule::in(array_keys(Player::BOWLING))],
            'tshirt_size' => [$req, Rule::in(array_keys(Player::TSHIRT))],
        ];
    }

    public static function jersey(bool $required = true): array
    {
        $req = $required ? 'required' : 'sometimes';

        return [
            'jersey_number' => [$req, 'regex:/^[0-9]{1,3}$/'],
            'jersey_name' => [$req, 'string', 'min:2', 'max:15', 'regex:/^[A-Za-z .]+$/'],
        ];
    }

    public static function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'mobile.unique' => 'This mobile number is already registered.',
            'email.unique' => 'This email address is already registered.',
            'first_name.regex' => 'First name may only contain letters.',
            'surname.regex' => 'Surname may only contain letters.',
            'jersey_number.regex' => 'Jersey number must be 0–999.',
            'jersey_name.regex' => 'Jersey name may only contain English letters, spaces and dots.',
        ];
    }

    /** File rules: images only, real MIME check, size limits (KB). */
    public static function document(string $type): array
    {
        $max = $type === 'photo' ? 2048 : 5120;

        return ['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.$max, 'dimensions:min_width=200,min_height=200']];
    }
}
