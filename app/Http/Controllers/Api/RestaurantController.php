<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Waiter;

class RestaurantController extends Controller
{
    // 1. Create a Table
    public function createTable(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:tables,name',
            'capacity' => 'nullable|integer',
            'is_available' => 'boolean'
        ]);

        $table = Table::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Table created successfully',
            'data' => $table
        ]);
    }

    // 2. Create a Waiter
    public function createWaiter(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string|unique:waiters,phone'
        ]);

        $waiter = Waiter::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Waiter created successfully',
            'data' => $waiter
        ]);
    }

    // 3. Assign Waiters to Table (many-to-many)
    public function assignWaitersToTable(Request $request, $table_id)
    {
        $validated = $request->validate([
            'waiter_ids' => 'required|array',
            'waiter_ids.*' => 'exists:waiters,id',
        ]);

        $table = Table::findOrFail($table_id);
        $table->waiters()->sync($validated['waiter_ids']); // attach or sync

        return response()->json([
            'status' => true,
            'message' => 'Waiters assigned to table successfully',
            'data' => $table->load('waiters')
        ]);
    }

    // 4. Get all Tables with Waiters
    public function getTables()
    {
        $tables = Table::with('waiters')->get();

        return response()->json([
            'status' => true,
            'data' => $tables
        ]);
    }

    // 5. Get all Waiters with Tables
    public function getWaiters()
    {
        $waiters = Waiter::with('tables')->get();

        return response()->json([
            'status' => true,
            'data' => $waiters
        ]);
    }
}
