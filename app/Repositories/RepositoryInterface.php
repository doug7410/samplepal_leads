<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Get all records
     *
     * @param  array  $columns  Columns to select
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     *
     * @param  int  $perPage  Number of items per page
     * @param  array  $columns  Columns to select
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update an existing record
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete a record
     */
    public function delete(Model $model): bool;

    /**
     * Find a record by its primary key
     *
     * @param  mixed  $id
     */
    public function find($id, array $columns = ['*']): ?Model;

    /**
     * Find a record by criteria or create it
     */
    public function findOrCreate(array $criteria, array $data): Model;

    /**
     * Get records based on criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;
}
