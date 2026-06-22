<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Fabric;
use Illuminate\Http\Request;

class FabricController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('flat')) {
            $fabrics = Fabric::select('standard_content')->pluck('standard_content');
            return response()->json([
                'status' => 'success',
                'data' => $fabrics
            ]);
        }

        $query = Fabric::with(['glGroup', 'aliases']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('standard_content', 'like', "%{$search}%")
                  ->orWhereHas('aliases', function($q) use ($search) {
                      $q->where('alias_content', 'like', "%{$search}%");
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
