<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Symfony\Component\Intl\Currencies;

/**
 * Three uppercase letters that form an existing ISO 4217 currency code.
 */
class Currency implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[A-Z]{3}$/', $value) !== 1) {
            $fail('The :attribute must be three uppercase letters, e.g. PLN.');

            return;
        }

        if (! Currencies::exists($value)) {
            $fail('The :attribute is not a known ISO 4217 currency code.');
        }
    }
}
