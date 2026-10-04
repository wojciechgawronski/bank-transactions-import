<?php

namespace Database\Seeders;

use App\Domain\Import\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

/**
 * Sample imports in every status, for local development of the dashboard.
 */
class ImportSeeder extends Seeder
{
    private const ERRORS = [
        'account_number: invalid IBAN checksum',
        'currency: unknown ISO 4217 code "XYZ"',
        'amount: must be an integer greater than 0',
        'transaction_date: must be a date in Y-m-d format',
        'transaction_id: duplicate in file',
    ];

    public function run(): void
    {
        $this->processed('transactions-2026-09.csv', successful: 25, failed: 0);
        $this->processed('bank-export.json', successful: 18, failed: 4);
        $this->processed('broken-records.xml', successful: 0, failed: 6);
        $this->processed('empty.csv', successful: 0, failed: 0);

        Import::factory()->create(['file_name' => 'uploading-now.csv', 'status' => ImportStatus::Processing]);
        Import::factory()->create(['file_name' => 'queued.json', 'status' => ImportStatus::Pending]);
    }

    private function processed(string $fileName, int $successful, int $failed): void
    {
        $import = Import::factory()
            ->processed($successful, $failed)
            ->create(['file_name' => $fileName]);

        Transaction::factory()
            ->count($successful)
            ->for($import)
            ->create();

        ImportLog::factory()
            ->count($failed)
            ->for($import)
            ->state(new Sequence(...array_map(
                fn (string $message) => ['error_message' => $message],
                self::ERRORS,
            )))
            ->create();
    }
}
