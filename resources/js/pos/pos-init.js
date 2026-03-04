'use strict';

// Local aliases for namespaced modules
const PosCustomer = (window.Pos && window.Pos.Customer) || {};
const PosCart     = (window.Pos && window.Pos.Cart)     || {};
const PosLocation = (window.Pos && window.Pos.Location) || {};

// Alias frequently used functions
const getProductDataById    = PosCustomer.getProductDataById;
const getBatchDataById      = PosCustomer.getBatchDataById;
const getCustomerTypePrice  = PosCustomer.getCustomerTypePrice;
const updateBillingRowPrice = PosCustomer.updateBillingRowPrice;
const getCurrentCustomer    = PosCustomer.getCurrentCustomer;
const updateTotals          = PosCart.updateTotals || function () {};
const fetchAllLocations     = PosLocation.fetchAllLocations || function () {};

/**
 * ============================================================
 * POS Init Module (Phase 18)
 * ============================================================
 * Core initialisation for the POS page:
 *   - CSRF AJAX setup
 *   - Permission & shipping globals
 *   - Cache management (clearAllCaches, storage listeners, refresh helpers)
 *   - DOMContentLoaded: calls init functions in order (see list below).
 *   - Init functions: initGlobalErrorHandler, initPosState, initLocationDropdown,
 *     initModals, initCategoriesBrandsAutocomplete, initCashRegister, initImageHealth,
 *     initFocusAndPrint, initCustomerHandler, initMobileProductModal, initCloseOffcanvas,
 *     initGlobalDiscount.
 *   - Helpers (scope of DOMContentLoaded): initDOMElements, updateAllBillingRowsPricing,
 *     handleLocationChange.
 * ============================================================
 */

/* ── CSRF ───────────────────────────────────────────────────── */
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

window.checkCSRFToken = function () {
    return $('meta[name="csrf-token"]').attr('content');
};

/* ── User Permissions (reads bare globals from pos.blade.php config block) ── */
var userPermissions = {
    canEditSale:      canEditSale,
    canDeleteSale:    canDeleteSale,
    canEditProduct:   canEditProduct,
    canDeleteProduct: canDeleteProduct
};
window.userPermissions = userPermissions;

/* ── Shipping Data ──────────────────────────────────────────── */
var shippingData = {
    shipping_details:  '',
    shipping_address:  '',
    shipping_charges:  0,
    shipping_status:   'pending',
    delivered_to:      '',
    delivery_person:   ''
};
window.shippingData = shippingData;

/* ── Cache Management ───────────────────────────────────────── */
function clearAllCaches() {
    customerCache.clear();
    staticDataCache.clear();
    searchCache.clear();
    domElementCache = {};
    customerPriceCache.clear();
    cachedLocations = null;
    locationCacheExpiry = null;
    window.cachedLocations = null;
    window.locationCacheExpiry = null;
    failedImages.clear();
    imageAttempts.clear();
    stockData.length = 0;
    allProducts.length = 0;
}

/* Cross-tab cache invalidation */
window.addEventListener('storage', function (e) {
    if (e.key === 'product_cache_invalidate') {
        clearAllCaches();
        if (selectedLocationId) {
            window.fetchPaginatedProducts && window.fetchPaginatedProducts(true);
        }
    }

    if (e.key === 'batch_prices_updated' && e.newValue) {
        try {
            var payload = JSON.parse(e.newValue);
            var updatedProductId = payload && payload.productId;
            if (updatedProductId) {
                if ((!stockData || stockData.length === 0) && window.stockData && window.stockData.length > 0) {
                    stockData = window.stockData;
                }
                if ((!allProducts || allProducts.length === 0) && window.allProducts && window.allProducts.length > 0) {
                    allProducts = window.allProducts;
                }
                var idx = stockData.findIndex(function (s) { return String(s.product.id) === String(updatedProductId); });
                if (idx !== -1) stockData.splice(idx, 1);
                var apIdx = allProducts.findIndex(function (s) { return String(s.product && s.product.id) === String(updatedProductId); });
                if (apIdx !== -1) allProducts.splice(apIdx, 1);
            } else {
                clearAllCaches();
                if (selectedLocationId) window.fetchPaginatedProducts && window.fetchPaginatedProducts(true);
            }
        } catch (err) {
            clearAllCaches();
            if (selectedLocationId) window.fetchPaginatedProducts && window.fetchPaginatedProducts(true);
        }
    }
});

