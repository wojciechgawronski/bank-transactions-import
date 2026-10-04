<?php

namespace Tests\Feature\Models;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_amount_and_date(): void
    {
        $transaction = Transaction::factory()
            ->create(['amount' => 150000, 'transaction_date' => '2026-01-15'])
            ->refresh();

        $this->assertSame(150000, $transaction->amount);
        $this->assertInstanceOf(CarbonImmutable::class, $transaction->transaction_date);
        $this->assertSame('2026-01-15', $transaction->transaction_date->toDateString());
    }

    public function test_transaction_id_is_unique_across_imports(): void
    {
        Transaction::factory()->create(['transaction_id' => 'TX-1']);

        $this->expectException(QueryException::class);

        Transaction::factory()->create(['transaction_id' => 'TX-1']);
    }
}
