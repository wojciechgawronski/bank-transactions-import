<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            'transaction_id' => fake()->unique()->uuid(),
            'account_number' => fake()->iban('PL'),
            'transaction_date' => fake()->date(),
            'amount' => fake()->numberBetween(1, 1_000_000),
            'currency' => fake()->randomElement(['PLN', 'EUR', 'USD']),
        ];
    }
}
