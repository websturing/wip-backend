<?php

namespace App\Features\Lines\Repositories;

use App\Features\Lines\Models\Line;

class LineRepository
{
    public function getAll()
    {
        return Line::latest()->paginate(20);
    }

    public function findById($id)
    {
        return Line::findOrFail($id);
    }

    public function create(array $data)
    {
        return Line::create($data);
    }

    public function update($id, array $data)
    {
        $line = Line::findOrFail($id);
        $line->update($data);
        return $line;
    }

    public function delete($id)
    {
        $line = Line::findOrFail($id);
        return $line->delete();
    }
}
