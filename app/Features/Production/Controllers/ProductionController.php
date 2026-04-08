<?php

namespace App\Features\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Production\Models\Production;
use App\Features\Lines\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Production::with(['line', 'items.lot', 'items.details'])->latest()->paginate(10)
        ]);
    }

    public function lines()
    {
        return response()->json([
            'status' => 'success',
            'data' => Line::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'production_date' => 'required|date',
            'items' => 'required|array',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.color' => 'required|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'required|integer',
            'items.*.sizes.*.qty_output' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated) {
            $production = Production::create([
                'line_id' => $validated['line_id'],
                'production_date' => $validated['production_date']
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $production->items()->create([
                    'lot_id' => $itemData['lot_id'],
                    'color' => $itemData['color']
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->details()->create($sizeData);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $production->load(['line', 'items.lot', 'items.details'])
            ], 201);
        });
    }

    public function destroy($id)
    {
        Production::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Production log deleted']);
    }
}
