<?php

namespace App\Features\Lines\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Lines\Models\Line;
use Illuminate\Http\Request;

class LineController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Line::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'location' => 'nullable|string'
        ]);

        $line = Line::create($validated);

        return response()->json(['status' => 'success', 'data' => $line], 201);
    }

    public function destroy($id)
    {
        Line::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Line deleted']);
    }
}
