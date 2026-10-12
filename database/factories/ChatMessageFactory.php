<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => 'user',
            'content' => fake()->sentence(8),
            'sources' => null,
        ];
    }

    /**
     * Indicate that the message is from assistant.
     */
    public function assistant(): static
    {
        return $this->state(fn () => [
            'role' => 'assistant',
            'sources' => [
                [
                    'id' => 1,
                    'title' => 'HRM Guidelines',
                    'module' => 'HRM',
                    'category' => 'Leave Policy',
                ],
            ],
        ]);
    }
}
