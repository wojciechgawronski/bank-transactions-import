<?php

namespace App\Jobs;

use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\ImportProcessor;
use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Thin queue wrapper around ImportProcessor. The uploaded file is kept until
 * the import is finished, so a retried job can read it again.
 */
class ProcessImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly Import $import,
        public readonly string $path,
        public readonly FileFormat $format,
    ) {}

    public function handle(ImportProcessor $processor): void
    {
        $processor->process($this->import, Storage::disk('local')->path($this->path), $this->format);

        Storage::disk('local')->delete($this->path);
    }

    /**
     * Called once all retries are used up.
     */
    public function failed(?Throwable $exception): void
    {
        app(ImportProcessor::class)->fail($this->import, 'The file could not be processed. Please try again.');

        Storage::disk('local')->delete($this->path);
    }
}
