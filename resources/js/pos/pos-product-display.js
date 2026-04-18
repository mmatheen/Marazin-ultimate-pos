'use strict';

/**
 * Phase 12: pos-product-display.js
 *
 * Loader helpers, category/subcategory/brand fetch + render,
 * paginated product fetch, lazy-load setup, display products,
 * filter product grid, mobile product display, mobile qty modal,
 * filtered products fetch.
 *
 * Dependencies (from earlier modules / window.*):
 *   pos-cache.js   → getCachedStaticData, setCachedStaticData, getCachedElement
 *   pos-ui.js      → createSafeImage
 *   pos-product-grid.js → filterProductsByCategory/SubCategory/Brand, showAllProducts
 *   pos_ajax       → window.addProductToTable, window.normalizeBatches, window.closeOffcanvas
 *                    window.setCurrentFilter, window.showFreeQtyColumn
 *
 * State lives on window.* (written by pos_ajax / this module):
 *   window.selectedLocationId, window.currentProductsPage, window.hasMoreProducts
 *   window.isLoadingProducts, window.allProducts, window.stockData, window.currentFilter
 */

// ---- Request-retry tracking (owned by fetchPaginatedProducts) ----
let _pdRetryCount   = 0;
const _pdMaxRetries = 3;
const _pdBaseDelay  = 1000; // 1 s

// ---- Loader helpers ----

function showLoader() {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) return;
    const productListArea = document.getElementById('productListArea');
    const mainContent = document.getElementById('mainContent');
    if (productListArea) {
        productListArea.classList.remove('d-none');
        productListArea.classList.add('show');
    }
    if (mainContent) {
        mainContent.classList.remove('col-md-12');
        mainContent.classList.add('col-md-7');
    }
    posProduct.innerHTML = `
    <div class="product-grid-loader-wrap">
        <div class="loader loader-more" role="status" aria-hidden="false">
            <span class="visually-hidden">Loading products...</span>
        </div>
        <small class="text-muted d-block mt-2">Loading products...</small>
    </div>`;
}

function hideLoader() {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) return;

    const mainLoader   = posProduct.querySelector('.loader-container');
    const productWrap  = posProduct.querySelector('.product-grid-loader-wrap');
    const smallLoader  = posProduct.querySelector('.small-loader');

    if (mainLoader)    mainLoader.remove();
    if (productWrap)   productWrap.remove();
    if (smallLoader)   smallLoader.remove();

    if (!posProduct.querySelector('.product-card')) {
        posProduct.innerHTML = '';
    }
}

// Smaller spinner shown at the bottom while loading additional pages
function showLoaderSmall() {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) return;

    if (posProduct.children.length > 0) {
        let existingSmallLoader = posProduct.querySelector('.small-loader');
        if (!existingSmallLoader) {
            const smallLoader = document.createElement('div');
            smallLoader.className = 'small-loader text-center p-3';
            smallLoader.innerHTML = `
                <div class="loader loader-more" role="status" aria-hidden="false">
                    <span class="visually-hidden">Loading more...</span>
                </div>
                <small class="text-muted d-block mt-1">Loading more products...</small>
            `;
            posProduct.appendChild(smallLoader);
        }
    } else {
        showLoader();
    }
}

// ---- CATEGORY / SUBCATEGORY / BRAND ----

function fetchCategories() {
    const cachedCategories = getCachedStaticData('categories');
    if (cachedCategories) {
        window._allCategories = cachedCategories;
        renderCategories(cachedCategories);
        return;
    }

    fetch('/main-category-get-all')
        .then(response => response.json())
        .then(data => {
            const categories = data.message;
            window._allCategories = categories;
            setCachedStaticData('categories', categories);
            renderCategories(categories);
        })
        .catch(error => console.error('Error fetching categories:', error));
}

function renderCategories(categories) {
    const categoryContainer = getCachedElement('categoryContainer');

    try {
        categoryContainer.innerHTML = '';
    } catch (clearError) {
        return;
    }

    if (Array.isArray(categories)) {
        categories.forEach((category) => {
            try {
                const card = document.createElement('div');
                card.classList.add('category-card');
                card.setAttribute('data-id', category.id);

                const cardTitle = document.createElement('h6');
                cardTitle.textContent = category.mainCategoryName;
                card.appendChild(cardTitle);

                const buttonContainer = document.createElement('div');
                buttonContainer.classList.add('category-footer');

                const allButton = document.createElement('button');
                allButton.textContent = 'All';
                allButton.classList.add('btn', 'btn-outline-green', 'me-2');
                allButton.addEventListener('click', () => {
                    if (typeof window.filterProductsByCategory === 'function') {
                        window.filterProductsByCategory(category.id);
                    }
                    if (typeof window.closeOffcanvas === 'function') {
                        window.closeOffcanvas('offcanvasCategory');
                    }
                });

                const nextButton = document.createElement('button');
                nextButton.textContent = 'Next >>';
                nextButton.classList.add('btn', 'btn-outline-purple');
                nextButton.addEventListener('click', () => {
                    fetchSubcategories(category.id);
                });

                buttonContainer.appendChild(allButton);
                buttonContainer.appendChild(nextButton);
                card.appendChild(buttonContainer);

                if (categoryContainer &&
                    typeof categoryContainer.appendChild === 'function' &&
                    categoryContainer.parentNode) {
                    categoryContainer.appendChild(card);
                } else {
                }
            } catch (categoryError) {
                console.error('[FETCHCATEGORIES] Error processing category:', category, categoryError);
            }
        });
    } else {
        console.error('[FETCHCATEGORIES] Categories not found or not array:', categories);
    }
}

