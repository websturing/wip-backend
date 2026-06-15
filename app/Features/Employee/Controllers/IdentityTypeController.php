<?php

namespace App\Features\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Employee\Models\IdentityType;
use Illuminate\Http\Request;

class IdentityTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = IdentityType::query();
        
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        return response()->json(IdentityType::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:identity_types,name',
            'is_expiration_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $identityType = IdentityType::create($validated);
        return response()->json($identityType, 201);
    }

    public function update(Request $request, $id)
    {
        $identityType = IdentityType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:identity_types,name,' . $identityType->id,
            'is_expiration_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $identityType->update($validated);
        return response()->json($identityType);
    }

    public function destroy($id)
    {
        $identityType = IdentityType::findOrFail($id);
        $identityType->delete();
        return response()->json(null, 204);
    }
}
