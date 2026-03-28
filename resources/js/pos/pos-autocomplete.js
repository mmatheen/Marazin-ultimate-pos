/**
 * @file pos-autocomplete.js
 * @module POS.Autocomplete
 * @description
 *   Product search autocomplete, barcode scanner (MP6300Y) support, and
 *   quick-add modal for products not yet in the system.
 *
 *   Extracted from resources/views/sell/pos_ajax.blade.php  Phase 9.
 *   Must be loaded BEFORE pos_ajax.blade.php (see @vite order in pos.blade.php).
 *
 *
 * TABLE OF CONTENTS
 *
 *  1  Constants & Configuration
 *  2  Module State
 *  3  Result Cache          (getCachedEntry, storeCacheEntry, getCascadeResults,
 *                             clearCacheForLocation, cancelAllPendingPaths,
 *                             resetAutocompleteState)
 *  4  UI Helpers            (showSearchIndicator, hideSearchIndicator, autoFocusSearchInput)
 *  5  Data Helpers          (filterStockData, mapSearchResults, findImeiMatch, createProductLabel)
 *  6  Server Requests       (createProductSearchRequest, searchForExactMatch,
 *                             checkProductExistsBeforeQuickAdd, fetchProductStock)
 *  7  Response Handlers     (handleSearchSuccess, handleSearchError)
 *  8  Auto-Add Logic        (checkForAutoAdd, addProductFromAutocomplete)
 *  9  Manual-Enter Logic    (handleManualEnter, getSelectedItem, getFirstResult, shouldAddProduct)
 *  10 Barcode Scanner       (handleBarcodeScan)
 *  11 jQuery UI Widget      (initAutocomplete)
 *  12 Menu Rendering        (setupCustomRendering, setupFirstItemFocus, createImeiItemHtml)
 *  13 Keyboard & Input      (setupKeyboardEvents, setupDirectKeyboardHandling,
 *                             navigateMenu, selectCurrentItem, setupInputEvents)
 *  14 Quick-Add Modal       (showQuickAddOption, setupQuickAddListeners, clearQuickAddForm)
 *  15 Autocomplete Styles   (setupAutocompleteStyles)
 *  16 Public API            (window.* exports)
 *
 *
 * Cross-boundary reads (window.*)
 *   window.selectedLocationId   active location ID
 *   window.stockData            stock array (read + mutate)
 *   window.allProducts          products array (read + mutate)
 *   window.addProductToTable    add row to billing table (pos_ajax closure)
 *   window.filterProductGrid    filter product card grid (pos_ajax closure)
 *   window.safeFetchJson        fetch helper (pos-ui.js)
 *   window.saveAndAddProduct    save quick-add form (pos_ajax closure)
 *   window.toggleStockQuantity  show/hide stock qty in modal (pos_ajax closure)
 *
 * Public exports (window.*)
 *   window.initAutocomplete
 *   window.autoFocusSearchInput
 *   window.showSearchIndicator
 *   window.hideSearchIndicator
 *   window.addProductFromAutocomplete
 *   window.clearAutocompleteCache
 *   window.autocompleteState
 */

'use strict';

/*
   1  CONSTANTS & CONFIGURATION
    */

/** Maximum results per autocomplete AJAX call. 10 = desktop-like speed; server returns fewer rows. */
const AUTOCOMPLETE_PER_PAGE = 10;

/** How long (ms) a cached result is considered fresh. */
const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

/** Maximum cache entries before LRU eviction. */
const CACHE_MAX_SIZE = 200;

/** Auto-add delay after exact SKU/IMEI or single-result match (shorter = snappier). */
const AUTO_ADD_DELAY_MS = 280;

/**
 * Barcode scanner timing constants.
 * Keys arriving faster than KEY_GAP_MS are treated as scanner input.
 */
const SCANNER = {
    KEY_GAP_MS:      50,   // below = scanner speed, above = human speed
    BUFFER_RESET_MS: 1000, // clear buffer if no key arrives within this window
    ADD_DELAY_MS:    300,  // pause before adding a scanned product (UX breathing room)
};

/*
   2  MODULE STATE
    */

/**
 * Centralised mutable state shared across all autocomplete functions.
 *
 * @property {number|null} debounceTimer  - setTimeout handle for source() debounce
 * @property {boolean}     isRequesting   - true while an AJAX call is in-flight
 * @property {Array}       lastResults    - most recent result set (product items only)
 * @property {string}      currentTerm    - search term that produced lastResults
 * @property {boolean}     adding         - guard: true while addProductFromAutocomplete runs
 * @property {Object|null} lastProduct    - last added product (dedup guard)
 * @property {number|null} autoAddTimer   - setTimeout handle for checkForAutoAdd
 */
const autocompleteState = {
    debounceTimer: null,
    isRequesting:  false,
    lastResults:   [],
    currentTerm:   '',
    adding:        false,
    lastProduct:   null,
    autoAddTimer:  null,
};
window.autocompleteState = autocompleteState;

/**
 * The currently in-flight autocomplete AJAX request, or null.
 * Stored so it can be aborted when a new search starts, preventing
 * stale response() callbacks from populating the dropdown.
 * @type {jqXHR|null}
 */
let currentAjaxRequest = null;

/*
   3  RESULT CACHE

 *
 * Three lookup tiers (fastest first):
 *   1. Exact hit       same term, same location     0 ms, 0 network
 *   2. Cascade filter  longer term, parent is full  0 ms, 0 network
 *   3. AJAX            nothing usable in cache      2003000 ms
 *
 * "full" = server returned fewer items than AUTOCOMPLETE_PER_PAGE,
 * meaning we have ALL matches for that term and can derive results
 * for any extension of it by filtering client-side.
 * Example: "000" can be filtered from a full "00" entry instantly.
 *  */

/** @type {Map<string, {results:Array, rawData:Array, timestamp:number, full:boolean}>} */
const searchCache = new Map();

/**
 * Return a cache entry if it exists and has not expired.
 * @param {string|number} locationId
 * @param {string}        term
 * @returns {Object|null}
 */
function getCachedEntry(locationId, term) {
    const key   = `${locationId}_${term.toLowerCase()}`;
    const entry = searchCache.get(key);
    if (!entry) return null;

    if ((Date.now() - entry.timestamp) > CACHE_TTL_MS) {
        searchCache.delete(key);
        return null;
    }
    return entry;
}

/**
 * Store results in the cache. Evicts the oldest entry when full.
 * @param {string|number} locationId
 * @param {string}  term
 * @param {Array}   results   - mapped autocomplete items
 * @param {Array}   rawData   - raw stock array from server (for cascade)
 */
function storeCacheEntry(locationId, term, results, rawData) {
    if (searchCache.size >= CACHE_MAX_SIZE) {
        searchCache.delete(searchCache.keys().next().value); // evict oldest
    }
    searchCache.set(`${locationId}_${term.toLowerCase()}`, {
        results,
        rawData,
        timestamp: Date.now(),
        full: rawData.length < AUTOCOMPLETE_PER_PAGE, // we have ALL server matches
    });
}

