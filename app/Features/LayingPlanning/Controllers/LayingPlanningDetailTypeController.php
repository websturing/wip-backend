<?php

namespace App\Features\LayingPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Features\LayingPlanning\Models\LayingPlanningDetailType;
use Illuminate\Http\Request;

class LayingPlanningDetailTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search');
        $order = strtolower($request->input('order', 'desc'));

        $query = LayingPlanningDetailType::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('detail_type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($order === 'asc') {
            $query->orderBy('detail_type', 'asc');
        } else {
            $query->orderBy('detail_type', 'desc');
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
            'data' => LayingPlanningDetailType::findOrFail($id)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'detail_type' => 'required|string|unique:laying_planning_detail_types,detail_type',
            'description' => 'nullable|string',
        ]);

        $type = LayingPlanningDetailType::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $type
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $type = LayingPlanningDetailType::findOrFail($id);

        $validated = $request->validate([
            'detail_type' => 'required|string|unique:laying_planning_detail_types,detail_type,' . $type->id,
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
        LayingPlanningDetailType::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Laying Planning Detail Type deleted'
        ]);
    }
}
