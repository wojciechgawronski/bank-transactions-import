<?php

namespace Tests\Unit\Domain\Import\Data;

use App\Domain\Import\Data\TransactionRecord;
use PHPUnit\Framework\TestCase;

class TransactionRecordTest extends TestCase
{
    public function test_normalizes_raw_values_to_trimmed_strings(): void
    {
        $record = TransactionRecord::fromArray(7, [
            'transaction_id' => '  TX-1 ',
            'account_number' => '',
            'transaction_date' => '2025-10-14',
            'amount' => 150000,
            'currency' => ['PLN'],
            'unknown' => 'ignored',
        ]);

        $this->assertSame(7, $record->position);
        $this->assertSame([
            'transaction_id' => 'TX-1',
            'account_number' => null,
            'transaction_date' => '2025-10-14',
            'amount' => '150000',
            'currency' => null,
        ], $record->toArray());
    }

    public function test_missing_fields_are_null(): void
    {
        $record = TransactionRecord::fromArray(1, []);

        $this->assertSame(array_fill_keys(TransactionRecord::FIELDS, null), $record->toArray());
    }
}