function renderSubcategories(subcategories, options) {
    const opts = options || {};
    const switchCanvas = opts.switchCanvas !== false;

    const subcategoryContainer = document.getElementById('subcategoryContainer');
    if (!subcategoryContainer) return;
    subcategoryContainer.innerHTML = '';

    if (Array.isArray(subcategories)) {
        subcategories.forEach(subcategory => {
            const card = document.createElement('div');
            card.classList.add('card', 'subcategory-card', 'mb-3');
            card.setAttribute('data-id', subcategory.id);

            const cardBody = document.createElement('div');
            cardBody.classList.add('card-body');

            const cardTitle = document.createElement('h6');
            cardTitle.classList.add('card-title');
            cardTitle.textContent = subcategory.subCategoryname;
            cardBody.appendChild(cardTitle);

            card.appendChild(cardBody);

            card.addEventListener('click', () => {
                if (typeof window.filterProductsBySubCategory === 'function') {
                    window.filterProductsBySubCategory(subcategory.id);
                }
                if (typeof window.closeOffcanvas === 'function') {
                    window.closeOffcanvas('offcanvasSubcategory');
                }
            });

            subcategoryContainer.appendChild(card);
        });
    }

    if (switchCanvas) {
        const subcategoryOffcanvas = new bootstrap.Offcanvas(
            document.getElementById('offcanvasSubcategory'));
        subcategoryOffcanvas.show();

        const categoryOffcanvas = bootstrap.Offcanvas.getInstance(
            document.getElementById('offcanvasCategory'));
        if (categoryOffcanvas) categoryOffcanvas.hide();
    }
}

function fetchSubcategories(categoryId) {
    const subcategorySearchInput = document.getElementById('subcategorySearchInput');
    if (subcategorySearchInput) subcategorySearchInput.value = '';

    fetch(`/sub_category-details-get-by-main-category-id/${categoryId}`)
        .then(response => response.json())
        .then(data => {
            const subcategories = data.message || [];
            window._allSubcategories = Array.isArray(subcategories) ? subcategories : [];
            renderSubcategories(window._allSubcategories);
        })
        .catch(error => console.error('Error fetching subcategories:', error));
}

function fetchBrands() {
    const cachedBrands = getCachedStaticData('brands');
    if (cachedBrands) {
        window._allBrands = cachedBrands;
        renderBrands(cachedBrands);
        return;
    }

    fetch('/brand-get-all')
        .then(response => response.json())
        .then(data => {
            const brands = data.message;
            window._allBrands = brands;
            setCachedStaticData('brands', brands);
            renderBrands(brands);
        })
        .catch(error => console.error('Error fetching brands:', error));
}

function renderBrands(brands) {
    let brandContainer = getCachedElement('brandContainer');

    if (!brandContainer) {
        console.error('[FETCHBRANDS] Brand container not found in DOM');
        return;
    }

    try {
        brandContainer.innerHTML = '';
    } catch (clearError) {
        console.error('[FETCHBRANDS] Error clearing container:', clearError);
        return;
    }

    if (Array.isArray(brands)) {
        brands.forEach((brand, index) => {
            try {
                // Re-check container on each iteration
                brandContainer = document.getElementById('brandContainer');
                if (!brandContainer) {
                    console.error('[FETCHBRANDS] Container disappeared during iteration:', index);
                    return;
                }

                const brandCard = document.createElement('div');
                brandCard.classList.add('brand-card');
                brandCard.setAttribute('data-id', brand.id);

                const brandName = document.createElement('h6');
                brandName.textContent = brand.name;
                brandCard.appendChild(brandName);

                brandCard.addEventListener('click', () => {
                    if (typeof window.filterProductsByBrand === 'function') {
                        window.filterProductsByBrand(brand.id);
                    }
                    if (typeof window.closeOffcanvas === 'function') {
                        window.closeOffcanvas('offcanvasBrand');
                    }
                });

                if (brandContainer &&
                    typeof brandContainer.appendChild === 'function' &&
                    brandContainer.parentNode) {
                    brandContainer.appendChild(brandCard);
                } else {
                }
            } catch (brandError) {
                console.error('[FETCHBRANDS] Error processing brand:', brand, brandError);
            }
        });
    } else {
        console.error('[FETCHBRANDS] Brands not found or not array:', brands);
    }
}

// ---- PAGINATED PRODUCT FETCH ----

