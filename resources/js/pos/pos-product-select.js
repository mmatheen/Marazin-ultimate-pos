/**
 * pos-product-select.js — Phase 13
 * Product selection: add-to-table, batch/IMEI modals, product detail modal,
 * customer previous-price lookup.
 * Extracted from pos_ajax.blade.php.
 *
 * Reads from window:
 *   window.selectedLocationId, window.isEditing, window.currentEditingSaleId
 *   window.stockData, window.allProducts
 *   window.PosConfig.permissions.allowedPriceTypes
 *   window.showFreeQtyColumn, window.userPermissions
 *   window.customerPriceCache                       (pos-cache.js)
 *   window.getCurrentCustomer()                     (pos-customer.js)
 *   window.getCustomerTypePrice()                   (pos-customer.js)
 *   window.updateTotals()                           (pos-cart.js)
 *   window.fetchPaginatedProducts()                 (pos-product-display.js)
 *   window.getSafeImageUrl()                        (pos-ui.js)
 *   window.formatAmountWithSeparators()             (pos-utils.js)
 *   window.cleanupModalBackdrop()                   (pos-ui.js)
 *   window.addProductToBillingBody()                (pos_ajax — bridge)
 *
 * Exports to window:
 *   window.addProductToTable, window.normalizeBatches,
 *   window.showBatchPriceSelectionModal, window.showImeiSelectionModal,
 *   window.showProductModal, window.getCustomerPreviousPrice
 */
'use strict';

// ---- Module-local state ----
let priceType   = 'retail';
window.priceType = priceType; // Phase 14: expose for pos-sale.js (gatherSaleData)
let selectedRow;
let activeModalProductId      = null;
let selectedImeisInBilling    = [];
let currentImeiProduct        = null;
let currentImeiStockEntry     = null;

// ---- addProductToTable ----
function addProductToTable(product, searchTermOrQty = '', matchType = '') {
    // Check if second parameter is a number (quantity from mobile modal)
    const isMobileQuantity = typeof searchTermOrQty === 'number';
    const mobileQty = isMobileQuantity ? searchTermOrQty : null;
    const searchTerm = isMobileQuantity ? '' : (searchTermOrQty || '');

    // Shorthand aliases for window globals used throughout
    const stockData    = window.stockData    || [];
    const allProducts  = window.allProducts  || [];

    if (!stockData || stockData.length === 0) {
        console.error('stockData is not defined or empty');
        toastr.error('Stock data is not available', 'Error');
        return;
    }

    const stockEntry = stockData.find(stock => stock.product.id == product.id); // Use == for type coercion

    if (!stockEntry) {
        toastr.error('Stock entry not found for the product', 'Error');
        return;
    }

    // ✅ FIX: Include free stock in total sellable quantity check
    const totalQuantity = (parseFloat(stockEntry.total_stock) || 0) + (parseFloat(stockEntry.total_free_stock) || 0);

    // Get current customer information for pricing
    const currentCustomer = window.getCurrentCustomer();

    const selectedLocationId = window.selectedLocationId;

    // If product is unlimited stock (stock_alert === 0), allow sale even if quantity is 0
    if (product.stock_alert === 0) {
        // Proceed to add product with batch "all" and quantity 0 (unlimited)
        const batchesArray = normalizeBatches(stockEntry);

        // Use latest batch for pricing determination
        const latestBatch = batchesArray.length > 0 ? batchesArray[0] : null;

        // Get customer-type-based price
        const priceResult = window.getCustomerTypePrice(latestBatch, product, currentCustomer.customer_type);

        if (priceResult.hasError) {
            toastr.error(
                `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                'Pricing Error');
            return;
        }

        window.locationId = selectedLocationId;
        window.addProductToBillingBody(
            product,
            stockEntry,
            priceResult.price,
            "all", // batchId is "all"
            0, // unlimited stock, so quantity is 0
            currentCustomer.customer_type,
            mobileQty || 1, // pass mobile quantity or default to 1
            [], // imeis
            null, // discountType
            null, // discountAmount
            latestBatch // selectedBatch with free_qty
        );
        return;
    }

    // Check if product requires IMEI
    if (product.is_imei_or_serial_no === 1) {
        const availableImeis = stockEntry.imei_numbers?.filter(imei => imei.status === "available") ||
        [];

        const billingBody = document.getElementById('billing-body');
        const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row =>
            row.querySelector('.product-id')?.textContent == product.id
        );

        if (existingRows.length > 0) {
            // For existing rows, don't specify a batch to show all available IMEIs
            showImeiSelectionModal(product, stockEntry, [], searchTerm, matchType, null);
            return;
        }

        // Get the available batches for this location to determine default batch
        let batchesArray = normalizeBatches(stockEntry);
        batchesArray = batchesArray.filter(batch =>
            Array.isArray(batch.location_batches) &&
            batch.location_batches.some(lb =>
                String(lb.location_id) == String(selectedLocationId) &&
                parseFloat(lb.quantity) > 0
            )
        );

        if (batchesArray.length === 0) {
            toastr.error('No batches with available quantity found in this location for IMEI product',
                'Error');
            return;
        }

        // Sort batches by id descending (latest batch first)
        batchesArray = batchesArray.sort((a, b) => parseInt(b.id) - parseInt(a.id));

        // Check if there are multiple batches with different prices
        const uniquePrices = [];
        for (const batch of batchesArray) {
            const priceResult = window.getCustomerTypePrice(batch, product, currentCustomer.customer_type);
            if (!priceResult.hasError) {
                uniquePrices.push(priceResult.price);
            }
        }
        const distinctPrices = [...new Set(uniquePrices)];

        if (distinctPrices.length <= 1) {
            // Single price - show all available IMEIs from all batches
            showImeiSelectionModal(product, stockEntry, [], searchTerm, matchType, "all");
        } else {
            // Multiple prices - user needs to select batch first, then IMEIs
            showBatchPriceSelectionModal(product, stockEntry, batchesArray, currentCustomer);
        }
        return;
    }

    // If no IMEI required, proceed normally
    if ((totalQuantity === 0 || totalQuantity === "0" || totalQuantity === "0.00") && product
        .stock_alert !== 0) {
        toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
        return;
    }

    // Ensure batches is always an array using helper function
    let batchesArray = normalizeBatches(stockEntry);

    // Filter batches by selected location and available quantity (paid OR free > 0)
    batchesArray = batchesArray.filter(batch =>
        Array.isArray(batch.location_batches) &&
        batch.location_batches.some(lb =>
            String(lb.location_id) == String(selectedLocationId) &&
            ((parseFloat(lb.quantity) || 0) + (parseFloat(lb.free_quantity) || 0)) > 0
        )
    );

    if (batchesArray.length === 0) {
        toastr.error('No batches with available quantity found in this location', 'Error');
        return;
    }

    // Sort batches by id descending (latest batch first)
    batchesArray = batchesArray.sort((a, b) => parseInt(b.id) - parseInt(a.id));

    // Get unique prices for the current customer type across batches in this location
    const customerTypePrices = [];
    for (const batch of batchesArray) {
        const priceResult = window.getCustomerTypePrice(batch, product, currentCustomer.customer_type);
        if (!priceResult.hasError) {
            customerTypePrices.push(priceResult.price);
        }
    }

    // Remove duplicates
    const uniquePrices = [...new Set(customerTypePrices)];

    // If there's only one price or all batches have the same price, add the latest batch
    if (uniquePrices.length <= 1) {
        // Default: select "All" batch (not a real batch, but for all available)
        // ✅ FIX: Calculate total quantity including free stock (paid + free)
        let totalQty = 0;
        batchesArray.forEach(batch => {
            batch.location_batches.forEach(lb => {
                if (String(lb.location_id) == String(selectedLocationId)) {
                    totalQty += (parseFloat(lb.quantity) || 0) + (parseFloat(lb.free_quantity) || 0);
                }
            });
        });

        // Use latest batch for pricing
        const latestBatch = batchesArray[0];
        const priceResult = window.getCustomerTypePrice(latestBatch, product, currentCustomer.customer_type);

        if (priceResult.hasError) {
            toastr.error(
                `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                'Pricing Error');
            return;
        }

        window.locationId = selectedLocationId;
        window.addProductToBillingBody(
            product,
            stockEntry,
            priceResult.price,
            "all", // batchId is "all"
            totalQty,
            currentCustomer.customer_type,
            mobileQty || 1, // pass mobile quantity or default to 1
            [], // imeis
            null, // discountType
            null, // discountAmount
            latestBatch // selectedBatch with free_qty
        );
    } else {
        // Multiple prices found → show modal (user must select batch)
        showBatchPriceSelectionModal(product, stockEntry, batchesArray, currentCustomer);
    }
}

