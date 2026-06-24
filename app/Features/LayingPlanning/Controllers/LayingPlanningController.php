<?php

namespace App\Features\LayingPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Features\LayingPlanning\Services\LayingPlanningService;
use App\Features\LayingPlanning\Requests\CreateLayingPlanningRequest;
use App\Features\LayingPlanning\Requests\UpdateLayingPlanningRequest;
use App\Features\LayingPlanning\Resources\LayingPlanningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LayingPlanningController extends Controller
{
    protected $service;

    public function __construct(LayingPlanningService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->paginate($request->all());
        
        $paginator->getCollection()->transform(function ($item) {
            return new LayingPlanningResource($item);
        });

        return response()->json([
            'status' => 'success',
            'data' => $paginator
        ]);
    }

    public function store(CreateLayingPlanningRequest $request): JsonResponse
    {
        $data = $this->service->create($request->validated());
        return response()->json(['status' => 'success', 'data' => new LayingPlanningResource($data)], 201);
    }

    public function show($id): JsonResponse
    {
        $data = $this->service->findById($id);
        if (!$data) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }
        return response()->json([
            'status' => 'success',
            'data'   => new LayingPlanningResource($data),
        ]);
    }

    public function update(UpdateLayingPlanningRequest $request, $id): JsonResponse
    {
        $updated = $this->service->update($id, $request->validated());
        if (!$updated) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update'], 400);
        }
        return response()->json([
            'status' => 'success',
            'data'   => new LayingPlanningResource($this->service->findById($id)),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete'], 400);
        }
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
