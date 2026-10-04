<?php

namespace App\Domain\Import\Exceptions;

use RuntimeException;
use Throwable;

/**
 * The file as a whole cannot be read (broken syntax, wrong structure).
 * Single bad records are not exceptions: they go to import_logs.
 */
final class InvalidImportFile extends RuntimeException
{
    public static function unreadable(string $path): self
    {
        return new self("Cannot read file {$path}.");
    }

    public static function malformed(string $format, string $reason, ?Throwable $previous = null): self
    {
        return new self("Malformed {$format} file: {$reason}", 0, $previous);
    }

    /**
     * @param  list<string>  $columns
     */
    public static function missingColumns(array $columns): self
    {
        return new self('Missing required columns: '.implode(', ', $columns).'.');
    }
}
