<?php

namespace Tests\Feature\Domain\Import;

use App\Domain\Import\Enums\FileFormat;
use App\Domain\Import\ParserRegistry;
use LogicException;
use Tests\TestCase;

class ParserRegistryTest extends TestCase
{
    public function test_container_registers_a_parser_for_every_format(): void
    {
        $registry = $this->app->make(ParserRegistry::class);

        foreach (FileFormat::cases() as $format) {
            $this->assertSame($format, $registry->for($format)->format());
        }
    }

    public function test_throws_for_format_without_parser(): void
    {
        $this->expectException(LogicException::class);

        (new ParserRegistry([]))->for(FileFormat::Csv);
    }
}