/**
 * Attempt to build results for `term` by filtering a shorter parent entry
 * that is marked full. Returns null if no suitable parent is found.
 *
 * @param {string|number} locationId
 * @param {string}        term
 * @returns {Array|null}
 */
function getCascadeResults(locationId, term) {
    const termLower = term.toLowerCase();

    for (let len = termLower.length - 1; len >= 1; len--) {
        const parent = getCachedEntry(locationId, termLower.slice(0, len));
        if (!parent || !parent.full) continue;

        const matching = parent.rawData.filter(stock => {
            if (!stock || !stock.product) return false;
            const name = (stock.product.product_name || '').toLowerCase();
            const sku  = (stock.product.sku          || '').toLowerCase();
            return name.includes(termLower) || sku.includes(termLower);
        });

        return mapSearchResults(filterStockData(matching), term);
    }
    return null;
}

/** Discard all cached entries for a specific location. */
function clearCacheForLocation(locationId) {
    for (const key of searchCache.keys()) {
        if (key.startsWith(`${locationId}_`)) searchCache.delete(key);
    }
}

/** Discard the entire cache (e.g. force-refresh after a stock change). */
window.clearAutocompleteCache = () => searchCache.clear();

/**
 * Cancel every pending async path so only ONE product-add can run at a time.
 * Must be called by the scanner Enter handler before invoking handleBarcodeScan().
 *
 * Cancels:
 *    scannerInputTimer   stops input.scanner from calling autocomplete('search')
 *    autoAddTimer        stops a stale checkForAutoAdd from adding a second item
 *    currentAjaxRequest  aborts any in-flight autocomplete AJAX
 *    debounceTimer       cancels any pending source() debounce
 *    autocomplete widget close  prevents source() re-firing
 *
 * @param {jQuery} [$input]  - the search input element
 */
function cancelAllPendingPaths($input) {
    if (cancelAllPendingPaths._scannerInputTimer) {
        clearTimeout(cancelAllPendingPaths._scannerInputTimer);
        cancelAllPendingPaths._scannerInputTimer = null;
    }
    if (autocompleteState.autoAddTimer) {
        clearTimeout(autocompleteState.autoAddTimer);
        autocompleteState.autoAddTimer = null;
    }
    if (currentAjaxRequest) {
        currentAjaxRequest.abort();
        currentAjaxRequest = null;
        autocompleteState.isRequesting = false;
    }
    if (autocompleteState.debounceTimer) {
        clearTimeout(autocompleteState.debounceTimer);
        autocompleteState.debounceTimer = null;
    }
    if ($input) $input.autocomplete('close');
}
cancelAllPendingPaths._scannerInputTimer = null; // shared slot for setupKeyboardEvents

/**
 * Reset autocompleteState before a new search begins.
 * Aborts in-flight AJAX, clears all timers, unlocks the adding guard.
 */
function resetAutocompleteState() {
    if (autocompleteState.autoAddTimer)  clearTimeout(autocompleteState.autoAddTimer);
    if (autocompleteState.debounceTimer) clearTimeout(autocompleteState.debounceTimer);

    if (currentAjaxRequest) {
        currentAjaxRequest.abort(); // stale response() must not populate the dropdown
        currentAjaxRequest = null;
    }

    autocompleteState.isRequesting = false;
    autocompleteState.adding       = false;
    autocompleteState.lastProduct  = null;
}

/*
   4  UI HELPERS
    */

/**
 * Show a small status badge inside the search input's parent container.
 * @param {string} text
 * @param {string} [color='#28a745']
 */
function showSearchIndicator(text, color = '#28a745') {
    hideSearchIndicator();
    const $container = $('#productSearchInput').parent();
    if ($container.css('position') !== 'relative') $container.css('position', 'relative');
    $container.append(`<span class="search-indicator" style="color:${color};">${text}</span>`);
}

/** Remove the status badge from the search input container. */
function hideSearchIndicator() {
    $('.search-indicator').remove();
}

/**
 * Clear the search input and re-focus it so the cashier can immediately
 * scan or type the next product.
 */
function autoFocusSearchInput() {
    setTimeout(() => {
        const $input = $('#productSearchInput');
        if ($input.length) $input.val('').focus();
    }, 100);
}

/*
   5  DATA HELPERS
    */

/**
 * Remove null or incomplete stock entries from a server response array.
 * @param {Array} stockArray
 * @returns {Array}
 */
function filterStockData(stockArray) {
    return stockArray.filter(stock => stock && stock.product);
}

/**
 * Convert a filtered stock array into jQuery UI autocomplete result items.
 * @param {Array}  filteredStocks
 * @param {string} term
 * @returns {Array}  items: { label, value, product, stockData, imeiMatch, exactImeiMatch }
 */
function mapSearchResults(filteredStocks, term) {
    return filteredStocks.map(stock => {
        const { imeiMatch, exactImeiMatch, imeiNumber } = findImeiMatch(stock, term);
        return {
            label:          createProductLabel(stock, imeiMatch, imeiNumber),
            value:          stock.product.product_name,
            product:        stock.product,
            stockData:      stock,
            imeiMatch:      !!imeiMatch,
            exactImeiMatch: exactImeiMatch,
        };
    });
}

/**
 * Search a stock entry's IMEI list for a number matching `term`.
 * @param {Object} stock
 * @param {string} term
 * @returns {{ imeiMatch:string, exactImeiMatch:boolean, imeiNumber:string }}
 */
function findImeiMatch(stock, term) {
    const noMatch = { imeiMatch: '', exactImeiMatch: false, imeiNumber: '' };
    if (!stock.imei_numbers || stock.imei_numbers.length === 0) return noMatch;

    const available = stock.imei_numbers.filter(
        imei => imei.status === 'available' || imei.status === undefined
    );
    const hit = available.find(
        imei => imei.imei_number &&
                imei.imei_number.toLowerCase().includes(term.toLowerCase())
    );
    if (!hit) return noMatch;

    return {
        imeiMatch:      `  IMEI: ${hit.imei_number}`,
        exactImeiMatch: hit.imei_number.toLowerCase() === term.toLowerCase(),
        imeiNumber:     hit.imei_number,
    };
}

/**
 * Build the display label shown in the dropdown for one stock entry.
 * @param {Object} stock
 * @param {string} imeiMatch  - formatted IMEI suffix, or ''
 * @returns {string}
 */
function createProductLabel(stock, imeiMatch) {
    const product = stock.product;
    const stockDisplay = (product.stock_alert == 0)
        ? 'Unlimited'
        : (parseFloat(stock.total_stock) || 0) + (parseFloat(stock.total_free_stock) || 0);

    return `${product.product_name} (${product.sku || ''})${imeiMatch} [Stock: ${stockDisplay}]`;
}