// ---- normalizeBatches ----
function normalizeBatches(stockEntry) {
    if (!stockEntry || !stockEntry.batches) {
        return [];
    }

    if (Array.isArray(stockEntry.batches)) {
        return stockEntry.batches;
    } else if (typeof stockEntry.batches === 'object' && stockEntry.batches !== null) {
        return Object.values(stockEntry.batches);
    }

    return [];
}

// ---- showBatchPriceSelectionModal ----
function showBatchPriceSelectionModal(product, stockEntry, batches, currentCustomer = null) {
    const tbody = document.getElementById('batch-price-list');
    const modalElement = document.getElementById('batchPriceModal');
    const modal = new bootstrap.Modal(modalElement);

    const allowedPriceTypes = window.PosConfig.permissions.allowedPriceTypes;
    const selectedLocationId = window.selectedLocationId;

    // Check if user has any price permissions
    if (!allowedPriceTypes || allowedPriceTypes.length === 0) {
        toastr.error('You do not have permission to view batch prices. Please contact your administrator.', 'Access Denied');
        return;
    }

    // Get current customer if not provided
    if (!currentCustomer) {
        currentCustomer = window.getCurrentCustomer();
    }

    // Prevent opening modal again for same product
    if (activeModalProductId === product.id) {
        toastr.info('Batch selection already in progress for this product.');
        return;
    }
    activeModalProductId = product.id;

    // Reset modal content
    tbody.innerHTML = '';
    const batchRows = [];

    // Filter and sort batches
    const validBatches = batches.filter(batch => {
        const locationBatch = batch.location_batches.find(lb => lb.location_id ==
            selectedLocationId);
        return locationBatch && parseFloat(locationBatch.quantity) > 0;
    }).sort((a, b) => parseInt(b.id) - parseInt(a.id));

    if (validBatches.length === 0) {
        // Calculate colspan based on allowed price types
        const colspanCount = 3 + allowedPriceTypes.length; // # + Batch No + Quantity + Action + price columns
        tbody.innerHTML =
            `<tr><td colspan="${colspanCount}" class="text-center text-danger">No batches available</td></tr>`;
        modal.show();
        setTimeout(() => modal.hide(), 1500);
        activeModalProductId = null;
        return;
    }

    // Populate modal with batches
    validBatches.forEach((batch, index) => {
        const locationBatch = batch.location_batches.find(lb => lb.location_id ==
            selectedLocationId);

        // Build price columns HTML based on allowed price types
        let priceColumnsHtml = '';

        if (allowedPriceTypes.includes('retail')) {
            const retailPrice = batch.retail_price ? parseFloat(batch.retail_price).toFixed(2) : 'N/A';
            priceColumnsHtml += `<td class="text-center">Rs ${retailPrice}</td>`;
        }

        if (allowedPriceTypes.includes('wholesale')) {
            const wholesalePrice = batch.wholesale_price ? parseFloat(batch.wholesale_price).toFixed(2) : 'N/A';
            priceColumnsHtml += `<td class="text-center">Rs ${wholesalePrice}</td>`;
        }

        if (allowedPriceTypes.includes('special')) {
            const specialPrice = batch.special_price ? parseFloat(batch.special_price).toFixed(2) : 'N/A';
            priceColumnsHtml += `<td class="text-center">Rs ${specialPrice}</td>`;
        }

        if (allowedPriceTypes.includes('max_retail')) {
            const maxRetailPrice = batch.max_retail_price ? parseFloat(batch.max_retail_price).toFixed(2) : 'N/A';
            priceColumnsHtml += `<td class="text-center">Rs ${maxRetailPrice}</td>`;
        }

        // Get customer-type-based price for this batch
        const priceResult = window.getCustomerTypePrice(batch, product, currentCustomer.customer_type);

        let buttonContent = '';

        if (priceResult.hasError) {
            buttonContent =
                `<button class="btn btn-sm btn-secondary" disabled>No Price</button>`;
        } else {
            const priceToUse = priceResult.price;
            const batchMrp = batch.max_retail_price !== undefined && batch.max_retail_price !==
                null ?
                parseFloat(batch.max_retail_price) : (product.max_retail_price || 0);

            buttonContent = `
                <button class="btn btn-sm btn-primary select-batch-btn"
                    data-batch-id="${batch.id}"
                    data-customer-price="${priceToUse}"
                    data-max-retail-price="${batchMrp}"
                    data-batch-json='${JSON.stringify(batch)}'>
                    Select
                </button>
            `;
        }

        const showFreeQtyColumn = window.showFreeQtyColumn;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>[${index + 1}]</strong></td>
            <td>${batch.batch_no}</td>
            ${priceColumnsHtml}
            <td class="text-center">
                ${locationBatch.quantity} PC(s)
                ${batch.free_qty && batch.free_qty > 0 && showFreeQtyColumn ? `<br><small style="color: #28a745; font-weight: 600;">FREE: ${batch.free_qty} (${batch.free_qty_percentage}%)</small>` : ''}
            </td>
            <td class="text-center">${buttonContent}</td>
        `;
        tbody.appendChild(tr);
        batchRows.push(tr);
    });

    let isModalOpen = false;

    // Handle batch selection
    function handleBatchSelect(e) {
        if (e.target.classList.contains('select-batch-btn')) {
            const batchJson = e.target.dataset.batchJson;
            const selectedBatch = JSON.parse(batchJson);
            const locationBatch = selectedBatch.location_batches.find(lb => lb.location_id ==
                selectedLocationId);
            const qty = locationBatch?.quantity || 0;

            // Get customer-type-based price for the selected batch
            const priceResult = window.getCustomerTypePrice(selectedBatch, product, currentCustomer
                .customer_type);

            if (priceResult.hasError) {
                toastr.error(
                    `This batch has no valid price configured for ${currentCustomer.customer_type} customers.`,
                    'Pricing Error');
                return;
            }

            const customerPrice = priceResult.price;
            const batchMrp = selectedBatch.max_retail_price !== undefined && selectedBatch
                .max_retail_price !== null ?
                parseFloat(selectedBatch.max_retail_price) : (product.max_retail_price || 0);

            const productWithBatchPrices = {
                ...product,
                retail_price: customerPrice,
                max_retail_price: batchMrp
            };

            // Check if this is an IMEI product
            if (product.is_imei_or_serial_no === 1) {
                // Close batch modal first
                if (isModalOpen) {
                    modal.hide();
                    isModalOpen = false;
                }

                // Show IMEI selection modal for the selected batch
                setTimeout(() => {
                    showImeiSelectionModal(product, stockEntry, [], '', 'BATCH_SELECTED',
                        selectedBatch.id);
                }, 300);
            } else {
                // Add non-IMEI product to billing with quantity 1

                window.addProductToBillingBody(
                    productWithBatchPrices,
                    stockEntry,
                    customerPrice,
                    selectedBatch.id,
                    qty,
                    currentCustomer.customer_type,
                    1, // Quantity is 1 when selecting from modal
                    [],
                    null,
                    null,
                    selectedBatch
                );

                if (isModalOpen) {
                    modal.hide();
                    isModalOpen = false;
                }
            }
        }
    }

    tbody.addEventListener('click', handleBatchSelect);

    // Keyboard navigation
    const handleKeyDown = function(event) {
        const key = event.key;
        if (!/^[1-9]$/.test(key)) return;

        const selectedIndex = parseInt(key, 10) - 1;
        if (batchRows[selectedIndex]) {
            const selectBtn = batchRows[selectedIndex].querySelector('.select-batch-btn');
            if (selectBtn) {
                selectBtn.click();
                if (isModalOpen) {
                    modal.hide();
                    isModalOpen = false;
                }
            }
        }
    };

    // Modal lifecycle
    const shownHandler = () => {
        document.addEventListener('keydown', handleKeyDown);
        isModalOpen = true;
    };

    const hiddenHandler = () => {
        document.removeEventListener('keydown', handleKeyDown);
        isModalOpen = false;
        activeModalProductId = null;
        tbody.removeEventListener('click', handleBatchSelect);
        modalElement.removeEventListener('shown.bs.modal', shownHandler);
        modalElement.removeEventListener('hidden.bs.modal', hiddenHandler);
    };

    modalElement.addEventListener('shown.bs.modal', shownHandler, {
        once: true
    });
    modalElement.addEventListener('hidden.bs.modal', hiddenHandler, {
        once: true
    });

    modal.show();
}

// ---- showImeiSelectionModal ----
function showImeiSelectionModal(product, stockEntry, imeis, searchTerm = '', matchType = '',
    selectedBatchId = null) {
    currentImeiProduct = product;
    currentImeiStockEntry = stockEntry;

    const isEditing = window.isEditing;
    const currentEditingSaleId = window.currentEditingSaleId;
    const selectedLocationId = window.selectedLocationId;
    const stockDataArr = window.stockData || [];

    // Force refresh stock data for IMEI products to get latest status
    if (!isEditing) {
        fetch(`/api/products/stocks/autocomplete?search=${encodeURIComponent(product.product_name)}&location_id=${selectedLocationId}`, {
                cache: 'no-store', // ✅ Prevent browser caching - always fetch fresh IMEI data
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                timeout: 15000 // 15 second timeout
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 429) {
                        const retryAfter = parseInt(response.headers.get('Retry-After') || '2', 10);
                        console.warn(`IMEI data refresh rate limited. Retry after ${retryAfter} seconds`);
                        throw new Error(`Rate limited. Please wait ${retryAfter} seconds.`);
                    }
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 200 && Array.isArray(data.data) && data.data.length > 0) {
                    // Find the current product in the response
                    const updatedStockEntry = data.data.find(item => item.product && item.product.id === product.id);
                    if (updatedStockEntry) {
                        // Update the global stockData
                        const stockIndex = stockDataArr.findIndex(stock => stock.product.id === product.id);
                        if (stockIndex !== -1) {
                            stockDataArr[stockIndex] = updatedStockEntry;
                        }
                        // Use the updated stock entry
                        continueWithImeiModal(product, updatedStockEntry, searchTerm, matchType, selectedBatchId);
                    } else {
                        continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
                    }
                } else {
                    continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
                }
            })
            .catch(error => {
                console.error('Error refreshing stock data:', error);
                if (error.message.includes('Rate limited')) {
                    toastr.warning(error.message, 'Rate Limited');
                }
                // Continue with original data on any error
                continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
            });
    } else {
        continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
    }
}

// ---- continueWithImeiModal ----
function continueWithImeiModal(product, stockEntry, searchTerm = '', matchType = '', selectedBatchId =
    null) {
    const isEditing = window.isEditing;
    const currentEditingSaleId = window.currentEditingSaleId;
    const selectedLocationId = window.selectedLocationId;

    // Collect already selected IMEIs in billing
    selectedImeisInBilling = [];
    const billingBody = document.getElementById('billing-body');
    const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row => {
        return row.querySelector('.product-id')?.textContent == product.id;
    });
    existingRows.forEach(row => {
        const imeiData = row.querySelector('.imei-data')?.textContent.trim();
        if (imeiData) {
            // Split comma-separated IMEI values and add to selected list
            const rowImeis = imeiData.split(',').filter(Boolean);
            selectedImeisInBilling.push(...rowImeis);
        }
    });

    // Function to process and display IMEI data
    const processImeiData = (allRelevantImeis) => {

        // *** BATCH-SPECIFIC FILTERING FOR IMEI PRODUCTS ***
        let filteredImeis = allRelevantImeis;

        // If selectedBatchId is provided, filter IMEIs to only show those belonging to that batch
        if (selectedBatchId && selectedBatchId !== "all") {
            filteredImeis = allRelevantImeis.filter(imei => {
                // Check if IMEI belongs to the selected batch
                const belongsToBatch = String(imei.batch_id) === String(selectedBatchId);
                return belongsToBatch;
            });

            if (filteredImeis.length === 0) {
                toastr.warning(
                    `No IMEIs found for the selected batch. Showing all available IMEIs for this product.`
                );
                filteredImeis = allRelevantImeis;
            }
        } else if (selectedBatchId === "all") {
            // Keep all IMEIs when "all" is selected
        } else {
        }

        // Ensure batches is always an array using helper function
        const batchesArray = normalizeBatches(stockEntry);

        // Find the batch for quantity calculation
        let selectedBatch = null;
        if (selectedBatchId && selectedBatchId !== "all") {
            selectedBatch = batchesArray.find(b => String(b.id) === String(selectedBatchId));
        } else {
            // Use the first available batch or find one with location batches
            selectedBatch = batchesArray.find(b =>
                b.location_batches && b.location_batches.some(lb => lb.location_id ==
                    selectedLocationId)
            );
        }

        const batchQty = selectedBatch ? selectedBatch.total_batch_quantity || 0 : 0;
        let missingImeiCount = Math.max(0, batchQty - filteredImeis.length);

        const tbody = document.getElementById('imei-table-body');
        if (!tbody) {
            toastr.error("IMEI table body not found");
            return;
        }
        tbody.innerHTML = '';
        const imeiRows = [];

        // Populate filtered IMEIs only
        filteredImeis.forEach((imei, index) => {
            const isChecked = selectedImeisInBilling.includes(imei.imei_number);

            // Check if this IMEI matches the search term (for auto-selection)
            const isSearchedImei = matchType === 'IMEI' && searchTerm &&
                imei.imei_number.toLowerCase() === searchTerm.toLowerCase();

            const row = document.createElement('tr');
            row.dataset.imei = imei.imei_number;
            row.dataset.imeiId = imei.id; // <-- Store primary key for edit
            row.dataset.batchId = imei.batch_id; // Store batch ID for reference

            // Add special styling for searched IMEI
            if (isSearchedImei) {
                row.style.backgroundColor = '#e8f4f8';
                row.style.border = '2px solid #17a2b8';
            }

            // Add batch information to the display
            const batchInfo = selectedBatchId && selectedBatchId !== "all" ?
                ` (Batch: ${imei.batch_id})` :
                (filteredImeis.length < allRelevantImeis.length ?
                    ` (Batch: ${imei.batch_id})` : '');

            row.innerHTML = `
                <td>${index + 1}</td>
                <td><input type="checkbox" class="imei-checkbox" value="${imei.imei_number}" ${isChecked || isSearchedImei ? 'checked' : ''} data-status="${imei.status}" /></td>
                <td class="imei-display">${imei.imei_number}${isSearchedImei ? ' 🔍' : ''}${batchInfo}</td>
                <td><span class="badge ${imei.status === 'available' ? 'bg-success' : 'bg-danger'}">${imei.status}</span></td>
                <td>
                    ${(typeof window.userPermissions !== 'undefined' && window.userPermissions.canEditProduct) ? `<button class="btn btn-sm btn-warning edit-imei-btn">Edit</button>` : ''}
                    ${(typeof window.userPermissions !== 'undefined' && window.userPermissions.canDeleteProduct) ? `<button class="btn btn-sm btn-danger remove-imei-btn">Remove</button>` : ''}
                </td>
            `;
            row.classList.add('clickable-row');
            row.addEventListener('click', function(event) {
                if (event.target.type !== 'checkbox') {
                    const checkbox = row.querySelector('.imei-checkbox');
                    checkbox.checked = !checkbox.checked;
                }
            });
            tbody.appendChild(row);
            imeiRows.push(row);
        });

        // Add initial manual IMEI row if needed
        if (missingImeiCount > 0) {
            addNewImeiRow(missingImeiCount, tbody, imeiRows);
        }

        // Show modal
        const modalElement = document.getElementById('imeiModal');
        if (!modalElement) {
            toastr.error("IMEI modal not found");
            return;
        }

        // Update modal title to indicate batch filtering if applicable
        const modalTitle = modalElement.querySelector('.modal-title');
        if (modalTitle) {
            let titleText = `Select IMEI for ${product.product_name}`;
            if (selectedBatchId && selectedBatchId !== "all") {
                titleText += ` (Batch: ${selectedBatchId})`;
            }
            modalTitle.textContent = titleText;
        }

        const modal = new bootstrap.Modal(modalElement);

        // Add event listener for modal cleanup
        modalElement.addEventListener('hidden.bs.modal', function modalCleanup() {
            // Ensure backdrop is removed and body styles are reset
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);

            // Remove the event listener to prevent memory leaks
            modalElement.removeEventListener('hidden.bs.modal', modalCleanup);
        });

        modal.show();

        setupSearchAndFilter(tbody, imeiRows, searchTerm, matchType);
        setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows);
        setupAddButtonContainer(missingImeiCount, tbody, imeiRows);
        attachEditRemoveHandlers();
    };

    // If we're in edit mode, fetch the current sale's IMEI data and merge with available IMEIs
    if (isEditing && currentEditingSaleId) {
        fetch(`/sales/edit/${currentEditingSaleId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    // Get the current sale's IMEI numbers for this product
                    const saleProducts = data.sale_details.sale_products.filter(sp => sp
                        .product_id == product.id);
                    let currentSaleImeis = [];

                    saleProducts.forEach(sp => {
                        if (sp.imei_numbers && sp.imei_numbers.length > 0) {
                            sp.imei_numbers.forEach(imeiNumber => {
                                // Create IMEI object that matches the format from autocomplete
                                currentSaleImeis.push({
                                    id: sp
                                        .id, // Use sale product ID as placeholder
                                    imei_number: imeiNumber,
                                    location_id: sp.location_id,
                                    batch_id: sp.batch_id,
                                    status: 'sold' // These are currently sold IMEIs
                                });
                            });
                        }
                    });

                    // Merge available IMEIs from stock with current sale IMEIs
                    let allRelevantImeis = [];

                    if (stockEntry.imei_numbers) {
                        // Add available IMEIs
                        const availableImeis = stockEntry.imei_numbers.filter(imei =>
                            imei.status === "available" && imei.location_id ==
                            selectedLocationId
                        );
                        allRelevantImeis.push(...availableImeis);
                    }

                    // Add current sale IMEIs (these will show as sold but selectable)
                    allRelevantImeis.push(...currentSaleImeis);

                    // Remove duplicates based on IMEI number
                    allRelevantImeis = allRelevantImeis.filter((imei, index, self) =>
                        index === self.findIndex(i => i.imei_number === imei.imei_number)
                    );

                    processImeiData(allRelevantImeis);
                } else {
                    console.error('Failed to fetch sale data for IMEI editing');
                    // Fallback to available IMEIs only
                    processImeiDataFallback();
                }
            })
            .catch(error => {
                console.error('Error fetching sale IMEI data:', error);
                // Fallback to available IMEIs only
                processImeiDataFallback();
            });
    } else {
        // Not in edit mode, use available IMEIs only
        processImeiDataFallback();
    }

    // Fallback function for non-edit mode
    function processImeiDataFallback() {
        let allRelevantImeis = [];

        if (stockEntry.imei_numbers) {
            // Add available IMEIs
            const availableImeis = stockEntry.imei_numbers.filter(imei => {
                return imei.status === "available" && imei.location_id == selectedLocationId;
            });
            allRelevantImeis.push(...availableImeis);
        }

        processImeiData(allRelevantImeis);
    }
}

