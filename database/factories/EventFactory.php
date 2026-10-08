<?php

namespace Database\Factories;

use App\Events\Event;
use App\Events\EventStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'starts_at' => now()->addDay(),
            'email' => fake()->safeEmail(),
            'status' => EventStatus::Confirmed,
            'confirmed_at' => now(),
            'lat' => '39.8220000',
            'lng' => '-7.4910000',
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => EventStatus::Pending,
            'confirmed_at' => null,
        ]);
    }
}