function fetchPaginatedProducts(reset = false, attemptNumber = 0) {
    if (reset) {
        window.hasMoreProducts       = true;
        window.currentProductsPage   = 1;
        window.allProducts           = [];
    }

    const selectedLocationId  = window.selectedLocationId;
    const hasMoreProducts     = window.hasMoreProducts;
    const isLoadingProducts   = window.isLoadingProducts;

    if (isLoadingProducts || !selectedLocationId || !hasMoreProducts) {
        return;
    }

    window.isLoadingProducts = true;
    const perPage = 50;

    if (reset) {
        window.currentProductsPage = 1;
        _pdRetryCount = 0;
        showLoader();
    } else {
        showLoaderSmall();
    }

    const url = `/products/stocks?location_id=${selectedLocationId}&page=${window.currentProductsPage}&per_page=${perPage}&with_stock=1&context=pos`;

    const fetchOptions = {
        method: 'GET',
        cache:  'no-store',
        headers: {
            'Accept':            'application/json',
            'Content-Type':      'application/json',
            'X-Requested-With':  'XMLHttpRequest',
            'X-CSRF-TOKEN':      document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    };

    fetch(url, fetchOptions)
        .then(res => {
            if (!res.ok) {
                if (res.status === 429) {
                    const retryAfter      = parseInt(res.headers.get('Retry-After') || '2', 10) * 1000;
                    const exponentialDelay = Math.min(_pdBaseDelay * Math.pow(2, attemptNumber), 10000);
                    const finalDelay       = Math.max(retryAfter, exponentialDelay);

                    console.warn(`Rate limited (429). Attempt ${attemptNumber + 1}/${_pdMaxRetries}. Retrying after ${finalDelay} ms`);

                    if (attemptNumber < _pdMaxRetries - 1) {
                        window.hasMoreProducts   = false;
                        setTimeout(() => {
                            window.hasMoreProducts   = true;
                            window.isLoadingProducts = false;
                            fetchPaginatedProducts(reset, attemptNumber + 1);
                        }, finalDelay);
                        return Promise.reject({ isHandled: true, message: '429 - Retrying' });
                    } else {
                        console.error('Max retries exceeded for rate limiting');
                        return Promise.reject({
                            isHandled: false,
                            message:   'Rate limit exceeded. Please try again later.',
                            status:    429
                        });
                    }
                } else if (res.status === 419) {
                    console.error('CSRF token mismatch (419)');
                    return Promise.reject({
                        isHandled: false,
                        message:   'Session expired. Please refresh the page.',
                        status:    419
                    });
                } else {
                    return res.text().then(text => {
                        console.error(`HTTP ${res.status} error:`, text);
                        return Promise.reject({
                            isHandled: false,
                            status:    res.status,
                            text,
                            message:   `Server error (${res.status}). Please try again.`
                        });
                    });
                }
            }

            const contentType = res.headers.get('content-type') || '';
            if (contentType.indexOf('application/json') === -1) {
                return res.text().then(text => {
                    console.error('Non-JSON response received:', text.substring(0, 200) + '...');
                    return Promise.reject({
                        isHandled: false,
                        text,
                        message: 'Invalid response format. Please check server configuration.'
                    });
                });
            }
            return res.json();
        })
        .then(data => {
            hideLoader();
            window.isLoadingProducts = false;
            _pdRetryCount = 0;

            // Show product list area
            const productListArea = document.getElementById('productListArea');
            const mainContent     = document.getElementById('mainContent');
            if (productListArea && mainContent && window.selectedLocationId) {
                productListArea.classList.remove('d-none');
                productListArea.classList.add('show');
                mainContent.classList.remove('col-md-12');
                mainContent.classList.add('col-md-7');
            }

            if (!data || data.status !== 200 || !Array.isArray(data.data)) {
                console.warn('⚠️ Invalid data structure received:', data);
                if (reset) {
                    const posProduct = document.getElementById('posProduct');
                    if (posProduct) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                }
                return;
            }

            if (reset) {
                window.allProducts = [];
                window.stockData   = [];
                const posProduct = document.getElementById('posProduct');
                if (posProduct) {
                    posProduct.innerHTML = '';
                } else {
                    console.error('❌ posProduct element not found during reset!');
                }
            }

            data.data.forEach(stock => window.allProducts.push(stock));

            // Keep stockData in sync
            window.stockData = [...window.allProducts];

            if (reset) {
                displayProducts(window.allProducts, false);
                const mobileModal = document.getElementById('mobileProductModal');
                if (mobileModal && mobileModal.classList.contains('show')) {
                    displayMobileProducts(window.allProducts, false);
                }
            } else {
                displayProducts(data.data, true);
                const mobileModal = document.getElementById('mobileProductModal');
                if (mobileModal && mobileModal.classList.contains('show')) {
                    displayMobileProducts(data.data, true);
                }
            }

            if (data.data.length === 0 || data.data.length < perPage) {
                window.hasMoreProducts = false;
            } else {
                window.hasMoreProducts     = true;
                window.currentProductsPage++;
            }
        })
        .catch(err => {
            hideLoader();

            if (err && err.isHandled) return;

            window.isLoadingProducts = false;
            console.error('Error fetching products:', err);

            if (err.text) console.error('Response text:', err.text.substring(0, 500));

            if (reset) {
                const posProduct = document.getElementById('posProduct');
                if (posProduct) {
                    posProduct.innerHTML = '<div class="text-center p-4"><p class="text-danger">Failed to load products</p><button onclick="window.fetchPaginatedProducts(true)" class="btn btn-primary btn-sm">Retry</button></div>';
                }
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(err.message || 'Failed to load products. Please try again.', 'Error');
            }
        });
}

// ---- INFINITE SCROLL ----

function setupLazyLoad() {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) {
        console.error('posProduct element not found for lazy loading');
        return;
    }

    let scrollThrottleTimer = null;
    const throttleDelay = 200;

    posProduct.addEventListener('scroll', () => {
        if (scrollThrottleTimer) return;

        scrollThrottleTimer = setTimeout(() => {
            scrollThrottleTimer = null;
            const pp = document.getElementById('posProduct');
            if (!pp) return;

            if (
                window.hasMoreProducts &&
                !window.isLoadingProducts &&
                pp.scrollTop + pp.clientHeight >= pp.scrollHeight - 120
            ) {
                const cf = window.currentFilter;
                if (cf && cf.type && cf.id) {
                    fetchFilteredProducts(cf.type, cf.id, false);
                } else {
                    fetchPaginatedProducts(false);
                }
            }
        }, throttleDelay);
    }, { passive: true });
}

