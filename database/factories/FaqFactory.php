<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faq>
 */
class FaqFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Faq>
     */
    protected $model = Faq::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $question = fake()->sentence(6) . '?';

        return [
            'shofol_module_id' => ShofolModule::factory(),
            'knowledge_category_id' => null,
            'question' => $question,
            'slug' => Str::slug($question),
            'answer' => fake()->paragraph(),
            'keywords' => implode(', ', fake()->words(4)),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the FAQ is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
