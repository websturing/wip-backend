<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\GlGroup;
use Illuminate\Http\Request;

class GlGroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('flat')) {
            $lots = \App\Features\Reference\Models\Lot::whereNotNull('lot_code')->select('id', 'lot_code as name', 'style_no', 'gmt_qty', 'order_date', 'brand', 'delivery_date')
            ->get();
            return response()->json([
                'status' => 'success',
                'data' => $lots
            ]);
        }

        $query = GlGroup::with([
            'customer', 
            'lots', 
            'colors.aliases', 
            'fabrics.aliases'
        ]);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('gl_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->input('per_page', 20);

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate($perPage)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'gl_number' => 'required|string',
        ]);

        $glGroup = GlGroup::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $glGroup
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => GlGroup::with([
                'customer', 
                'lots.color', 
                'lots.fabric', 
                'colors.aliases', 
                'fabrics.aliases'
            ])->findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $glGroup = GlGroup::findOrFail($id);
        $glGroup->update($request->validate([
            'customer_id' => 'required|exists:customers,id',
            'gl_number' => 'required|string',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $glGroup
        ]);
    }

    public function destroy($id)
    {
        GlGroup::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'GL Group deleted']);
    }
}
