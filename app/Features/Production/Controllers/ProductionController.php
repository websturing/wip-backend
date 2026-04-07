<?php

namespace App\Features\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Production\Repositories\ProductionRepository;
use App\Features\Lines\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    protected $repository;

    public function __construct(ProductionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getLines()
    {
        return response()->json([
            'status' => 'success',
            'data' => Line::all()
        ]);
    }

    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->repository->getAll()
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->repository->findById($id)
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'production_date' => 'required|date',
            'line_id' => 'required|exists:lines,id',
            'items' => 'required|array',
            'items.*.gl_number' => 'required|string',
            'items.*.color' => 'required|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'nullable|integer',
            'items.*.sizes.*.qty_output' => 'nullable|integer',
        ]);

        $validatedData['created_by'] = Auth::id() ?? 1;

        $production = $this->repository->create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Production created successfully',
            'data' => $production
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'production_date' => 'required|date',
            'line_id' => 'required|exists:lines,id',
            'items' => 'required|array',
            'items.*.gl_number' => 'required|string',
            'items.*.color' => 'required|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'nullable|integer',
            'items.*.sizes.*.qty_output' => 'nullable|integer',
        ]);

        $validatedData['updated_by'] = Auth::id() ?? 1;

        $production = $this->repository->update($id, $validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Production updated successfully',
            'data' => $production
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Production deleted successfully'
        ]);
    }
}
