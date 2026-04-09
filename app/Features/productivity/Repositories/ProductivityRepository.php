<?php

namespace App\Features\productivity\Repositories;

use App\Features\productivity\Models\Productivity;
use Illuminate\Support\Collection;

class ProductivityRepository
{
    public function getAll(): Collection
    {
        return Productivity::all();
    }

    public function findById(int $id): ?Productivity
    {
        return Productivity::find($id);
    }

    public function create(array $data): Productivity
    {
        return Productivity::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = Productivity::findOrFail($id);
        return $record->update($data);
    }

    public function delete(int $id): bool
    {
        $record = Productivity::findOrFail($id);
        return $record->delete();
    }
}