// ---- DISPLAY PRODUCTS ----

function escapeHtml(str) {
    if (str == null) return '';
    const s = String(str);
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function displayProducts(products, append = false) {
    let posProduct = document.getElementById('posProduct');
    if (!posProduct) {
        console.error('❌ posProduct element not found in DOM!');
        return;
    }

    if (!append) posProduct.innerHTML = '';

    const selectedLocationId = window.selectedLocationId;
    const showFreeQtyColumn  = window.showFreeQtyColumn;

    if (products.length === 0) {
        if (!append) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
        return;
    }

    const newlyAddedCards = [];

    // Show products with paid/free stock > 0 or unlimited stock
    const filteredProducts = products.filter(stock => {
        const product = stock.product;
        if (product.stock_alert === 0) {
            return true;
        }
        const paidStockLevel = parseFloat(stock.total_stock) || 0;
        const freeStockLevel = parseFloat(stock.total_free_stock) || 0;
        const hasStock   = (paidStockLevel + freeStockLevel) > 0;
        return hasStock;
    });

    if (filteredProducts.length === 0) {
        console.warn('⚠️ No products with stock at this location');
        if (!append) posProduct.innerHTML = '<p class="text-center text-warning">No products with stock available at this location.</p>';
        return;
    }

    const fmtAmount = (typeof window.formatAmountWithSeparators === 'function')
        ? window.formatAmountWithSeparators
        : (v) => (v == null || v === '') ? '' : String(parseFloat(v));

    filteredProducts.forEach(stock => {
        const product = stock.product;

        // Skip duplicates when appending
        if (append && document.querySelector(`[data-id="${product.id}"]`)) {
            return;
        }

        const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
        const totalStockNum = (parseFloat(stock.total_stock) || 0) + (parseFloat(stock.total_free_stock) || 0);
        let stockBadgeText;
        let stockBadgeClass;

        if (product.stock_alert === 0) {
            stockBadgeText = 'Unlimited';
            stockBadgeClass = 'product-card-stock-badge product-card-stock-info';
        } else {
            if (product.unit && (product.unit.allow_decimal === true || product.unit.allow_decimal === 1)) {
                const displayQty = parseFloat(totalStockNum).toFixed(4).replace(/\.?0+$/, '');
                stockBadgeText = `${displayQty} IN STOCK`;
            } else {
                stockBadgeText = `${parseInt(totalStockNum, 10)} IN STOCK`;
            }
            stockBadgeClass = totalStockNum <= 2
                ? 'product-card-stock-badge product-card-stock-low'
                : 'product-card-stock-badge product-card-stock-ok';
        }

        // Display price: customer-type price or product fallback
        let displayPrice = '';
        const batches = (typeof window.normalizeBatches === 'function') ? window.normalizeBatches(stock) : (stock.batches || []);
        const firstBatch = Array.isArray(batches) && batches.length ? batches[0] : null;
        const customer = (window.Pos && window.Pos.Customer && typeof window.Pos.Customer.getCurrentCustomer === 'function')
            ? window.Pos.Customer.getCurrentCustomer()
            : { customer_type: 'retailer' };
        if (window.Pos && window.Pos.Customer && typeof window.Pos.Customer.getCustomerTypePrice === 'function') {
            const priceResult = window.Pos.Customer.getCustomerTypePrice(firstBatch, product, customer.customer_type);
            if (!priceResult.hasError && priceResult.price > 0) {
                displayPrice = 'Rs ' + fmtAmount(priceResult.price.toFixed(2));
            }
        }
        if (!displayPrice && product.max_retail_price && parseFloat(product.max_retail_price) > 0) {
            displayPrice = 'Rs ' + fmtAmount(parseFloat(product.max_retail_price).toFixed(2));
        }
        if (!displayPrice) displayPrice = '—';

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12';

        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.setAttribute('data-id', product.id);

        // Image block with overlay stock badge
        const imageWrap = document.createElement('div');
        imageWrap.className = 'product-card-image';
        const img = createSafeImage(product, 'width: 100%; height: 100%; object-fit: cover;');
        if (product.product_image && product.product_image.trim()) {
            img.dataset.productImage = product.product_image.trim();
        }
        const stockBadge = document.createElement('span');
        stockBadge.className = stockBadgeClass;
        stockBadge.textContent = stockBadgeText;
        imageWrap.appendChild(img);
        imageWrap.appendChild(stockBadge);

        const cardBody = document.createElement('div');
        cardBody.className = 'product-card-body';
        cardBody.innerHTML = `
            <h6 class="product-card-name">${escapeHtml(product.product_name)}</h6>
            <div class="product-card-sku">SKU: ${escapeHtml(product.sku || 'N/A')}</div>
            <div class="product-card-price">${displayPrice}</div>
            <button type="button" class="product-card-add-btn" aria-label="Add to cart" title="Add to cart">
                <span aria-hidden="true">+</span>
            </button>
        `;

        productCard.appendChild(imageWrap);
        productCard.appendChild(cardBody);
        cardDiv.appendChild(productCard);

        const addBtn = cardBody.querySelector('.product-card-add-btn');
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const productStock = window.allProducts.find(s => String(s.product.id) === String(product.id));
                if (productStock && typeof window.addProductToTable === 'function') {
                    window.addProductToTable(productStock.product);
                }
            });
        }

        // Re-fetch posProduct in case it was re-created
        posProduct = document.getElementById('posProduct');
        if (posProduct) posProduct.appendChild(cardDiv);

        newlyAddedCards.push(productCard);
    });

    // Attach click events to card (clicking card body/image adds to cart)
    newlyAddedCards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.product-card-add-btn')) return;
            const productId = card.getAttribute('data-id');
            const productStock = window.allProducts.find(s => String(s.product.id) === productId);
            if (productStock && typeof window.addProductToTable === 'function') {
                window.addProductToTable(productStock.product);
            }
        });
    });
}