/*
   6  SERVER REQUESTS
    */

/**
 * Fire an autocomplete AJAX search request.
 * Stores the jqXHR in currentAjaxRequest so it can be aborted on the
 * next keystroke before a stale response populates the dropdown.
 *
 * @param {string}   term
 * @param {Function} response  - jQuery UI autocomplete response() callback
 * @returns {jqXHR}
 */
function createProductSearchRequest(term, response) {
    currentAjaxRequest = $.ajax({
        url:     window.PosConfig?.routes?.productAutocomplete || '/products/stocks/autocomplete',
        data: {
            location_id: window.selectedLocationId,
            search:      term,
            per_page:    AUTOCOMPLETE_PER_PAGE,
            context:     'pos',
        },
        cache:   false,
        timeout: 10000,
        success(data) {
            currentAjaxRequest = null;
            handleSearchSuccess(data, term, response);
        },
        error(jqXHR, textStatus) {
            if (jqXHR.statusText === 'abort') return; // aborted by new keystroke  expected
            currentAjaxRequest = null;
            handleSearchError(jqXHR, textStatus, response);
        },
    });
    return currentAjaxRequest;
}

/**
 * Fire a direct (non-debounced) search for a barcode-scanned value.
 * Uses smaller per_page because a scanner always sends the full barcode
 * and we expect 0 or 1 exact matches.
 *
 * @param {string} searchTerm  - full barcode / SKU string
 */
function searchForExactMatch(searchTerm) {
    if (!window.selectedLocationId) return;

    showSearchIndicator(' Scanner searching...', '#17a2b8');

    $.ajax({
        url:     window.PosConfig?.routes?.productAutocomplete || '/products/stocks/autocomplete',
        data: {
            location_id: window.selectedLocationId,
            search:      searchTerm,
            per_page:    15,
        },
        cache:   false,
        timeout: 5000,
        success(data) {
            hideSearchIndicator();

            if (data.status !== 200 || !Array.isArray(data.data)) {
                showSearchIndicator(' No results', '#dc3545');
                setTimeout(() => { hideSearchIndicator(); autoFocusSearchInput(); }, 2000);
                return;
            }

            const results    = mapSearchResults(filterStockData(data.data), searchTerm);
            const exactSkuImei = results.find(r => {
                if (!r.product) return false;
                return (r.product.sku && r.product.sku.toLowerCase() === searchTerm.toLowerCase())
                    || r.exactImeiMatch;
            });
            // Scanner: add on exact SKU/IMEI OR when exactly one result (unambiguous barcode)
            const toAdd = exactSkuImei || (results.length === 1 && results[0].product ? results[0] : null);

            if (toAdd) {
                if (autocompleteState.autoAddTimer) {
                    clearTimeout(autocompleteState.autoAddTimer);
                    autocompleteState.autoAddTimer = null;
                }
                const matchType = exactSkuImei
                    ? (toAdd.product.sku && toAdd.product.sku.toLowerCase() === searchTerm.toLowerCase() ? 'SCANNER_SKU' : 'SCANNER_IMEI')
                    : 'SCANNER_SKU';

                showSearchIndicator(' Adding scanned item...', '#28a745');
                $('#productSearchInput').autocomplete('close');

                autocompleteState.adding = true;

                setTimeout(() => {
                    addProductFromAutocomplete(toAdd, searchTerm, matchType);
                    $('#productSearchInput').val('');
                    hideSearchIndicator();
                    autocompleteState.adding = false;
                    setTimeout(() => autoFocusSearchInput(), 100);
                }, SCANNER.ADD_DELAY_MS);

            } else if (results.length > 0) {
                //  Multiple matches: show dropdown for cashier to choose
                autocompleteState.lastResults = results.filter(r => r.product);
                $('#productSearchInput').autocomplete('close');
                setTimeout(() => $('#productSearchInput').autocomplete('search', searchTerm), 100);

            } else {
                //  Nothing at this location: check if product exists at all
                showSearchIndicator(' Checking product database...', '#ffc107');
                checkProductExistsBeforeQuickAdd(searchTerm, () => {}, true);
            }
        },
        error(jqXHR) {
            hideSearchIndicator();
            showSearchIndicator(' Search error', '#dc3545');
            setTimeout(() => { hideSearchIndicator(); autoFocusSearchInput(); }, 2000);
        },
    });
}

/**
 * When autocomplete returns zero results, check whether the SKU exists
 * in any other location before offering the quick-add option.
 *
 * @param {string}   term
 * @param {Function} response   - autocomplete response() callback
 * @param {boolean}  isScanner  - true = scanner path (show toast, not dropdown)
 */
function checkProductExistsBeforeQuickAdd(term, response, isScanner = false) {
    $.ajax({
        url:    '/product/check-sku',
        method: 'POST',
        data: {
            sku:    term,
            _token: $('meta[name="csrf-token"]').attr('content'),
        },
        cache:   false,
        timeout: 3000,
        success(skuCheckData) {
            if (skuCheckData.exists === true) {
                // Product exists but is not stocked at this location
                if (isScanner) {
                    showSearchIndicator(' Product exists but not in this location', '#ff9800');
                    toastr.warning(
                        `Product "${term}" exists but has no stock at this location.`,
                        'Not Available Here'
                    );
                    setTimeout(() => { hideSearchIndicator(); autoFocusSearchInput(); }, 3000);
                } else {
                    response([]);
                    $('#productSearchInput').autocomplete('close');
                }
            } else {
                // Product is completely unknown  offer quick-add
                if (isScanner) {
                    showSearchIndicator(' Product not found in system', '#dc3545');
                    setTimeout(() => showQuickAddOption(term), 1000);
                    setTimeout(() => hideSearchIndicator(), 3000);
                } else {
                    response([{
                        label:        ` Add New Product: ${term}`,
                        value:        '',
                        showQuickAdd: true,
                        searchTerm:   term,
                    }]);
                }
            }
        },
        error() {
            if (isScanner) {
                showSearchIndicator(' Error checking product', '#dc3545');
                setTimeout(() => { hideSearchIndicator(); autoFocusSearchInput(); }, 2000);
            } else {
                response([]);
                $('#productSearchInput').autocomplete('close');
            }
        },
    });
}

/**
 * Fetch a single product's full stock entry by product ID.
 * Fallback used when addProductFromAutocomplete cannot find the product
 * in window.stockData (e.g. just added via quick-add modal).
 *
 * @param {number} productId
 * @param {string} searchTerm
 * @param {string} matchType
 */
