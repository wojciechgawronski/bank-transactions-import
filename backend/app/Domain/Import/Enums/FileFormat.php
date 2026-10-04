<?php

namespace App\Domain\Import\Enums;

enum FileFormat: string
{
    case Csv = 'csv';
    case Json = 'json';
    case Xml = 'xml';

    /**
     * @throws \ValueError when the extension is not supported
     */
    public static function fromExtension(string $extension): self
    {
        return self::from(strtolower(ltrim($extension, '.')));
    }
}
