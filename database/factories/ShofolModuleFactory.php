<?php

namespace Database\Factories;

use App\Models\ShofolModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShofolModule>
 */
class ShofolModuleFactory extends Factory
{
    protected $model = ShofolModule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8));

        return [
            'name' => ucfirst($name),
            'code' => $code . fake()->unique()->numberBetween(10, 999),
            'icon' => 'bi-grid',
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    /**
     * Indicate that the module is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
