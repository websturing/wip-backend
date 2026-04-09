<?php

namespace App\Features\IeLayout\Services;

use App\Features\IeLayout\Repositories\IeLayoutRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class IeLayoutService
{
    protected $repository;

    public function __construct(IeLayoutRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all layouts with optional filtering.
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Find a layout by ID.
     */
    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new layout.
     */
    public function create(array $data)
    {
        // Add auth user context if needed
        $data['created_by_id'] = Auth::id();
        $data['updated_by_id'] = Auth::id();
        
        return $this->repository->create($data);
    }

    /**
     * Update a layout.
     */
    public function update(int $id, array $data)
    {
        // Add auth user context
        $data['updated_by_id'] = Auth::id();
        
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a layout.
     */
    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
