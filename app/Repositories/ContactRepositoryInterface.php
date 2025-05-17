<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface ContactRepositoryInterface extends RepositoryInterface
{
    /**
     * Find contacts by company
     */
    public function findByCompany(int $companyId): Collection;

    /**
     * Find contacts by filter criteria
     */
    public function findByFilters(array $filters): Collection;

    /**
     * Search contacts by name or email
     */
    public function search(string $searchTerm): Collection;

    /**
     * Update contact status (deal status, has been contacted, etc.)
     */
    public function updateStatus(int $contactId, array $statusData): bool;
}
