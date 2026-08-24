<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-2 months');

        return [
            'user_id' => User::factory(),
            'date' => $date->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_minutes' => 30,
            'description' => fake()->sentence(),
        ];
    }
}
