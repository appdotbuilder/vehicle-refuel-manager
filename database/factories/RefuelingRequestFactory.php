<?php

namespace Database\Factories;

use App\Models\RefuelingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RefuelingRequest>
 */
class RefuelingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_do' => 'DO-' . fake()->year() . '-' . fake()->unique()->numberBetween(1000, 9999),
            'nopol' => fake()->regexify('[A-Z]{1,2} [0-9]{1,4} [A-Z]{1,3}'),
            'distributor_name' => 'PT ' . fake()->company(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'completed']),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Indicate that the request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the request is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-1 week', '-1 day'),
            'rejection_reason' => null,
        ]);
    }
}