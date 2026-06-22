<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('flat')) {
            $colors = Color::select('standard_name')->pluck('standard_name');
            return response()->json([
                'status' => 'success',
                'data' => $colors
            ]);
        }

        $query = Color::with(['glGroup', 'aliases']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('standard_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('aliases', function($q) use ($search) {
                      $q->where('alias_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->input('per_page', 20);

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate($perPage)
        ]);
    }
}