// --- Helper Functions ---

// ---- setupSearchAndFilter ----
function setupSearchAndFilter(tbody, imeiRows, searchTerm = '', matchType = '') {
    const searchInput = document.getElementById('imeiSearch');
    const filterSelect = document.getElementById('checkboxFilter');

    // Pre-populate search field if we searched by IMEI
    if (matchType === 'IMEI' && searchTerm && searchInput) {
        searchInput.value = searchTerm;
        searchInput.focus();

        // Add visual indication that this was auto-filled
        searchInput.style.backgroundColor = '#e8f4f8';
        setTimeout(() => {
            searchInput.style.backgroundColor = '';
        }, 2000);
    }

    function applyFilters() {
        const searchTerm = (searchInput?.value || '').toLowerCase();
        const filterType = filterSelect?.value || 'all';

        imeiRows.forEach(row => {
            const isManual = !row.dataset.imei;
            const imeiNumber = isManual ?
                (row.querySelector('.new-imei-input')?.value || '').toLowerCase() :
                row.dataset.imei.toLowerCase();

            const checkbox = row.querySelector('.imei-checkbox');
            const isChecked = checkbox?.checked || false;

            let matchesSearch = imeiNumber.includes(searchTerm);
            let matchesFilter = true;

            if (filterType === 'checked') {
                matchesFilter = isChecked;
            } else if (filterType === 'unchecked') {
                matchesFilter = !isChecked;
            }

            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyFilters);
    filterSelect?.addEventListener('change', applyFilters);
}

// ---- setupConfirmHandler ----
function setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows) {
    const selectedLocationId = window.selectedLocationId;

    document.getElementById('confirmImeiSelection').onclick = function() {
        const checkboxes = document.querySelectorAll('.imei-checkbox:not(.manual-checkbox)');
        const manualInputs = document.querySelectorAll('.new-imei-input');

        const selectedImeis = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
        const newImeis = Array.from(manualInputs).map(input => input.value.trim()).filter(val =>
            val);

        const allImeis = [...selectedImeis, ...newImeis];
        const uniqueImeis = [...new Set(allImeis)];

        highlightDuplicates(allImeis, tbody);

        if (allImeis.length !== uniqueImeis.length) {
            toastr.error("Duplicate IMEI found. Please enter unique IMEIs.");
            return;
        }

        if (uniqueImeis.length === 0) {
            toastr.warning("Please select or enter at least one IMEI.");
            return;
        }

        // Properly hide modal and ensure backdrop is removed
        try {
            modal.hide();
            // Remove any remaining backdrop manually as a fallback
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                // Ensure body overflow is restored
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);
        } catch (error) {
            console.error('Error hiding modal:', error);
            // Force cleanup if modal.hide() fails
            const modalElement = document.getElementById('imeiModal');
            if (modalElement) {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.removeAttribute('aria-modal');
                modalElement.removeAttribute('role');
            }
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }

        // Check if we have stored batch information from the product modal
        let batchId, price, currentCustomer;

        if (window.modalSelectedBatch) {
            // Use the stored batch information from the product modal
            batchId = window.modalSelectedBatch.batchId;
            price = window.modalSelectedBatch.price;
            currentCustomer = window.getCurrentCustomer();

            // Clear the stored information
            delete window.modalSelectedBatch;
        } else {
            // Use the default logic - set to "all" for FIFO method when no specific batch
            batchId = selectedBatch ? selectedBatch.id : "all";
            currentCustomer = window.getCurrentCustomer();
            const priceResult = window.getCustomerTypePrice(selectedBatch, product, currentCustomer
                .customer_type);

            if (priceResult.hasError) {
                toastr.error(
                    `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                    'Pricing Error');
                return;
            }
            price = priceResult.price;
        }

        const imeiLocationId = selectedBatch?.location_batches?.[0]?.location_id ??
            selectedLocationId;

        if (newImeis.length > 0) {
            // Use intelligent batch selection - just send product_id, location_id, and imeis
            fetch('/save-or-update-imei', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .content
                    },
                    body: JSON.stringify({
                        product_id: product.id,
                        location_id: imeiLocationId,
                        imeis: newImeis
                    })
                }).then(response => response.json())
                .then(data => {
                    if (data.status === 200) {
                        const message = data.message ||
                            `${newImeis.length} IMEI(s) added successfully with intelligent batch selection.`;
                        toastr.success(message);
                        updateBilling(uniqueImeis, product, stockEntry, price, batchId);
                    } else {
                        toastr.error(data.message || "Failed to save new IMEIs");
                    }
                    // Final cleanup to ensure modal is completely closed
                    window.cleanupModalBackdrop();
                })
                .catch(err => {
                    console.error(err);
                    toastr.error("Error saving new IMEIs");
                    // Final cleanup to ensure modal is completely closed
                    window.cleanupModalBackdrop();
                });
        } else {
            updateBilling(uniqueImeis, product, stockEntry, price, batchId);
            // Final cleanup to ensure modal is completely closed
            window.cleanupModalBackdrop();
        }
    };
}

// ---- highlightDuplicates ----
function highlightDuplicates(imeis, tbody) {
    const counts = {};
    imeis.forEach(imei => counts[imei] = (counts[imei] || 0) + 1);

    tbody.querySelectorAll('tr').forEach(row => {
        const isManual = !row.dataset.imei;
        const imei = isManual ? row.querySelector('.new-imei-input')?.value.trim() : row.dataset
            .imei;

        if (counts[imei] > 1) {
            row.style.backgroundColor = "#fff3cd"; // Light Yellow
        } else {
            row.style.backgroundColor = ""; // Reset
        }
    });
}

// ---- updateBilling ----
function updateBilling(imeis, product, stockEntry, price, batchId) {
    const existingRows = Array.from(document.querySelectorAll('#billing-body tr'))
        .filter(row => row.querySelector('.product-id')?.textContent == product.id);

    existingRows.forEach(row => row.remove());

    // Get current customer and determine appropriate price
    const currentCustomer = window.getCurrentCustomer();

    // Ensure batches is always an array using helper function
    const batchesArray = normalizeBatches(stockEntry);
    const selectedBatch = batchesArray.find(b => b.id === parseInt(batchId));

    // Get customer-type-based price
    const priceResult = window.getCustomerTypePrice(selectedBatch, product, currentCustomer.customer_type);

    if (priceResult.hasError) {
        toastr.error(
            `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
            'Pricing Error');
        return;
    }

    // *** FIX: Create separate billing row for each IMEI ***
    // Each IMEI = One row with quantity = 1 (no grouping by batch)

    imeis.forEach(imeiNumber => {
        // Find the IMEI object in stockEntry to get its batch_id
        const imeiObj = stockEntry.imei_numbers?.find(imei => imei.imei_number === imeiNumber);
        const imeiBatchId = imeiObj ? imeiObj.batch_id : batchId;

        // Find the appropriate batch for pricing
        const batchForPricing = batchesArray.find(b => b.id === parseInt(imeiBatchId)) ||
            batchesArray.find(b => b.id === parseInt(batchId)) ||
            batchesArray[0]; // Fallback to first batch

        // Get customer-type-based price for this specific batch
        const batchPriceResult = window.getCustomerTypePrice(batchForPricing, product, currentCustomer
            .customer_type);

        let finalPrice;
        if (batchPriceResult.hasError) {
            console.warn(`Price error for batch ${imeiBatchId}, using stored price`);
            finalPrice = price; // Use the price passed from the modal
        } else {
            finalPrice = batchPriceResult.price;
        }

        // Add individual billing row for this single IMEI with quantity = 1
        window.addProductToBillingBody(
            product,
            stockEntry,
            finalPrice,
            imeiBatchId,
            1, // batchQuantity = 1 for individual IMEI
            currentCustomer.customer_type,
            1, // saleQuantity = 1 for individual IMEI
            [imeiNumber], // Array with single IMEI
            null, // discountType
            null, // discountAmount
            batchForPricing // selectedBatch
        );
    });

    // Reset lastAddedProduct to allow adding more products
    window.lastAddedProduct = null;

    window.updateTotals();
    window.fetchPaginatedProducts(true);
}

// ---- addNewImeiRow ----
function addNewImeiRow(count, tbody, imeiRows) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${tbody.querySelectorAll('tr').length + 1}</td>
            <td><input type="checkbox" class="imei-checkbox manual-checkbox" disabled /></td>
            <td>
                <div class="input-group">
                    <input type="text" class="form-control new-imei-input" placeholder="Enter IMEI" maxlength="15" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
                    <button type="button" class="btn btn-danger btn-sm remove-imei-row">&times;</button>
                </div>
            </td>
            <td><span class="badge bg-secondary">Manual</span></td>
            <td></td>
        `;

    const removeBtn = row.querySelector('.remove-imei-row');
    removeBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        row.remove();
        count++;
        toggleAddButton(count);
    });

    const input = row.querySelector('.new-imei-input');
    const checkbox = row.querySelector('.imei-checkbox');

    input.addEventListener('input', () => {
        checkbox.checked = input.value.trim() !== "";
    });

    tbody.appendChild(row);
    imeiRows.push(row);
    input.focus();
    count--;
    toggleAddButton(count);
}

// ---- setupAddButtonContainer ----
function setupAddButtonContainer(count) {
    const container = document.getElementById('add-button-container') || (() => {
        const el = document.createElement('div');
        el.id = 'add-button-container';
        document.getElementById('imeiModalFooter').appendChild(el);
        return el;
    })();

    toggleAddButton(count);
}

// ---- toggleAddButton ----
function toggleAddButton(count) {
    const container = document.getElementById('add-button-container');
    if (!container) return;

    if (count > 0) {
        container.innerHTML =
            `<button id="add-new-imei-btn" class="btn btn-sm btn-primary mt-2">+ Add New IMEI</button>`;
        document.getElementById('add-new-imei-btn').addEventListener('click', () => {
            addNewImeiRow(count, document.getElementById('imei-table-body'), []);
        });
    } else {
        container.innerHTML = '';
    }
}

// ---- attachEditRemoveHandlers ----
function attachEditRemoveHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-imei-btn')) handleEditImei(e);
        if (e.target.classList.contains('remove-imei-btn')) handleDeleteImei(e);
    });
}

// ---- handleEditImei ----
function handleEditImei(e) {
    const row = e.target.closest('tr');
    const displayCell = row.querySelector('.imei-display');
    const originalImei = displayCell.textContent.trim();
    const imeiId = row.dataset.imeiId;

    if (!imeiId) return toastr.error("IMEI ID not found. Can't update.");

    displayCell.innerHTML =
        `<input type="text" class="form-control edit-imei-input" value="${originalImei}" />`;
    e.target.textContent = "Update";
    e.target.classList.replace("btn-warning", "btn-success");

    e.target.onclick = function() {
        const newImei = row.querySelector('.edit-imei-input').value.trim();
        if (!newImei) return toastr.error("IMEI cannot be empty.");

        fetch('/update-imei', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: imeiId,
                    new_imei: newImei
                })
            }).then(res => res.json())
            .then(data => {
                if (data.status === 200) {
                    displayCell.textContent = newImei;
                    row.dataset.imei = newImei;
                    row.querySelector('.imei-checkbox').value = newImei;
                    e.target.textContent = "Edit";
                    e.target.classList.replace("btn-success", "btn-warning");
                    toastr.success("IMEI updated successfully!");
                } else {
                    toastr.error(data.message || "Failed to update IMEI");
                }
            }).catch(() => toastr.error("Network error updating IMEI"));
    };
}

// ---- handleDeleteImei ----
function handleDeleteImei(e) {
    const row = e.target.closest('tr');
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    document.getElementById('confirmDeleteBtn').onclick = function() {
        const imeiId = row.dataset.imeiId;
        fetch('/delete-imei', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: imeiId
                })
            }).then(res => res.json())
            .then(data => {
                if (data.status === 200) {
                    row.remove();
                    toastr.success("IMEI deleted successfully!");
                    window.fetchPaginatedProducts(true);
                } else {
                    toastr.error(data.message || "Failed to delete IMEI");
                }
            }).catch(() => toastr.error("Network error deleting IMEI"));

        modal.hide();
    };
    modal.show();
}

// ---- showProductModal ----
function showProductModal(product, stockEntry, row) {
    const modalBody = document.getElementById('productModalBody');
    const basePrice = product.retail_price;
    const discountAmount = product.discount_amount || 0;
    const finalPrice = product.discount_type === 'percentage' ?
        basePrice * (1 - (discountAmount || 0) / 100) :
        basePrice - (discountAmount || 0);

    // Store product and stock entry for IMEI handling
    currentImeiProduct = product;
    currentImeiStockEntry = stockEntry;

    // Get current customer for default selection
    const currentCustomer = window.getCurrentCustomer();

    const allowedPriceTypes  = window.PosConfig.permissions.allowedPriceTypes;
    const selectedLocationId = window.selectedLocationId;

    let batchOptions = '';
    let locationBatches = [];

    // Normalize batches to array using helper function
    const batchesArray = normalizeBatches(stockEntry);

    // ✅ FIX: Get current row's quantities to add back when editing
    let currentRowPaidQty = 0;
    let currentRowFreeQty = 0;
    let currentRowBatchId = null;

    if (row) {
        // Get quantities from the current row being edited
        const qtyInput = row.querySelector('input[name="quantity[]"]');
        const freeQtyInput = row.querySelector('input[name="free_quantity[]"]');
        const batchIdCell = row.querySelector('.batch-id');

        currentRowPaidQty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
        currentRowFreeQty = freeQtyInput ? parseFloat(freeQtyInput.value) || 0 : 0;
        currentRowBatchId = batchIdCell ? batchIdCell.textContent.trim() : null;

    }

    // Only show batches for the selected location
    locationBatches = batchesArray
        .filter(batch =>
            Array.isArray(batch.location_batches) &&
            batch.location_batches.some(lb => String(lb.location_id) == String(selectedLocationId))
        )
        .map(batch => {
            // Find the location batch for the selected location
            const locationBatch = batch.location_batches.find(lb => String(lb.location_id) ==
                String(selectedLocationId));

            let batchQuantity = locationBatch ? parseFloat(locationBatch.quantity) : 0;
            let batchFreeQuantity = locationBatch ? parseFloat(locationBatch.free_quantity || 0) : 0;

            // ✅ FIX: Add back quantities ONLY if editing a SPECIFIC batch (not 'all')
            if (row && currentRowBatchId && currentRowBatchId !== 'all' && String(batch.id) === String(currentRowBatchId)) {
                batchQuantity += currentRowPaidQty;
                batchFreeQuantity += currentRowFreeQty;
            }

            return {
                batch_id: batch.id,
                batch_no: batch.batch_no,
                retail_price: parseFloat(batch.retail_price),
                wholesale_price: parseFloat(batch.wholesale_price),
                special_price: parseFloat(batch.special_price),
                max_retail_price: parseFloat(batch.max_retail_price) || parseFloat(product
                    .max_retail_price),
                batch_quantity: batchQuantity,
                batch_free_quantity: batchFreeQuantity,
                created_at: batch.created_at || null // If available
            };
        })
        .filter(batch => (batch.batch_quantity + batch.batch_free_quantity) > 0);

    // Calculate total quantity for all batches in the selected location
    // ✅ FIX: Include free stock (paid + free)
    let totalQuantity = 0;
    if (batchesArray.length > 0) {
        totalQuantity = batchesArray.reduce((sum, batch) => {
            if (Array.isArray(batch.location_batches)) {
                return sum + batch.location_batches
                    .filter(lb => String(lb.location_id) == String(selectedLocationId))
                    .reduce((s, lb) => s + (parseFloat(lb.quantity) || 0) + (parseFloat(lb.free_quantity) || 0), 0);
            }
            return sum;
        }, 0);

        // ✅ FIX: Add back total quantities from current row if editing with 'all' batches
        if (row && currentRowBatchId === 'all') {
            const totalRowQty = currentRowPaidQty + currentRowFreeQty;
            totalQuantity += totalRowQty;
        }
    }

    // Find latest batch by created_at or by highest batch_id
    let latestBatch = null;
    if (locationBatches.length > 0) {
        latestBatch = locationBatches.reduce((latest, current) => {
            if (current.created_at && latest.created_at) {
                return new Date(current.created_at) > new Date(latest.created_at) ?
                    current : latest;
            }
            // fallback: use batch_id as number
            return (parseInt(current.batch_id) > parseInt(latest.batch_id)) ? current :
                latest;
        }, locationBatches[0]);
    }

    // Determine which price types are available (non-zero)
    let hasWholesale = false;
    let hasSpecial = false;
    let hasRetail = true; // Always show retail

    if (locationBatches.length > 0) {
        hasWholesale = locationBatches.some(batch => batch.wholesale_price > 0);
        hasSpecial = locationBatches.some(batch => batch.special_price > 0);
    }

    // Check for previously selected price type from the row, otherwise default based on customer type
    let defaultPriceType = 'retail';

    // First, check if this row has a previously selected price type
    const storedPriceType = row.querySelector('.selected-price-type');
    if (storedPriceType && storedPriceType.textContent) {
        const savedPriceType = storedPriceType.textContent.trim();
        // Validate that the saved price type is available for this product
        if (savedPriceType === 'retail' ||
            (savedPriceType === 'wholesale' && hasWholesale) ||
            (savedPriceType === 'special' && hasSpecial)) {
            defaultPriceType = savedPriceType;
        } else {
        }
    } else if (currentCustomer.customer_type === 'wholesaler' && hasWholesale) {
        // Fall back to customer type default if no saved selection
        defaultPriceType = 'wholesale';
    }

    // Check for previously selected batch BEFORE building options
    let selectedBatchId = 'all'; // default to "All"
    const storedBatchId = row.querySelector('.batch-id');
    if (storedBatchId && storedBatchId.textContent && storedBatchId.textContent.trim() !== '') {
        selectedBatchId = storedBatchId.textContent.trim();
    }

    const formatAmountWithSeparators = window.formatAmountWithSeparators;

    if (locationBatches.length > 0) {
        // Build batch options - only show prices user has permission to see
        batchOptions = locationBatches.map((batch, idx) => {
            let priceDisplay = '';
            let priceComponents = [];

            // Only show prices the user has permission for
            if (allowedPriceTypes.includes('retail')) {
                priceComponents.push(`R: ${formatAmountWithSeparators(batch.retail_price.toFixed(2))}`);
            }

            if (allowedPriceTypes.includes('wholesale') && batch.wholesale_price > 0) {
                priceComponents.push(`W: ${formatAmountWithSeparators(batch.wholesale_price.toFixed(2))}`);
            }

            if (allowedPriceTypes.includes('special') && batch.special_price > 0) {
                priceComponents.push(`S: ${formatAmountWithSeparators(batch.special_price.toFixed(2))}`);
            }

            if (allowedPriceTypes.includes('max_retail')) {
                priceComponents.push(`MRP: ${formatAmountWithSeparators(batch.max_retail_price.toFixed(2))}`);
            }

            priceDisplay = priceComponents.join(' | ');

            // Calculate paid and free quantities
            const paidQty = batch.batch_quantity || 0;
            const freeQty = batch.batch_free_quantity || 0;
            // Only show free quantity if it's greater than 0
            const qtyDisplay = freeQty > 0
                ? `Paid: ${formatAmountWithSeparators(paidQty)} | Free: ${formatAmountWithSeparators(freeQty)}`
                : `${formatAmountWithSeparators(paidQty)}`;

            return `
                <option value="${batch.batch_id}"
                data-retail-price="${batch.retail_price}"
                data-wholesale-price="${batch.wholesale_price}"
                data-special-price="${batch.special_price}"
                data-max-retail-price="${batch.max_retail_price}"
                data-quantity="${batch.batch_quantity}" ${selectedBatchId === batch.batch_id ? 'selected' : ''}>
                ${batch.batch_no} - ${qtyDisplay} - ${priceDisplay}
                </option>
            `;
        }).join('');

        // Build price type radio buttons (only show available options AND user has permission)
        let priceTypeButtons = '';

        // Determine which price types to show based on BOTH availability AND permissions
        // IMPORTANT: If customer is wholesaler, always show wholesale even if user doesn't have permission
        // This ensures correct pricing for customer type
        const currentCust = window.getCurrentCustomer();
        const isWholesalerCustomer = currentCust && currentCust.customer_type === 'wholesaler';

        // Show retail if user has permission OR if it's the only available option
        if (allowedPriceTypes.includes('retail')) {
            const isRetailSelected = defaultPriceType === 'retail';
            priceTypeButtons += `
                <label class="btn ${isRetailSelected ? 'btn-success' : 'btn-outline-success'} price-type-btn ${isRetailSelected ? 'active' : ''}" style="flex: 1; min-width: 70px; margin: 2px;">
                    <input type="radio" name="modal-price-type" value="retail" ${isRetailSelected ? 'checked' : ''} hidden>
                    <i class="fas fa-tag d-block d-sm-inline me-sm-1"></i>
                    <span class="fw-bold d-none d-sm-inline">Retail</span>
                    <span class="fw-bold d-inline d-sm-none small">R</span>
                </label>
            `;
        }

        // Show wholesale if: (available AND user has permission) OR customer is wholesaler
        if (hasWholesale && (allowedPriceTypes.includes('wholesale') || isWholesalerCustomer)) {
            const isWholesaleSelected = defaultPriceType === 'wholesale';
            const isAutoSelected = isWholesalerCustomer && !allowedPriceTypes.includes('wholesale');
            priceTypeButtons += `
                <label class="btn ${isWholesaleSelected ? 'btn-info' : 'btn-outline-info'} price-type-btn ${isWholesaleSelected ? 'active' : ''}" style="flex: 1; min-width: 70px; margin: 2px;" ${isAutoSelected ? 'title="Auto-selected for wholesaler customer"' : ''}>
                    <input type="radio" name="modal-price-type" value="wholesale" ${isWholesaleSelected ? 'checked' : ''} hidden>
                    <i class="fas fa-boxes d-block d-sm-inline me-sm-1"></i>
                    <span class="fw-bold d-none d-sm-inline">Wholesale</span>
                    <span class="fw-bold d-inline d-sm-none small">W</span>
                    ${isAutoSelected ? '<i class="fas fa-lock ms-1 small"></i>' : ''}
                </label>
            `;
        }

        // Show special if available AND user has permission
        if (hasSpecial && allowedPriceTypes.includes('special')) {
            const isSpecialSelected = defaultPriceType === 'special';
            priceTypeButtons += `
                <label class="btn ${isSpecialSelected ? 'btn-warning' : 'btn-outline-warning'} price-type-btn ${isSpecialSelected ? 'active' : ''}" style="flex: 1; min-width: 70px; margin: 2px;">
                    <input type="radio" name="modal-price-type" value="special" ${isSpecialSelected ? 'checked' : ''} hidden>
                    <i class="fas fa-star d-block d-sm-inline me-sm-1"></i>
                    <span class="fw-bold d-none d-sm-inline">Special</span>
                    <span class="fw-bold d-inline d-sm-none small">S</span>
                </label>
            `;
        }

        // Calculate default prices for "All" option
        let allRetailPrice = latestBatch ? latestBatch.retail_price : finalPrice;
        let allWholesalePrice = latestBatch ? latestBatch.wholesale_price : 0;
        let allSpecialPrice = latestBatch ? latestBatch.special_price : 0;
        let allMrpPrice = latestBatch ? latestBatch.max_retail_price : parseFloat(product
            .max_retail_price);

        modalBody.innerHTML = `
            <div class="d-flex align-items-center mb-3">
                <img src="${window.getSafeImageUrl(product)}"
                     style="width:50px; height:50px; margin-right:15px; border-radius:8px; object-fit:cover;"
                     alt="${product.product_name}"
                     onerror="this.onerror=null; this.src='/assets/images/No Product Image Available.png'; " />
                <div>
                    <div class="fw-bold fs-5">${product.product_name}</div>
                    <div class="text-muted">${product.sku}</div>
                    ${product.description ? `<div class="text-muted small">${product.description}</div>` : ''}
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-muted small">PRICE TYPE</label>
                <div class="d-flex flex-wrap" style="gap: 4px;">
                    ${priceTypeButtons}
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-muted small">BATCH SELECTION</label>
                <select id="modalBatchDropdown" class="form-select batch-dropdown">
                    <option value="all"
                        data-retail-price="${allRetailPrice}"
                        data-wholesale-price="${allWholesalePrice}"
                        data-special-price="${allSpecialPrice}"
                        data-max-retail-price="${allMrpPrice}"
                        data-quantity="${totalQuantity}" ${selectedBatchId === 'all' ? 'selected' : ''}>
                        All - Qty: ${formatAmountWithSeparators(totalQuantity)}${(() => {
                            let allPrices = [];
                            if (allowedPriceTypes.includes('retail')) {
                                allPrices.push('R: ' + formatAmountWithSeparators(allRetailPrice.toFixed(2)));
                            }
                            if (allowedPriceTypes.includes('wholesale') && allWholesalePrice > 0) {
                                allPrices.push('W: ' + formatAmountWithSeparators(allWholesalePrice.toFixed(2)));
                            }
                            if (allowedPriceTypes.includes('special') && allSpecialPrice > 0) {
                                allPrices.push('S: ' + formatAmountWithSeparators(allSpecialPrice.toFixed(2)));
                            }
                            if (allowedPriceTypes.includes('max_retail')) {
                                allPrices.push('MRP: ' + formatAmountWithSeparators(allMrpPrice.toFixed(2)));
                            }
                            return allPrices.length > 0 ? ' - ' + allPrices.join(' | ') : '';
                        })()}
                    </option>
                    ${batchOptions}
                </select>
                <style>
                    .batch-dropdown {
                        font-size: 1rem;
                    }
                    @media (max-width: 576px) {
                        .batch-dropdown {
                            font-size: 0.85em;
                        }
                    }

                    /* Customer Price History Styles */
                    .customer-price-history {
                        background: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        padding: 8px 12px;
                        margin: 5px 0;
                        font-size: 0.85em;
                        animation: slideIn 0.3s ease-out;
                    }

                    .price-history-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 4px;
                    }

                    .price-history-item:last-child {
                        margin-bottom: 0;
                    }

                    .price-badge {
                        background: #ffc107;
                        color: #000;
                        padding: 2px 6px;
                        border-radius: 3px;
                        font-weight: 500;
                        font-size: 0.8em;
                    }

                    /* Hover tooltip enhancement */
                    .product-image:hover,
                    .product-name:hover {
                        opacity: 0.8;
                        transition: opacity 0.2s ease;
                        cursor: help;
                    }

                    @keyframes slideIn {
                        from {
                            opacity: 0;
                            transform: translateY(-10px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                </style>
            </div>
        `;
    } else {
        // No valid batches
        modalBody.innerHTML = `<div>No valid batches found for the product in this location.</div>`;
    }

    selectedRow = row;
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();

    const radioButtons = document.querySelectorAll('input[name="modal-price-type"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove active state from all price type buttons
            document.querySelectorAll('.price-type-btn').forEach(btn => {
                btn.classList.remove('active');
                // Reset to outline style
                if (btn.classList.contains('btn-success')) {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-success');
                } else if (btn.classList.contains('btn-info')) {
                    btn.classList.remove('btn-info');
                    btn.classList.add('btn-outline-info');
                } else if (btn.classList.contains('btn-warning')) {
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-outline-warning');
                }
            });

            // Add active state to selected button
            const selectedBtn = this.parentElement;
            selectedBtn.classList.add('active');

            // Change to solid style for active button
            if (selectedBtn.classList.contains('btn-outline-success')) {
                selectedBtn.classList.remove('btn-outline-success');
                selectedBtn.classList.add('btn-success');
            } else if (selectedBtn.classList.contains('btn-outline-info')) {
                selectedBtn.classList.remove('btn-outline-info');
                selectedBtn.classList.add('btn-info');
            } else if (selectedBtn.classList.contains('btn-outline-warning')) {
                selectedBtn.classList.remove('btn-outline-warning');
                selectedBtn.classList.add('btn-warning');
            }
        });
    });

    // Attach change handler on dropdown to update max quantity
    const batchDropdown = document.getElementById('modalBatchDropdown');
    if (batchDropdown) {
        batchDropdown.addEventListener('change', () => {
            const selectedOption = batchDropdown.selectedOptions[0];
            if (!selectedOption) return;

            const maxQty = parseFloat(selectedOption.getAttribute('data-quantity'));
            const qtyInput = selectedRow?.querySelector('.quantity-input');

            if (qtyInput) {
                qtyInput.setAttribute('max', maxQty);
                qtyInput.setAttribute('title', `Available: ${maxQty}`);
            }
        });
    }
}

// ---- getCustomerPreviousPrice ----
async function getCustomerPreviousPrice(customerId, productId) {
    if (!customerId || customerId === '' || customerId === 'walk-in') {
        return null;
    }

    // Check cache first
    const cacheKey = `${customerId}_${productId}`;
    if (window.customerPriceCache.has(cacheKey)) {
        return window.customerPriceCache.get(cacheKey);
    }

    // Fetch from API
    try {
        const response = await fetch(`/customer-previous-price?customer_id=${customerId}&product_id=${productId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) return null;

        const data = await response.json();
        const result = (data.status === 200) ? data.data : null;

        // Save to cache
        window.customerPriceCache.set(cacheKey, result);

        return result;
    } catch (error) {
        return null;
    }
}

// ---- Window exports ----
window.addProductToTable              = addProductToTable;
window.normalizeBatches               = normalizeBatches;
window.showBatchPriceSelectionModal   = showBatchPriceSelectionModal;
window.showImeiSelectionModal         = showImeiSelectionModal;
window.showProductModal               = showProductModal;
window.getCustomerPreviousPrice       = getCustomerPreviousPrice;
