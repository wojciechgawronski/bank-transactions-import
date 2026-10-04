<?php

namespace App\Domain\Import\Data;

use App\Domain\Import\Enums\ImportStatus;

final readonly class ImportResult
{
    public function __construct(
        public int $successful,
        public int $failed,
    ) {}

    public function total(): int
    {
        return $this->successful + $this->failed;
    }

    public function status(): ImportStatus
    {
        return ImportStatus::fromCounts($this->successful, $this->failed);
    }
}
