<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface CompanyRepositoryInterface extends RepositoryInterface
{
    /**
     * Find companies by manufacturer
     */
    public function findByManufacturer(string $manufacturer): Collection;

    /**
     * Search companies by name
     */
    public function search(string $searchTerm): Collection;

    /**
     * Get companies with counts of their contacts
     */
    public function getWithContactCounts(): Collection;
}
