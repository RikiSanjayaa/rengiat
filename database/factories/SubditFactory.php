<?php

namespace Database\Factories;

use App\Models\Subdit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subdit>
 */
class SubditFactory extends Factory
{
    protected $model = Subdit::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'order_index' => fake()->numberBetween(1, 20),
        ];
    }
}
