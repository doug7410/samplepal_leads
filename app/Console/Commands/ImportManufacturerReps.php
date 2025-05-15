<?php

namespace App\Console\Commands;

use App\Services\ManufacturersService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportManufacturerReps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-manufacturer-reps {manufacturer : The manufacturer name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import companies from a specific manufacturer';

    /**
     * Execute the console command.
     */
    public function handle(ManufacturersService $manufacturersService): int
    {
        $manufacturerName = $this->argument('manufacturer');
        $this->info("Importing companies from manufacturer: {$manufacturerName}");

        try {
            // Get the manufacturer representatives
            $this->info("Collecting representatives from {$manufacturerName}...");
            $representatives = $manufacturersService->getManufacturerReps($manufacturerName);
            
            $this->info("Found " . $representatives->count() . " representatives.");
            
            // Save the representatives to the database
            $this->info("Saving representatives to the database...");
            $savedCompanies = $manufacturersService->saveRepresentatives($representatives);
            
            // Output success message
            $this->newLine();
            $this->info("Successfully imported {$savedCompanies->count()} companies from {$manufacturerName}.");
            
            // List the imported companies
            $this->table(
                ['Company Name', 'Phone', 'Email', 'Location'],
                $savedCompanies->map(function ($company) {
                    return [
                        'name' => $company->company_name,
                        'phone' => $company->company_phone,
                        'email' => $company->email,
                        'location' => "{$company->city_or_region}, {$company->state}"
                    ];
                })
            );
            
            return Command::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            $this->newLine();
            $this->info("Supported manufacturer: 'signify'");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}