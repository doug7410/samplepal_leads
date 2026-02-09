<?php

namespace Database\Factories;

use App\Enums\DealStatus;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_source' => 'scraped',
            'cell_phone' => $this->faker->phoneNumber(),
            'office_phone' => $this->faker->phoneNumber(),
            'job_title' => $this->faker->jobTitle(),
            'job_title_category' => $this->faker->optional()->randomElement(['Principal', 'Sales', 'Operations', 'Project Manager']),
            'deal_status' => $this->faker->randomElement(DealStatus::cases()),
            'is_enrichment_unusable' => false,
        ];
    }
}