// ---- FILTER PRODUCT GRID BY SEARCH TEXT ----
// When user types in search, we only filter *existing* cards.
// To show search results in the grid, autocomplete success calls displaySearchResultsInGrid().

function filterProductGrid(searchText) {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) return;

    const searchLower = searchText.toLowerCase().trim();
    const productCards = posProduct.querySelectorAll('.product-card');
    let visibleCount = 0;

    productCards.forEach(card => {
        if (!searchLower) {
            card.parentElement.style.display = '';
            visibleCount++;
            return;
        }
        const cardText  = card.textContent?.toLowerCase() || '';
        const productId = card.getAttribute('data-id') || '';
        const matches   = cardText.includes(searchLower) || productId.includes(searchLower);
        card.parentElement.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });
}

/**
 * Show autocomplete search results in the product grid (same data as dropdown).
 * Called from pos-autocomplete when a search returns results so the grid is not empty.
 * Ensures search results are in window.stockData/allProducts so card click adds to billing.
 * @param {Array} stockData - same format as autocomplete API data.data (product, batches, total_stock, etc.)
 */
function displaySearchResultsInGrid(stockData) {
    if (!Array.isArray(stockData)) return;
    // So that product card click finds the stock entry and addProductToTable works
    window.stockData   = stockData;
    window.allProducts = stockData;
    displayProducts(stockData, false);
}

/**
 * Scroll the product grid so the latest-added product card is visible.
 * Called after auto-add / scanner add so the display shows the product just added.
 * @param {string|number} productId
 */
function scrollProductCardIntoView(productId) {
    const posProduct = document.getElementById('posProduct');
    if (!posProduct) return;
    const card = posProduct.querySelector(`.product-card[data-id="${productId}"]`);
    if (card && card.parentElement) {
        card.parentElement.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }
}

// ---- DISPLAY PRODUCTS IN MOBILE MODAL ----

