<?php

namespace App\Features\LayingPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Features\LayingPlanning\Services\LayingPlanningDetailService;
use App\Features\LayingPlanning\Requests\CreateLayingPlanningDetailRequest;
use App\Features\LayingPlanning\Requests\UpdateLayingPlanningDetailRequest;
use App\Features\LayingPlanning\Requests\DuplicateLayingPlanningDetailRequest;
use App\Features\LayingPlanning\Resources\LayingPlanningDetailResource;
use Illuminate\Http\JsonResponse;

class LayingPlanningDetailController extends Controller
{
    protected $service;

    public function __construct(LayingPlanningDetailService $service)
    {
        $this->service = $service;
    }

    public function index($lpId): JsonResponse
    {
        $data = $this->service->getAllByLayingPlanning($lpId);

        return response()->json([
            'status' => 'success',
            'data' => LayingPlanningDetailResource::collection($data),
        ]);
    }

    public function store(CreateLayingPlanningDetailRequest $request, $lpId): JsonResponse
    {
        $data = $this->service->create($lpId, $request->validated());

        return response()->json([
            'status' => 'success',
            'data' => new LayingPlanningDetailResource($data),
        ], 201);
    }

    public function show($lpId, $detailId): JsonResponse
    {
        $data = $this->service->findById($detailId);

        if (!$data || $data->laying_planning_id !== $lpId) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new LayingPlanningDetailResource($data),
        ]);
    }

    public function update(UpdateLayingPlanningDetailRequest $request, $lpId, $detailId): JsonResponse
    {
        $detail = $this->service->findById($detailId);

        if (!$detail || $detail->laying_planning_id !== $lpId) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }

        $updated = $this->service->update($detailId, $request->validated());
        if (!$updated) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update'], 400);
        }

        $data = $this->service->findById($detailId);

        return response()->json([
            'status' => 'success',
            'data' => new LayingPlanningDetailResource($data),
        ]);
    }

    public function duplicate(DuplicateLayingPlanningDetailRequest $request, $lpId, $detailId): JsonResponse
    {
        $count = $request->validated()['count'];

        $data = $this->service->duplicate($lpId, $detailId, $count);

        if ($data->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => LayingPlanningDetailResource::collection($data),
        ], 201);
    }

    public function destroy($lpId, $detailId): JsonResponse
    {
        $detail = $this->service->findById($detailId);

        if (!$detail || $detail->laying_planning_id !== $lpId) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }

        $deleted = $this->service->delete($detailId);
        if (!$deleted) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
