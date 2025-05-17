<?php

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Support\Collection;

class EloquentContactRepository extends EloquentRepository implements ContactRepositoryInterface
{
    /**
     * EloquentContactRepository constructor.
     */
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    /**
     * Find contacts by company
     */
    public function findByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    /**
     * Find contacts by filter criteria
     */
    public function findByFilters(array $filters): Collection
    {
        $query = $this->model->newQuery();

        // Handle company filter
        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Handle state filter
        if (isset($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        // Handle region filter
        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        // Handle deal status filter
        if (isset($filters['deal_status'])) {
            $query->where('deal_status', $filters['deal_status']);
        }

        // Handle contacted filter
        if (isset($filters['has_been_contacted'])) {
            $query->where('has_been_contacted', $filters['has_been_contacted']);
        }

        // Handle relevance score filter
        if (isset($filters['min_relevance_score'])) {
            $query->where('relevance_score', '>=', $filters['min_relevance_score']);
        }

        // Handle manufacturer filter
        if (isset($filters['manufacturer'])) {
            $query->whereHas('company', function ($q) use ($filters) {
                $q->where('manufacturer', $filters['manufacturer']);
            });
        }

        return $query->get();
    }

    /**
     * Search contacts by name or email
     */
    public function search(string $searchTerm): Collection
    {
        return $this->model
            ->where('first_name', 'like', "%{$searchTerm}%")
            ->orWhere('last_name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%")
            ->orWhere('job_title', 'like', "%{$searchTerm}%")
            ->get();
    }

    /**
     * Update contact status (deal status, has been contacted, etc.)
     */
    public function updateStatus(int $contactId, array $statusData): bool
    {
        $contact = $this->find($contactId);

        if (! $contact) {
            return false;
        }

        // Update status fields
        foreach ($statusData as $field => $value) {
            if (in_array($field, ['deal_status', 'has_been_contacted', 'notes'])) {
                $contact->{$field} = $value;
            }
        }

        return $contact->save();
    }
}
