<?php

namespace App\Features\IeLayout\Controllers;

use App\Http\Controllers\Controller;
use App\Features\IeLayout\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OperationController extends Controller
{
    /**
     * Display a listing of operations.
     */
    public function index(): JsonResponse
    {
        $data = Operation::orderBy('sequence')->orderBy('name')->get();
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    /**
     * Store a newly created operation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:operations,code',
            'sequence' => 'nullable|integer',
            'machine_type' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
        ]);

        $data = Operation::create($validated);
        return response()->json(['message' => 'Created', 'data' => $data], 201);
    }

    /**
     * Display the specified operation.
     */
    public function show($id): JsonResponse
    {
        $data = Operation::find($id);
        if (!$data) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    /**
     * Update the specified operation.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $operation = Operation::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:operations,code,' . $id,
            'sequence' => 'nullable|integer',
            'machine_type' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
        ]);

        $operation->update($validated);
        return response()->json(['message' => 'Updated', 'data' => $operation]);
    }

    /**
     * Remove the specified operation.
     */
    public function destroy($id): JsonResponse
    {
        $operation = Operation::findOrFail($id);
        $operation->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
