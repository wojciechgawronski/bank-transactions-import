<?php

namespace Tests\Unit\Rules;

use App\Rules\Iban;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IbanTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function validProvider(): array
    {
        return [
            'Poland' => ['PL61109010140000071219812874'],
            'Germany' => ['DE89370400440532013000'],
            'United Kingdom' => ['GB82WEST12345698765432'],
            'Norway (shortest)' => ['NO9386011117947'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidProvider(): array
    {
        return [
            'brief example (bad checksum)' => ['PL12345678901234567890123456'],
            'one digit changed' => ['PL61109010140000071219812875'],
            'no country code' => ['12345678901234567890123456'],
            'lowercase' => ['pl61109010140000071219812874'],
            'with spaces' => ['PL61 1090 1014 0000 0712 1981 2874'],
            'too short' => ['PL6110901014'],
            'too long' => ['PL61109010140000071219812874000000000'],
            'not a string' => [61109010140000071219812874],
        ];
    }

    #[DataProvider('validProvider')]
    public function test_accepts_valid_iban(mixed $value): void
    {
        $this->assertSame([], $this->errors($value));
    }

    #[DataProvider('invalidProvider')]
    public function test_rejects_invalid_iban(mixed $value): void
    {
        $this->assertCount(1, $this->errors($value));
    }

    public function test_reports_checksum_separately_from_format(): void
    {
        $this->assertStringContainsString('checksum', $this->errors('PL12345678901234567890123456')[0]);
        $this->assertStringContainsString('must be an IBAN', $this->errors('not-an-iban')[0]);
    }

    /**
     * @return list<string>
     */
    private function errors(mixed $value): array
    {
        $errors = [];
        (new Iban)->validate('account_number', $value, function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        return $errors;
    }
}
