<?php

namespace App\Domain\Import\Parsers;

use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Exceptions\InvalidImportFile;
use Generator;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

final class CsvParser implements TransactionParser
{
    public function format(): FileFormat
    {
        return FileFormat::Csv;
    }

    /**
     * @return Generator<int, TransactionRecord>
     */
    public function parse(string $path): Generator
    {
        if (! is_readable($path)) {
            throw InvalidImportFile::unreadable($path);
        }

        try {
            $csv = Reader::from($path)->setHeaderOffset(0)->skipEmptyRecords();
            $header = $csv->getHeader();
            $records = $csv->getRecords();
        } catch (CsvException $e) {
            throw InvalidImportFile::malformed('CSV', $e->getMessage(), $e);
        }

        $missing = array_values(array_diff(TransactionRecord::FIELDS, array_map('trim', $header)));
        if ($missing !== []) {
            throw InvalidImportFile::missingColumns($missing);
        }

        $position = 0;
        foreach ($records as $row) {
            yield TransactionRecord::fromArray(++$position, array_combine(array_map('trim', array_keys($row)), $row));
        }
    }
}
