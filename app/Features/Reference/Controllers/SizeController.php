<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('flat')) {
            $sizes = Size::select('size')->pluck('size');
            return response()->json([
                'status' => 'success',
                'data' => $sizes
            ]);
        }

        $query = Size::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('size', 'like', "%{$search}%");
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
            'size' => 'required|string|unique:sizes,size',
        ]);

        $size = Size::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $size
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Size::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $size = Size::findOrFail($id);
        $validated = $request->validate([
            'size' => 'required|string|unique:sizes,size,' . $id,
        ]);
        $size->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $size
        ]);
    }

    public function destroy($id)
    {
        Size::findOrFail($id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Size deleted'
        ]);
    }
}
