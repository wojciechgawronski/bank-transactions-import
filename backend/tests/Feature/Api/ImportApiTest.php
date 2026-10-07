<?php

namespace Tests\Feature\Api;

use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Http\Requests\StoreImportRequest;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_upload_stores_file_and_queues_import(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/imports', ['file' => $this->fixtureUpload('valid.csv')]);

        $response->assertAccepted()
            ->assertJsonPath('data.file_name', 'valid.csv')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_records', 0);

        $import = Import::query()->sole();
        Queue::assertPushed(ProcessImport::class, function (ProcessImport $job) use ($import) {
            Storage::disk('local')->assertExists($job->path);

            return $job->import->is($import) && $job->format === FileFormat::Csv;
        });
    }

    public function test_upload_records_queue_connection_and_dispatches_on_it(): void
    {
        Queue::fake();
        config(['queue.default' => 'rabbitmq']);

        $this->postJson('/api/imports', ['file' => $this->fixtureUpload('valid.csv')])
            ->assertAccepted()
            ->assertJsonPath('data.queue_connection', 'rabbitmq');

        $this->assertSame('rabbitmq', Import::query()->sole()->queue_connection);
        Queue::assertPushed(ProcessImport::class, fn (ProcessImport $job) => $job->connection === 'rabbitmq');
    }

    public function test_uploaded_file_is_processed_and_removed(): void
    {
        // phpunit.xml uses the sync queue, so the job runs during the request.
        $this->postJson('/api/imports', ['file' => $this->fixtureUpload('mixed.json')])->assertAccepted()
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.successful_records', 2)
            ->assertJsonPath('data.failed_records', 4);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_detects_format_from_extension_case_insensitively(): void
    {
        Queue::fake();

        $this->postJson('/api/imports', ['file' => $this->fixtureUpload('valid.xml', 'EXPORT.XML')])->assertAccepted();

        Queue::assertPushed(ProcessImport::class, fn (ProcessImport $job) => $job->format === FileFormat::Xml);
    }

    public function test_upload_requires_a_file(): void
    {
        $this->postJson('/api/imports')->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_unsupported_extension(): void
    {
        $this->postJson('/api/imports', ['file' => UploadedFile::fake()->create('report.pdf', 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertSame(0, Import::query()->count());
    }

    public function test_upload_rejects_too_large_file(): void
    {
        $file = UploadedFile::fake()->create('big.csv', StoreImportRequest::MAX_SIZE_KB + 1);

        $this->postJson('/api/imports', ['file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_errors_are_json_even_without_accept_header(): void
    {
        $this->post('/api/imports')->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->get('/api/imports/999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_lists_imports_newest_first_with_pagination(): void
    {
        $imports = Import::factory()->count(3)->processed(successful: 5, failed: 1)->create();

        $this->getJson('/api/imports?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $imports[2]->id)
            ->assertJsonPath('data.0.total_records', 6)
            ->assertJsonPath('data.0.status', 'partial')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonMissingPath('data.0.logs');
    }

    public function test_shows_import_with_its_error_logs(): void
    {
        $import = Import::factory()->processed(successful: 1, failed: 2)->create();
        $logs = ImportLog::factory()->count(2)->for($import)->create();

        $this->getJson("/api/imports/{$import->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.status', ImportStatus::Partial->value)
            ->assertJsonCount(2, 'data.logs')
            ->assertJsonPath('data.logs.0.transaction_id', $logs[0]->transaction_id)
            ->assertJsonPath('data.logs.0.error_message', $logs[0]->error_message);
    }

    public function test_paginates_logs_of_an_import(): void
    {
        $import = Import::factory()->create();
        ImportLog::factory()->count(3)->for($import)->create();
        ImportLog::factory()->create();

        $this->getJson("/api/imports/{$import->id}/logs?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'transaction_id', 'error_message', 'created_at']]]);
    }

    public function test_unknown_import_returns_404(): void
    {
        $this->getJson('/api/imports/999')->assertNotFound();
        $this->getJson('/api/imports/999/logs')->assertNotFound();
    }

    private function fixtureUpload(string $fixture, ?string $name = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name ?? $fixture,
            (string) file_get_contents(base_path('tests/fixtures/'.$fixture)),
        );
    }
}
