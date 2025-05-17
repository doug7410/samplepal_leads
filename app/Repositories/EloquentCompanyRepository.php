<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Support\Collection;

class EloquentCompanyRepository extends EloquentRepository implements CompanyRepositoryInterface
{
    /**
     * EloquentCompanyRepository constructor.
     */
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    /**
     * Find companies by manufacturer
     */
    public function findByManufacturer(string $manufacturer): Collection
    {
        return $this->model->where('manufacturer', $manufacturer)->get();
    }

    /**
     * Search companies by name
     */
    public function search(string $searchTerm): Collection
    {
        return $this->model
            ->where('company_name', 'like', "%{$searchTerm}%")
            ->orWhere('manufacturer', 'like', "%{$searchTerm}%")
            ->orWhere('city_or_region', 'like', "%{$searchTerm}%")
            ->orWhere('state', 'like', "%{$searchTerm}%")
            ->get();
    }

    /**
     * Get companies with counts of their contacts
     */
    public function getWithContactCounts(): Collection
    {
        return $this->model->withCount('contacts')->get();
    }
}