/* Console helpers for manual cache refresh */
window.refreshPOSCache = function () {
    clearAllCaches();
    if (typeof window.initAutocomplete === 'function') {
        try {
            $("#productSearchInput").autocomplete('destroy');
            window.initAutocomplete();
        } catch (e) { /* ignore */ }
    }
    if (selectedLocationId) {
        window.fetchPaginatedProducts && window.fetchPaginatedProducts(true);
        toastr.info('Cache refreshed! Product data updated.', 'Cache Refresh');
    } else {
        toastr.info('Cache cleared. Select a location to refresh products.', 'Cache Cleared');
    }
};

window.refreshLocationCache = function () {
    cachedLocations = null;
    locationCacheExpiry = null;
    window.cachedLocations = null;
    window.locationCacheExpiry = null;
        fetchAllLocations(true);
    toastr.info('Location cache refreshed!', 'Cache Refresh');
};

window.clearImageCache = function () {
    var count = failedImages.size;
    failedImages.clear();
    imageAttempts.clear();
    toastr.info('Image cache cleared! (' + count + ' entries removed)', 'Cache Cleared');
};

/* ── Closure-scoped variables (module-level for DOMContentLoaded access) ── */
var posProduct, billingBody, discountInput, finalValue, categoryBtn, allProductsBtn, subcategoryBackBtn;
var selectedLocationId   = null;
var currentProductsPage  = 1;
var hasMoreProducts      = true;
var isLoadingProducts    = false;
var allProducts          = [];
var stockData            = [];
var isEditing            = false;
var currentEditingSaleId = null;
var isEditingFinalizedSale = false;
var cachedLocations      = null;
var locationCacheExpiry  = null;
var currentFilter        = { type: null, id: null };
var isSalesRep           = false;
var cashItemCounter      = 0;

/* ── Shared helpers (module level — used by multiple init functions) ─── */

function initDOMElements() {
    posProduct         = getCachedElement('posProduct');
    billingBody        = getCachedElement('billing-body');
    discountInput      = getCachedElement('discount');
    finalValue         = getCachedElement('total');
    categoryBtn        = getCachedElement('category-btn');
    allProductsBtn     = getCachedElement('allProductsBtn');
    subcategoryBackBtn = getCachedElement('subcategoryBackBtn');
}

var _editModeToastShown = false;
window._editModeToastShown = false;

function updateAllBillingRowsPricing(newCustomerType) {
    if (window.isEditing) {
        if (!window._editModeToastShown) {
            window._editModeToastShown = true;
            toastr.info('Edit Mode: Original sale prices preserved. Customer pricing not applied.', 'Edit Mode Active');
        }
        return;
    }
    window._editModeToastShown = false;
    var billingBodyEl = document.getElementById('billing-body');
    var existingRows  = billingBodyEl ? billingBodyEl.querySelectorAll('tr') : [];
    if (existingRows.length === 0) return;
    existingRows.forEach(function (row) {
        try {
            var productId = row.getAttribute('data-product-id');
            var batchId   = row.getAttribute('data-batch-id');
            if (!productId) return;
            var productData = null;
            var batchData   = null;
            var productAttr = row.getAttribute('data-product-pricing');
            var batchAttr   = row.getAttribute('data-batch-pricing');
            if (productAttr) { try { productData = JSON.parse(productAttr); } catch (e) { /* ignore */ } }
            if (batchAttr) { try { batchData = JSON.parse(batchAttr); } catch (e) { /* ignore */ } }
            if (!productData) productData = getProductDataById(productId);
            if (!batchData && batchId) batchData = getBatchDataById(batchId);
            if (!productData) return;
            var pricingResult = getCustomerTypePrice(batchData, productData, newCustomerType);
            if (pricingResult.hasError || pricingResult.price <= 0) return;
            updateBillingRowPrice(row, pricingResult.price, pricingResult.source);
        } catch (error) { /* skip row */ }
    });
    updateTotals();
}

