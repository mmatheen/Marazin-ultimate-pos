<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ApplyDiscountRequest;
use App\Http\Requests\Product\CheckSkuUniquenessRequest;
use App\Http\Requests\Product\ImportProductsExcelRequest;
use App\Http\Requests\Product\SaveProductLocationsRequest;
use App\Http\Requests\Product\StoreOrUpdateProductRequest;
use App\Services\Product\ProductExportService;
use App\Services\Product\ProductReadService;
use App\Services\Product\ProductStockService;
use App\Services\Product\ProductWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API product endpoints — delegates to the same services as {@see \App\Http\Controllers\Web\ProductController}
 * so web and mobile stay aligned. No duplicated business logic here.
 */
class ProductController extends Controller
{
    private ProductWriteService $productWriteService;
    private ProductExportService $productExportService;
    private ProductReadService $productReadService;
    private ProductStockService $productStockService;

    public function __construct(
        ProductWriteService $productWriteService,
        ProductExportService $productExportService,
        ProductReadService $productReadService,
        ProductStockService $productStockService
    ) {
        $this->productWriteService = $productWriteService;
        $this->productExportService = $productExportService;
        $this->productReadService = $productReadService;
        $this->productStockService = $productStockService;

        $this->middleware('permission:view product')->only([
            'getStockHistory',
            'reconcileStock',
            'initialProductDetails',
            'index',
            'getProductDetails',
            'getLastProduct',
            'getProductsByCategory',
            'checkSkuUniqueness',
            'getAllProductStocks',
            'autocompleteStock',
            'getNotifications',
            'markNotificationsAsSeen',
            'OpeningStockGetAll',
            'getProductLocations',
            'getImeis',
            'showSubCategoryDetailsUsingByMainCategoryId',
        ]);
        $this->middleware('permission:create product')->only(['storeOrUpdate']);
        $this->middleware('permission:edit product')->only(['editProduct', 'toggleStatus', 'saveChanges', 'applyDiscount']);
        $this->middleware('permission:delete product')->only(['destroy', 'deleteImei']);
        $this->middleware('permission:manage opening stock')->only([
            'showOpeningStock',
            'editOpeningStock',
            'storeOrUpdateOpeningStock',
            'deleteOpeningStock',
        ]);
        $this->middleware('permission:imei management')->only(['saveOrUpdateImei', 'updateSingleImei']);
        $this->middleware('permission:import product')->only(['importProduct', 'importProductStore']);
        $this->middleware('permission:export product')->only(['exportBlankTemplate', 'exportProducts']);
    }

    public function getStockHistory($productId)
    {
        return $this->productStockService->getStockHistory(request(), $productId);
    }

    public function reconcileStock(int $productId): JsonResponse
    {
        return $this->productStockService->reconcileStock(request(), $productId);
    }

    public function initialProductDetails()
    {
        return $this->productReadService->respondInitialProductDetails(auth()->user());
    }

    public function index()
    {
        return $this->productReadService->respondIndexJson(auth()->user());
    }

    public function getProductDetails($id)
    {
        return $this->productReadService->respondProductDetailsJson((int) $id);
    }

    public function getLastProduct()
    {
        return $this->productReadService->respondLastProductJson();
    }

    public function getProductsByCategory($categoryId)
    {
        return $this->productReadService->respondProductsByCategoryJson((int) $categoryId);
    }

    public function editProduct($id)
    {
        return $this->productReadService->respondEditProduct(request(), (int) $id, auth()->user());
    }

    public function storeOrUpdate(StoreOrUpdateProductRequest $request, $id = null)
    {
        return $this->productWriteService->respondStoreOrUpdate($request, $id ? (int) $id : null);
    }

    public function checkSkuUniqueness(CheckSkuUniquenessRequest $request)
    {
        return $this->productReadService->respondCheckSkuUniqueness($request);
    }

    public function showOpeningStock($productId)
    {
        return $this->productStockService->showOpeningStock(request(), $productId);
    }

    public function editOpeningStock($productId)
    {
        return $this->productStockService->editOpeningStock(request(), $productId);
    }

    public function storeOrUpdateOpeningStock(Request $request, $productId)
    {
        return $this->productStockService->storeOrUpdateOpeningStock($request, $productId);
    }

    public function deleteOpeningStock($productId)
    {
        return $this->productStockService->deleteOpeningStock($productId);
    }

    public function saveOrUpdateImei(Request $request)
    {
        return $this->productStockService->saveOrUpdateImei($request);
    }

    public function updateSingleImei(Request $request)
    {
        return $this->productStockService->respondUpdateSingleImei($request);
    }

    public function deleteImei(Request $request)
    {
        return $this->productStockService->respondDeleteImei($request);
    }

    public function getImeis($productId, Request $request)
    {
        return $this->productStockService->getImeis((int) $productId, $request);
    }

    public function OpeningStockGetAll()
    {
        return $this->productStockService->openingStockGetAll();
    }

    public function getAllProductStocks(Request $request)
    {
        return $this->productStockService->getAllProductStocks($request);
    }

    public function autocompleteStock(Request $request)
    {
        return $this->productStockService->autocompleteStock($request);
    }

    public function getNotifications()
    {
        return $this->productStockService->getNotifications();
    }

    public function markNotificationsAsSeen()
    {
        return $this->productStockService->markNotificationsAsSeen();
    }

    public function showSubCategoryDetailsUsingByMainCategoryId(string $main_category_id)
    {
        return $this->productReadService->respondSubCategoriesByMainCategoryId($main_category_id);
    }

    /**
     * Mobile clients: upload Excel via POST /import-product-excel-store (multipart).
     */
    public function importProduct(): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'POST to import-product-excel-store with file (xlsx/xls) and import_location.',
        ]);
    }

    public function destroy(int $id)
    {
        return $this->productWriteService->respondDestroyProduct($id);
    }

    public function exportBlankTemplate()
    {
        return $this->productExportService->downloadBlankImportTemplate();
    }

    public function exportProducts()
    {
        return $this->productExportService->downloadProductsExport();
    }

    public function importProductStore(ImportProductsExcelRequest $request)
    {
        return $this->productWriteService->respondImportProductsFromExcel($request, true);
    }

    public function getProductLocations(Request $request)
    {
        return $this->productReadService->getProductLocationsPayload(
            $request->input('product_ids', [])
        );
    }

    public function saveChanges(SaveProductLocationsRequest $request)
    {
        return $this->productWriteService->respondSaveChanges($request);
    }

    public function applyDiscount(ApplyDiscountRequest $request)
    {
        return $this->productWriteService->respondApplyDiscount($request);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        return $this->productWriteService->respondToggleStatusForApi($id);
    }
}
