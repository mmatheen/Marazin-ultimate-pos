<?php

namespace App\Services\Product\Concerns;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

trait ProductStockNotifications
{
    public function getNotifications(): JsonResponse
    {
        // Only select needed columns for performance
        $products = Product::select([
            'id',
            'product_name',
            'sku',
            'unit_id',
            'brand_id',
            'main_category_id',
            'sub_category_id',
            'stock_alert',
            'alert_quantity',
            'product_image',
            'description',
            'is_imei_or_serial_no',
            'is_for_selling',
            'product_type',
            'pax',
            'original_price',
            'retail_price',
            'whole_sale_price',
            'special_price',
            'max_retail_price',
            'created_at',
            'updated_at'
        ])
            ->with([
                'batches:id,product_id',
                'batches.locationBatches:id,batch_id,qty'
            ])
            ->whereNotNull('alert_quantity')
            ->where('alert_quantity', '>', 0)
            ->get();

        $notifications = [];

        foreach ($products as $product) {
            // Sum all batch quantities for this product
            $totalStock = 0;
            foreach ($product->batches as $batch) {
                foreach ($batch->locationBatches as $lb) {
                    $totalStock += $lb->qty;
                }
            }

            if ($totalStock <= $product->alert_quantity) {
                $notifications[] = [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit_id' => $product->unit_id,
                    'brand_id' => $product->brand_id,
                    'main_category_id' => $product->main_category_id,
                    'sub_category_id' => $product->sub_category_id,
                    'stock_alert' => $product->stock_alert,
                    'alert_quantity' => $product->alert_quantity,
                    'product_image' => $product->product_image,
                    'description' => $product->description,
                    'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
                    'is_for_selling' => $product->is_for_selling,
                    'product_type' => $product->product_type,
                    'pax' => $product->pax,
                    'original_price' => $product->original_price,
                    'retail_price' => $product->retail_price,
                    'whole_sale_price' => $product->whole_sale_price,
                    'special_price' => $product->special_price,
                    'max_retail_price' => $product->max_retail_price,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'total_stock' => $totalStock,
                ];
            }
        }

        return response()->json([
            'status' => 200,
            'data' => $notifications,
            'count' => count($notifications)
        ]);
    }

    public function markNotificationsAsSeen(): JsonResponse
    {
        $products = Product::all();
        $seenNotifications = Session::get('seen_notifications', []);

        $notifications = $products->filter(function ($product) {
            // ✅ Use live DB query for accurate stock totals
            $totalStock = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $product->id)
                ->sum('location_batches.qty');

            return $totalStock <= $product->alert_quantity;
        });

        foreach ($notifications as $notification) {
            if (!in_array($notification->id, $seenNotifications)) {
                $seenNotifications[] = $notification->id;
            }
        }

        Session::put('seen_notifications', $seenNotifications);

        return response()->json(['status' => 200, 'message' => 'Notifications marked as seen.']);
    }
}
