<?php

namespace App\Domain\Import\Data;

/**
 * One raw record read from an import file, before validation.
 *
 * Values are kept as trimmed strings (or null when missing/empty) so that every
 * format reaches the validator in the same shape.
 */
final readonly class TransactionRecord
{
    public const FIELDS = [
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
    ];

    public function __construct(
        public int $position,
        public ?string $transactionId,
        public ?string $accountNumber,
        public ?string $transactionDate,
        public ?string $amount,
        public ?string $currency,
    ) {}

    /**
     * @param  int  $position  1-based number of the record in the file
     * @param  array<array-key, mixed>  $fields  raw values keyed by field name
     */
    public static function fromArray(int $position, array $fields): self
    {
        return new self(
            $position,
            self::normalize($fields['transaction_id'] ?? null),
            self::normalize($fields['account_number'] ?? null),
            self::normalize($fields['transaction_date'] ?? null),
            self::normalize($fields['amount'] ?? null),
            self::normalize($fields['currency'] ?? null),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'account_number' => $this->accountNumber,
            'transaction_date' => $this->transactionDate,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    private static function normalize(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
