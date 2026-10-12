<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'subject' => fake()->sentence(5),
            'description' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(SupportTicket::CATEGORIES),
            'priority' => fake()->randomElement(SupportTicket::PRIORITIES),
            'status' => 'Open',
            'admin_response' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Set status to Resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'Resolved',
            'admin_response' => 'The issue has been verified and resolved by support.',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Set status to In Progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'In Progress',
            'admin_response' => 'Our support team is currently investigating your ticket.',
        ]);
    }

    /**
     * Set priority to High.
     */
    public function highPriority(): static
    {
        return $this->state(fn () => [
            'priority' => 'High',
        ]);
    }
}
