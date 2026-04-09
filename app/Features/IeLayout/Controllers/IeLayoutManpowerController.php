<?php

namespace App\Features\IeLayout\Controllers;

use App\Http\Controllers\Controller;
use App\Features\IeLayout\Models\IeLayoutManpower;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class IeLayoutManpowerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = IeLayoutManpower::query();
        
        if ($request->has('ie_layout_id')) {
            $query->where('ie_layout_id', $request->ie_layout_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest('date')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ie_layout_id' => 'required|exists:ie_layouts,id',
            'date' => 'required|date',
            'man_power_sewer' => 'required|numeric',
            'man_power_matching' => 'required|numeric',
            'man_power_qc' => 'required|numeric',
            'man_power_others' => 'required|numeric',
        ]);

        $validated['created_by_id'] = Auth::id();

        $manpower = IeLayoutManpower::updateOrCreate(
            ['ie_layout_id' => $validated['ie_layout_id'], 'date' => $validated['date']],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'data' => $manpower
        ]);
    }

    public function destroy($id): JsonResponse
    {
        IeLayoutManpower::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Manpower record deleted']);
    }
}