function handleLocationChange(event) {
    selectedLocationId = $(event.target).val();
    window.selectedLocationId = selectedLocationId;
    currentProductsPage = 1;
    window.currentProductsPage = currentProductsPage;
    hasMoreProducts = true;
    window.hasMoreProducts = hasMoreProducts;
    allProducts = [];
    window.allProducts = allProducts;
    if (!posProduct) posProduct = document.getElementById('posProduct');
    if (posProduct) posProduct.innerHTML = '';
    if (selectedLocationId) {
        var productListArea = document.getElementById('productListArea');
        var mainContent     = document.getElementById('mainContent');
        if (productListArea && mainContent) {
            productListArea.classList.remove('d-none');
            productListArea.classList.add('show');
            mainContent.classList.remove('col-md-12');
            mainContent.classList.add('col-md-7');
        }
        window.fetchPaginatedProducts(true);
    } else {
        var productListArea2 = document.getElementById('productListArea');
        var mainContent2     = document.getElementById('mainContent');
        if (productListArea2 && mainContent2) {
            productListArea2.classList.add('d-none');
            productListArea2.classList.remove('show');
            mainContent2.classList.remove('col-md-7');
            mainContent2.classList.add('col-md-12');
        }
    }
    billingBody.innerHTML = '';
    updateTotals();
    if (window.isSalesRep && selectedLocationId) window.checkAndToggleSalesRepButtons(selectedLocationId);
    setTimeout(function () { var inp = document.getElementById('productSearchInput'); if (inp) inp.focus(); }, 300);
}

/* ── DOMContentLoaded: run all inits in order ─────────────────── */
/* initDOMElements runs early so posProduct/billingBody etc exist before
   initLocationDropdown callback can trigger handleLocationChange */
document.addEventListener('DOMContentLoaded', function () {
    initGlobalErrorHandler();
    initPosState();
    initDOMElements();
    initLocationDropdown();
    initModals();
    initCategoriesBrandsAutocomplete();
    initCashRegister();
    initImageHealth();
    initFocusAndPrint();
    initCustomerHandler();
    initMobileProductModal();
    initCloseOffcanvas();
    initGlobalDiscount();
});

/* ── Init helpers (defined at module level, called from DOMContentLoaded above) ── */
function initGlobalErrorHandler() {
    window.addEventListener('error', function (e) {
        if (e.message && e.message.includes('appendChild')) {
            e.preventDefault();
            return true;
        }
        if (e.message && (e.message.includes('Infinity') || e.message.includes('cannot be parsed'))) {
            e.preventDefault();
            return true;
        }
    });
}

function initPosState() {
    window.isEditing = false;
    window.currentEditingSaleId = null;
    window.isEditingFinalizedSale = false;
    window.getPosState = function () {
        return {
            isEditing: window.isEditing,
            isEditingFinalizedSale: window.isEditingFinalizedSale,
            shippingData: window.shippingData
        };
    };
    function navigateToPosCreate() {
        if (window.location.pathname.includes('/edit/') || window.location.pathname.includes('/sales/')) {
            window.location.href = '/pos-create';
            return;
        }
        window.history.pushState({ page: 'pos-create' }, 'POS', '/pos-create');
        isEditing = false;
        currentEditingSaleId = null;
        window.isEditing = false;
        window.currentEditingSaleId = null;
        isEditingFinalizedSale = false;
        window.isEditingFinalizedSale = false;
        window.originalSaleData = null;
        window._editModeToastShown = false;
        var saleInvoiceElement = document.getElementById('sale-invoice-no');
        if (saleInvoiceElement) saleInvoiceElement.textContent = '';
        document.title = 'POS - Create Sale';
        window.resetForm();
        setTimeout(function () {
            var searchInput = document.getElementById('productSearchInput');
            if (searchInput) { searchInput.focus(); searchInput.select(); }
        }, 100);
    }
    window.navigateToPosCreate = navigateToPosCreate;
    window.addEventListener('popstate', function () {
        if (window.location.pathname === '/pos-create') navigateToPosCreate();
    });
    window.isSalesRep = false;
    window.selectedLocationId   = selectedLocationId;
    window.allProducts          = allProducts;
    window.stockData            = stockData;
    window.isLoadingProducts    = isLoadingProducts;
    window.hasMoreProducts      = hasMoreProducts;
    window.currentProductsPage  = currentProductsPage;
    window.currentFilter = currentFilter;
    window.setCurrentFilter = function (f) {
        currentFilter = f;
        window.currentFilter = f;
    };
}

