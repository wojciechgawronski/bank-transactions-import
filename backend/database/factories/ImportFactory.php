<?php

namespace Database\Factories;

use App\Domain\Import\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_name' => fake()->slug(2).'.'.fake()->randomElement(['csv', 'json', 'xml']),
            'status' => ImportStatus::Pending,
            'queue_connection' => 'database',
        ];
    }

    /**
     * Indicate that the import has been processed with the given record counts.
     */
    public function processed(int $successful, int $failed): static
    {
        return $this->state(fn (array $attributes) => [
            'total_records' => $successful + $failed,
            'successful_records' => $successful,
            'failed_records' => $failed,
            'status' => ImportStatus::fromCounts($successful, $failed),
        ]);
    }
}
