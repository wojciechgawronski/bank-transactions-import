<?php

namespace App\Providers;

use App\Domain\Import\Contracts\RecordValidator;
use App\Domain\Import\Contracts\TransactionParser;
use App\Domain\Import\LaravelRecordValidator;
use App\Domain\Import\ParserRegistry;
use App\Domain\Import\Parsers\CsvParser;
use App\Domain\Import\Parsers\JsonParser;
use App\Domain\Import\Parsers\XmlParser;
use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // A new file format is one parser class plus one entry here.
        $this->app->tag([
            CsvParser::class,
            JsonParser::class,
            XmlParser::class,
        ], TransactionParser::class);

        $this->app->bind(RecordValidator::class, LaravelRecordValidator::class);

        $this->app->singleton(ParserRegistry::class);
        $this->app->when(ParserRegistry::class)
            ->needs('$parsers')
            ->giveTagged(TransactionParser::class);
    }
}
