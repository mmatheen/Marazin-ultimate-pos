<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use App\Http\Requests\UpdateReceiptConfigRequest;
use App\ValueObjects\ReceiptConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReceiptSettingsController extends Controller
{
    /**
     * Show receipt settings for a location
     *
     * @param int $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($locationId)
    {
        try {
            $location = Location::findOrFail($locationId);
            $config = $location->getReceiptConfig();
            $presets = ReceiptConfig::getPresets();
            $spacingModes = ReceiptConfig::getSpacingModes();

            return response()->json([
                'success' => true,
                'config' => $config,
                'presets' => $presets,
                'spacingModes' => $spacingModes,
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching receipt settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load receipt settings.',
            ], 500);
        }
    }

    /**
     * Update receipt settings for a location
     *
     * @param UpdateReceiptConfigRequest $request
     * @param int $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateReceiptConfigRequest $request, $locationId)
    {
        try {
            $location = Location::findOrFail($locationId);

            $config = $request->validated();
            $location->updateReceiptConfig($config);

            return response()->json([
                'success' => true,
                'message' => 'Receipt settings updated successfully.',
                'config' => $location->getReceiptConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating receipt settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update receipt settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset receipt settings to defaults
     *
     * @param int $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset($locationId)
    {
        try {
            $location = Location::findOrFail($locationId);
            $location->resetReceiptConfig();

            return response()->json([
                'success' => true,
                'message' => 'Receipt settings reset to defaults.',
                'config' => $location->getReceiptConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error resetting receipt settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset receipt settings.',
            ], 500);
        }
    }

    /**
     * Apply a preset configuration
     *
     * @param Request $request
     * @param int $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyPreset(Request $request, $locationId)
    {
        try {
            $location = Location::findOrFail($locationId);
            $presetKey = $request->input('preset');

            $presets = ReceiptConfig::getPresets();

            if (!isset($presets[$presetKey])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid preset selected.',
                ], 400);
            }

            $config = $presets[$presetKey]['config'];
            $location->updateReceiptConfig($config);

            return response()->json([
                'success' => true,
                'message' => 'Preset "' . $presets[$presetKey]['name'] . '" applied successfully.',
                'config' => $location->getReceiptConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error applying preset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply preset.',
            ], 500);
        }
    }

    /**
     * Generate receipt preview with temporary settings
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        try {
            // Get temporary config from request
            $tempConfig = [
                'show_logo' => filter_var($request->input('show_logo', true), FILTER_VALIDATE_BOOLEAN),
                'show_customer_phone' => filter_var($request->input('show_customer_phone', true), FILTER_VALIDATE_BOOLEAN),
                'show_mrp_strikethrough' => filter_var($request->input('show_mrp_strikethrough', true), FILTER_VALIDATE_BOOLEAN),
                'show_imei' => filter_var($request->input('show_imei', true), FILTER_VALIDATE_BOOLEAN),
                'show_discount_breakdown' => filter_var($request->input('show_discount_breakdown', true), FILTER_VALIDATE_BOOLEAN),
                'show_payment_method' => filter_var($request->input('show_payment_method', true), FILTER_VALIDATE_BOOLEAN),
                'show_outstanding_due' => filter_var($request->input('show_outstanding_due', true), FILTER_VALIDATE_BOOLEAN),
                'show_stats_section' => filter_var($request->input('show_stats_section', true), FILTER_VALIDATE_BOOLEAN),
                'show_footer_note' => filter_var($request->input('show_footer_note', true), FILTER_VALIDATE_BOOLEAN),
                'spacing_mode' => $request->input('spacing_mode', 'compact'),
                'font_size_base' => (int) $request->input('font_size_base', 11),
                'line_spacing' => (int) $request->input('line_spacing', 5),
            ];

            $locationId = $request->input('location_id');

            // Get sample data for preview
            $location = Location::find($locationId);

            if (!$location) {
                return response('<div style="padding:20px;">Location not found</div>', 404);
            }

            // Get sample sale data or create mock data
            $sale = Sale::where('location_id', $locationId)
                ->with(['products.product', 'products.imeis', 'products.batch'])
                ->latest()
                ->first();

            if (!$sale) {
                // Create mock sale data for preview
                $sale = $this->getMockSaleData($location);
                $products = collect([]);
                $customer = $this->getMockCustomer();
                $user = auth()->user() ?? User::first();
                $payments = collect([]);
                $amount_given = 0;
                $balance_amount = 0;
            } else {
                $products = $sale->products;
                $customer = $sale->customer ?? $this->getMockCustomer();
                $user = $sale->user ?? User::first();
                $payments = $sale->payments ?? collect([]);
                $amount_given = $payments->sum('amount');
                $balance_amount = $amount_given > $sale->final_total ? ($amount_given - $sale->final_total) : 0;
            }

            // Render receipt with temporary config
            $html = view('sell.receipt', [
                'sale' => $sale,
                'location' => $location,
                'products' => $products,
                'customer' => $customer,
                'user' => $user,
                'payments' => $payments,
                'amount_given' => $amount_given,
                'balance_amount' => $balance_amount,
                'receiptConfig' => $tempConfig,
            ])->render();

            return response($html)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            Log::error('Error generating receipt preview: ' . $e->getMessage());
            return response('<div style="padding:20px;color:red;">Error generating preview: ' . $e->getMessage() . '</div>', 500);
        }
    }

    /**
     * Get mock sale data for preview
     *
     * @param Location $location
     * @return object
     */
    private function getMockSaleData($location)
    {
        return (object) [
            'id' => 1,
            'invoice_no' => $location->invoice_prefix . '-' . date('Ymd') . '-001',
            'sales_date' => now()->format('Y-m-d'),
            'status' => 'final',
            'subtotal' => 15000.00,
            'discount_type' => 'percentage',
            'discount_amount' => 5,
            'shipping_charges' => 0,
            'final_total' => 14250.00,
            'total_paid' => 14250.00,
            'total_due' => 0,
            'sale_notes' => 'Sample receipt preview - This is how your receipt will look.',
        ];
    }

    /**
     * Get mock customer data for preview
     *
     * @return object
     */
    private function getMockCustomer()
    {
        return (object) [
            'id' => 1,
            'first_name' => 'SAMPLE',
            'last_name' => 'CUSTOMER',
            'mobile_no' => '0771234567',
        ];
    }
}