function fetchProductStock(productId, searchTerm, matchType) {
    const url = `/products/stocks?location_id=${window.selectedLocationId}&product_id=${productId}`;

    window.safeFetchJson(url)
        .then(data => {
            if (data.status === 200 && Array.isArray(data.data) && data.data.length > 0) {
                const freshEntry  = data.data[0];
                const existingIdx = window.stockData.findIndex(s => s.product.id === freshEntry.product.id);

                if (existingIdx !== -1) {
                    window.stockData[existingIdx] = freshEntry;
                } else {
                    window.stockData.push(freshEntry);
                    window.allProducts.push(freshEntry);
                }

                if (typeof window.addProductToTable === 'function') {
                    window.addProductToTable(freshEntry.product, searchTerm, matchType);
                }
                setTimeout(() => autoFocusSearchInput(), 200);
            } else {
                toastr.error('Stock entry not found', 'Error');
                autoFocusSearchInput();
            }
        })
        .catch(err => {
            if (err.status === 429) {
                toastr.warning(`Rate limited. Wait ${Math.ceil(err.retryAfter / 1000)}s`, 'Too Many Requests');
            } else {
                toastr.error('Error fetching product data', 'Error');
            }
            autoFocusSearchInput();
        });
}

/*
   7  RESPONSE HANDLERS
    */

/**
 * Process a successful autocomplete AJAX response.
 * Caches the results, triggers auto-add on exact match, then calls response()
 * to populate the jQuery UI dropdown.
 *
 * @param {Object}   data
 * @param {string}   term
 * @param {Function} response
 */
function handleSearchSuccess(data, term, response) {
    autocompleteState.isRequesting = false;

    if (data.status !== 200 || !Array.isArray(data.data)) {
        autocompleteState.lastResults = [];
        return response([{ label: 'No results found', value: '' }]);
    }

    const results = mapSearchResults(filterStockData(data.data), term);

    if (window.selectedLocationId) {
        storeCacheEntry(window.selectedLocationId, term, results, data.data);
    }

    if (results.length === 0) {
        if (data.data && typeof window.displaySearchResultsInGrid === 'function') {
            window.displaySearchResultsInGrid([]);
        }
        checkProductExistsBeforeQuickAdd(term, response, false);
        return;
    }

    autocompleteState.lastResults = results.filter(r => r.product);
    checkForAutoAdd(results, term);
    // Show same results in the product grid so the display area is not empty
    if (data.data && data.data.length > 0 && typeof window.displaySearchResultsInGrid === 'function') {
        window.displaySearchResultsInGrid(data.data);
    }
    response(results);
}

/**
 * Handle a failed autocomplete AJAX response.
 * Respects rate-limit Retry-After headers.
 *
 * @param {jqXHR}    jqXHR
 * @param {string}   textStatus
 * @param {Function} response
 */
function handleSearchError(jqXHR, textStatus, response) {
    autocompleteState.isRequesting = false;

    if (jqXHR.status === 429) {
        const retryAfter = parseInt(jqXHR.getResponseHeader('Retry-After') || '2', 10);
        response([{ label: `Rate limited. Retrying in ${retryAfter}s`, value: '' }]);
        setTimeout(() => { autocompleteState.isRequesting = false; }, retryAfter * 1000);
    } else {
        response([{ label: 'Error loading results. Please try again.', value: '' }]);
    }
}

/*
   8  AUTO-ADD LOGIC
    */

/**
 * After results arrive (from AJAX or cache), check whether the search term
 * is an exact SKU or IMEI match. If so, schedule an automatic product add
 * after AUTO_ADD_DELAY_MS so the cashier sees the match flash in the
 * dropdown before it closes.
 *
 * Called from both handleSearchSuccess AND the cache paths in source()
 * so behaviour is identical on first and repeat searches.
 *
 * @param {Array}  results
 * @param {string} term
 */
function checkForAutoAdd(results, term) {
    if (term.length < 2) return; // ignore single-char to avoid accidental add

    const exactSkuOrImei = results.find(r => {
        if (!r.product) return false;
        const skuMatch = r.product.sku &&
                         r.product.sku.toLowerCase() === term.toLowerCase();
        return skuMatch || r.exactImeiMatch;
    });

    // Auto-add: exact SKU/IMEI match OR single unambiguous result (e.g. search "0100" → one product)
    const exactMatch = exactSkuOrImei ||
        (results.length === 1 && results[0].product ? results[0] : null);

    if (!exactMatch || autocompleteState.adding) return;

    const matchType = exactSkuOrImei
        ? (exactMatch.product.sku && exactMatch.product.sku.toLowerCase() === term.toLowerCase() ? 'SKU' : 'IMEI')
        : 'MANUAL';

    showSearchIndicator(' Auto-adding...', 'orange');

    autocompleteState.autoAddTimer = setTimeout(() => {
        if (autocompleteState.adding) return; // another path already got here first

        autocompleteState.adding = true;
        $('#productSearchInput').autocomplete('close').val('');
        addProductFromAutocomplete(exactMatch, term, matchType);
        hideSearchIndicator();

        setTimeout(() => {
            autocompleteState.adding = false;
            autoFocusSearchInput(); // ready for next scan/search
        }, 100);
    }, AUTO_ADD_DELAY_MS);
}

/**
 * Add a product to the billing table from an autocomplete result item.
 *
 * Responsibilities:
 *   1. Dedup guard  block same-product-same-IMEI rescans, and non-manual repeats
 *   2. Refresh stale window.stockData with the latest prices from the result
 *   3. Fall through to fetchProductStock if no stock entry is available
 *
 * @param {Object} item        - autocomplete result item
 * @param {string} [searchTerm='']
 * @param {string} [matchType='']  - 'SKU'|'IMEI'|'MANUAL'|'MANUAL_ENTER'|'SCANNER_SKU'|
 */
function addProductFromAutocomplete(item, searchTerm = '', matchType = '') {
    if (!item.product) return;

    //  Dedup guard
    if (autocompleteState.lastProduct &&
        autocompleteState.lastProduct.id === item.product.id) {

        if (item.product.is_imei_or_serial_no === 1 && matchType === 'IMEI') {
            autoFocusSearchInput(); // never allow duplicate IMEI add
            return;
        }
        if (matchType !== 'MANUAL' && matchType !== 'MANUAL_ENTER') {
            autoFocusSearchInput(); // block accidental scanner/auto-add repeat
            return;
        }
    }

    autocompleteState.lastProduct = item.product;

    //  Keep window.stockData current
    if (item.stockData) {
        const idx = window.stockData.findIndex(s => s.product.id === item.product.id);
        if (idx !== -1) {
            window.stockData[idx] = item.stockData; // refresh stale entry
        } else {
            window.stockData.push(item.stockData);
            window.allProducts.push(item.stockData);
        }
    }

    const stockEntry = item.stockData ||
                       window.stockData.find(s => s.product.id === item.product.id);

    if (!stockEntry) {
        fetchProductStock(item.product.id, searchTerm, matchType);
        return;
    }

    if (typeof window.addProductToTable === 'function') {
        window.addProductToTable(item.product, searchTerm, matchType);
    }
    // Show latest-added product in the display grid (scroll card into view)
    if (typeof window.scrollProductCardIntoView === 'function') {
        window.scrollProductCardIntoView(item.product.id);
    }

    setTimeout(() => autoFocusSearchInput(), 200);
}

