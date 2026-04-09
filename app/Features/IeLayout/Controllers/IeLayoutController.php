<?php

namespace App\Features\IeLayout\Controllers;

use App\Http\Controllers\Controller;
use App\Features\IeLayout\Services\IeLayoutService;
use App\Features\IeLayout\Requests\CreateIeLayoutRequest;
use App\Features\IeLayout\Requests\UpdateIeLayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IeLayoutController extends Controller
{
    protected $service;

    public function __construct(IeLayoutService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of IE layouts.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getAll($request->all());
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    /**
     * Store a newly created IE layout.
     */
    public function store(CreateIeLayoutRequest $request): JsonResponse
    {
        $data = $this->service->create($request->validated());
        return response()->json(['message' => 'Created', 'data' => $data], 201);
    }

    /**
     * Display the specified IE layout.
     */
    public function show($id): JsonResponse
    {
        $data = $this->service->findById((int)$id);
        if (!$data) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    /**
     * Update the specified IE layout.
     */
    public function update(UpdateIeLayoutRequest $request, $id): JsonResponse
    {
        $updated = $this->service->update((int)$id, $request->validated());
        if (!$updated) {
            return response()->json(['message' => 'Failed to Update'], 400);
        }
        $data = $this->service->findById((int)$id);
        return response()->json(['message' => 'Updated', 'data' => $data]);
    }

    /**
     * Remove the specified IE layout.
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->service->delete((int)$id);
        if (!$deleted) {
            return response()->json(['message' => 'Failed to Delete'], 400);
        }
        return response()->json(['message' => 'Deleted']);
    }
}