function displayMobileProducts(products, append = false) {
    const mobileProductGrid = document.getElementById('mobileProductGrid');
    if (!mobileProductGrid) return;

    if (!append) mobileProductGrid.innerHTML = '';

    const selectedLocationId = window.selectedLocationId;
    const showFreeQtyColumn  = window.showFreeQtyColumn;

    if (!selectedLocationId || products.length === 0) {
        if (!append) {
            mobileProductGrid.innerHTML = '<div class="col-12"><p class="text-center">No products found.</p></div>';
        }
        return;
    }

    const filteredProducts = products.filter(stock => {
        const product = stock.product;
        if (product.stock_alert === 0) return true;
        const paidStockLevel = parseFloat(stock.total_stock) || 0;
        const freeStockLevel = parseFloat(stock.total_free_stock) || 0;
        return (paidStockLevel + freeStockLevel) > 0;
    });

    const fmtAmount = (typeof window.formatAmountWithSeparators === 'function')
        ? window.formatAmountWithSeparators
        : (v) => (v == null || v === '') ? '' : String(parseFloat(v));

    filteredProducts.forEach(stock => {
        const product = stock.product;
        const totalStockNum = (parseFloat(stock.total_stock) || 0) + (parseFloat(stock.total_free_stock) || 0);
        let stockBadgeText;
        let stockBadgeClass;

        if (product.stock_alert === 0) {
            stockBadgeText = 'Unlimited';
            stockBadgeClass = 'product-card-stock-badge product-card-stock-info';
        } else {
            if (product.unit && (product.unit.allow_decimal === true || product.unit.allow_decimal === 1)) {
                const displayQty = parseFloat(totalStockNum).toFixed(4).replace(/\.?0+$/, '');
                stockBadgeText = `${displayQty} IN STOCK`;
            } else {
                stockBadgeText = `${parseInt(totalStockNum, 10)} IN STOCK`;
            }
            stockBadgeClass = totalStockNum <= 2
                ? 'product-card-stock-badge product-card-stock-low'
                : 'product-card-stock-badge product-card-stock-ok';
        }

        let displayPrice = '';
        const batches = (typeof window.normalizeBatches === 'function') ? window.normalizeBatches(stock) : (stock.batches || []);
        const firstBatch = Array.isArray(batches) && batches.length ? batches[0] : null;
        const customer = (window.Pos && window.Pos.Customer && typeof window.Pos.Customer.getCurrentCustomer === 'function')
            ? window.Pos.Customer.getCurrentCustomer()
            : { customer_type: 'retailer' };
        if (window.Pos && window.Pos.Customer && typeof window.Pos.Customer.getCustomerTypePrice === 'function') {
            const priceResult = window.Pos.Customer.getCustomerTypePrice(firstBatch, product, customer.customer_type);
            if (!priceResult.hasError && priceResult.price > 0) {
                displayPrice = 'Rs ' + fmtAmount(priceResult.price.toFixed(2));
            }
        }
        if (!displayPrice && product.max_retail_price && parseFloat(product.max_retail_price) > 0) {
            displayPrice = 'Rs ' + fmtAmount(parseFloat(product.max_retail_price).toFixed(2));
        }
        if (!displayPrice) displayPrice = '—';

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-6';

        const productCard = document.createElement('div');
        productCard.className = 'product-card product-card-mobile';
        productCard.setAttribute('data-id', product.id);

        const imageWrap = document.createElement('div');
        imageWrap.className = 'product-card-image';
        const img = createSafeImage(product, 'width: 100%; height: 100%; object-fit: cover;');
        if (product.product_image && product.product_image.trim()) {
            img.dataset.productImage = product.product_image.trim();
        }
        const stockBadge = document.createElement('span');
        stockBadge.className = stockBadgeClass;
        stockBadge.textContent = stockBadgeText;
        imageWrap.appendChild(img);
        imageWrap.appendChild(stockBadge);

        const cardBody = document.createElement('div');
        cardBody.className = 'product-card-body';
        cardBody.innerHTML = `
            <h6 class="product-card-name">${escapeHtml(product.product_name)}</h6>
            <div class="product-card-sku">SKU: ${escapeHtml(product.sku || 'N/A')}</div>
            <div class="product-card-price">${displayPrice}</div>
            <button type="button" class="product-card-add-btn" aria-label="Add to cart" title="Add to cart">
                <span aria-hidden="true">+</span>
            </button>
        `;

        productCard.appendChild(imageWrap);
        productCard.appendChild(cardBody);
        cardDiv.appendChild(productCard);
        mobileProductGrid.appendChild(cardDiv);

        const addBtn = cardBody.querySelector('.product-card-add-btn');
        const handleAdd = (e) => {
            if (e && e.stopPropagation) e.stopPropagation();
            const productStock = window.allProducts.find(s => String(s.product.id) === String(product.id));
            if (productStock) showMobileQuantityModal(productStock);
        };
        if (addBtn) addBtn.addEventListener('click', handleAdd);
        productCard.addEventListener('click', (e) => {
            if (e.target.closest('.product-card-add-btn')) return;
            handleAdd();
        });
    });
}

// ---- MOBILE QUANTITY MODAL ----

