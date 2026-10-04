<?php

namespace App\Domain\Import;

use App\Domain\Import\Contracts\RecordValidator;
use App\Domain\Import\Data\ImportResult;
use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Exceptions\InvalidImportFile;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Reads an import file, validates every record and stores valid ones in
 * transactions and invalid ones in import_logs, in chunks.
 *
 * Independent of HTTP and the queue: the job only calls process().
 */
final class ImportProcessor
{
    public const CHUNK_SIZE = 500;

    public function __construct(
        private readonly ParserRegistry $parsers,
        private readonly RecordValidator $validator,
        private readonly int $chunkSize = self::CHUNK_SIZE,
    ) {}

    public function process(Import $import, string $path, FileFormat $format): ImportResult
    {
        // Start from a clean state, so a retried job does not see its own rows as duplicates.
        $this->reset($import, ImportStatus::Processing);

        try {
            $result = $this->importRecords($import, $this->parsers->for($format)->parse($path));
        } catch (InvalidImportFile $e) {
            $this->fail($import, $e->getMessage());

            return new ImportResult(0, 0);
        }

        $import->update(['status' => $result->status()]);

        return $result;
    }

    /**
     * Marks the whole import as failed, e.g. for an unreadable file. All-or-nothing:
     * chunks saved before the error are removed and one file-level log explains why.
     */
    public function fail(Import $import, string $reason): void
    {
        $this->reset($import, ImportStatus::Failed);
        $import->logs()->create(['error_message' => $reason]);
    }

    /**
     * @param  iterable<int, TransactionRecord>  $records
     */
    private function importRecords(Import $import, iterable $records): ImportResult
    {
        /** @var array<string, true> $accepted transaction ids already accepted from this file */
        $accepted = [];
        $successful = 0;
        $failed = 0;
        $chunk = [];

        foreach ($records as $record) {
            $chunk[] = $record;

            if (count($chunk) === $this->chunkSize) {
                [$saved, $rejected] = $this->saveChunk($import, $chunk, $accepted);
                $successful += $saved;
                $failed += $rejected;
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            [$saved, $rejected] = $this->saveChunk($import, $chunk, $accepted);
            $successful += $saved;
            $failed += $rejected;
        }

        return new ImportResult($successful, $failed);
    }

    /**
     * @param  list<TransactionRecord>  $chunk
     * @param  array<string, true>  $accepted
     * @return array{int, int} saved and rejected record counts
     */
    private function saveChunk(Import $import, array $chunk, array &$accepted): array
    {
        $ids = array_values(array_filter(array_map(fn (TransactionRecord $r) => $r->transactionId, $chunk)));
        $existing = Transaction::query()->whereIn('transaction_id', $ids)->pluck('transaction_id')->flip();

        $now = now();
        $transactions = [];
        $logs = [];

        foreach ($chunk as $record) {
            $errors = $this->validator->validate($record);
            $id = $record->transactionId;

            if ($errors === [] && $id !== null) {
                if (isset($accepted[$id])) {
                    $errors[] = 'The transaction_id is duplicated in the file.';
                } elseif ($existing->has($id)) {
                    $errors[] = 'The transaction_id has already been imported.';
                }
            }

            if ($errors !== [] || $id === null) {
                $logs[] = [
                    'import_id' => $import->id,
                    'transaction_id' => $id === null ? null : mb_substr($id, 0, 255),
                    'error_message' => "Record {$record->position}: ".implode(' ', $errors),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                continue;
            }

            $accepted[$id] = true;
            $transactions[] = [
                'import_id' => $import->id,
                'transaction_id' => $id,
                'account_number' => $record->accountNumber,
                'transaction_date' => $record->transactionDate,
                'amount' => (int) $record->amount,
                'currency' => $record->currency,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($import, $transactions, $logs) {
            if ($transactions !== []) {
                Transaction::query()->insert($transactions);
            }
            if ($logs !== []) {
                ImportLog::query()->insert($logs);
            }

            $import->total_records += count($transactions) + count($logs);
            $import->successful_records += count($transactions);
            $import->failed_records += count($logs);
            $import->save();
        });

        return [count($transactions), count($logs)];
    }

    private function reset(Import $import, ImportStatus $status): void
    {
        DB::transaction(function () use ($import, $status) {
            $import->transactions()->delete();
            $import->logs()->delete();
            $import->update([
                'total_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
                'status' => $status,
            ]);
        });
    }
}
