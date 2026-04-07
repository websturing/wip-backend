<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Customer::latest()->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'country' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $customer
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Customer::with('glGroups')->findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validate([
            'name' => 'required|string',
            'country' => 'nullable|string',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $customer
        ]);
    }

    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Customer deleted']);
    }
}