/*
   9  MANUAL-ENTER LOGIC
    */

/**
 * Handle Enter when no autocomplete item is explicitly selected.
 * Adds the focused item, or the first result if nothing is highlighted.
 * @param {jQuery} $input
 */
function handleManualEnter($input) {
    const focused    = $input.autocomplete('widget').find('.ui-state-focus');
    const term       = $input.val().trim();
    let itemToAdd    = getSelectedItem(focused) || getFirstResult();

    if (itemToAdd && itemToAdd.product && shouldAddProduct(itemToAdd)) {
        autocompleteState.lastProduct = itemToAdd.product;
        addProductFromAutocomplete(itemToAdd, term, itemToAdd.imeiMatch ? 'IMEI' : 'MANUAL_ENTER');
        $input.autocomplete('close').val('');
        return;
    }
    // No selection but input has value (e.g. paste or scanner without Enter): run one search and add if single result
    if (term.length >= 2) {
        $input.autocomplete('close');
        searchForExactMatch(term);
        return;
    }
    $input.autocomplete('close').val('');
    autoFocusSearchInput();
}

/**
 * Return the autocomplete item for the focused menu row, or null.
 * @param {jQuery} focused
 * @returns {Object|null}
 */
function getSelectedItem(focused) {
    if (!focused.length) return null;
    const instance = $('#productSearchInput').autocomplete('instance');
    return (instance && instance.menu && instance.menu.active)
        ? instance.menu.active.data('ui-autocomplete-item')
        : null;
}

/** Return the first item from the last result set, or null. */
function getFirstResult() {
    return autocompleteState.lastResults.length > 0
        ? autocompleteState.lastResults[0]
        : null;
}

/**
 * Return true if the item is safe to add (not the same product as the
 * most recently added one, guarding against accidental Enter repeats).
 * @param {Object} item
 * @returns {boolean}
 */
function shouldAddProduct(item) {
    return !autocompleteState.lastProduct;
           autocompleteState.lastProduct.id !== item.product.id;
}

/*
   10 BARCODE SCANNER
    */

/**
 * Entry point for a confirmed barcode scan.
 * Called by setupKeyboardEvents() when Enter arrives after scanner-speed keys.
 * @param {string} scannedValue
 */
function handleBarcodeScan(scannedValue) {
    if (!scannedValue || scannedValue.length < 2) {
        autoFocusSearchInput();
        return;
    }
    $('#productSearchInput').val(scannedValue);
    searchForExactMatch(scannedValue);
}

/*
   11 JQUERY UI AUTOCOMPLETE WIDGET
    */

/**
 * Initialise (or reinitialise) the jQuery UI Autocomplete widget on
 * #productSearchInput.
 *
 * Called on page load and whenever the location changes.
 * The result cache is cleared on each call so stale stock data is discarded.
 */
function initAutocomplete() {
    searchCache.clear(); // new location = fresh stock data needed

    $('#productSearchInput').autocomplete({
        position:  { my: 'left top', at: 'left bottom', collision: 'none' },
        minLength: 1,
        delay:     0, // debouncing is handled manually in source()

        //  source: three-tier lookup
        source(request, response) {
            if (!window.selectedLocationId) return response([]);

            const term       = request.term;
            const locationId = window.selectedLocationId;
            autocompleteState.currentTerm = term;
            resetAutocompleteState();

            // Tier 1  exact cache hit (0 ms, 0 network)
            const cached = getCachedEntry(locationId, term);
            if (cached) {
                autocompleteState.lastResults = cached.results.filter(r => r.product);
                checkForAutoAdd(cached.results, term);
                if (typeof window.displaySearchResultsInGrid === 'function') {
                    window.displaySearchResultsInGrid(cached.rawData || []);
                }
                response(cached.results);
                return;
            }

            // Tier 2  cascade from full parent entry (0 ms, 0 network)
            const cascaded = getCascadeResults(locationId, term);
            if (cascaded !== null) {
                const cascadedRaw = cascaded.map(r => r.stockData).filter(Boolean);
                storeCacheEntry(locationId, term, cascaded, cascadedRaw);
                autocompleteState.lastResults = cascaded.filter(r => r.product);
                checkForAutoAdd(cascaded, term);
                if (typeof window.displaySearchResultsInGrid === 'function') {
                    window.displaySearchResultsInGrid(cascadedRaw);
                }
                response(cascaded.length > 0
                    ? cascaded
                    : [{ label: 'No results found', value: '' }]);
                return;
            }

            // Tier 3  AJAX (debounced: wait for user to pause typing = fewer requests, faster feel)
            const DEBOUNCE_MS = 120;
            autocompleteState.debounceTimer = setTimeout(() => {
                if (!autocompleteState.isRequesting) {
                    autocompleteState.isRequesting = true;
                    createProductSearchRequest(term, response);
                }
            }, DEBOUNCE_MS);
        },

        //  select: user clicks or presses Enter on a menu item
        select(event, ui) {
            if (ui.item.showQuickAdd && ui.item.searchTerm) {
                event.preventDefault();
                $('#productSearchInput').val('');
                showQuickAddOption(ui.item.searchTerm);
                return false;
            }
            if (ui.item.notAvailable) {
                event.preventDefault();
                $('#productSearchInput').val('');
                return false;
            }
            if (!ui.item.product || autocompleteState.adding) return false;

            if (autocompleteState.autoAddTimer) {
                clearTimeout(autocompleteState.autoAddTimer);
                autocompleteState.autoAddTimer = null;
            }
            autocompleteState.adding = true;
            $('#productSearchInput').val('');
            addProductFromAutocomplete(ui.item, autocompleteState.currentTerm,
                ui.item.imeiMatch ? 'IMEI' : 'MANUAL');
            setTimeout(() => { autocompleteState.adding = false; }, 50);
            return false;
        },

        //  focus: update hint when keyboard cursor moves
        focus(event, ui) {
            event.preventDefault();
            if (ui.item && ui.item.product) showSearchIndicator('↵ Press Enter to add');
            else                            hideSearchIndicator();
            return false;
        },

        //  open: apply responsive styles and focus first item
        open() {
            const $input   = $(this);
            const instance = $input.autocomplete('instance');
            if (instance && instance.menu && instance.menu.element) {
                instance.menu.element.css({
                    'max-height': '350px',
                    'overflow-y': 'auto',
                    'overflow-x': 'hidden',
                    'display':    'block',
                });
            }
            setupDirectKeyboardHandling($input, instance);
            setTimeout(() => setupFirstItemFocus(), 100);
        },

        //  close: clean up indicator and key handler
        close() {
            hideSearchIndicator();
            $('#productSearchInput').off('keydown.custom');
        },
    });

    setupCustomRendering();
    setupKeyboardEvents();
    setupInputEvents();
    setupAutocompleteStyles();
}

