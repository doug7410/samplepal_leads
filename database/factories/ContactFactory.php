<?php

namespace Database\Factories;

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
            'cell_phone' => $this->faker->phoneNumber(),
            'office_phone' => $this->faker->phoneNumber(),
            'job_title' => $this->faker->jobTitle(),
            'deal_status' => $this->faker->randomElement(['none', 'contacted', 'responded', 'in_progress', 'closed_won', 'closed_lost']),
        ];
    }
}
