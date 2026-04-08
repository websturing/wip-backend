<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function index()
    {
        $lastImport = Lot::max('updated_at');

        return response()->json([
            'status' => 'success',
            'data' => Lot::with('glGroup.customer')->latest()->paginate(20),
            'meta' => [
                'last_import' => $lastImport
            ]
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
