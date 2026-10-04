<?php

namespace Tests\Feature\Domain\Import;

use App\Domain\Import\Contracts\RecordValidator;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\ImportProcessor;
use App\Domain\Import\ParserRegistry;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportProcessorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->tempFiles);
        parent::tearDown();
    }

    /**
     * @return array<string, array{FileFormat}>
     */
    public static function formatProvider(): array
    {
        return array_combine(
            array_map(fn (FileFormat $f) => $f->value, FileFormat::cases()),
            array_map(fn (FileFormat $f) => [$f], FileFormat::cases()),
        );
    }

    #[DataProvider('formatProvider')]
    public function test_valid_file_imports_every_record(FileFormat $format): void
    {
        $import = $this->process("valid.{$format->value}", $format);

        $this->assertImport($import, ImportStatus::Success, total: 3, successful: 3, failed: 0);
        $transaction = Transaction::query()->where('transaction_id', '550e8400-e29b-41d4-a716-446655440000')->sole();
        $this->assertSame('PL61109010140000071219812874', $transaction->account_number);
        $this->assertSame('2025-10-14', $transaction->transaction_date->toDateString());
        $this->assertSame(150000, $transaction->amount);
        $this->assertSame('PLN', $transaction->currency);
        $this->assertTrue($transaction->import->is($import));
    }

    #[DataProvider('formatProvider')]
    public function test_mixed_file_is_partial_with_a_log_per_invalid_record(FileFormat $format): void
    {
        $import = $this->process("mixed.{$format->value}", $format);

        $this->assertImport($import, ImportStatus::Partial, total: 6, successful: 2, failed: 4);
        $this->assertSame(
            [
                '660e8400-e29b-41d4-a716-446655440001' => 'Record 2: The account number must be an IBAN (country code, check digits, no spaces).',
                '660e8400-e29b-41d4-a716-446655440002' => 'Record 3: The amount must be greater than 0.',
                '660e8400-e29b-41d4-a716-446655440003' => 'Record 4: The account number field is required.',
                '660e8400-e29b-41d4-a716-446655440004' => 'Record 5: The currency must be three uppercase letters, e.g. PLN.',
            ],
            $import->logs()->orderBy('id')->pluck('error_message', 'transaction_id')->all(),
        );
    }

    #[DataProvider('formatProvider')]
    public function test_corrupted_file_fails_without_partial_data(FileFormat $format): void
    {
        $import = $this->process("corrupted.{$format->value}", $format);

        $this->assertImport($import, ImportStatus::Failed, total: 0, successful: 0, failed: 0);
        $this->assertSame(0, Transaction::query()->count());
        $log = $import->logs()->sole();
        $this->assertNull($log->transaction_id);
        $this->assertNotSame('', $log->error_message);
    }

    public function test_brief_example_accounts_are_rejected_for_bad_iban_checksum(): void
    {
        $import = $this->process('brief-example.csv', FileFormat::Csv);

        $this->assertImport($import, ImportStatus::Failed, total: 2, successful: 0, failed: 2);
        $import->logs->each(fn (ImportLog $log) => $this->assertStringContainsString('invalid IBAN checksum', $log->error_message));
    }

    public function test_file_without_records_fails(): void
    {
        $import = $this->processContents("transaction_id,account_number,transaction_date,amount,currency\n", FileFormat::Csv);

        $this->assertImport($import, ImportStatus::Failed, total: 0, successful: 0, failed: 0);
    }

    public function test_rejects_transaction_already_imported_by_another_import(): void
    {
        $this->process('valid.csv', FileFormat::Csv);

        $second = $this->process('valid.json', FileFormat::Json);

        $this->assertImport($second, ImportStatus::Failed, total: 3, successful: 0, failed: 3);
        $this->assertStringContainsString('already been imported', (string) $second->logs()->value('error_message'));
        $this->assertSame(3, Transaction::query()->count());
    }

    public function test_keeps_first_occurrence_of_transaction_id_duplicated_in_file(): void
    {
        $row = 'TX-1,PL61109010140000071219812874,2025-10-14,100,PLN';
        $import = $this->processContents("transaction_id,account_number,transaction_date,amount,currency\n{$row}\n{$row}\n", FileFormat::Csv);

        $this->assertImport($import, ImportStatus::Partial, total: 2, successful: 1, failed: 1);
        $this->assertStringContainsString('Record 2: The transaction_id is duplicated in the file.', (string) $import->logs()->value('error_message'));
    }

    public function test_processing_the_same_import_again_starts_from_scratch(): void
    {
        $import = Import::factory()->create(['file_name' => 'valid.csv']);
        $processor = $this->app->make(ImportProcessor::class);

        $processor->process($import, $this->fixture('valid.csv'), FileFormat::Csv);
        $processor->process($import, $this->fixture('valid.csv'), FileFormat::Csv);

        $this->assertImport($import->refresh(), ImportStatus::Success, total: 3, successful: 3, failed: 0);
        $this->assertSame(3, Transaction::query()->count());
        $this->assertSame(0, ImportLog::query()->count());
    }

    public function test_saves_records_in_chunks(): void
    {
        $rows = ['transaction_id,account_number,transaction_date,amount,currency'];
        foreach (range(1, 7) as $i) {
            $rows[] = $i === 4
                ? "TX-{$i},PL61109010140000071219812874,2025-10-14,0,PLN"
                : "TX-{$i},PL61109010140000071219812874,2025-10-14,{$i}00,PLN";
        }
        $processor = new ImportProcessor(
            $this->app->make(ParserRegistry::class),
            $this->app->make(RecordValidator::class),
            chunkSize: 3,
        );
        $import = Import::factory()->create();

        $result = $processor->process($import, $this->tempFile(implode("\n", $rows)), FileFormat::Csv);

        $this->assertSame(6, $result->successful);
        $this->assertSame(1, $result->failed);
        $this->assertImport($import->refresh(), ImportStatus::Partial, total: 7, successful: 6, failed: 1);
    }

    private function process(string $fixture, FileFormat $format): Import
    {
        $import = Import::factory()->create(['file_name' => $fixture]);
        $this->app->make(ImportProcessor::class)->process($import, $this->fixture($fixture), $format);

        return $import->refresh();
    }

    private function processContents(string $contents, FileFormat $format): Import
    {
        $import = Import::factory()->create();
        $this->app->make(ImportProcessor::class)->process($import, $this->tempFile($contents), $format);

        return $import->refresh();
    }

    private function assertImport(Import $import, ImportStatus $status, int $total, int $successful, int $failed): void
    {
        $this->assertSame($status, $import->status);
        $this->assertSame($total, $import->total_records);
        $this->assertSame($successful, $import->successful_records);
        $this->assertSame($failed, $import->failed_records);
        $this->assertSame($successful, $import->transactions()->count());
    }

    private function fixture(string $name): string
    {
        return base_path('tests/fixtures/'.$name);
    }

    private function tempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