function showMobileQuantityModal(productStock) {
    const product    = productStock.product;
    const hasDecimal = product.unit && (product.unit.allow_decimal === true || product.unit.allow_decimal === 1);

    const selectedLocationId = window.selectedLocationId;

    // Calculate available stock
    let locationQty = 0;
    const batches = (typeof window.normalizeBatches === 'function')
        ? window.normalizeBatches(productStock)
        : (Array.isArray(productStock.batches) ? productStock.batches : []);

    batches.forEach(batch => {
        if (batch.location_batches) {
            batch.location_batches.forEach(lb => {
                if (lb.location_id == selectedLocationId) locationQty += parseFloat(lb.quantity);
            });
        }
    });

    const availableStock = product.stock_alert === 0 ? Infinity : locationQty;
    const unitName       = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';

    // Check existing cart row
    const billingBody = document.getElementById('billing-body');
    const existingRow = billingBody
        ? Array.from(billingBody.querySelectorAll('tr')).find(row => {
            const el = row.querySelector('.product-id');
            return el && el.textContent == product.id;
          })
        : null;

    let currentQtyInTable = 0;
    if (existingRow) {
        const qtyInput = existingRow.querySelector('.quantity-input');
        if (qtyInput) currentQtyInTable = parseFloat(qtyInput.value) || 0;
    }

    // Populate modal
    document.getElementById('mobileQtyProductName').textContent = product.product_name;
    if (product.stock_alert === 0) {
        document.getElementById('mobileQtyAvailable').textContent = 'Available: Unlimited';
    } else {
        const stockDisplay = hasDecimal
            ? parseFloat(availableStock).toFixed(4).replace(/\.?0+$/, '')
            : parseInt(availableStock, 10);
        document.getElementById('mobileQtyAvailable').textContent =
            `Available: ${stockDisplay} ${unitName}` +
            (currentQtyInTable > 0 ? ` | In Cart: ${currentQtyInTable}` : '');
    }

    const qtyInput = document.getElementById('mobileQtyInput');
    qtyInput.value  = currentQtyInTable > 0 ? currentQtyInTable : '';
    qtyInput.step   = hasDecimal ? '0.0001' : '1';
    qtyInput.min    = hasDecimal ? '0.0001' : '1';
    document.getElementById('mobileQtyError').style.display = 'none';

    const qtyModal = new bootstrap.Modal(document.getElementById('mobileQuantityModal'));
    qtyModal.show();

    document.getElementById('mobileQuantityModal').addEventListener('shown.bs.modal', function() {
        qtyInput.focus();
    }, { once: true });

    // Replace confirm button to remove stale listeners
    const confirmBtn    = document.getElementById('mobileQtyConfirm');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener('click', function() {
        const qty      = parseFloat(qtyInput.value);
        const errorDiv = document.getElementById('mobileQtyError');

        if (!qty || qty <= 0) {
            errorDiv.textContent = 'Please enter a valid quantity';
            errorDiv.style.display = 'block';
            return;
        }
        if (!hasDecimal && qty % 1 !== 0) {
            errorDiv.textContent = `This product does not allow decimal quantities`;
            errorDiv.style.display = 'block';
            return;
        }
        if (product.stock_alert !== 0 && qty > availableStock) {
            errorDiv.textContent = `Only ${hasDecimal ? parseFloat(availableStock).toFixed(4).replace(/\.?0+$/, '') : parseInt(availableStock, 10)} ${unitName} available`;
            errorDiv.style.display = 'block';
            return;
        }

        if (typeof window.addProductToTable === 'function') {
            window.addProductToTable(product, qty);
        }
        qtyModal.hide();
        toastr.success(`${product.product_name} ${existingRow ? 'updated' : 'added to cart'}`);
    });

    qtyInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') newConfirmBtn.click();
    });
}

// ---- FETCH FILTERED PRODUCTS (category / subcategory / brand) ----

function fetchFilteredProducts(filterType, filterId, reset = true) {
    if (window.isLoadingProducts || !window.selectedLocationId) return;

    window.isLoadingProducts = true;
    const perPage = 24;

    if (reset) {
        window.currentProductsPage = 1;
        const posProduct = document.getElementById('posProduct');
        if (posProduct) posProduct.innerHTML = '';
        showLoader();
    } else {
        showLoaderSmall();
    }

    let url = `/products/stocks?location_id=${window.selectedLocationId}&page=${window.currentProductsPage}&per_page=${perPage}&context=pos`;

    switch (filterType) {
        case 'category':    url += `&main_category_id=${filterId}`;  break;
        case 'subcategory': url += `&sub_category_id=${filterId}`;   break;
        case 'brand':       url += `&brand_id=${filterId}`;          break;
    }

    const fetchOptions = {
        method: 'GET',
        cache:  'no-store',
        headers: {
            'Accept':           'application/json',
            'Content-Type':     'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    };

    fetch(url, fetchOptions)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            return res.json();
        })
        .then(data => {

            if (data.status === 200 && Array.isArray(data.data)) {
                const posProduct = document.getElementById('posProduct');

                if (reset) {
                    window.allProducts = [];
                    window.stockData   = [];
                    if (posProduct) posProduct.innerHTML = '';
                }

                data.data.forEach(stock => {
                    window.allProducts.push(stock);
                    const existingIdx = window.stockData.findIndex(s => s.product.id === stock.product.id);
                    if (existingIdx === -1) {
                        window.stockData.push(stock);
                    } else {
                        window.stockData[existingIdx] = stock;
                    }
                });

                window.hasMoreProducts     = data.data.length === perPage;
                window.currentProductsPage++;

                if (reset) {
                    displayProducts(window.allProducts, false);
                    const mobileModal = document.getElementById('mobileProductModal');
                    if (mobileModal && mobileModal.classList.contains('show')) {
                        displayMobileProducts(window.allProducts, false);
                    }
                } else {
                    displayProducts(data.data, true);
                    const mobileModal = document.getElementById('mobileProductModal');
                    if (mobileModal && mobileModal.classList.contains('show')) {
                        displayMobileProducts(data.data, true);
                    }
                }

                if (data.data.length === 0 && reset) {
                    const pp = document.getElementById('posProduct');
                    if (pp) {
                        pp.innerHTML = `<div class="text-center p-4">
                            <p class="text-muted">No products found for selected ${filterType}</p>
                            <button onclick="window.showAllProducts && window.showAllProducts()" class="btn btn-primary btn-sm">Show All Products</button>
                        </div>`;
                    }
                }
            } else {
                console.error('Invalid filtered products response:', data);
                const pp = document.getElementById('posProduct');
                if (pp) {
                    pp.innerHTML = `<div class="text-center p-4">
                        <p class="text-danger">Failed to load filtered products</p>
                        <button onclick="window.showAllProducts && window.showAllProducts()" class="btn btn-primary btn-sm">Show All Products</button>
                    </div>`;
                }
            }
        })
        .catch(err => {
            console.error(`Error fetching filtered products (${filterType}):`, err);
            const pp = document.getElementById('posProduct');
            if (pp) {
                pp.innerHTML = `<div class="text-center p-4">
                    <p class="text-danger">Failed to load filtered products</p>
                    <button onclick="window.showAllProducts && window.showAllProducts()" class="btn btn-primary btn-sm">Try Again</button>
                </div>`;
            }
        })
        .finally(() => {
            window.isLoadingProducts = false;
            hideLoader();
        });
}

