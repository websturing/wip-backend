<?php

namespace App\Features\Production\Repositories;

use App\Features\Production\Models\Production;
use Illuminate\Support\Facades\DB;

class ProductionRepository
{
    public function getAll()
    {
        return Production::with(['line', 'items.sizes', 'creator'])->latest('production_date')->paginate(20);
    }

    public function findById($id)
    {
        return Production::with(['line', 'items.sizes', 'creator'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $production = Production::create([
                'production_date' => $data['production_date'],
                'line_id' => $data['line_id'],
                'created_by' => $data['created_by'],
            ]);

            foreach ($data['items'] as $itemData) {
                $item = $production->items()->create([
                    'gl_number' => $itemData['gl_number'],
                    'color' => $itemData['color'],
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->sizes()->create([
                        'size_name' => $sizeData['size_name'],
                        'qty_input' => $sizeData['qty_input'] ?? 0,
                        'qty_output' => $sizeData['qty_output'] ?? 0,
                    ]);
                }
            }

            return $production;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $production = Production::findOrFail($id);
            $production->update([
                'production_date' => $data['production_date'],
                'line_id' => $data['line_id'],
                'updated_by' => $data['updated_by'],
            ]);

            // For simplicity in this specialized WIP app, we replace items on update 
            // OR we could do a more complex sync. 
            // Usually for specialized production entries, re-saving is fine.
            $production->items()->delete(); 

            foreach ($data['items'] as $itemData) {
                $item = $production->items()->create([
                    'gl_number' => $itemData['gl_number'],
                    'color' => $itemData['color'],
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->sizes()->create([
                        'size_name' => $sizeData['size_name'],
                        'qty_input' => $sizeData['qty_input'] ?? 0,
                        'qty_output' => $sizeData['qty_output'] ?? 0,
                    ]);
                }
            }

            return $production;
        });
    }

    public function delete($id)
    {
        $production = Production::findOrFail($id);
        return $production->delete();
    }
}
