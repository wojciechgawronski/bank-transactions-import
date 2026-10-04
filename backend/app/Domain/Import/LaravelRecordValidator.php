<?php

namespace App\Domain\Import;

use App\Domain\Import\Contracts\RecordValidator;
use App\Domain\Import\Data\TransactionRecord;
use App\Rules\Currency;
use App\Rules\Iban;
use Illuminate\Contracts\Validation\Factory;

final class LaravelRecordValidator implements RecordValidator
{
    public function __construct(private readonly Factory $validator) {}

    public function validate(TransactionRecord $record): array
    {
        $validator = $this->validator->make($record->toArray(), [
            'transaction_id' => ['required', 'string', 'max:255'],
            'account_number' => ['required', new Iban],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', new Currency],
        ], [
            'amount.integer' => 'The amount must be a whole number of minor units (e.g. 150000 = 1500.00).',
            'amount.min' => 'The amount must be greater than 0.',
        ]);

        return array_values($validator->errors()->all());
    }
}
