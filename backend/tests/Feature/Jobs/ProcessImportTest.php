<?php

namespace Tests\Feature\Jobs;

use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProcessImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_processes_stored_file_and_removes_it(): void
    {
        Storage::disk('local')->put('imports/valid.csv', (string) file_get_contents(base_path('tests/fixtures/valid.csv')));
        $import = Import::factory()->create();

        ProcessImport::dispatchSync($import, 'imports/valid.csv', FileFormat::Csv);

        $this->assertSame(ImportStatus::Success, $import->refresh()->status);
        $this->assertSame(3, $import->successful_records);
        Storage::disk('local')->assertMissing('imports/valid.csv');
    }

    public function test_marks_import_failed_after_last_retry(): void
    {
        Storage::disk('local')->put('imports/x.csv', 'data');
        $import = Import::factory()->create();

        (new ProcessImport($import, 'imports/x.csv', FileFormat::Csv))->failed(new RuntimeException('database is down'));

        $this->assertSame(ImportStatus::Failed, $import->refresh()->status);
        $this->assertStringContainsString('could not be processed', (string) $import->logs()->value('error_message'));
        Storage::disk('local')->assertMissing('imports/x.csv');
    }
}
