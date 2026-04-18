<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCacheService
{
    public function afterProductSaved(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        Cache::forget("initial_product_details_user_{$userId}");
        Cache::forget("initial_product_details_api_user_{$userId}");
        Cache::forget("product_dropdown_data_user_{$userId}");
    }

    public function afterQuickAdd(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        Cache::forget("initial_product_details_user_{$userId}");
        Cache::forget("product_dropdown_data_user_{$userId}");
    }

    /**
     * Use when master-data changes (brands/categories/units/locations) and you must
     * refresh cached product form dropdowns.
     */
    public function clearProductDetailsCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("initial_product_details_user_{$userId}");
            Cache::forget("initial_product_details_api_user_{$userId}");
            return;
        }

        Cache::flush();
    }

    /**
     * Mirrors the current aggressive cache clear behavior from ProductController.
     * Phase 4/6 can reduce this to targeted invalidation and/or tagged cache flush.
     */
    public function clearAllProductRelatedCachesAggressive(): bool
    {
        try {
            $this->clearProductDetailsCache();

            $userIds = DB::table('users')->pluck('id');
            foreach ($userIds as $userId) {
                Cache::forget("product_dropdown_data_user_{$userId}");
                Cache::forget("initial_product_details_user_{$userId}");
                Cache::forget("initial_product_details_api_user_{$userId}");
                Cache::forget("product_stocks_{$userId}");
                Cache::forget("autocomplete_stock_{$userId}");
            }

            Cache::forget('all_products');
            Cache::forget('all_categories');
            Cache::forget('all_brands');
            Cache::forget('locations_list');
            Cache::forget('cities_list');
            Cache::forget('customer_groups_list');
            Cache::forget('main_categories_list');
            Cache::forget('sub_categories_list');
            Cache::forget('brands_list');

            try {
                if (method_exists(Cache::getStore(), 'tags')) {
                    Cache::tags(['products', 'batches', 'stocks', 'prices'])->flush();
                }
            } catch (\Exception $e) {
                Log::debug('Cache tags not supported: ' . $e->getMessage());
            }

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            Log::info('✅ All product caches cleared successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Error clearing product caches: ' . $e->getMessage());
            return false;
        }
    }
}

