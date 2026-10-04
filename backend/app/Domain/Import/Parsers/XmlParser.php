<?php

namespace App\Domain\Import\Parsers;

use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\Exceptions\InvalidImportFile;
use DOMElement;
use Generator;
use XMLReader;

/**
 * Streams <transactions><transaction>...</transaction></transactions>
 * without loading the whole document into memory.
 */
final class XmlParser implements TransactionParser
{
    private const ROOT = 'transactions';

    private const RECORD = 'transaction';

    public function format(): FileFormat
    {
        return FileFormat::Xml;
    }

    /**
     * @return Generator<int, TransactionRecord>
     */
    public function parse(string $path): Generator
    {
        if (! is_readable($path)) {
            throw InvalidImportFile::unreadable($path);
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $reader = XMLReader::open($path, null, LIBXML_NONET);
            if ($reader === false) {
                throw InvalidImportFile::unreadable($path);
            }

            $position = 0;
            $hasNode = $reader->read();

            while ($hasNode) {
                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    $hasNode = $reader->read();

                    continue;
                }

                if ($reader->depth === 0 && $reader->localName !== self::ROOT) {
                    throw InvalidImportFile::malformed('XML', 'expected <'.self::ROOT."> root element, got <{$reader->localName}>.");
                }

                if ($reader->depth === 1 && $reader->localName === self::RECORD) {
                    $node = $this->expand($reader);
                    yield TransactionRecord::fromArray(++$position, $node !== null ? $this->fields($node) : []);
                    $hasNode = $reader->next();

                    continue;
                }

                $hasNode = $reader->read();
            }

            $this->throwOnLibxmlError();
            $reader->close();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * A truncated record still expands to a partial node and only emits a PHP
     * warning, so the warning is silenced here and the libxml error decides.
     */
    private function expand(XMLReader $reader): ?DOMElement
    {
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            $node = $reader->expand();
        } finally {
            restore_error_handler();
        }

        $this->throwOnLibxmlError();

        return $node instanceof DOMElement ? $node : null;
    }

    private function throwOnLibxmlError(): void
    {
        $error = libxml_get_last_error();
        if ($error !== false) {
            throw InvalidImportFile::malformed('XML', trim($error->message)." (line {$error->line}).");
        }
    }

    /**
     * @return array<string, string>
     */
    private function fields(DOMElement $record): array
    {
        $fields = [];
        foreach ($record->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $fields[$child->localName] = $child->textContent;
            }
        }

        return $fields;
    }
}
