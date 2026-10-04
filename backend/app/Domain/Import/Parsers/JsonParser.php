<?php

namespace App\Domain\Import\Parsers;

use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Exceptions\InvalidImportFile;
use Generator;
use JsonException;

final class JsonParser implements TransactionParser
{
    public function format(): FileFormat
    {
        return FileFormat::Json;
    }

    /**
     * @return Generator<int, TransactionRecord>
     */
    public function parse(string $path): Generator
    {
        $contents = is_readable($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw InvalidImportFile::unreadable($path);
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw InvalidImportFile::malformed('JSON', $e->getMessage(), $e);
        }

        if (! is_array($data) || ! array_is_list($data)) {
            throw InvalidImportFile::malformed('JSON', 'expected a top-level array of transactions.');
        }

        foreach ($data as $index => $item) {
            yield TransactionRecord::fromArray($index + 1, is_array($item) ? $item : []);
        }
    }
}
