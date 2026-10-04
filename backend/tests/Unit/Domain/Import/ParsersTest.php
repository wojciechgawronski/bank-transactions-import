<?php

namespace Tests\Unit\Domain\Import;

use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\Data\TransactionRecord;
use App\Domain\Import\Exceptions\InvalidImportFile;
use App\Domain\Import\Parsers\CsvParser;
use App\Domain\Import\Parsers\JsonParser;
use App\Domain\Import\Parsers\XmlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParsersTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->tempFiles);
        parent::tearDown();
    }

    /**
     * @return array<string, array{TransactionParser, string}>
     */
    public static function parserProvider(): array
    {
        return [
            'csv' => [new CsvParser, 'csv'],
            'json' => [new JsonParser, 'json'],
            'xml' => [new XmlParser, 'xml'],
        ];
    }

    #[DataProvider('parserProvider')]
    public function test_parses_valid_file(TransactionParser $parser, string $extension): void
    {
        $records = $this->parse($parser, $this->fixture("valid.{$extension}"));

        $this->assertCount(3, $records);
        $this->assertEquals(new TransactionRecord(
            position: 1,
            transactionId: '550e8400-e29b-41d4-a716-446655440000',
            accountNumber: 'PL61109010140000071219812874',
            transactionDate: '2025-10-14',
            amount: '150000',
            currency: 'PLN',
        ), $records[0]);
        $this->assertSame([1, 2, 3], array_map(fn (TransactionRecord $r) => $r->position, $records));
    }

    #[DataProvider('parserProvider')]
    public function test_returns_invalid_records_as_is_for_validation(TransactionParser $parser, string $extension): void
    {
        $records = $this->parse($parser, $this->fixture("mixed.{$extension}"));

        $this->assertCount(6, $records);
        $this->assertSame('0', $records[2]->amount);
        $this->assertNull($records[3]->accountNumber);
        $this->assertSame('EURO', $records[4]->currency);
    }

    #[DataProvider('parserProvider')]
    public function test_rejects_corrupted_file(TransactionParser $parser, string $extension): void
    {
        $this->expectException(InvalidImportFile::class);

        $this->parse($parser, $this->fixture("corrupted.{$extension}"));
    }

    #[DataProvider('parserProvider')]
    public function test_rejects_missing_file(TransactionParser $parser): void
    {
        $this->expectException(InvalidImportFile::class);

        $this->parse($parser, '/nonexistent/file');
    }

    public function test_csv_with_header_only_has_no_records(): void
    {
        $file = $this->tempFile(implode(',', TransactionRecord::FIELDS)."\n");

        $this->assertSame([], $this->parse(new CsvParser, $file));
    }

    public function test_csv_accepts_columns_in_any_order_and_skips_blank_lines(): void
    {
        $file = $this->tempFile("currency,amount,transaction_date,account_number,transaction_id\nPLN,100,2025-10-14,PL61109010140000071219812874,TX-1\n\n");

        $records = $this->parse(new CsvParser, $file);

        $this->assertCount(1, $records);
        $this->assertSame('TX-1', $records[0]->transactionId);
        $this->assertSame('PLN', $records[0]->currency);
    }

    public function test_csv_rejects_empty_file(): void
    {
        $this->expectException(InvalidImportFile::class);

        $this->parse(new CsvParser, $this->tempFile(''));
    }

    public function test_json_rejects_object_instead_of_list(): void
    {
        $this->expectException(InvalidImportFile::class);

        $this->parse(new JsonParser, $this->tempFile('{"transactions": []}'));
    }

    public function test_json_turns_non_object_items_into_empty_records(): void
    {
        $records = $this->parse(new JsonParser, $this->tempFile('["oops", 42]'));

        $this->assertCount(2, $records);
        $this->assertSame(array_fill_keys(TransactionRecord::FIELDS, null), $records[0]->toArray());
    }

    public function test_xml_rejects_unexpected_root_element(): void
    {
        $this->expectException(InvalidImportFile::class);

        $this->parse(new XmlParser, $this->tempFile('<payments><transaction/></payments>'));
    }

    public function test_xml_yields_records_read_before_the_file_breaks(): void
    {
        $records = [];

        try {
            foreach ((new XmlParser)->parse($this->fixture('corrupted.xml')) as $record) {
                $records[] = $record;
            }
            $this->fail('Expected InvalidImportFile.');
        } catch (InvalidImportFile) {
            $this->assertCount(1, $records);
        }
    }

    public function test_xml_does_not_resolve_external_entities(): void
    {
        $xml = '<?xml version="1.0"?><!DOCTYPE t [<!ENTITY x SYSTEM "file:///etc/passwd">]>'
            .'<transactions><transaction><transaction_id>&x;</transaction_id></transaction></transactions>';

        $records = $this->parse(new XmlParser, $this->tempFile($xml));

        $this->assertNull($records[0]->transactionId);
    }

    /**
     * @return list<TransactionRecord>
     */
    private function parse(TransactionParser $parser, string $path): array
    {
        return iterator_to_array($parser->parse($path), false);
    }

    private function fixture(string $name): string
    {
        return dirname(__DIR__, 3).'/fixtures/'.$name;
    }

    private function tempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
