<?php

namespace Tests\Feature\Models;

use App\Domain\Import\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_import_is_pending_with_zero_counters(): void
    {
        $import = Import::create(['file_name' => 'transactions.csv'])->refresh();

        $this->assertSame(ImportStatus::Pending, $import->status);
        $this->assertSame(0, $import->total_records);
        $this->assertSame(0, $import->successful_records);
        $this->assertSame(0, $import->failed_records);
    }

    public function test_processed_state_sets_counters_and_status(): void
    {
        $import = Import::factory()->processed(successful: 8, failed: 2)->create();

        $this->assertSame(10, $import->total_records);
        $this->assertSame(ImportStatus::Partial, $import->status);
    }

    public function test_import_has_transactions_and_logs(): void
    {
        $import = Import::factory()
            ->has(Transaction::factory()->count(3))
            ->has(ImportLog::factory()->count(2), 'logs')
            ->create();

        $this->assertCount(3, $import->transactions);
        $this->assertCount(2, $import->logs);
        $this->assertTrue($import->transactions->first()?->import->is($import));
        $this->assertTrue($import->logs->first()?->import->is($import));
    }

    public function test_deleting_import_removes_its_transactions_and_logs(): void
    {
        $import = Import::factory()
            ->has(Transaction::factory()->count(2))
            ->has(ImportLog::factory(), 'logs')
            ->create();

        $import->delete();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('import_logs', 0);
    }
}