function initLocationDropdown() {
    fetchAllLocations(false, function () {

        if (window.PosConfig.auth.isSalesRepUser) {
            window.checkSalesRepStatus(function () {
                isSalesRep = window.isSalesRep;
            });
            window.protectSalesRepCustomerFiltering();
        } else {
            window.isSalesRep = false;
            isSalesRep = false;

            if (!isEditing) {
                var locationSelect        = $('#locationSelect');
                var locationSelectDesktop  = $('#locationSelectDesktop');

                if (window.cachedLocations && window.cachedLocations.length > 0) {
                    var parentLocations     = window.cachedLocations.filter(function (loc) { return !loc.parent_id; });
                    var firstParentLocation = parentLocations[0];

                    if (firstParentLocation) {
                        setTimeout(function () {
                            locationSelect.val(firstParentLocation.id).trigger('change');
                            locationSelectDesktop.val(firstParentLocation.id).trigger('change');
                        }, 200);
                    }
                }
            }
        }

        /* Edit-mode detection */
        var saleId = null;
        var pathSegments = window.location.pathname.split('/');
        saleId = pathSegments[pathSegments.length - 1];

        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            window.fetchEditSale(saleId);
        }
    });
    $('#locationSelect').on('change', handleLocationChange);
    $('#locationSelectDesktop').on('change', handleLocationChange);
    $('#locationSelect').on('change', function () {
        $("#productSearchInput").val('');
        if ($("#productSearchInput").data('ui-autocomplete')) {
            $("#productSearchInput").autocomplete('destroy');
        }
        if (typeof window.initAutocomplete === 'function') window.initAutocomplete();
    });
}

function initModals() {
    $(document).on('show.bs.modal', '#mobileMenuModal', function () {
        if (window.isSalesRep) {
            var selection = window.getSalesRepSelection();
            if (selection && selection.vehicle && selection.route) {
                setTimeout(function () { window.updateMobileSalesRepDisplay(selection); }, 100);
            }
        }
    });

    $(document).on('show.bs.modal', '#mobilePaymentModal', function () {
        setTimeout(function () {
            if (window.isSalesRep) {
                var locationSelect = document.getElementById('locationSelect');
                if (locationSelect && locationSelect.value) {
                    window.checkAndToggleSalesRepButtons(locationSelect.value);
                } else {
                    window.hideSalesRepButtonsExceptSaleOrder();
                }
            } else {
                window.showAllSalesRepButtons();
            }
        }, 50);
    });

    setTimeout(function () {
        if (!window.isSalesRep) window.showAllSalesRepButtons();
    }, 1000);
}

function initCategoriesBrandsAutocomplete() {
    try { window.fetchCategories(); } catch (e) { /* ignore */ }
    try { window.fetchBrands();     } catch (e) { /* ignore */ }
    if (typeof window.initAutocomplete === 'function') window.initAutocomplete();
    initDOMElements();
}

