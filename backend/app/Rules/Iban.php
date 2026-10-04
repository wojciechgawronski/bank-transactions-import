<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * IBAN in electronic format (no spaces, uppercase): country code, check digits,
 * 11-30 alphanumeric characters, and a valid ISO 7064 mod-97 checksum.
 */
class Iban implements ValidationRule
{
    private const FORMAT = '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::FORMAT, $value) !== 1) {
            $fail('The :attribute must be an IBAN (country code, check digits, no spaces).');

            return;
        }

        if (! self::hasValidChecksum($value)) {
            $fail('The :attribute has an invalid IBAN checksum.');
        }
    }

    public static function hasValidChecksum(string $iban): bool
    {
        // Move country code and check digits to the end, letters become 10..35.
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $remainder = 0;
        foreach (str_split($rearranged) as $char) {
            $digits = ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }
}
