<?php

namespace App\Features\Lines\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Lines\Repositories\LineRepository;
use Illuminate\Http\Request;

class LineController extends Controller
{
    protected $repository;

    public function __construct(LineRepository $repository)
    {
        $this->repository = $repository;
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
            'name' => 'required|string|max:255|unique:lines,name',
            'location' => 'nullable|string|max:450',
        ]);

        $line = $this->repository->create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Line created successfully',
            'data' => $line
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:lines,name,' . $id,
            'location' => 'nullable|string|max:450',
        ]);

        $line = $this->repository->update($id, $validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Line updated successfully',
            'data' => $line
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Line deleted successfully'
        ]);
    }
}
