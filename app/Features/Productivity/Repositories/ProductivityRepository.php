<?php

namespace App\Features\Productivity\Repositories;

use App\Features\Productivity\Models\Productivity;

class ProductivityRepository
{
    public function getAll($date = null)
    {
        $query = Productivity::with(['line', 'lots.glGroup.customer']);
        
        if ($date) {
            $query->whereDate('date', $date);
        }

        $data = $query->latest()->get();

        $data->each(function($p) {
            $p->lots->each(function($l) {
                if ($l->pivot && $l->pivot->media) {
                    $l->pivot->media_url = $l->pivot->media->url;
                }
            });
        });

        return $data;
    }

    public function findById($id)
    {
        $item = Productivity::with(['line', 'lots.glGroup.customer'])->findOrFail($id);
        $item->lots->each(function($l) {
            if ($l->pivot && $l->pivot->media) {
                $l->pivot->media_url = $l->pivot->media->url;
            }
        });
        return $item;
    }

    public function create(array $data)
    {
        return Productivity::updateOrCreate(
            [
                'line_id' => $data['line_id'],
                'lot_id' => $data['lot_id'],
                'date' => $data['date']
            ],
            $data
        );
    }

    public function update($id, array $data)
    {
        $productivity = Productivity::findOrFail($id);
        $productivity->update($data);
        return $productivity;
    }

    public function delete($id)
    {
        $productivity = Productivity::findOrFail($id);
        return $productivity->delete();
    }
}
