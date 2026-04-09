<?php

namespace App\Features\productivity\Controllers;

use App\Http\Controllers\Controller;
use App\Features\productivity\Services\ProductivityService;
use App\Features\productivity\Requests\CreateProductivityRequest;
use App\Features\productivity\Requests\UpdateProductivityRequest;
use Illuminate\Http\JsonResponse;

class ProductivityController extends Controller
{
    protected $service;

    public function __construct(ProductivityService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    public function store(CreateProductivityRequest $request): JsonResponse
    {
        $data = $this->service->create($request->validated());
        return response()->json(['message' => 'Created', 'data' => $data], 201);
    }

    public function show($id): JsonResponse
    {
        $data = $this->service->findById($id);
        if (!$data) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    public function update(UpdateProductivityRequest $request, $id): JsonResponse
    {
        $updated = $this->service->update($id, $request->validated());
        if (!$updated) {
            return response()->json(['message' => 'Failed to update'], 400);
        }
        $data = $this->service->findById($id);
        return response()->json(['message' => 'Updated', 'data' => $data]);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Failed to delete'], 400);
        }
        return response()->json(['message' => 'Deleted']);
    }
}
