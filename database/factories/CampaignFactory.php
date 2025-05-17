<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'subject' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'from_email' => fake()->email(),
            'from_name' => fake()->name(),
            'reply_to' => fake()->email(),
            'status' => Campaign::STATUS_DRAFT,
            'user_id' => User::factory(),
            'filter_criteria' => ['manufacturer' => fake()->randomElement(['Acuity', 'Cooper', 'Signify'])],
        ];
    }

    /**
     * Indicate that the campaign is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * Indicate that the campaign is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_IN_PROGRESS,
            'scheduled_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_COMPLETED,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-1 week'),
            'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
