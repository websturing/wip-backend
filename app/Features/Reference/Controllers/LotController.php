<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function index(Request $request)
    {
        $lastImport = Lot::max('updated_at');
        
        $query = Lot::with('glGroup.customer')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('lot_number', 'LIKE', "%$search%")
                  ->orWhereHas('glGroup', function($sq) use ($search) {
                      $sq->where('gl_number', 'LIKE', "%$search%");
                  });
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($request->get('per_page', 50)),
            'meta' => [
                'last_import' => $lastImport
            ]
        ]);
    }

    public function list()
    {
        return response()->json([
            'status' => 'success',
            'data' => Lot::with('glGroup')->get()->map(function ($lot) {
                return [
                    'id' => $lot->id,
                    'lot_code' => $lot->lot_code,
                    'lot_number' => $lot->lot_number,
                    'gl_number' => $lot->glGroup?->gl_number
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gl_id' => 'required|exists:gl_groups,id',
            'lot_number' => 'required|string',
            'gmt_qty' => 'nullable|integer',
            'is_cancelled' => 'boolean',
        ]);

        $lot = Lot::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $lot->load('glGroup')
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Lot::with('glGroup.customer')->findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $lot = Lot::findOrFail($id);
        $validated = $request->validate([
            'gl_id' => 'required|exists:gl_groups,id',
            'lot_number' => 'required|string',
            'gmt_qty' => 'nullable|integer',
            'is_cancelled' => 'boolean',
        ]);

        $lot->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $lot->load('glGroup')
        ]);
    }

    public function destroy($id)
    {
        Lot::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Lot deleted']);
    }
}
