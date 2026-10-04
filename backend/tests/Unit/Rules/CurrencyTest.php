<?php

namespace Tests\Unit\Rules;

use App\Rules\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function currencyProvider(): array
    {
        return [
            'PLN' => ['PLN', true],
            'USD' => ['USD', true],
            'EUR' => ['EUR', true],
            'unknown code' => ['ABC', false],
            'four letters' => ['EURO', false],
            'lowercase' => ['pln', false],
            'digits' => ['123', false],
            'not a string' => [985, false],
        ];
    }

    #[DataProvider('currencyProvider')]
    public function test_validates_iso_4217_code(mixed $value, bool $valid): void
    {
        $failed = false;
        (new Currency)->validate('currency', $value, function () use (&$failed) {
            $failed = true;
        });

        $this->assertSame($valid, ! $failed);
    }
}