// ---- window.* exports ----
window.showLoader              = showLoader;
window.hideLoader              = hideLoader;
window.showLoaderSmall         = showLoaderSmall;
window.fetchCategories         = fetchCategories;
window.renderCategories        = renderCategories;
window.fetchSubcategories      = fetchSubcategories;
window.renderSubcategories     = renderSubcategories;
window.fetchBrands             = fetchBrands;
window.renderBrands            = renderBrands;
window.fetchPaginatedProducts  = fetchPaginatedProducts;
window.setupLazyLoad           = setupLazyLoad;
window.displayProducts           = displayProducts;
window.filterProductGrid         = filterProductGrid;
window.displaySearchResultsInGrid = displaySearchResultsInGrid;
window.scrollProductCardIntoView   = scrollProductCardIntoView;
window.displayMobileProducts   = displayMobileProducts;
window.showMobileQuantityModal = showMobileQuantityModal;
window.fetchFilteredProducts   = fetchFilteredProducts;

// ---- Offcanvas search filter (category, brand, subcategory) ----
function setupOffcanvasSearchFilters() {
    const categorySearchInput = document.getElementById('categorySearchInput');
    if (categorySearchInput) {
        categorySearchInput.addEventListener('input', function () {
            const q = (this.value || '').trim().toLowerCase();
            const list = window._allCategories || [];
            const filtered = q ? list.filter(function (c) {
                return (c.mainCategoryName || '').toLowerCase().indexOf(q) !== -1;
            }) : list;
            if (typeof window.renderCategories === 'function') {
                window.renderCategories(filtered);
            }
        });
    }

    const brandSearchInput = document.getElementById('brandSearchInput');
    if (brandSearchInput) {
        brandSearchInput.addEventListener('input', function () {
            const q = (this.value || '').trim().toLowerCase();
            const list = window._allBrands || [];
            const filtered = q ? list.filter(function (b) {
                return (b.name || '').toLowerCase().indexOf(q) !== -1;
            }) : list;
            if (typeof window.renderBrands === 'function') {
                window.renderBrands(filtered);
            }
        });
    }

    const subcategorySearchInput = document.getElementById('subcategorySearchInput');
    if (subcategorySearchInput) {
        subcategorySearchInput.addEventListener('input', function () {
            const q = (this.value || '').trim().toLowerCase();
            const list = window._allSubcategories || [];
            const filtered = q ? list.filter(function (s) {
                return (s.subCategoryname || '').toLowerCase().indexOf(q) !== -1;
            }) : list;
            if (typeof window.renderSubcategories === 'function') {
                window.renderSubcategories(filtered, { switchCanvas: false });
            }
        });
    }
}

// ---- One-time DOM setup (runs after DOM is ready) ----
document.addEventListener('DOMContentLoaded', function () {
    // Infinite scroll for desktop product grid
    setupLazyLoad();

    // Offcanvas search filters (category, brand, subcategory)
    setupOffcanvasSearchFilters();

    // "Back to categories" button in subcategory offcanvas
    const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');
    if (subcategoryBackBtn) {
        subcategoryBackBtn.addEventListener('click', () => {
            const categoryOffcanvas = new bootstrap.Offcanvas(
                document.getElementById('offcanvasCategory'));
            categoryOffcanvas.show();
            const subcategoryOffcanvas = bootstrap.Offcanvas.getInstance(
                document.getElementById('offcanvasSubcategory'));
            if (subcategoryOffcanvas) subcategoryOffcanvas.hide();
        });
    }
});
