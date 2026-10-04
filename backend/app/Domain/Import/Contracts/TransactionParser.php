<?php

namespace App\Domain\Import\Contracts;

use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Exceptions\InvalidImportFile;

interface TransactionParser
{
    public function format(): FileFormat;

    /**
     * Streams records from the file. Records are yielded lazily, so a file that
     * breaks halfway may throw after some records were already returned.
     *
     * @return iterable<int, TransactionRecord>
     *
     * @throws InvalidImportFile
     */
    public function parse(string $path): iterable;
}
