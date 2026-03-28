<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view settings', ['only' => ['index', 'getAll', 'edit']]);
        $this->middleware('permission:edit business-settings', ['only' => ['store', 'update', 'destroy']]);
    }

    public function index(): View
    {
        return view('tax_rates.index');
    }

    public function getAll(): JsonResponse
    {
        $taxRates = TaxRate::orderBy('name')->get();

        return response()->json([
            'status' => 200,
            'message' => $taxRates,
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $taxRate = TaxRate::find($id);

        if (!$taxRate) {
            return response()->json([
                'status' => 404,
                'message' => 'Tax rate not found.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => $taxRate,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $normalizedName = trim((string) $validated['name']);
        $taxRate = TaxRate::whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])->first();

        if ($taxRate) {
            $taxRate->update([
                'rate' => $validated['rate'],
                'is_active' => (bool)($validated['is_active'] ?? true),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Tax rate updated successfully for existing name.',
                    'newTaxRateId' => $taxRate->id,
                ]);
            }

            return redirect()->route('tax-rates.index')->with('success', 'Tax rate updated successfully for existing name.');
        }

        $createdTaxRate = TaxRate::create([
            'name' => $normalizedName,
            'rate' => $validated['rate'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => 'Tax rate created successfully.',
                'newTaxRateId' => $createdTaxRate->id,
            ]);
        }

        return redirect()->route('tax-rates.index')->with('success', 'Tax rate created successfully.');
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tax_rates,name,' . $taxRate->id,
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $taxRate->update([
            'name' => trim((string) $validated['name']),
            'rate' => $validated['rate'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => 'Tax rate updated successfully.',
                'newTaxRateId' => $taxRate->id,
            ]);
        }

        return redirect()->route('tax-rates.index')->with('success', 'Tax rate updated successfully.');
    }

    public function destroy(Request $request, TaxRate $taxRate): JsonResponse|RedirectResponse
    {
        $taxRate->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => 'Tax rate deleted successfully.',
            ]);
        }

        return redirect()->route('tax-rates.index')->with('success', 'Tax rate deleted successfully.');
    }
}