function initCashRegister() {
    function addCashItem(price, qty) {
        if (!miscItemProductId || miscItemProductId === 0) {
            toastr.error('Cash Item product not configured. Create a product and set MISC_ITEM_PRODUCT_ID in .env', 'Setup Required');
            return;
        }
        cashItemCounter++;
        var label = 'Cash Item ' + cashItemCounter;

        var product = {
            id: miscItemProductId,
            product_name: label,
            sku: 'CASH-ITEM',
            stock_alert: 0,
            retail_price: price,
            whole_sale_price: price,
            special_price: price,
            max_retail_price: price,
            original_price: price
        };
        var stockEntry = {
            product: product,
            total_stock: 999999,
            batches: [],
            discounts: [],
            imei_numbers: []
        };
                if (window.Pos && window.Pos.Billing && typeof window.Pos.Billing.addProductToBillingBody === 'function') {
                    window.Pos.Billing.addProductToBillingBody(product, stockEntry, price, 'all', 999999, 'retail', qty);
                }
        document.getElementById('cashPriceInput').value = '';
        document.getElementById('cashQtyInput').value   = '1';
        document.getElementById('cashPriceInput').focus();
        var bar = document.getElementById('cashEntryBar');
        var tog = document.getElementById('cashEntryToggle');
        if (bar && !bar.classList.contains('show')) {
            bar.classList.add('show');
            if (tog) { tog.classList.remove('btn-outline-secondary'); tog.classList.add('btn-warning'); }
        }
    }

    var cashPriceInput = document.getElementById('cashPriceInput');
    var cashQtyInput   = document.getElementById('cashQtyInput');

    if (cashPriceInput) {
        cashPriceInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var price = parseFloat(this.value);
                var qty   = parseFloat(cashQtyInput.value) || 1;
                if (price > 0) addCashItem(price, qty);
                else toastr.warning('Enter a price first', 'Quick Add');
            }
        });
    }

    if (cashQtyInput) {
        cashQtyInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var price = parseFloat(cashPriceInput.value);
                var qty   = parseFloat(this.value) || 1;
                if (price > 0) addCashItem(price, qty);
                else if (cashPriceInput) cashPriceInput.focus();
            }
        });
    }

    var cashEntryToggle = document.getElementById('cashEntryToggle');
    var cashEntryBar    = document.getElementById('cashEntryBar');
    var cashEntryClose  = document.getElementById('cashEntryClose');

    function openCashEntry() {
        if (cashEntryBar) cashEntryBar.classList.add('show');
        if (cashEntryToggle) { cashEntryToggle.classList.remove('btn-outline-secondary'); cashEntryToggle.classList.add('btn-warning'); }
        setTimeout(function () { cashPriceInput && cashPriceInput.focus(); }, 50);
    }
    function closeCashEntry() {
        if (cashEntryBar) cashEntryBar.classList.remove('show');
        if (cashEntryToggle) { cashEntryToggle.classList.remove('btn-warning'); cashEntryToggle.classList.add('btn-outline-secondary'); }
    }

    if (cashEntryToggle) {
        cashEntryToggle.addEventListener('click', function () {
            cashEntryBar && cashEntryBar.classList.contains('show') ? closeCashEntry() : openCashEntry();
        });
    }
    if (cashEntryClose) {
        cashEntryClose.addEventListener('click', closeCashEntry);
    }
}

function initImageHealth() {
    setTimeout(function () {
        checkImageHealth();
        refreshProductImages();
    }, 3000);
}

function initFocusAndPrint() {
    setTimeout(function () {
        var productSearchInput = document.getElementById('productSearchInput');
        if (productSearchInput) {
            productSearchInput.focus();
            productSearchInput.select();
            productSearchInput.style.transition  = 'all 0.3s ease';
            productSearchInput.style.boxShadow   = '0 0 10px rgba(0, 123, 255, 0.3)';
            productSearchInput.style.borderColor  = '#007bff';
            setTimeout(function () {
                productSearchInput.style.boxShadow  = '';
                productSearchInput.style.borderColor = '';
            }, 2000);
        }
    }, 500);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            setTimeout(function () {
                var inp = document.getElementById('productSearchInput');
                if (inp && document.activeElement !== inp) inp.focus();
            }, 200);
        }
    });
    window.addEventListener('afterprint', function () {
        setTimeout(function () {
            var inp = document.getElementById('productSearchInput');
            if (inp) { inp.focus(); inp.select(); }
        }, 300);
    });
}

function initCustomerHandler() {
    $('#customer-id').on('change', function () {
        customerPriceCache.clear();

        var billingBodyEl = document.getElementById('billing-body');
        var existingRows  = billingBodyEl ? billingBodyEl.querySelectorAll('tr') : [];

        if (existingRows.length > 0) {
            var currentCustomer = getCurrentCustomer();
            updateAllBillingRowsPricing(currentCustomer.customer_type);
        }
    });
}

