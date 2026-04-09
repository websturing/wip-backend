<?php

namespace App\Features\Productivity\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Productivity\Repositories\ProductivityRepository;
use Illuminate\Http\Request;

class ProductivityController extends Controller
{
    private $repository;

    public function __construct(ProductivityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $data = $this->repository->getAll($date);
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $data = $this->repository->findById($id);
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'date' => 'required|date',
            'manpower' => 'required|numeric',
            'plan_manpower' => 'required|numeric',
            'working_hour' => 'required|numeric',
            'lot_data' => 'required|array|min:1',
            'lot_data.*.lot_id' => 'required|exists:lots,id',
            'lot_data.*.smv' => 'required|numeric',
            'lot_data.*.last_step' => 'required|numeric',
            'lot_data.*.target_plan' => 'required|numeric',
        ]);

        // Primary lot_id for legacy/summary (compatibility)
        $validated['lot_id'] = $request->lot_data[0]['lot_id'];
        $validated['smv'] = $request->lot_data[0]['smv'];
        $validated['last_step'] = $request->lot_data[0]['last_step'];
        $validated['target_plan'] = $request->lot_data[0]['target_plan'];

        $productivity = $this->repository->create($validated);
        
        $syncData = [];
        foreach ($request->lot_data as $ld) {
            $syncData[$ld['lot_id']] = [
                'smv' => $ld['smv'],
                'last_step' => $ld['last_step'],
                'target_plan' => $ld['target_plan']
            ];
        }
        $productivity->lots()->sync($syncData);

        return response()->json([
            'status' => 'success',
            'data' => $productivity->load('lots')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'manpower' => 'sometimes|numeric',
            'plan_manpower' => 'sometimes|numeric',
            'working_hour' => 'sometimes|numeric',
            'lot_data' => 'sometimes|array',
            'lot_data.*.lot_id' => 'required|exists:lots,id',
            'lot_data.*.smv' => 'required|numeric',
            'lot_data.*.last_step' => 'required|numeric',
            'lot_data.*.target_plan' => 'required|numeric',
        ]);

        $productivity = $this->repository->update($id, $request->except('lot_data'));

        if ($request->has('lot_data')) {
            $syncData = [];
            foreach ($request->lot_data as $ld) {
                $syncData[$ld['lot_id']] = [
                    'smv' => $ld['smv'],
                    'last_step' => $ld['last_step'],
                    'target_plan' => $ld['target_plan']
                ];
            }
            $productivity->lots()->sync($syncData);
        }

        return response()->json([
            'status' => 'success',
            'data' => $productivity->load('lots')
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Productivity log deleted'
        ]);
    }

    public function lastInfo(Request $request, $lotId)
    {
        $last = \App\Features\Productivity\Models\Productivity::whereHas('lots', function($q) use ($lotId) {
                $q->where('lots.id', $lotId);
            })
            ->with(['lots' => function($q) use ($lotId) {
                $q->where('lots.id', $lotId);
            }])
            ->latest('date')
            ->latest('id')
            ->first();

        if (!$last) {
            return response()->json(['status' => 'success', 'data' => null]);
        }

        $pivot = $last->lots->first()->pivot ?? null;
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'plan_manpower' => $last->plan_manpower,
                'smv' => $pivot ? $pivot->smv : 0,
                'target_plan' => $pivot ? $pivot->target_plan : 0,
            ]
        ]);
    }
}
