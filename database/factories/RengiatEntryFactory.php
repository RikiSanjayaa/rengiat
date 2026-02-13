<?php

namespace Database\Factories;

use App\Models\RengiatEntry;
use App\Models\Subdit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RengiatEntry>
 */
class RengiatEntryFactory extends Factory
{
    protected $model = RengiatEntry::class;

    public function definition(): array
    {
        return [
            'subdit_id' => Subdit::factory(),
            'unit_id' => Unit::factory(),
            'entry_date' => fake()->dateTimeBetween('-10 days', 'now')->format('Y-m-d'),
            'time_start' => fake()->optional()->time('H:i'),
            'description' => fake()->boolean()
                ? fake()->sentence(12)
                : fake()->sentence(8).' (No. Kasus: '.fake()->bothify('LP/***/####').')',
            'case_number' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
