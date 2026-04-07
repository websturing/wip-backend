<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\GlGroup;
use Illuminate\Http\Request;

class GlGroupController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => GlGroup::with('customer')->latest()->paginate(20)
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
            'data' => GlGroup::with('customer', 'lots')->findOrFail($id)
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
