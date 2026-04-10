<?php

namespace App\Features\Wip\Services;

use App\Features\Wip\Repositories\WipRepository;
use Illuminate\Support\Facades\Auth;

class WipService
{
    protected $repository;

    public function __construct(WipRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        $data['created_by_id'] = Auth::id();
        $data['updated_by_id'] = Auth::id();
        return $this->repository->create($data);
    }

    public function update(int $id, array $data)
    {
        $data['updated_by_id'] = Auth::id();
        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
