<?php

namespace App\Services;

use App\Models\Company;
use App\Strategies\ManufacturerStrategyFactory;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ManufacturersService
{
    protected $strategyFactory;
    
    /**
     * Constructor with dependency injection
     */
    public function __construct(ManufacturerStrategyFactory $strategyFactory = null)
    {
        $this->strategyFactory = $strategyFactory ?? new ManufacturerStrategyFactory();
    }
    
    /**
     * Get representatives from a manufacturer
     *
     * @return Collection Collection of representative company data
     */
    public function getManufacturerReps(string $manufacturerName): Collection
    {
        try {
            $strategy = $this->strategyFactory->create($manufacturerName);

            return $strategy->collectReps();
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Exception("Error collecting representatives: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Save manufacturer representatives to the companies table
     *
     * @return Collection Collection of created/updated Company models
     */
    public function saveRepresentatives(Collection $representatives): Collection
    {
        return $representatives->map(function ($repData) {
            // Identify by unique fields
            $keys = [
                'company_name' => $repData['company_name'],
                'manufacturer' => $repData['manufacturer'],
            ];

            // Remove any ID from the data to prevent conflicts with autoincrement
            if (isset($repData['id'])) {
                unset($repData['id']);
            }

            // Try to find existing company
            $existingCompany = Company::where($keys)->first();

            // If we found an existing company, update it; otherwise create a new one
            if ($existingCompany) {
                $existingCompany->update($repData);

                return $existingCompany;
            } else {
                return Company::create($repData);
            }
        });
    }
}
