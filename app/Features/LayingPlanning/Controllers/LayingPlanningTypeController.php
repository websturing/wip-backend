<?php

namespace App\Features\LayingPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Features\LayingPlanning\Models\LayingPlanningType;
use Illuminate\Http\Request;

class LayingPlanningTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search');
        $order = strtolower($request->input('order', 'desc'));

        $query = LayingPlanningType::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($order === 'asc') {
            $query->orderBy('type', 'asc');
        } else {
            $query->orderBy('type', 'desc');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($perPage)
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => LayingPlanningType::findOrFail($id)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|unique:laying_planning_types,type',
            'description' => 'nullable|string',
        ]);

        $type = LayingPlanningType::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $type
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $type = LayingPlanningType::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|unique:laying_planning_types,type,' . $type->id,
            'description' => 'nullable|string',
        ]);

        $type->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $type
        ]);
    }

    public function destroy($id)
    {
        LayingPlanningType::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Laying Planning Type deleted'
        ]);
    }
}