/*
   12 MENU RENDERING
    */

/**
 * Override jQuery UI's _renderItem and _resizeMenu.
 * Must be called after .autocomplete() is initialised.
 */
function setupCustomRendering() {
    const instance = $('#productSearchInput').autocomplete('instance');

    instance._renderItem = function (ul, item) {
        const li = $('<li>').addClass('ui-menu-item').data('ui-autocomplete-item', item);

        if (item.product) {
            li.append(item.imeiMatch
                ? createImeiItemHtml(item)
                : `<div class="autocomplete-item" style="padding:8px 12px;">${item.label}</div>`);
        } else {
            li.append(`<div class="autocomplete-item no-product"
                            style="color:red;padding:8px 12px;font-style:italic;">
                            ${item.label}
                        </div>`);
        }

        return li.appendTo(ul);
    };

    instance._resizeMenu = function () {
        const isMobile = window.innerWidth <= 991;
        const width    = isMobile
            ? (window.innerWidth - 10) + 'px'
            : Math.max(this.element.outerWidth(), 450) + 'px';

        this.menu.element.css({
            width,
            'max-height': '350px',
            'overflow-y': 'auto',
            'overflow-x': 'hidden',
            ...(isMobile ? { 'max-width': width, left: '5px' } : {}),
        });
    };
}

/**
 * Focus the first valid product item after the dropdown opens so the
 * cashier can confirm with Enter without moving the cursor.
 */
function setupFirstItemFocus() {
    const instance = $('#productSearchInput').autocomplete('instance');
    if (!instance || !instance.menu) return;

    const menu      = instance.menu;
    const firstItem = menu.element.find('li.ui-menu-item').first();
    if (!firstItem.length) return;

    const itemData = firstItem.data('ui-autocomplete-item');
    if (itemData && itemData.product) {
        menu.element.find('.ui-state-focus').removeClass('ui-state-focus');
        firstItem.addClass('ui-state-focus');
        menu.active = firstItem;
        showSearchIndicator(' Press Enter to add');
    }
}

/**
 * Build rich HTML for an IMEI-matched product row.
 * @param {Object} item
 * @returns {string}
 */
function createImeiItemHtml(item) {
    const { product_name: name, sku = '' } = item.product;
    const imeiMatch  = item.label.match(/ IMEI: ([^\[]+)/);
    const stockMatch = item.label.match(/\[Stock: ([^\]]+)\]/);
    const imei  = imeiMatch  ? imeiMatch[1].trim()  : '';
    const stock = stockMatch ? stockMatch[1]          : '';

    return `
        <div style="padding:10px 12px;background:#e8f4f8;border-left:4px solid #17a2b8;">
            <div style="font-weight:600;color:#2c3e50;margin-bottom:4px;">
                ${name}
                ${sku ? `<span style="color:#6c757d;font-size:0.9em;">(${sku})</span>` : ''}
            </div>
            <div style="display:flex;justify-content:space-between;font-size:0.9em;">
                <span style="color:#17a2b8;font-weight:500;"> IMEI: ${imei}</span>
                <span style="color:#28a745;font-weight:500;padding-left:10px;">Stock: ${stock}</span>
            </div>
        </div>`;
}

/*
   13 KEYBOARD & INPUT EVENTS

 *
 * Key design constraint:
 *   When the scanner is active, input.scanner MUST NOT call
 *   autocomplete('search') because handleBarcodeScan() already fires one
 *   direct AJAX search on Enter.  Doing both creates parallel AJAX calls
 *   and parallel checkForAutoAdd timers, which causes double-adds and
 *   stuck state.
 *
 *   cancelAllPendingPaths() is called before handleBarcodeScan() to
 *   ensure only one add path runs at any time.
 *  */

/**
 * Attach keydown + input listeners that correctly distinguish between:
 *   a) Barcode scanner   keys < 50 ms apart  buffer accumulates  Enter fires handleBarcodeScan
 *   b) Fast human        keys < 50 ms, no scanner Enter  defer autocomplete('search')
 *   c) Normal human      keys  50 ms  jQuery UI source() debounce handles it naturally
 */
