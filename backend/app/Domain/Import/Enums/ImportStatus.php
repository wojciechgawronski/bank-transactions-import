<?php

namespace App\Domain\Import\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';

    /**
     * Final status of a processed import. An empty file counts as failed.
     */
    public static function fromCounts(int $successful, int $failed): self
    {
        return match (true) {
            $successful === 0 => self::Failed,
            $failed === 0 => self::Success,
            default => self::Partial,
        };
    }
}