function initMobileProductModal() {
    if (allProductsBtn) {
        allProductsBtn.onclick = function () { showAllProducts(); };
    }

    /* ── Mobile product modal ────────────────────────────── */
    var mobileProductModal = document.getElementById('mobileProductModal');
    if (mobileProductModal) {
        mobileProductModal.addEventListener('show.bs.modal', function () {
            window.displayMobileProducts(window.allProducts);
        });
    }

    var mobileAllProductsBtn = document.getElementById('mobileAllProductsBtn');
    if (mobileAllProductsBtn) {
        mobileAllProductsBtn.addEventListener('click', function () {
            if (!selectedLocationId) {
                toastr.error('Please select a location first', 'Location Required');
                return;
            }
            currentFilter = null;
            window.currentFilter = null;
            currentProductsPage = 1;
            window.currentProductsPage = 1;
            hasMoreProducts = true;
            window.hasMoreProducts = true;
            allProducts = [];
            window.allProducts = allProducts;
            window.showLoader();
            window.fetchPaginatedProducts(true);
        });
    }

    var mobileCategoryBtn = document.getElementById('mobileCategoryBtn');
    if (mobileCategoryBtn) {
        mobileCategoryBtn.addEventListener('click', function () {
            var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCategory'));
            offcanvas.show();
        });
    }

    var mobileBrandBtn = document.getElementById('mobileBrandBtn');
    if (mobileBrandBtn) {
        mobileBrandBtn.addEventListener('click', function () {
            var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasBrand'));
            offcanvas.show();
        });
    }

    /* Lazy loading for mobile product modal */
    var mobileProductModalBody = document.getElementById('mobileProductModalBody');
    if (mobileProductModalBody) {
        var isLoadingMobileProducts = false;
        mobileProductModalBody.addEventListener('scroll', function () {
            var scrollTop    = this.scrollTop;
            var scrollHeight = this.scrollHeight;
            var clientHeight = this.clientHeight;

            if (scrollTop + clientHeight >= scrollHeight - 100 && !isLoadingMobileProducts && hasMoreProducts && selectedLocationId) {
                isLoadingMobileProducts = true;
                if (currentFilter) {
                    window.fetchFilteredProducts(currentFilter.type, currentFilter.id, false);
                } else {
                    window.fetchPaginatedProducts(false);
                }
                setTimeout(function () { isLoadingMobileProducts = false; }, 1000);
            }
        });
    }
}

function initCloseOffcanvas() {
    function closeOffcanvas(offcanvasId) {
        var offcanvasElement = document.getElementById(offcanvasId);
        var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (bsOffcanvas) bsOffcanvas.hide();
    }
    window.closeOffcanvas = closeOffcanvas;
}

function initGlobalDiscount() {
    var globalDiscountInput     = document.getElementById('global-discount');
    var globalDiscountTypeInput = document.getElementById('discount-type');

    if (globalDiscountInput) {
        globalDiscountInput.addEventListener('input', function () { updateTotals(); });

        globalDiscountInput.addEventListener('change', function () {
            var discountValue = parseFloat(this.value) || 0;
            var discountType  = globalDiscountTypeInput.value;
            if (discountType === 'percentage') this.value = Math.min(discountValue, 100);
            updateTotals();
        });

        globalDiscountInput.addEventListener('blur', function () {
            var discountValue = parseFloat(this.value) || 0;
            var discountType  = globalDiscountTypeInput.value;
            if (discountType === 'percentage') this.value = Math.min(discountValue, 100);
            updateTotals();
        });

        globalDiscountInput.addEventListener('keyup', function () { updateTotals(); });
    }

    if (globalDiscountTypeInput) {
        globalDiscountTypeInput.addEventListener('change', function () {
            if (globalDiscountInput) {
                globalDiscountInput.value = '0';
                globalDiscountInput.dispatchEvent(new Event('input',  { bubbles: true }));
                globalDiscountInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            updateTotals();
        });
    }
}
