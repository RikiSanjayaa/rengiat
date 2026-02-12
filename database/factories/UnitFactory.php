<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Unit',
            'order_index' => fake()->numberBetween(1, 50),
            'active' => true,
        ];
    }
}
