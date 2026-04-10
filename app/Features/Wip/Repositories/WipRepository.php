<?php

namespace App\Features\Wip\Repositories;

use App\Features\Wip\Models\Wip;
use Illuminate\Support\Collection;

class WipRepository
{
    public function getAll(): Collection
    {
        return Wip::all();
    }

    public function findById(int $id): ?Wip
    {
        return Wip::find($id);
    }

    public function create(array $data): Wip
    {
        return Wip::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = Wip::findOrFail($id);
        return $record->update($data);
    }

    public function delete(int $id): bool
    {
        $record = Wip::findOrFail($id);
        return $record->delete();
    }
}
