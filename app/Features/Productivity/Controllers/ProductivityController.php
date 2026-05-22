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
            'sewer' => 'required|numeric',
            'plan_sewer' => 'required|numeric',
            'working_hour' => 'required|numeric',
            'lot_data' => 'required|array|min:1',
            'lot_data.*.lot_id' => 'required|exists:lots,id',
            'lot_data.*.smv' => 'required|numeric',
            'lot_data.*.last_step' => 'required|numeric',
            'lot_data.*.target_plan' => 'required|numeric',
            'lot_data.*.manpower' => 'sometimes|numeric',
            'lot_data.*.plan_manpower' => 'sometimes|numeric',
            'lot_data.*.sewer' => 'sometimes|numeric',
            'lot_data.*.plan_sewer' => 'sometimes|numeric',
            'lot_data.*.working_hour' => 'sometimes|numeric',
            'lot_data.*.media_id' => 'nullable|string',
            'lot_data.*.section' => 'nullable|string',
        ]);

        // Primary lot_id for legacy/summary (compatibility)
        $validated['lot_id'] = $request->lot_data[0]['lot_id'];
        $validated['smv'] = $request->lot_data[0]['smv'];
        $validated['last_step'] = $request->lot_data[0]['last_step'];
        $validated['target_plan'] = $request->lot_data[0]['target_plan'];

        $productivity = $this->repository->create($validated);
        
        $records = [];
        foreach ($request->lot_data as $ld) {
            $records[] = [
                'lot_id' => $ld['lot_id'],
                'merge_id' => $ld['merge_id'] ?? null,
                'smv' => $ld['smv'],
                'last_step' => $ld['last_step'],
                'target_plan' => $ld['target_plan'],
                'manpower' => $ld['manpower'] ?? 0,
                'plan_manpower' => $ld['plan_manpower'] ?? 0,
                'sewer' => $ld['sewer'] ?? 0,
                'plan_sewer' => $ld['plan_sewer'] ?? 0,
                'working_hour' => $ld['working_hour'] ?? 8,
                'media_id' => !empty($ld['media_id']) ? $ld['media_id'] : null,
                'section' => $ld['section'] ?? 'all',
            ];
        }
        $productivity->productivityLots()->delete();
        $productivity->productivityLots()->createMany($records);

        $productivity->load(['lots.glGroup.customer']);
        $productivity->lots->each(function($l) {
            if ($l->pivot && $l->pivot->media) {
                $l->pivot->media_url = $l->pivot->media->url;
            }
        });

        return response()->json([
            'status' => 'success',
            'data' => $productivity
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'manpower' => 'sometimes|numeric',
            'plan_manpower' => 'sometimes|numeric',
            'sewer' => 'sometimes|numeric',
            'plan_sewer' => 'sometimes|numeric',
            'working_hour' => 'sometimes|numeric',
            'lot_data' => 'sometimes|array',
            'lot_data.*.lot_id' => 'required|exists:lots,id',
            'lot_data.*.smv' => 'required|numeric',
            'lot_data.*.last_step' => 'required|numeric',
            'lot_data.*.target_plan' => 'required|numeric',
            'lot_data.*.media_id' => 'nullable|string',
            'lot_data.*.section' => 'nullable|string',
        ]);

        $productivity = $this->repository->update($id, $request->except('lot_data'));

        if ($request->has('lot_data')) {
            $records = [];
            foreach ($request->lot_data as $ld) {
                $records[] = [
                    'lot_id' => $ld['lot_id'],
                    'merge_id' => $ld['merge_id'] ?? null,
                    'smv' => $ld['smv'],
                    'last_step' => $ld['last_step'],
                    'target_plan' => $ld['target_plan'],
                    'manpower' => $ld['manpower'] ?? 0,
                    'plan_manpower' => $ld['plan_manpower'] ?? 0,
                    'sewer' => $ld['sewer'] ?? 0,
                    'plan_sewer' => $ld['plan_sewer'] ?? 0,
                    'working_hour' => $ld['working_hour'] ?? 8,
                    'media_id' => !empty($ld['media_id']) ? $ld['media_id'] : null,
                    'section' => $ld['section'] ?? 'all',
                ];
            }
            $productivity->productivityLots()->delete();
            $productivity->productivityLots()->createMany($records);
        }

        $productivity->load(['lots.glGroup.customer']);
        $productivity->lots->each(function($l) {
            if ($l->pivot && $l->pivot->media) {
                $l->pivot->media_url = $l->pivot->media->url;
            }
        });

        return response()->json([
            'status' => 'success',
            'data' => $productivity
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

        $lot = $last->lots->first();
        if ($lot && $lot->pivot) {
             $lot->pivot->load('media');
        }
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'plan_manpower' => $last->plan_manpower,
                'sewer' => $last->sewer,
                'plan_sewer' => $last->plan_sewer,
                'smv' => $lot->pivot ? $lot->pivot->smv : 0,
                'target_plan' => $lot->pivot ? $lot->pivot->target_plan : 0,
                'media_id' => $lot->pivot ? $lot->pivot->media_id : null,
                'media_url' => ($lot->pivot && $lot->pivot->media) ? $lot->pivot->media->url : null,
            ]
        ]);
    }

    public function export($id, \App\Features\Productivity\Services\ProductivityExportService $service)
    {
        $filePath = $service->export($id);
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportByDate(Request $request, \App\Features\Productivity\Services\ProductivityExportService $service)
    {
        $date = $request->get('date', now()->toDateString());
        $lineIds = $request->get('line_ids');
        $type = $request->get('type', 'productivity');
        $filePath = $service->exportByDate($date, $lineIds, $type);
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function reportData(Request $request, \App\Features\Productivity\Services\ProductivityReportService $service)
    {
        $date = $request->get('date', now()->toDateString());
        $lineIds = $request->get('line_ids');
        
        $data = $service->getSummaryData($date, $lineIds);
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
