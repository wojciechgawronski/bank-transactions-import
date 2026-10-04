<?php

namespace Tests\Unit\Domain\Import\Enums;

use App\Domain\Import\Enums\FileFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class FileFormatTest extends TestCase
{
    /**
     * @return array<string, array{string, FileFormat}>
     */
    public static function extensionProvider(): array
    {
        return [
            'csv' => ['csv', FileFormat::Csv],
            'json' => ['json', FileFormat::Json],
            'xml' => ['xml', FileFormat::Xml],
            'uppercase' => ['CSV', FileFormat::Csv],
            'leading dot' => ['.xml', FileFormat::Xml],
        ];
    }

    #[DataProvider('extensionProvider')]
    public function test_from_extension(string $extension, FileFormat $expected): void
    {
        $this->assertSame($expected, FileFormat::fromExtension($extension));
    }

    public function test_from_extension_rejects_unsupported_format(): void
    {
        $this->expectException(ValueError::class);

        FileFormat::fromExtension('txt');
    }
}
