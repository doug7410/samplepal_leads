<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignSegment>
 */
class CampaignSegmentFactory extends Factory
{
    protected $model = CampaignSegment::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fn () => 'Segment '.fake()->numberBetween(1, 10),
            'position' => fake()->numberBetween(1, 5),
            'subject' => null,
            'content' => null,
            'status' => CampaignSegment::STATUS_DRAFT,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignSegment::STATUS_IN_PROGRESS,
            'sent_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignSegment::STATUS_COMPLETED,
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignSegment::STATUS_FAILED,
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => now(),
        ]);
    }

    public function withOverrides(): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
        ]);
    }
}
