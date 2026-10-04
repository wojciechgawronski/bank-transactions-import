<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportLog>
 */
class ImportLogFactory extends Factory
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
            'transaction_id' => fake()->uuid(),
            'error_message' => fake()->sentence(),
        ];
    }
}
