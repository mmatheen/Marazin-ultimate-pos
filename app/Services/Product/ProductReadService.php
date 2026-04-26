<?php

namespace App\Services\Product;

use App\Models\Brand;
use App\Models\MainCategory;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Unit;
use App\Services\Location\LocationAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductReadService
{
    public function getProductFormData($user): array
    {
        $mainCategories = MainCategory::select('id', 'mainCategoryName')
            ->orderBy('mainCategoryName')
            ->get();

        $subCategories = DB::table('sub_categories')
            ->select(
                'sub_categories.id',
                'sub_categories.subCategoryname',
                'sub_categories.main_category_id',
                'main_categories.mainCategoryName'
            )
            ->leftJoin('main_categories', 'sub_categories.main_category_id', '=', 'main_categories.id')
            ->orderBy('sub_categories.subCategoryname')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'subCategoryname' => $item->subCategoryname,
                    'main_category_id' => $item->main_category_id,
                    'mainCategory' => [
                        'mainCategoryName' => $item->mainCategoryName,
                    ],
                ];
            });

        $brands = Brand::select('id', 'name')
            ->orderBy('name')
            ->get();

        $units = Unit::select('id', 'name', 'short_name', 'allow_decimal')
            ->orderBy('name')
            ->get();

        $locations = $this->getUserAccessibleLocations($user);

        return [
            'mainCategories' => $mainCategories,
            'subCategories' => $subCategories,
            'brands' => $brands,
            'units' => $units,
            'locations' => $locations,
        ];
    }

    public function respondInitialProductDetails($user): JsonResponse
    {
        try {
            return response()->json([
                'status' => 200,
                'message' => $this->buildInitialProductDetailsPayload($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Web initialProductDetails: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => [
                    'brands' => [],
                    'subCategories' => [],
                    'mainCategories' => [],
                    'units' => [],
                    'locations' => [],
                    'auto_select_single_location' => false,
                ],
                'error' => 'Error loading product details. Please refresh the page.'
            ]);
        }
    }

    public function respondSubCategoriesByMainCategoryId(string $main_category_id): JsonResponse
    {
        $subcategoryDetails = $this->getSubCategoriesByMainCategoryId($main_category_id);
        if ($subcategoryDetails) {
            return response()->json([
                'status' => 200,
                'message' => $subcategoryDetails
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => "No Such sub category Details record Found!"
        ]);
    }

    public function buildInitialProductDetailsPayload($user): array
    {
        $formData = $this->getProductFormData($user);

        $locations = $formData['locations'];
        $locationsWithSelection = $locations->map(function ($location) use ($locations) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'selected' => $locations->count() === 1,
            ];
        })->values()->all();

        return [
            'brands' => $formData['brands'],
            'subCategories' => $formData['subCategories'],
            'mainCategories' => $formData['mainCategories'],
            'units' => $formData['units'],
            'locations' => $locationsWithSelection,
            'auto_select_single_location' => $locations->count() === 1,
        ];
    }

    public function listProductsForUser($user): Collection
    {
        if ($user && $user->location_id !== null) {
            $locationId = $user->location_id;

            return Product::whereHas('locations', function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            })->with('locations')->get();
        }

        return Product::with('locations')->get();
    }

    /**
     * Product with relations needed for the add/edit product form (edit screen).
     */
    public function findProductForEdit(int $id): ?Product
    {
        return Product::with([
            'locations:id,name',
            'mainCategory:id,mainCategoryName',
            'brand:id,name',
            'unit:id,name,short_name,allow_decimal',
        ])->find($id);
    }

    /**
     * Find or auto-create the "Cash Item" placeholder product used for
     * instant cash-register price entry on the POS page.
     * Uses withoutGlobalScopes() to bypass LocationScope on Unit/Brand/MainCategory.
     * Result is cached for 24 hours — DB is only queried once per day.
     */
    public function resolveCashItemProductId(): int
    {
        return Cache::remember('misc_item_product_id', 86400, function () {
            $unit = Unit::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'Pieces'],
                ['short_name' => 'PCS', 'allow_decimal' => 0]
            );
            $brand = Brand::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'General'],
                ['description' => 'Auto-created']
            );
            $category = MainCategory::withoutGlobalScopes()->firstOrCreate(
                ['mainCategoryName' => 'General'],
                ['description' => 'Auto-created']
            );

            $product = Product::firstOrCreate(
                ['sku' => 'CASH-ITEM'],
                [
                    'product_name' => 'Cash Item',
                    'unit_id' => $unit->id,
                    'brand_id' => $brand->id,
                    'main_category_id' => $category->id,
                    'stock_alert' => 0,
                    'is_for_selling' => 1,
                    'is_active' => 1,
                    'retail_price' => 1,
                    'whole_sale_price' => 1,
                    'special_price' => 1,
                    'original_price' => 1,
                    'max_retail_price' => 1,
                ]
            );

            return (int) $product->id;
        });
    }

    public function getProductDetailsById(int $id): ?Product
    {
        return Product::with([
            'locations',
            'mainCategory',
            'brand',
            'batches' => function ($query) {
                $query->select([
                    'id',
                    'batch_no',
                    'product_id',
                    'unit_cost',
                    'wholesale_price',
                    'special_price',
                    'retail_price',
                    'max_retail_price',
                    'expiry_date',
                    'created_at',
                ])->with(['locationBatches' => function ($q) {
                    $q->with('location:id,name');
                }])->orderBy('created_at', 'desc');
            },
        ])->find($id);
    }

    public function getLastProduct(): ?Product
    {
        return Product::with(['unit', 'brand', 'locations'])->latest('created_at')->first();
    }

    public function getProductsByCategory(int $categoryId): Collection
    {
        return Product::where('main_category_id', $categoryId)->get();
    }

    public function skuExists(string $sku, ?int $excludeProductId = null): bool
    {
        $query = Product::query()->where('sku', $sku);

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        return $query->exists();
    }

    public function getSubCategoriesByMainCategoryId(string $mainCategoryId): Collection
    {
        return SubCategory::withoutGlobalScope(\App\Scopes\LocationScope::class)
            ->where('main_category_id', $mainCategoryId)
            ->select('id', 'subCategoryname', 'main_category_id', 'subCategoryCode', 'description')
            ->orderBy('subCategoryname', 'asc')
            ->get();
    }

    public function getProductLocationsPayload(array $productIds): JsonResponse
    {
        if (empty($productIds)) {
            return response()->json(['status' => 'error', 'message' => 'No products selected.'], 400);
        }

        try {
            $products = Product::with(['locations' => function ($query) {
                $query->select('locations.id', 'locations.name');
            }])
                ->whereIn('id', $productIds)
                ->get(['id', 'product_name'])
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->product_name,
                        'locations' => $product->locations->map(function ($location) {
                            return [
                                'id' => $location->id,
                                'name' => $location->name
                            ];
                        })
                    ];
                });

            return response()->json(['status' => 'success', 'data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function respondIndexJson($user): JsonResponse
    {
        $getValue = $this->listProductsForUser($user);

        if ($getValue->count() > 0) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => "No Records Found!"
        ]);
    }

    public function respondProductDetailsJson(int $id): JsonResponse
    {
        $product = $this->getProductDetailsById($id);

        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found']);
        }

        return response()->json(['status' => 200, 'message' => $product]);
    }

    public function respondLastProductJson(): JsonResponse
    {
        $product = $this->getLastProduct();

        if ($product) {
            return response()->json([
                'status' => 200,
                'product' => $product,
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No product found.',
        ]);
    }

    public function respondProductsByCategoryJson(int $categoryId): JsonResponse
    {
        $products = $this->getProductsByCategory($categoryId);

        if ($products) {
            return response()->json([
                'status' => 200,
                'message' => $products
            ]);
        }

        return response()->json([
            'status' => 500,
            'message' => 'Error fetching products',
        ]);
    }

    public function respondEditProduct(Request $request, int $id, $user): JsonResponse|View
    {
        $product = $this->findProductForEdit($id);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }

        $formData = $this->getProductFormData($user);

        if ($request->ajax() || $request->is('api/*')) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'product' => $product,
                    'mainCategories' => $formData['mainCategories'],
                    'subCategories' => $formData['subCategories'],
                    'brands' => $formData['brands'],
                    'units' => $formData['units'],
                    'locations' => $formData['locations'],
                ]
            ]);
        }

        return view('product.add_product', compact('product') + $formData);
    }

    public function respondCheckSkuUniqueness(Request $request): JsonResponse
    {
        $sku = $request->input('sku');
        $productId = $request->input('product_id');

        if (!$sku) {
            return response()->json(['exists' => false]);
        }

        $exists = $this->skuExists((string) $sku, $productId ? (int) $productId : null);

        return response()->json(['exists' => $exists]);
    }

    private function getUserAccessibleLocations($user): Collection
    {
        /** @var LocationAccessService $locationAccess */
        $locationAccess = app(LocationAccessService::class);
        return $locationAccess->forUser($user);
    }
}

