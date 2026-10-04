<?php

namespace Tests\Feature\Domain\Import;

use App\Domain\Import\Contracts\RecordValidator;
use App\Domain\Import\Data\TransactionRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LaravelRecordValidatorTest extends TestCase
{
    private const VALID = [
        'transaction_id' => 'TX-1',
        'account_number' => 'PL61109010140000071219812874',
        'transaction_date' => '2025-10-14',
        'amount' => '150000',
        'currency' => 'PLN',
    ];

    public function test_valid_record_has_no_errors(): void
    {
        $this->assertSame([], $this->validate([]));
    }

    /**
     * @return array<string, array{array<string, string|null>, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'missing transaction id' => [['transaction_id' => null], 'transaction id field is required'],
            'missing account' => [['account_number' => null], 'account number field is required'],
            'bad iban checksum' => [['account_number' => 'PL12345678901234567890123456'], 'invalid IBAN checksum'],
            'date format' => [['transaction_date' => '14.10.2025'], 'must match the format Y-m-d'],
            'impossible date' => [['transaction_date' => '2025-02-30'], 'must match the format Y-m-d'],
            'zero amount' => [['amount' => '0'], 'greater than 0'],
            'negative amount' => [['amount' => '-100'], 'greater than 0'],
            'decimal amount' => [['amount' => '1500.50'], 'whole number of minor units'],
            'unknown currency' => [['currency' => 'XYZ'], 'not a known ISO 4217'],
            'currency too long' => [['currency' => 'EURO'], 'three uppercase letters'],
        ];
    }

    /**
     * @param  array<string, string|null>  $override
     */
    #[DataProvider('invalidProvider')]
    public function test_reports_readable_error(array $override, string $expected): void
    {
        $errors = $this->validate($override);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString($expected, $errors[0]);
    }

    public function test_reports_every_invalid_field(): void
    {
        $this->assertCount(5, $this->validate(array_fill_keys(TransactionRecord::FIELDS, null)));
    }

    /**
     * @param  array<string, string|null>  $override
     * @return list<string>
     */
    private function validate(array $override): array
    {
        return $this->app->make(RecordValidator::class)
            ->validate(TransactionRecord::fromArray(1, array_merge(self::VALID, $override)));
    }
}
