<?php

namespace Tests\Feature\Seeders;

use App\Domain\Import\Enums\ImportStatus;
use App\Models\Import;
use Database\Seeders\ImportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_imports_in_every_status_with_matching_counters(): void
    {
        $this->seed(ImportSeeder::class);

        $statuses = Import::query()->pluck('status')->unique();
        $this->assertCount(count(ImportStatus::cases()), $statuses);

        Import::query()->withCount(['transactions', 'logs'])->get()->each(function (Import $import) {
            $this->assertSame($import->successful_records, $import->transactions_count);
            $this->assertSame($import->failed_records, $import->logs_count);
        });
    }
}
