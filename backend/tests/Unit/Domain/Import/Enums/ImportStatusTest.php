<?php

namespace Tests\Unit\Domain\Import\Enums;

use App\Domain\Import\Enums\ImportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImportStatusTest extends TestCase
{
    /**
     * @return array<string, array{int, int, ImportStatus}>
     */
    public static function countsProvider(): array
    {
        return [
            'all records valid' => [10, 0, ImportStatus::Success],
            'some records invalid' => [7, 3, ImportStatus::Partial],
            'all records invalid' => [0, 5, ImportStatus::Failed],
            'empty file' => [0, 0, ImportStatus::Failed],
        ];
    }

    #[DataProvider('countsProvider')]
    public function test_from_counts(int $successful, int $failed, ImportStatus $expected): void
    {
        $this->assertSame($expected, ImportStatus::fromCounts($successful, $failed));
    }
}
