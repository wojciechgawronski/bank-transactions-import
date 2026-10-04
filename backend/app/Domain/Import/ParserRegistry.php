<?php

namespace App\Domain\Import;

use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\Enums\FileFormat;
use LogicException;

final class ParserRegistry
{
    /**
     * @var array<string, TransactionParser>
     */
    private array $parsers = [];

    /**
     * @param  iterable<TransactionParser>  $parsers
     */
    public function __construct(iterable $parsers)
    {
        foreach ($parsers as $parser) {
            $this->parsers[$parser->format()->value] = $parser;
        }
    }

    public function for(FileFormat $format): TransactionParser
    {
        return $this->parsers[$format->value]
            ?? throw new LogicException("No parser registered for {$format->value} files.");
    }
}
