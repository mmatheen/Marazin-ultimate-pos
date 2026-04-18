<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Requests\Product\ApplyDiscountRequest;
use App\Http\Requests\Product\SaveProductLocationsRequest;
use App\Http\Requests\Product\ImportProductsExcelRequest;
use App\Http\Requests\Product\CheckSkuUniquenessRequest;
use App\Http\Requests\Product\QuickAddProductRequest;
use App\Http\Requests\Product\StoreOrUpdateProductRequest;
use App\Http\Requests\Product\UpdateBatchPricesRequest;
use App\Services\Product\ProductBatchPriceService;
use App\Services\Product\ProductExportService;
use App\Services\Product\ProductReadService;
use App\Services\Product\ProductStockService;
use App\Services\Product\ProductWriteService;

class ProductController extends Controller
{
    private ProductWriteService $productWriteService;
    private ProductBatchPriceService $productBatchPriceService;
    private ProductExportService $productExportService;
    private ProductReadService $productReadService;
    private ProductStockService $productStockService;

    public function __construct(
        ProductWriteService $productWriteService,
        ProductBatchPriceService $productBatchPriceService,
        ProductExportService $productExportService,
        ProductReadService $productReadService,
        ProductStockService $productStockService
    )
    {
        $this->productWriteService = $productWriteService;
        $this->productBatchPriceService = $productBatchPriceService;
        $this->productExportService = $productExportService;
        $this->productReadService = $productReadService;
        $this->productStockService = $productStockService;

        $this->middleware('permission:view product', ['only' => ['product', 'index', 'getProductDetails', 'getLastProduct', 'getProductsByCategory', 'initialProductDetails', 'getStockHistory', 'getAllProductStocks', 'autocompleteStock', 'getNotifications', 'OpeningStockGetAll', 'getImeis', 'showSubCategoryDetailsUsingByMainCategoryId', 'updatePrice']]);
        $this->middleware('permission:create product', ['only' => ['addProduct', 'storeOrUpdate', 'quickAdd']]);
        $this->middleware('permission:edit product', ['only' => ['editProduct']]);
        $this->middleware('permission:delete product', ['only' => ['deleteImei']]);
        $this->middleware('permission:import product', ['only' => ['importProduct', 'importProductStore']]);
        $this->middleware('permission:export product', ['only' => ['exportBlankTemplate', 'exportProducts']]);
        $this->middleware('permission:edit batch prices', ['only' => ['getProductBatches', 'updateBatchPrices']]);
    }

    public function product()
    {
        return view('product.product');
    }

    public function addProduct()
    {
        return view('product.add_product');
    }


    public function getStockHistory($productId)
    {
        return $this->productStockService->getStockHistory(request(), $productId);
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

    /**
     * Check if SKU is unique (for real-time validation)
     */
    public function checkSkuUniqueness(CheckSkuUniquenessRequest $request)
    {
        return $this->productReadService->respondCheckSkuUniqueness($request);
    }

    /**
     * Quick Add Product from POS (when product not found in system)
     */
    public function quickAdd(QuickAddProductRequest $request)
    {
        return $this->productWriteService->respondQuickAdd($request);
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

    public function updatePrice()
    {
        return view('product.update_price');
    }

    public function importProduct()
    {
        return view('product.import_product');
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
        return $this->productWriteService->respondImportProductsFromExcel($request, false);
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

    /**
     * Toggle product active/inactive status
     */
    public function toggleStatus(int $id)
    {
        return $this->productWriteService->respondToggleStatus($id);
    }

    /**
     * Get all batches for a specific product with their prices
     */
    public function getProductBatches($productId)
    {
        return $this->productBatchPriceService->respondProductBatchesJson((int) $productId);
    }

    /**
     * Update batch prices (excluding original_price/cost price)
     */
    public function updateBatchPrices(UpdateBatchPricesRequest $request)
    {
        return $this->productBatchPriceService->respondUpdateBatchPricesJson($request->input('batches', []));
    }
}
