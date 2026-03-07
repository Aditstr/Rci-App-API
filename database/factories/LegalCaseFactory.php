<?php

namespace Database\Factories;

use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LegalCase>
 */
class LegalCaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LegalCase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_number' => LegalCase::generateCaseNumber(),
            'client_id' => User::factory()->create(['role' => 'client'])->id,
            'expert_id' => User::factory()->create(['role' => 'lawyer'])->id,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['corporate', 'criminal', 'family', 'property', 'labor', 'general']),
            'status' => $this->faker->randomElement(['submitted', 'ai_analyzing', 'bidding', 'active', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'ai_complexity_score' => $this->faker->numberBetween(1, 10),
            'ai_estimated_cost' => $this->faker->randomFloat(2, 500000, 50000000),
            'ai_review_result' => ['summary' => $this->faker->sentence],
            'submitted_at' => now(),
            'is_marketplace' => $this->faker->boolean(),
            'proposed_fee' => $this->faker->randomFloat(2, 500000, 50000000),
        ];
    }
}
