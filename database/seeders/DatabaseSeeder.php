<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Run the Kiwi Tech Lab test seeder
        $this->call(KiwiTestSeeder::class);

        // Run the Company Campaign test seeder
        $this->call(CampaignTestSeeder::class);
    }
}