function setupKeyboardEvents() {
    let scannerBuffer  = '';      // accumulates chars sent by the scanner
    let scannerTimeout = null;    // resets buffer if scanner goes silent
    let scannerFallbackTimer = null; // fallback search when fast typing looks like scanner but no Enter arrives
    let prevKeyTime    = 0;       // timestamp of second-most-recent keydown
    let lastKeyTime    = 0;       // timestamp of most-recent keydown
    let scannerActive  = false;   // true while scanner is mid-sequence

    $('#productSearchInput').off('keydown.scanner keypress.scanner input.scanner')

        //  keydown: classify and route
        .on('keydown.scanner', function (event) {
            const now          = Date.now();
            const interKeyDiff = now - lastKeyTime;
            prevKeyTime = lastKeyTime;
            lastKeyTime = now;

            const isMenuOpen = $(this).autocomplete('widget').is(':visible');

            // jQuery UI handles its own navigation when the menu is open
            if (isMenuOpen &&
                (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter')) {
                return;
            }

            const isScannerSpeed = interKeyDiff > 0 &&
                                   interKeyDiff < SCANNER.KEY_GAP_MS &&
                                   event.key !== 'Enter';

            if (event.key === 'Enter' && !isMenuOpen) {
                event.preventDefault();
                const value          = $(this).val().trim();
                const isScannerEnter = scannerActive ||
                                       scannerBuffer.length > 0 ||
                                       (value.length > 0 && interKeyDiff < 100);

                if (scannerFallbackTimer) {
                    clearTimeout(scannerFallbackTimer);
                    scannerFallbackTimer = null;
                }

                if (isScannerEnter) {
                    // Kill all other async paths first, then do one clean scan add
                    cancelAllPendingPaths($(this));
                    autocompleteState.adding = false;
                    const barcode = value || scannerBuffer;
                    scannerBuffer = '';
                    scannerActive = false;
                    handleBarcodeScan(barcode);
                } else {
                    handleManualEnter($(this));
                    event.stopImmediatePropagation();
                }

            } else if (isScannerSpeed) {
                if (event.key.length === 1) scannerBuffer += event.key;
                scannerActive = true;

                // Fast manual typing (e.g. 0241) can look like scanner speed.
                // If no Enter comes shortly, fall back to standard autocomplete search.
                if (scannerFallbackTimer) clearTimeout(scannerFallbackTimer);
                scannerFallbackTimer = setTimeout(() => {
                    if (!scannerActive) return;
                    const $input = $('#productSearchInput');
                    const val = $input.val().trim();
                    if (val.length > 0) {
                        scannerActive = false;
                        scannerBuffer = '';
                        $input.autocomplete('search', val);
                    }
                }, 180);

                if (scannerTimeout) clearTimeout(scannerTimeout);
                scannerTimeout = setTimeout(() => {
                    scannerBuffer = '';
                    scannerActive = false;
                    if (scannerFallbackTimer) {
                        clearTimeout(scannerFallbackTimer);
                        scannerFallbackTimer = null;
                    }
                }, SCANNER.BUFFER_RESET_MS);

            } else {
                if (scannerFallbackTimer) {
                    clearTimeout(scannerFallbackTimer);
                    scannerFallbackTimer = null;
                }
                scannerActive = false; // human-speed key  clear scanner state
            }
        })

        //  input: handle fast human typing without interfering with scanner
        .on('input.scanner', function () {
            // If scanner is active, DO NOT fire autocomplete('search').
            // handleBarcodeScan() will do exactly one search on Enter.
            if (scannerActive) return;

            // Fast human typing (< 50 ms inter-key): wait for a natural pause
            // before triggering a search. Uses an isolated timer  never touches
            // autocompleteState.debounceTimer, which would break normal auto-complete.
            const interKeyDiff = lastKeyTime - prevKeyTime;
            const isFastHuman  = prevKeyTime > 0 &&
                                 interKeyDiff > 0 &&
                                 interKeyDiff < SCANNER.KEY_GAP_MS;

            if (isFastHuman) {
                const $self = $(this);
                clearTimeout(cancelAllPendingPaths._scannerInputTimer);
                cancelAllPendingPaths._scannerInputTimer = setTimeout(() => {
                    if (!scannerActive) { // re-check scanner didn't activate during wait
                        const val = $self.val().trim();
                        if (val.length > 0) $self.autocomplete('search', val);
                    }
                }, 120);
            }
            // Normal human typing ( 50 ms): source()'s 50 ms debounce handles it.
        });
}

/**
 * Attach arrow-key / Enter / Escape handlers directly on the input once
 * the dropdown opens.  jQuery UI's built-in handlers are inconsistent when
 * _renderItem is overridden, so navigation is handled manually here.
 *
 * @param {jQuery} $input
 * @param {Object} instance  - jQuery UI autocomplete instance
 */
function setupDirectKeyboardHandling($input, instance) {
    $input.off('keydown.custom').on('keydown.custom', function (event) {
        if (!$input.autocomplete('widget').is(':visible') || !instance.menu) return;

        switch (event.key || event.keyCode) {
            case 'ArrowDown': case 40:
                event.preventDefault(); event.stopPropagation();
                navigateMenu('down', instance.menu); return false;

            case 'ArrowUp': case 38:
                event.preventDefault(); event.stopPropagation();
                navigateMenu('up', instance.menu); return false;

            case 'Enter': case 13:
                event.preventDefault(); event.stopPropagation();
                selectCurrentItem(instance.menu, instance); return false;

            case 'Escape': case 27:
                $input.autocomplete('close'); return false;
        }
    });
}

/**
 * Move the highlight one step up or down, wrapping at boundaries.
 * Scrolls the menu element to keep the highlighted item visible.
 *
 * @param {'up'|'down'} direction
 * @param {Object}      menu
 */
function navigateMenu(direction, menu) {
    const items = menu.element.find('li.ui-menu-item');
    let current = -1;
    items.each(function (i) { if ($(this).hasClass('ui-state-focus')) { current = i; return false; } });

    const last = items.length - 1;
    const next = direction === 'down'
        ? (current < last ? current + 1 : 0)
        : (current > 0   ? current - 1  : last);

    items.removeClass('ui-state-focus');
    const $next    = items.eq(next).addClass('ui-state-focus');
    menu.active    = $next;

    const nextData = $next.data('ui-autocomplete-item');
    if (nextData && nextData.product) showSearchIndicator('↵ Press Enter to add');
    else                              hideSearchIndicator();

    // Scroll into view
    const menuEl = menu.element[0];
    const itemEl = $next[0];
    if (itemEl.offsetTop < menuEl.scrollTop) {
        menuEl.scrollTop = itemEl.offsetTop;
    } else if (itemEl.offsetTop + itemEl.offsetHeight > menuEl.scrollTop + menuEl.offsetHeight) {
        menuEl.scrollTop = itemEl.offsetTop + itemEl.offsetHeight - menuEl.offsetHeight;
    }
}

/**
 * Trigger the select event on the currently highlighted menu item.
 * @param {Object} menu
 * @param {Object} instance
 */
function selectCurrentItem(menu, instance) {
    const focused  = menu.element.find('li.ui-state-focus');
    if (!focused.length) return;
    const itemData = focused.data('ui-autocomplete-item');
    if (itemData && itemData.product) instance._trigger('select', null, { item: itemData });
}

/**
 * Attach general-purpose input events on the search box.
 *    Sync product card grid as the user types
 *    Clear state when the field is emptied
 *    Treat paste as a barcode scan
 *    Hide indicator on blur (unless dropdown is open or add is in progress)
 */
function setupInputEvents() {
    $('#productSearchInput')

        .on('input.general', function () {
            autocompleteState.lastProduct = null;
            const value = $(this).val();

            if (typeof window.filterProductGrid === 'function') {
                window.filterProductGrid(value);
            }

            if (value.length === 0) {
                hideSearchIndicator();
                resetAutocompleteState();
                // Restore product grid to first page when search is cleared
                if (typeof window.fetchPaginatedProducts === 'function') {
                    window.fetchPaginatedProducts(true);
                }
            }
            // autoAddTimer is already cancelled inside resetAutocompleteState(),
            // called from source() on every keystroke  no extra cancel needed here.
        })

        .on('paste', function () {
            setTimeout(() => {
                const pasted = $(this).val().trim();
                if (pasted.length > 0) handleBarcodeScan(pasted);
            }, 50);
        })

        .on('blur', function () {
            setTimeout(() => {
                const isOpen = $(this).autocomplete('widget').is(':visible');
                if (!isOpen && !autocompleteState.adding) hideSearchIndicator();
            }, 200);
        });
}

/*
   14 QUICK-ADD MODAL
    */

/**
 * Show the Quick-Add modal pre-filled with `searchTerm` as the SKU.
 * The modal DOM is injected only once; subsequent calls reuse it.
 *
 * @param {string} searchTerm  - unrecognised barcode / product name
 */
function showQuickAddOption(searchTerm) {
    if (document.getElementById('quickAddModal')) {
        $('#quickAddSku').val(searchTerm);
        $('#quickAddName').val('Unnamed Product');
        $('#quickAddCategory').val('General');
        $('#quickAddModal').modal('show');
        return;
    }

    const modalHtml = `
        <div class="modal fade" id="quickAddModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-warning">
                <h5 class="modal-title">
                  <i class="fas fa-exclamation-triangle"></i> Product Not Found
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="alert alert-info">
                  <strong>This product is not in the system.</strong> Do you want to add it quickly?
                </div>
                <form id="quickAddForm">
                  <div class="row">
                    <div class="col-md-6">
                      <label class="form-label">SKU / Barcode:</label>
                      <input type="text" class="form-control" id="quickAddSku" placeholder="Enter SKU/Barcode">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Product Name:</label>
                      <input type="text" class="form-control" id="quickAddName" value="Unnamed Product" required>
                    </div>
                  </div>
                  <div class="row mt-3">
                    <div class="col-md-4">
                      <label class="form-label">Price:</label>
                      <input type="number" class="form-control" id="quickAddPrice" placeholder="0.00" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Quantity:</label>
                      <input type="number" class="form-control" id="quickAddQty" value="1" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Total:</label>
                      <input type="number" class="form-control" id="quickAddTotal" readonly>
                    </div>
                  </div>
                  <div class="row mt-3">
                    <div class="col-md-6">
                      <label class="form-label">Category:</label>
                      <input type="text" class="form-control" id="quickAddCategory" value="General" list="categoryOptions">
                      <datalist id="categoryOptions">
                        <option value="General"><option value="Grocery"><option value="Electronics">
                        <option value="Clothing"><option value="Food &amp; Beverages">
                        <option value="Home &amp; Garden"><option value="Sports &amp; Outdoors">
                        <option value="Health &amp; Beauty"><option value="Books &amp; Media">
                        <option value="Automotive">
                      </datalist>
                      <small class="text-muted">Type or select a category</small>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Stock Type:</label>
                      <select class="form-control" id="quickAddStockType" onchange="toggleStockQuantity()">
                        <option value="unlimited">Unlimited Stock</option>
                        <option value="limited">Limited Stock</option>
                      </select>
                    </div>
                  </div>
                  <div class="row mt-3" id="stockQuantityRow" style="display:none;">
                    <div class="col-12">
                      <label class="form-label">Stock Quantity:</label>
                      <input type="number" class="form-control" id="quickAddStockQty" value="100" min="1">
                      <small class="text-muted">Inventory quantity, not sale quantity</small>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveAndAddProduct()">
                  <i class="fas fa-save"></i> Save &amp; Add to Bill
                </button>
              </div>
            </div>
          </div>
        </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    setupQuickAddListeners();
    $('#quickAddSku').val(searchTerm);
    $('#quickAddModal').modal('show');
    setTimeout(() => { $('#quickAddName').focus().select(); }, 500);
}

/** Wire up price  qty  total, Enter key focus chain, and clear-on-close. */
function setupQuickAddListeners() {
    $('#quickAddPrice, #quickAddQty').on('input', function () {
        const price = parseFloat($('#quickAddPrice').val()) || 0;
        const qty   = parseFloat($('#quickAddQty').val())   || 0;
        $('#quickAddTotal').val((price * qty).toFixed(2));
    });

    $('#quickAddName').on('keypress',  e => { if (e.which === 13) $('#quickAddPrice').focus(); });
    $('#quickAddPrice').on('keypress', e => { if (e.which === 13) $('#quickAddQty').focus(); });
    $('#quickAddQty').on('keypress',   e => {
        if (e.which === 13 && typeof window.saveAndAddProduct === 'function') window.saveAndAddProduct();
    });

    $('#quickAddModal').on('hidden.bs.modal', clearQuickAddForm);
}

/** Reset all quick-add form fields to default values. */
function clearQuickAddForm() {
    $('#quickAddSku').val('');
    $('#quickAddName').val('Unnamed Product');
    $('#quickAddPrice').val('0.00');
    $('#quickAddQty').val('1');
    $('#quickAddTotal').val('0.00');
    $('#quickAddCategory').val('General');
    $('#quickAddStockType').val('unlimited');
    $('#quickAddStockQty').val('100');
    $('#stockQuantityRow').hide();
}

/*
   15 AUTOCOMPLETE STYLES
    */

/**
 * Inject autocomplete dropdown CSS into <head> once.
 * Safe to call multiple times  skips if already injected.
 */
function setupAutocompleteStyles() {
    if (document.getElementById('autocomplete-styles')) return;

    const style   = document.createElement('style');
    style.id      = 'autocomplete-styles';
    style.textContent = `
        /* Dropdown container */
        .ui-autocomplete {
            max-height: 350px !important; overflow-y: auto !important; overflow-x: hidden !important;
            z-index: 1000; border: 1px solid #ddd; border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15); min-width: 400px !important; background: white;
        }
        .ui-autocomplete::-webkit-scrollbar        { width: 8px; }
        .ui-autocomplete::-webkit-scrollbar-track  { background: #f1f1f1; border-radius: 4px; }
        .ui-autocomplete::-webkit-scrollbar-thumb  { background: #888; border-radius: 4px; }
        .ui-autocomplete::-webkit-scrollbar-thumb:hover { background: #555; }

        /* List items */
        .ui-autocomplete .ui-menu-item {
            border-bottom: 1px solid #f0f0f0; list-style: none;
            margin: 0; padding: 0; cursor: pointer; outline: none;
        }
        .ui-autocomplete .ui-menu-item:last-child { border-bottom: none; }

        /* Highlighted item */
        .ui-autocomplete .ui-menu-item.ui-state-focus,
        .ui-autocomplete .ui-menu-item.ui-state-active,
        .ui-autocomplete .ui-menu-item:hover {
            background: #007bff !important; color: white !important; outline: none;
        }
        .ui-autocomplete .ui-menu-item.ui-state-focus   div,
        .ui-autocomplete .ui-menu-item.ui-state-active  div,
        .ui-autocomplete .ui-menu-item:hover            div,
        .ui-autocomplete .ui-menu-item.ui-state-focus   span,
        .ui-autocomplete .ui-menu-item.ui-state-active  span,
        .ui-autocomplete .ui-menu-item:hover            span {
            color: white !important; background: transparent !important; border-left-color: white !important;
        }

        /* Item inner wrapper */
        .ui-autocomplete .ui-menu-item .autocomplete-item {
            white-space: normal; word-wrap: break-word; display: block; width: 100%;
        }
        .ui-autocomplete .ui-menu-item.no-product { opacity: .7; cursor: default; }

        /* Search box context + status badge */
        #productSearchInput { position: relative; }
        .search-indicator {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            font-size: 12px; pointer-events: none; background: white; padding: 0 5px; z-index: 1001;
        }
    `;
    document.head.appendChild(style);
}

/*
   16 PUBLIC API
   Expose only what other modules / pos_ajax.blade.php need.
    */

window.initAutocomplete           = initAutocomplete;
window.autoFocusSearchInput       = autoFocusSearchInput;
window.showSearchIndicator        = showSearchIndicator;
window.hideSearchIndicator        = hideSearchIndicator;
window.addProductFromAutocomplete = addProductFromAutocomplete;
// window.clearAutocompleteCache and window.autocompleteState already assigned above

