<?php

namespace App\Features\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Employee\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'identities']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function show($id)
    {
        $employee = Employee::with(['user', 'identities'])->findOrFail($id);
        return response()->json($employee);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
            'employee_code' => 'required|string|unique:employees,employee_code',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'identities' => 'nullable|array',
            'identities.*.identity_type' => 'required|string',
            'identities.*.identity_number' => 'required|string',
            'identities.*.expiration_date' => 'nullable|date',
            'identities.*.document_path' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::create($validated);

            if (!empty($validated['identities'])) {
                foreach ($validated['identities'] as $identityData) {
                    $employee->identities()->create($identityData);
                }
            }

            DB::commit();
            return response()->json($employee->load(['user', 'identities']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,' . $employee->id,
            'employee_code' => 'required|string|unique:employees,employee_code,' . $employee->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'identities' => 'nullable|array',
            'identities.*.id' => 'nullable|exists:employee_identities,id',
            'identities.*.identity_type' => 'required_with:identities|string',
            'identities.*.identity_number' => 'required_with:identities|string',
            'identities.*.expiration_date' => 'nullable|date',
            'identities.*.document_path' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $employee->update($validated);

            if (isset($validated['identities'])) {
                // Get existing identity IDs
                $existingIds = collect($validated['identities'])->pluck('id')->filter()->toArray();
                
                // Delete removed identities
                $employee->identities()->whereNotIn('id', $existingIds)->delete();

                foreach ($validated['identities'] as $identityData) {
                    if (isset($identityData['id'])) {
                        // Update existing
                        $employee->identities()->where('id', $identityData['id'])->update($identityData);
                    } else {
                        // Create new
                        $employee->identities()->create($identityData);
                    }
                }
            }

            DB::commit();
            return response()->json($employee->load(['user', 'identities']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json(null, 204);
    }
}
