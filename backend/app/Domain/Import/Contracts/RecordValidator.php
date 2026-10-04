<?php

namespace App\Domain\Import\Contracts;

use App\Domain\Import\Data\TransactionRecord;

interface RecordValidator
{
    /**
     * Validates a single record on its own (no file or database context).
     *
     * @return list<string> error messages, empty when the record is valid
     */
    public function validate(TransactionRecord $record): array;
}
