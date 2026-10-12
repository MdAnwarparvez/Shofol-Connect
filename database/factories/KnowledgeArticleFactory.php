<?php

namespace Database\Factories;

use App\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticle>
 */
class KnowledgeArticleFactory extends Factory
{
    protected $model = KnowledgeArticle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modules = KnowledgeArticle::MODULES;
        $module = fake()->randomElement($modules);

        return [
            'module' => $module,
            'category' => fake()->word() . ' Management',
            'title' => fake()->sentence(5),
            'question' => fake()->sentence(8) . '?',
            'content' => fake()->paragraphs(3, true),
            'keywords' => implode(', ', fake()->words(4)),
            'status' => 'active',
            'created_by' => User::factory()->admin(),
        ];
    }

    /**
     * Set status as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
