/**
 * pos-sale.js — Phase 14
 * Sale processing: gather data, send sale, payments, shipping, suspended sales,
 * edit sale, and all related event wiring.
 *
 * Structure (split for clarity):
 *   Sale data: getSalesDateString, buildSaleBasePayload, validateBillingRow,
 *              buildProductPayloadFromRow, gatherSaleData
 *   Send sale: validateWalkInCheque, resolveSaleIdFromUrl, showCreditOrStockSwal,
 *              handleSaleFailureResponse, handleSaleXhrError, openPrintForSale,
 *              runAfterSaleSuccess, handleSaleSuccessResponse, sendSaleData
 *   Payment:  gatherCashPaymentData, gatherCardPaymentData, gatherChequePaymentData,
 *             gatherPaymentData, validateChequeFields
 *   Shipping: openShippingModal, updateShippingData, updateShippingButtonState,
 *             clearShippingData, getShippingDataForSale
 *   Reset:    resetToWalkingCustomer, resetForm
 *
 * External dependencies (on window):
 *   window.parseFormattedAmount()       — pos-utils.js (Phase 2)
 *   window.formatAmountWithSeparators() — pos-utils.js (Phase 2)
 *   window.formatCurrency()             — pos-utils.js (Phase 2)
 *   window.updateTotals()               — pos-cart.js (Phase 10)
 *   window.updatePaymentButtonsState()  — pos-cart.js (Phase 10)
 *   window.validateAllQuantities()      — pos-cart.js (Phase 10)
 *   window.addProductToBillingBody()    — pos_ajax bridge (Phase 13)
 *   window.fetchPaginatedProducts()     — pos-product-display.js (Phase 12)
 *   window.fetchSalesData()             — pos-sales-list.js (Phase 15)
 *   window.printReceipt()               — pos-receipt.js (Phase 16)
 *   window.navigateToPosCreate()        — pos_ajax bridge (Phase 16)
 *   window.checkSalesAccess()           — pos-salesrep-display.js (Phase 11)
 *   window.validatePaymentMethodCompatibility() — pos-salesrep-display.js (Phase 11)
 *   window.checkAndToggleSalesRepButtons()      — pos-salesrep-display.js (Phase 11)
 *   window.customerFunctions             — pos-customer.js (Phase 5)
 *   window.searchCache                   — pos-cache.js (Phase 3)
 *   window.enableButton()               — pos_ajax bridge (Phase 14)
 *   window.preventDoubleClick()          — pos_ajax bridge (Phase 14)
 *   window.getPosState()                — pos_ajax bridge (Phase 10)
 *
 * Closure variables accessed via window.*:
 *   window.isEditing, window.currentEditingSaleId, window.isEditingFinalizedSale,
 *   window.selectedLocationId, window.locationId, window.allProducts,
 *   window.stockData, window.currentProductsPage, window.hasMoreProducts,
 *   window.shippingData, window.priceType, window._editModeToastShown,
 *   window.originalSaleData, window.isSalesRep, window.userPermissions
 */
'use strict';

// Local aliases for namespaced helpers
const PosUtils   = (window.Pos && window.Pos.Utils)   || {};
const PosCart    = (window.Pos && window.Pos.Cart)    || {};
const PosBilling = (window.Pos && window.Pos.Billing) || {};

const parseFormattedAmount       = PosUtils.parseFormattedAmount;
const formatAmountWithSeparators = PosUtils.formatAmountWithSeparators;
const formatCurrency             = PosUtils.formatCurrency;

// ---- Module-local state ----
let isProcessingAmountGiven = false;

// ================================================================
//  fetchEditSale — outside $(document).ready, but needs DOM
// ================================================================
function fetchEditSale(saleId) {
    // Set editing mode to true
    window.isEditing = true;
    window.currentEditingSaleId = saleId;
    window.isEditingFinalizedSale = false; // Will be set to true if sale has invoice

    fetch(`/sales/edit/${saleId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const contentType = response.headers.get('Content-Type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid response format. Expected JSON.');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 200) {
                const saleDetails = data.sale_details;

                // *** NEW: Store original sale data for payment method validation ***
                window.originalSaleData = {
                    payment_status: saleDetails.sale.payment_status,
                    total_paid: saleDetails.sale.total_paid,
                    final_total: saleDetails.sale.final_total,
                    total_due: saleDetails.sale.total_due,
                    customer_id: saleDetails.sale.customer_id,
                    invoice_no: saleDetails.sale.invoice_no,
                    transaction_type: saleDetails.sale.transaction_type,
                    order_number: saleDetails.sale.order_number
                };

                // 🔒 Check if this is a finalized sale.
                // IMPORTANT: Drafts also have numbers like "D/2026/0003", so invoice_no alone is NOT a safe signal.
                // Treat as finalized only when status is "final".
                const saleStatus = (saleDetails.sale.status || '').toString().toLowerCase();
                window.isEditingFinalizedSale = saleStatus === 'final';

                // Update invoice number
                const saleInvoiceElement = document.getElementById('sale-invoice-no');
                if (saleInvoiceElement && saleDetails.sale) {
                    saleInvoiceElement.textContent = `Invoice No: ${saleDetails.sale.invoice_no}`;
                }

                // Clear existing billing body before setting location
                const billingBody = document.getElementById('billing-body');
                if (billingBody) {
                    billingBody.innerHTML = '';
                }

                // Set the locationId based on the sale's location_id
                if (saleDetails.sale && saleDetails.sale.location_id) {
                    window.locationId = saleDetails.sale.location_id;
                    window.selectedLocationId = saleDetails.sale.location_id;

                    // Function to set location with retry logic
                    const setLocationWithRetry = (retryCount = 0, maxRetries = 5) => {
                        const $locationSelect = $('#locationSelect');
                        const $locationSelectDesktop = $('#locationSelectDesktop');
                        const locationIdStr = saleDetails.sale.location_id.toString();

                        // Check if option exists in dropdown
                        const optionExists = $locationSelect.find(`option[value="${locationIdStr}"]`).length > 0;

                        if (optionExists) {
                            // Option exists - set the value
                            $locationSelect.val(locationIdStr);
                            $locationSelectDesktop.val(locationIdStr);

                            // Refresh Select2 visual display WITHOUT triggering the jQuery
                            // change handler (which would re-fetch products and reset the sale).
                            $locationSelect.trigger('change.select2');
                            $locationSelectDesktop.trigger('change.select2');

                            // Fetch products for the product grid display after location is set
                            if (window.selectedLocationId) {
                                window.currentProductsPage = 1;
                                window.hasMoreProducts = true;
                                window.allProducts = [];
                                const posProduct = document.getElementById('posProduct');
                                if (posProduct) posProduct.innerHTML = '';
                                window.fetchPaginatedProducts(true);
                            }

                            // Check sales rep button visibility if applicable
                            if (window.isSalesRep && window.selectedLocationId) {
                                window.checkAndToggleSalesRepButtons(window.selectedLocationId);
                            }
                        } else if (retryCount < maxRetries) {
                            // Option doesn't exist yet - retry after delay
                            setTimeout(() => setLocationWithRetry(retryCount + 1, maxRetries), 200);
                        } else {
                            // Max retries reached
                            console.error('❌ Location option not found after', maxRetries, 'attempts. ID:', locationIdStr);

                            // Show error to user
                            toastr.error('Unable to load sale location. The location may have been deleted or you may not have access.', 'Location Error');
                        }
                    };

                    // Start location setting with retry logic
                    setLocationWithRetry();
                }

                // Populate sale products
                saleDetails.sale_products.forEach(saleProduct => {
                    // *** CRITICAL FIX: Always use original sale price in edit mode ***
                    const price = parseFloat(saleProduct.price);

                    if (!price || price <= 0) {
                        console.error('Invalid original sale price for product:', saleProduct.product.product_name, 'Price:', saleProduct.price);
                        toastr.error(`Invalid price data for product: ${saleProduct.product.product_name}. Cannot load for editing.`, 'Edit Error');
                        return; // Skip this product
                    }

                    // Use the corrected total_quantity from backend as the max available stock
                    const maxAvailableStock = saleProduct.total_quantity;

                    // Create a normalized stock entry for the frontend
                    const normalizedStockEntry = {
                        batches: [{
                            id: saleProduct.batch_id,
                            batch_no: saleProduct.batch?.batch_no || 'BATCH-' +
                                saleProduct.batch_id,
                            retail_price: parseFloat(saleProduct.batch
                                ?.retail_price || saleProduct.product
                                .retail_price),
                            wholesale_price: parseFloat(saleProduct.batch
                                ?.wholesale_price || saleProduct.product
                                .whole_sale_price),
                            special_price: parseFloat(saleProduct.batch
                                ?.special_price || saleProduct.product
                                .special_price),
                            location_batches: [{
                                location_id: saleProduct.location_id,
                                quantity: maxAvailableStock
                            }]
                        }],
                        total_stock: maxAvailableStock,
                        product: {
                            ...saleProduct.product,
                            batches: saleProduct.product.batches || []
                        }
                    };

                    // Add the product to allProducts array for getProductDataById to find it
                    const existingProductIndex = window.allProducts.findIndex(p => p && p.id && p
                        .id === saleProduct.product.id);
                    if (existingProductIndex === -1 && saleProduct.product && saleProduct
                        .product.id) {
                        const productToAdd = {
                            ...saleProduct.product,
                            batches: saleProduct.product.batches || []
                        };

                        // Ensure unit structure exists for updateTotals function
                        if (!productToAdd.unit && saleProduct.unit) {
                            productToAdd.unit = saleProduct.unit;
                        }

                        window.allProducts.push(productToAdd);
                    }

                    // Add product to billing with correct stock calculation
                    try {
                        // Debug edit mode batch handling

                        let editModeBatchId = saleProduct.batch_id || "all";
                        let editBatch = null;

                        if (saleProduct.imei_numbers && saleProduct.imei_numbers.length > 0) {
                            editModeBatchId = saleProduct.batch_id;
                            editBatch = normalizedStockEntry.batches?.find(b => b.id === parseInt(saleProduct.batch_id));
                        } else {
                            editBatch = normalizedStockEntry.batches && normalizedStockEntry.batches.length > 0 ? normalizedStockEntry.batches[0] : null;
                        }

                        const productForBilling = {
                            ...saleProduct.product,
                            unit: saleProduct.unit || saleProduct.product?.unit || null,
                            batches: saleProduct.product?.batches || []
                        };

                        PosBilling.addProductToBillingBody(
                            productForBilling,
                            normalizedStockEntry,
                            price,
                            editModeBatchId,
                            maxAvailableStock,
                            saleProduct.price_type,
                            saleProduct.quantity,
                            saleProduct.imei_numbers || [],
                            saleProduct.discount_type,
                            saleProduct.discount_amount,
                            editBatch,
                            saleProduct.free_quantity || 0,
                            true,
                            saleProduct.custom_name || null
                        );

                    } catch (error) {
                        console.error('Error adding product to billing:', error,
                            saleProduct);
                    }
                });

                // If the sale has a customer_id, trigger customer data fetch
                if (saleDetails.sale && saleDetails.sale.customer_id) {
                    const $customerSelect = $('#customer-id');
                    if ($customerSelect.length) {
                        $customerSelect.val(saleDetails.sale.customer_id.toString());

                        setTimeout(() => {
                            $customerSelect.trigger('change');

                            if (window.customerFunctions && typeof window.customerFunctions
                                .fetchCustomerData === 'function') {
                                window.customerFunctions.fetchCustomerData().then(() => {
                                    $customerSelect.val(saleDetails.sale.customer_id
                                        .toString());
                                    $customerSelect.trigger('change');
                                });
                            } else {
                            }
                        }, 200);
                    }
                }

                // Set global discount values
                const discountElement = document.getElementById('global-discount');
                const discountTypeElement = document.getElementById('discount-type');
                if (discountElement && saleDetails.sale) {
                    discountElement.value = saleDetails.sale.discount_amount || 0;
                }
                if (discountTypeElement && saleDetails.sale) {
                    discountTypeElement.value = saleDetails.sale.discount_type || 'fixed';
                }

                // ✅ Populate sale notes textarea (desktop and mobile)
                const saleNotesTextarea = document.getElementById('sale-notes-textarea');
                const saleNotesMobile = document.getElementById('sale-notes-textarea-mobile');
                const notesVal = (saleDetails.sale && saleDetails.sale.sale_notes) ? saleDetails.sale.sale_notes : '';
                if (saleNotesTextarea) saleNotesTextarea.value = notesVal;
                if (saleNotesMobile) saleNotesMobile.value = notesVal;

                // Update totals
                if (PosCart.updateTotals) PosCart.updateTotals();

                // 🔒 Disable restricted buttons in edit mode
                if (PosCart.updatePaymentButtonsState) PosCart.updatePaymentButtonsState();
            } else {
                console.error('Invalid sale data:', data);
                toastr.error('Failed to fetch sale data.', 'Error');
            }
        })
        .catch(error => {
            console.error('Error fetching sale data:', error);
            toastr.error('An error occurred while fetching sale data.', 'Error');
        });
}

// ================================================================
//  Sale data helpers (for gatherSaleData)
// ================================================================
function getSalesDateString() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    const h = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    return `${y}-${m}-${d} ${h}:${min}:${s}`;
}

function buildSaleBasePayload(status) {
    const locationId = window.locationId;
    const discountType = $('#discount-type').val() || 'fixed';
    const discountAmount = parseFormattedAmount($('#global-discount').val()) || 0;
    const totalAmount = parseFormattedAmount($('#total-amount').text()) || 0;
    let finalAmount = Math.max(0, totalAmount - (discountType === 'percentage' ? totalAmount * (discountAmount / 100) : discountAmount));
    const shippingCharges = parseFloat(window.shippingData.shipping_charges) || 0;
    finalAmount += shippingCharges;

    const isEditing = window.isEditing;
    return {
        customer_id: $('#customer-id').val(),
        sales_date: getSalesDateString(),
        location_id: locationId,
        status: status,
        sale_type: 'POS',
        sale_notes: (document.getElementById('sale-notes-textarea')?.value?.trim() || document.getElementById('sale-notes-textarea-mobile')?.value?.trim()) || null,
        products: [],
        discount_type: discountType,
        discount_amount: discountAmount,
        total_amount: totalAmount,
        final_total: finalAmount,
        shipping_charges: shippingCharges,
        transaction_type: (isEditing && window.originalSaleData?.transaction_type === 'sale_order' && status === 'final')
            ? 'invoice' : (window.originalSaleData?.transaction_type || undefined),
    };
}

/** @returns {{ valid: boolean, message?: string }} */
function validateBillingRow(productRow, options = {}) {
    const allowStockOverflow = !!options.allowStockOverflow;
    const isUnlimitedStock = String(productRow.attr('data-unlimited-stock') || '0') === '1';
    const rowLocationId = productRow.find('.location-id').text().trim();
    if (!rowLocationId) return { valid: false, message: 'Location ID is missing for a product.' };

    const qtyVal = productRow.find('.quantity-input').val().trim();
    const freeQtyVal = productRow.find('.free-quantity-input').val().trim();
    const isImeiProduct = productRow.find('.imei-data').text().trim() !== '';
    const quantity = isImeiProduct ? 1 : (parseFloat(qtyVal) || 0);
    const freeQuantity = parseFloat(freeQtyVal) || 0;
    const totalQuantity = quantity + freeQuantity;
    const maxStock = parseFloat(productRow.find('.quantity-input').attr('max')) || 0;

    if (!allowStockOverflow && !isUnlimitedStock && totalQuantity > maxStock) {
        const productName = productRow.find('.product-name').text().trim();
        return { valid: false, message: `Product "${productName}": Total quantity (${quantity} + ${freeQuantity} free = ${totalQuantity}) exceeds available stock (${maxStock}). Please reduce the quantity.` };
    }
    return { valid: true };
}

function canUseSaleOrderBackorderValidationBypass() {
    return !!window.PosConfig?.features?.enableBackorders;
}

function buildProductPayloadFromRow(productRow) {
    const batchId = productRow.find('.batch-id').text().trim();
    const rowLocationId = productRow.find('.location-id').text().trim();
    const discountFixed = parseFloat(productRow.find('.fixed_discount').val().trim()) || 0;
    const discountPercent = parseFloat(productRow.find('.percent_discount').val().trim()) || 0;
    const rowDiscountType = discountFixed > 0 ? 'fixed' : 'percentage';
    const rowDiscountAmount = discountFixed > 0 ? discountFixed : discountPercent;
    const imeiData = productRow.find('.imei-data').text().trim();
    const imeis = imeiData ? imeiData.split(',').filter(Boolean) : [];
    const isImeiProduct = imeiData !== '';
    const qtyVal = productRow.find('.quantity-input').val().trim();
    const freeQtyVal = productRow.find('.free-quantity-input').val().trim();
    const quantity = isImeiProduct ? 1 : (parseFloat(qtyVal) || 0);
    const freeQuantity = parseFloat(freeQtyVal) || 0;
    // Batch selection rules:
    // - If cashier explicitly selected a batch in the modal → always respect it.
    // - Else, in EDIT DRAFT mode: keep original batch only if it still has enough stock at this location.
    // - Otherwise fallback to FIFO ("all") so the backend can pick an available batch.
    const userSelectedBatch = String(productRow.attr('data-user-selected-batch') || '0') === '1';
    const isEditing = !!window.isEditing;
    const isEditingFinalizedSale = !!window.isEditingFinalizedSale;
    const totalQuantity = (parseFloat(quantity) || 0) + (parseFloat(freeQuantity) || 0);

    let processedBatchId = 'all';
    const hasSpecificBatch = batchId && batchId !== 'null' && batchId !== '' && batchId !== 'all';

    if (userSelectedBatch && hasSpecificBatch) {
        processedBatchId = String(batchId);
    } else if (isEditing && !isEditingFinalizedSale && hasSpecificBatch) {
        // Draft edit: validate original batch still has stock
        const batchFetcher = window.Pos?.Customer?.getBatchDataById;
        const batch = typeof batchFetcher === 'function' ? batchFetcher(batchId) : null;

        let available = null;
        if (batch && Array.isArray(batch.location_batches)) {
            const lb = batch.location_batches.find(x => String(x.location_id) === String(rowLocationId));
            if (lb) {
                const paid = parseFloat(lb.qty ?? lb.quantity ?? 0) || 0;
                const free = parseFloat(lb.free_qty ?? lb.free_quantity ?? 0) || 0;
                available = paid + free;
            }
        }

        if (available === null) {
            // If we can't reliably read stock for this batch from the frontend cache,
            // keep the original batch to avoid switching everything to FIFO.
            processedBatchId = String(batchId);
        } else if (totalQuantity > 0 && totalQuantity <= available) {
            processedBatchId = String(batchId); // keep original batch
        } else {
            processedBatchId = 'all'; // fallback to FIFO ONLY when we know original batch is insufficient
        }
    } else if (hasSpecificBatch) {
        // Finalized sale edit (or any non-draft edit): preserve batch id if present
        processedBatchId = String(batchId);
    }
    const customNameEl = productRow.find('.custom-name-input');
    const customName = customNameEl.length > 0 ? (customNameEl.val()?.trim() || null) : null;
    const selectedPriceType = (productRow.find('.selected-price-type').text().trim() || window.priceType || 'retail');
    const taxPercent = parseFloat(productRow.attr('data-tax-percent')) || 0;
    const sellingPriceTaxType = ((productRow.attr('data-selling-price-tax-type') || 'inclusive') + '').toLowerCase();
    const subtotalValue = parseFormattedAmount(productRow.find('.subtotal').text().trim());
    const quantityValue = parseFloat(quantity) || 0;
    const unitPriceValue = parseFormattedAmount(productRow.find('.price-input').val().trim());
    const baseSubtotal = quantityValue * unitPriceValue;
    const lineTaxAmount = sellingPriceTaxType === 'exclusive'
        ? Math.max(0, subtotalValue - baseSubtotal)
        : 0;

    const productData = {
        product_id: parseInt(productRow.find('.product-id').text().trim(), 10),
        location_id: parseInt(rowLocationId, 10),
        quantity,
        free_quantity: freeQuantity,
        price_type: selectedPriceType,
        unit_price: unitPriceValue,
        subtotal: subtotalValue,
        discount_amount: rowDiscountAmount,
        discount_type: rowDiscountType,
        tax: lineTaxAmount,
        tax_percent: taxPercent,
        selling_price_tax_type: sellingPriceTaxType,
        batch_id: processedBatchId,
        custom_name: customName,
    };
    if (imeis.length > 0) productData.imei_numbers = imeis;
    return productData;
}

// ================================================================
//  gatherSaleData
// ================================================================
function gatherSaleData(status, options = {}) {
    // Use locationId from dropdown (synced in pos-init) so sale works when location is selected
    const locationId = window.locationId || window.selectedLocationId;
    if (!locationId) {
        toastr.error('Location ID is required. Please select a location.');
        return null;
    }

    const productRows = $('#billing-body tr');
    if (productRows.length === 0) {
        toastr.error('Please add at least one product before completing the sale.');
        return null;
    }

    const saleData = buildSaleBasePayload(status);

    for (let i = 0; i < productRows.length; i++) {
        const productRow = $(productRows[i]);
        const validation = validateBillingRow(productRow, options);
        if (!validation.valid) {
            toastr.error(validation.message);
            return null;
        }
        saleData.products.push(buildProductPayloadFromRow(productRow));
    }

    const shippingInfo = getShippingDataForSale();
    if (shippingInfo.shipping_details || shippingInfo.shipping_address || shippingInfo.shipping_charges > 0) {
        Object.assign(saleData, shippingInfo);
    }
    return saleData;
}

// ================================================================
//  refreshStockDataAfterSale
// ================================================================
function refreshStockDataAfterSale(saleData) {
    const stockData = Array.isArray(window.stockData) ? window.stockData : [];
    if (!saleData || !Array.isArray(saleData.products) || stockData.length === 0) {
        return;
    }

    const q = (v) => parseFloat(v) || 0;

    // Mirrors backend allocation intent for local cache sync only.
    function deductFromLocationBatch(lb, paidReq, freeReq) {
        let paid = q(lb.quantity);
        let free = q(lb.free_quantity);

        // paid: paid pool first, then free pool
        const paidFromPaid = Math.min(paid, paidReq);
        paid -= paidFromPaid;
        paidReq -= paidFromPaid;

        const paidFromFree = Math.min(free, paidReq);
        free -= paidFromFree;
        paidReq -= paidFromFree;

        // free: remaining free pool first, then remaining paid pool
        const freeFromFree = Math.min(free, freeReq);
        free -= freeFromFree;
        freeReq -= freeFromFree;

        const freeFromPaid = Math.min(paid, freeReq);
        paid -= freeFromPaid;
        freeReq -= freeFromPaid;

        lb.quantity = Math.max(0, paid);
        lb.free_quantity = Math.max(0, free);

        return {
            remainingPaid: Math.max(0, paidReq),
            remainingFree: Math.max(0, freeReq)
        };
    }

    saleData.products.forEach((line) => {
        const productId = parseInt(line.product_id, 10);
        if (!productId) return;

        const stockEntry = stockData.find(s => parseInt(s?.product?.id, 10) === productId);
        if (!stockEntry || !Array.isArray(stockEntry.batches)) return;

        let remainingPaid = q(line.quantity);
        let remainingFree = q(line.free_quantity);
        const lineLocationId = line.location_id || window.selectedLocationId;
        const requestedBatchId = String(line.batch_id || 'all');

        let candidateBatches = stockEntry.batches
            .filter(b => Array.isArray(b.location_batches))
            .map(b => ({
                batch: b,
                lb: b.location_batches.find(x => String(x.location_id) === String(lineLocationId))
            }))
            .filter(x => x.lb);

        if (requestedBatchId !== 'all') {
            candidateBatches = candidateBatches.filter(x => String(x.batch.id) === requestedBatchId);
        }

        for (const item of candidateBatches) {
            if (remainingPaid <= 0 && remainingFree <= 0) break;
            const result = deductFromLocationBatch(item.lb, remainingPaid, remainingFree);
            remainingPaid = result.remainingPaid;
            remainingFree = result.remainingFree;
        }

        // Recompute stock entry totals from location_batches
        let totalPaid = 0;
        let totalFree = 0;

        stockEntry.batches.forEach((b) => {
            const lbs = Array.isArray(b.location_batches) ? b.location_batches : [];
            const paidSum = lbs.reduce((s, lb) => s + q(lb.quantity), 0);
            const freeSum = lbs.reduce((s, lb) => s + q(lb.free_quantity), 0);

            totalPaid += paidSum;
            totalFree += freeSum;

            b.total_batch_quantity = paidSum + freeSum;
            b.free_qty = freeSum;
            b.paid_qty = paidSum;
        });

        stockEntry.total_stock = totalPaid;
        stockEntry.total_free_stock = totalFree;
    });

    // Update IMEI status cache for IMEI products in the same local snapshot
    const soldImeiMap = new Map();
    saleData.products.forEach((line) => {
        if (!Array.isArray(line.imei_numbers) || line.imei_numbers.length === 0) return;
        soldImeiMap.set(parseInt(line.product_id, 10), line.imei_numbers);
    });

    soldImeiMap.forEach((soldImeis, productId) => {
        const stockEntry = stockData.find(s => parseInt(s?.product?.id, 10) === productId);
        if (!stockEntry || !Array.isArray(stockEntry.imei_numbers)) return;

        stockEntry.imei_numbers.forEach((imei) => {
            if (soldImeis.includes(imei.imei_number)) {
                imei.status = 'sold';
            }
        });
    });

    // Server refresh is triggered centrally in runAfterSaleSuccess.
}

// ================================================================
//  Send sale helpers (validation, resolve ID, error UI, print, post-success)
// ================================================================
function validateWalkInCheque(saleData) {
    if (saleData.customer_id != 1 || !saleData.payments) return true;
    for (let p of saleData.payments) {
        if (p.payment_method === 'cheque') {
            toastr.error('Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.');
            return false;
        }
    }
    return true;
}

function resolveSaleIdFromUrl(saleId) {
    if (saleId) return saleId;
    // After a successful edit-save we clear isEditing; never treat /sales/edit/{id} as active edit.
    if (!window.isEditing && !window.currentEditingSaleId) {
        return null;
    }
    const segs = window.location.pathname.split('/');
    const last = segs[segs.length - 1];
    if (!isNaN(last) && last !== 'pos' && last !== 'list-sale') return last;
    return null;
}

/**
 * After a successful POST /sales/update/{id} from POS, clear edit flags and URL so the next bill
 * is always a new sale (avoids updating the same invoice number again).
 */
function clearPosEditSessionAfterSave() {
    window.isEditing = false;
    window.currentEditingSaleId = null;
    window.isEditingFinalizedSale = false;
    window.originalSaleData = null;
    try {
        if (window.location.pathname.includes('/sales/edit/')) {
            window.history.replaceState({}, document.title, '/pos-create');
            document.title = 'POS - Create Sale';
            const invEl = document.getElementById('sale-invoice-no');
            if (invEl) invEl.textContent = '';
        }
    } catch (e) {
        console.warn('clearPosEditSessionAfterSave:', e);
    }
}

function showCreditOrStockSwal(message, isCreditLimit) {
    const formatted = (message || '').replace(/\n/g, '<br>').replace(/•/g, '&bull;');
    swal({
        title: isCreditLimit ? 'Credit Limit Exceeded' : '⚠️ Insufficient Stock',
        text: formatted,
        html: true,
        type: isCreditLimit ? 'error' : 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: isCreditLimit ? '#d33' : '#f0ad4e'
    });
}

function handleSaleFailureResponse(response, onComplete) {
    if (response.message && response.message.includes('Credit limit exceeded')) {
        showCreditOrStockSwal(response.message, true);
    } else if (response.message && response.message.includes('Insufficient stock')) {
        showCreditOrStockSwal(response.message, false);
    } else {
        toastr.error('Failed to record sale: ' + (response.message || ''));
    }
    if (onComplete) onComplete();
}

function handleSaleXhrError(xhr, onComplete) {
    let errorMessage = 'An error occurred while processing the sale.';
    let useToastr = true;
    try {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : null;
        if (msg) {
            errorMessage = msg;
            if (msg.includes('Credit limit exceeded')) { useToastr = false; showCreditOrStockSwal(msg, true); }
            else if (msg.includes('Insufficient stock')) { useToastr = false; showCreditOrStockSwal(msg, false); }
        } else if (xhr.responseText) {
            try {
                const parsed = JSON.parse(xhr.responseText);
                if (parsed.message) {
                    errorMessage = parsed.message;
                    if (parsed.message.includes('Credit limit exceeded')) { useToastr = false; showCreditOrStockSwal(parsed.message, true); }
                    else if (parsed.message.includes('Insufficient stock')) { useToastr = false; showCreditOrStockSwal(parsed.message, false); }
                }
            } catch (_) { errorMessage = xhr.responseText; }
        }
    } catch (e) { console.error('Error parsing response:', e); }
    if (useToastr) toastr.error(errorMessage);
    if (onComplete) onComplete();
}

function openPrintForSale(response, saleId, wasEditingSale) {
    if (!response.invoice_html) {
        const returnedSaleId = (response.sale && response.sale.id) || response.id || saleId;
        if (typeof window.printReceipt === 'function') {
            window.printReceipt(returnedSaleId);
        } else {
            const w = window.open(`/sales/print-recent-transaction/${returnedSaleId}`, '_blank');
            if (!w) toastr.error('Print window was blocked. Please allow pop-ups.');
        }
        if (wasEditingSale) {
            setTimeout(() => { window.location.replace('/pos-create'); }, 1200);
        }
        return;
    }
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const returnedSaleId = (response.sale && response.sale.id) || response.id || saleId;
    const isEdit = !!wasEditingSale;
    const focusSearch = () => {
        const el = document.getElementById('productSearchInput');
        if (el) { el.focus(); el.select(); }
    };

    if (isMobile) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) { toastr.error('Print window was blocked. Please allow pop-ups.'); return; }
        printWindow.document.open();
        printWindow.document.write(response.invoice_html);
        printWindow.document.close();
        printWindow.onload = function() {
            printWindow.print();
            printWindow.onafterprint = function() { setTimeout(focusSearch, 300); };
        };
        if (isEdit) {
            const t = setInterval(() => { if (printWindow.closed) { clearInterval(t); window.navigateToPosCreate(); } }, 100);
            setTimeout(() => clearInterval(t), 30000);
        } else {
            const t = setInterval(() => { if (printWindow.closed) { clearInterval(t); setTimeout(focusSearch, 100); } }, 500);
        }
        return;
    }

    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:absolute;width:0;height:0;border:none;left:-9999px;visibility:hidden;';
    document.body.appendChild(iframe);
    const iframeDoc = iframe.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(response.invoice_html);
    iframeDoc.close();
    iframe.onload = function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        iframe.contentWindow.onafterprint = function() {
            setTimeout(() => {
                if (document.body.contains(iframe)) document.body.removeChild(iframe);
                if (isEdit) window.navigateToPosCreate();
                else focusSearch();
            }, 200);
        };
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe); }, 60000);
    };
}

function runAfterSaleSuccess(saleData, onComplete) {
    resetForm();
    if (window.searchCache && window.searchCache.clear) window.searchCache.clear();
    if (onComplete) onComplete();
    setTimeout(() => refreshStockDataAfterSale(saleData), 100);
    setTimeout(() => { if (!window.fetchingSalesData && window.fetchSalesData) window.fetchSalesData(); }, 150);
    setTimeout(() => {
        window.allProducts = [];
        window.currentProductsPage = 1;
        window.hasMoreProducts = true;
        if (window.fetchPaginatedProducts) window.fetchPaginatedProducts(true);
    }, 200);
    if (window.isSalesRep) {
        window.salesRepCustomerResetInProgress = true;
        window.lastCustomerResetTime = Date.now();
        setTimeout(() => {
            const customerSelect = $('#customer-id');
            if (customerSelect.val() && customerSelect.val() !== '') customerSelect.val('').trigger('change');
            setTimeout(() => { window.salesRepCustomerResetInProgress = false; }, 3000);
        }, 500);
    }
    setTimeout(() => {
        fetch('/api/sales/clear-cache', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } }).catch(() => {});
        if (saleData && saleData.status === 'final' && window.customerFunctions && typeof window.customerFunctions.fetchCustomerData === 'function' && !window.isSalesRep) {
            window.customerFunctions.fetchCustomerData().catch(e => console.error('Failed to refresh customer data:', e));
        }
    }, 300);
}

function handleSaleSuccessResponse(response, saleData, saleId, onComplete) {
    if (!response.message || (!response.invoice_html && !response.sale)) {
        handleSaleFailureResponse(response, onComplete);
        return;
    }
    try { document.getElementsByClassName('successSound')[0].play(); } catch (_) {}
    const isEdit = !!(saleId && (window.location.pathname.includes('/edit/') || window.isEditing));

    if (response.sale && response.sale.transaction_type === 'sale_order') {
        toastr.success(response.message + ' Order Number: ' + response.sale.order_number, 'Sale Order Created', { timeOut: 5000, progressBar: true });
        if (saleId) clearPosEditSessionAfterSave();
        if (isEdit) { setTimeout(() => window.navigateToPosCreate(), 1500); if (onComplete) onComplete(); return; }
        setTimeout(() => { resetForm(); if (onComplete) onComplete(); }, 50);
        return;
    }
    toastr.success(response.message);
    $(document).trigger('pos:register-refresh');
    if (isEdit && (saleData.status === 'draft' || saleData.status === 'quotation')) {
        if (saleId) clearPosEditSessionAfterSave();
        setTimeout(() => window.navigateToPosCreate(), 1500);
        if (onComplete) onComplete();
        return;
    }
    if (isEdit && saleData.status === 'suspend') {
        if (saleId) clearPosEditSessionAfterSave();
        setTimeout(() => { window.location.replace('/pos-create'); }, 1200);
        if (onComplete) onComplete();
        return;
    }

    // Final (and similar) invoice updates: clear edit URL/flags immediately so the next sale cannot
    // POST to /sales/update/{sameId} even if the user skips/cancels print (onafterprint never fires).
    const wasEditingSale = isEdit;
    if (saleId) clearPosEditSessionAfterSave();

    if (saleData.status !== 'suspend' && saleData.transaction_type !== 'sale_order') {
        try { openPrintForSale(response, saleId, wasEditingSale); } catch (err) { console.warn('Error initiating print:', err); }
    }
    setTimeout(() => runAfterSaleSuccess(saleData, onComplete), 50);

    // Hard fallback: full reload to clean POS if browser history/print left any stale state
    if (wasEditingSale && (saleData.status === 'final' || saleData.status === 'jobticket')) {
        setTimeout(() => {
            if (window.location.pathname.includes('/sales/edit/')) {
                window.location.replace('/pos-create');
            }
        }, 4000);
    }
}

// ================================================================
//  sendSaleData
// ================================================================
function sendSaleData(saleData, saleId = null, onComplete = () => {}) {
    if (!window.checkSalesAccess()) { onComplete(); return; }
    if (!validateWalkInCheque(saleData)) { onComplete(); return; }

    saleId = resolveSaleIdFromUrl(saleId);
    const url = saleId ? `/sales/update/${saleId}` : '/sales/store';

    $.ajax({
        url: url,
        type: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        data: JSON.stringify(saleData),
        timeout: 30000,
        cache: false,
        success: (response) => handleSaleSuccessResponse(response, saleData, saleId, onComplete),
        error: (xhr) => handleSaleXhrError(xhr, onComplete)
    });
}

// ================================================================
//  Payment data gatherers
// ================================================================
function gatherCashPaymentData() {
    const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());
    const today = new Date().toISOString().slice(0, 10);
    return [{
        payment_method: 'cash',
        payment_date: today,
        amount: totalAmount
    }];
}

function gatherCardPaymentData() {
    const cardNumber = $('#card_number').val().trim();
    const cardHolderName = $('#card_holder_name').val().trim();
    const cardExpiryMonth = $('#card_expiry_month').val().trim();
    const cardExpiryYear = $('#card_expiry_year').val().trim();
    const cardSecurityCode = $('#card_security_code').val().trim();
    const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());
    const today = new Date().toISOString().slice(0, 10);

    return [{
        payment_method: 'card',
        payment_date: today,
        amount: totalAmount,
        card_number: cardNumber,
        card_holder_name: cardHolderName,
        card_expiry_month: cardExpiryMonth,
        card_expiry_year: cardExpiryYear,
        card_security_code: cardSecurityCode
    }];
}

function gatherChequePaymentData() {
    const chequeNumber = $('#cheque_number').val().trim();
    const bankBranch = $('#cheque_bank_branch').val().trim();
    const chequeReceivedDate = $('#cheque_received_date').val().trim();
    const chequeValidDate = $('#cheque_valid_date').val().trim();
    const chequeGivenBy = $('#cheque_given_by').val().trim();
    const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());
    const today = new Date().toISOString().slice(0, 10);

    return [{
        payment_method: 'cheque',
        payment_date: today,
        amount: totalAmount,
        cheque_number: chequeNumber,
        cheque_bank_branch: bankBranch,
        cheque_received_date: chequeReceivedDate,
        cheque_valid_date: chequeValidDate,
        cheque_given_by: chequeGivenBy,
        cheque_status: 'pending',
        payment_status: 'pending'
    }];
}

function validateChequeFields() {
    let isValid = true;

    if ($('#cheque_number').val().trim() === '') {
        $('#chequeNumberError').text('Cheque Number is required.');
        isValid = false;
    } else {
        $('#chequeNumberError').text('');
    }

    // Clear any previous error messages (dates are now optional)
    $('#chequeReceivedDateError').text('');
    $('#chequeValidDateError').text('');

    return isValid;
}

function gatherPaymentData() {
    const paymentData = [];
    document.querySelectorAll('.payment-row').forEach(row => {
        const paymentMethod = row.querySelector('.payment-method').value;
        let paymentDate = row.querySelector('.payment-date').value;

        // Convert date from DD-MM-YYYY or DD/MM/YYYY to YYYY-MM-DD format
        if (paymentDate && paymentDate.match(/^\d{2}[\-\/]\d{2}[\-\/]\d{4}$/)) {
            const parts = paymentDate.split(/[\-\/]/);
            paymentDate = parts[2] + '-' + parts[1] + '-' + parts[0];
        } else if (!paymentDate) {
            paymentDate = new Date().toISOString().slice(0, 10);
        }

        const amountInput = row.querySelector('.payment-amount').value;
        let amount = parseFormattedAmount(amountInput);

        const conditionalFields = {};
        row.querySelectorAll(
            '.conditional-fields input, .conditional-fields select').forEach(
            input => {
                conditionalFields[input.name] = input.value.trim();
            });

        if (!isNaN(amount) && amount > 0) {
            const paymentRow = {
                payment_method: paymentMethod,
                payment_date: paymentDate,
                amount: amount,
                ...conditionalFields
            };

            if (paymentMethod === 'cheque') {
                if (!paymentRow.cheque_status) {
                    paymentRow.cheque_status = 'pending';
                }
                paymentRow.payment_status = (paymentRow.cheque_status === 'cleared' || paymentRow.cheque_status === 'deposited') ? 'completed' : 'pending';
            }

            paymentData.push(paymentRow);
        }
    });

    return paymentData;
}

/**
 * Adjust cash payments so that total paid does not exceed the final
 * payable amount (used for Walk‑In and "Return Cash" flow in
 * multiple-payment modal).
 *
 * @param {Array<{payment_method:string, amount:number}>} paymentData
 * @param {number} finalTotal
 * @returns {{ paymentData: any[], amountGiven: number, excess: number }}
 */
function adjustCashPaymentForExcess(paymentData, finalTotal) {
    let amountGiven = paymentData.reduce((sum, pay) => sum + (parseFloat(pay.amount) || 0), 0);
    let excess = amountGiven - finalTotal;
    if (excess <= 0) {
        return { paymentData, amountGiven, excess: 0 };
    }

    let remainingExcess = excess;
    for (let i = paymentData.length - 1; i >= 0 && remainingExcess > 0; i--) {
        const pay = paymentData[i];
        if (pay.payment_method !== 'cash') continue;
        const currentAmount = parseFloat(pay.amount) || 0;
        if (currentAmount <= 0) continue;

        const reduceBy = Math.min(currentAmount, remainingExcess);
        const newAmount = currentAmount - reduceBy;
        pay.amount = parseFloat(newAmount.toFixed(2));
        remainingExcess -= reduceBy;
    }

    amountGiven = paymentData.reduce((sum, pay) => sum + (parseFloat(pay.amount) || 0), 0);
    return { paymentData, amountGiven, excess: excess };
}

// ================================================================
//  Modal resets
// ================================================================
function resetCardModal() {
    $('#card_number').val('');
    $('#card_holder_name').val('');
    $('#card_type').val('visa');
    $('#card_expiry_month').val('');
    $('#card_expiry_year').val('');
    $('#card_security_code').val('');
}

function resetChequeModal() {
    $('#cheque_number').val('');
    $('#bank_branch').val('');
    $('#cheque_received_date').val('');
    $('#cheque_valid_date').val('');
    $('#cheque_given_by').val('');
    $('.error-message').text('');
}

// ================================================================
//  Shipping functions
// ================================================================
function openShippingModal() {
    const totalWithCurrentShipping = parseFormattedAmount($('#final-total-amount').text());
    const currentShippingCharges = parseFloat(window.shippingData.shipping_charges) || 0;
    const subtotalWithoutShipping = totalWithCurrentShipping - currentShippingCharges;

    $('#modalSubtotal').text(formatCurrency(subtotalWithoutShipping));
    $('#modalShippingCharges').text(formatCurrency(currentShippingCharges));
    $('#modalTotalWithShipping').text(formatCurrency(totalWithCurrentShipping));

    $('#shippingDetails').val(window.shippingData.shipping_details);
    $('#shippingAddress').val(window.shippingData.shipping_address);
    $('#shippingCharges').val(window.shippingData.shipping_charges);
    $('#shippingStatus').val(window.shippingData.shipping_status);
    $('#deliveredTo').val(window.shippingData.delivered_to);
    $('#deliveryPerson').val(window.shippingData.delivery_person);
    $('#trackingNumber').val(window.shippingData.tracking_number);

    $('#shippingModal').modal('show');
}

function updateShippingData() {
    const shippingCharges = parseFloat($('#shippingCharges').val()) || 0;

    if (shippingCharges <= 0) {
        toastr.error('Please enter shipping charges');
        $('#shippingCharges').focus();
        return;
    }

    window.shippingData = {
        shipping_details: ($('#shippingDetails').val() || '').trim(),
        shipping_address: ($('#shippingAddress').val() || '').trim(),
        shipping_charges: shippingCharges,
        shipping_status: $('#shippingStatus').val() || 'ordered',
        delivered_to: ($('#deliveredTo').val() || '').trim(),
        delivery_person: ($('#deliveryPerson').val() || '').trim()
    };

    if (PosCart.updateTotals) PosCart.updateTotals();
    updateShippingButtonState();

    $('#shippingModal').modal('hide');
    toastr.success('Shipping information updated successfully');
}

function updateShippingButtonState() {
    const shippingButton = $('#shippingButton');
    if (window.shippingData.shipping_charges > 0 || window.shippingData.shipping_details || window.shippingData.shipping_address) {
        shippingButton.removeClass('btn-outline-info').addClass('btn-info');
        shippingButton.html('<i class="fas fa-shipping-fast"></i> Shipping (' + formatCurrency(window.shippingData.shipping_charges) + ')');
    } else {
        shippingButton.removeClass('btn-info').addClass('btn-outline-info');
        shippingButton.html('<i class="fas fa-shipping-fast"></i> Shipping');
    }
}

function clearShippingData() {
    window.shippingData = {
        shipping_details: '',
        shipping_address: '',
        shipping_charges: 0,
        shipping_status: 'pending',
        delivered_to: '',
        delivery_person: ''
    };

    if (PosCart.updateTotals) PosCart.updateTotals();
    updateShippingButtonState();
}

function getShippingDataForSale() {
    return window.shippingData;
}

// ================================================================
//  Job ticket
// ================================================================
function calculateJobTicketBalance() {
    const totalAmount = parseFloat($('#totalAmountInput').val()) || 0;
    const advanceAmount = parseFloat($('#advanceAmountInput').val()) || 0;
    let balance = totalAmount - advanceAmount;
    if (balance < 0) balance = 0;
    $('#balanceAmountInput').val(balance.toFixed(2));
}

// ================================================================
//  Suspended sales
// ================================================================
function fetchSuspendedSales() {
    $.ajax({
        url: '/sales/suspended',
        type: 'GET',
        success: function(response) {
            displaySuspendedSales(response);
            $('#suspendSalesModal').modal('show');
        },
        error: function(xhr, status, error) {
            toastr.error('Failed to fetch suspended sales: ' + xhr.responseText);
        }
    });
}

function displaySuspendedSales(sales) {
    const suspendedSalesContainer = $('#suspendedSalesContainer');
    suspendedSalesContainer.empty();

    const userPerms = window.userPermissions || {};

    sales.forEach(sale => {
        const finalTotal = parseFormattedAmount(sale.final_total);
        const saleRow = `
        <tr>
            <td>${sale.invoice_no}</td>
            <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
            <td>${sale.customer ? sale.customer.name : 'Walk-In Customer'}</td>
            <td>${sale.products.length}</td>
            <td>Rs. ${formatAmountWithSeparators(finalTotal.toFixed(2))}</td>
            <td>
                ${userPerms.canEditSale ? `<a href="/sales/edit/${sale.id}" class="btn btn-success editSaleButton" data-sale-id="${sale.id}">Edit</a>` : ''}
                ${userPerms.canDeleteSale ? `<button class="btn btn-danger deleteSuspendButton" data-sale-id="${sale.id}">Delete</button>` : ''}
            </td>
        </tr>`;
        suspendedSalesContainer.append(saleRow);
    });

    $('.editSaleButton').on('click', function() {
        const saleId = $(this).data('sale-id');
    });

    $('.deleteSuspendButton').on('click', function() {
        const saleId = $(this).data('sale-id');
        deleteSuspendedSale(saleId);
    });
}

function deleteSuspendedSale(saleId) {
    if (!confirm('Are you sure you want to delete this suspended sale? This will restore stock and update customer balance.')) {
        return;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    if (!csrfToken) {
        toastr.error('Security token not found. Please refresh the page and try again.');
        return;
    }

    $.ajax({
        url: `/sales/delete-suspended/${saleId}`,
        type: 'DELETE',
        data: {
            _token: csrfToken
        },
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        success: function(response) {
            toastr.success(response.message || 'Suspended sale deleted successfully');
            fetchSuspendedSales();
        },
        error: function(xhr, status, error) {
            console.error('Delete suspended sale error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });

            let errorMessage = 'Failed to delete suspended sale';

            if (xhr.status === 419) {
                errorMessage = 'Session expired. Please refresh the page and try again.';
                setTimeout(() => {
                    if (confirm('Session expired. Would you like to reload the page?')) {
                        window.location.reload();
                    }
                }, 2000);
            } else if (xhr.status === 403) {
                errorMessage = 'You do not have permission to delete this sale.';
            } else if (xhr.status === 404) {
                errorMessage = 'Suspended sale not found.';
            } else {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    } else if (errorResponse.errors) {
                        errorMessage = Object.values(errorResponse.errors).flat().join(', ');
                    }
                } catch (e) {
                    errorMessage = `Error ${xhr.status}: ${xhr.statusText || 'Unknown error'}`;
                }
            }

            toastr.error(errorMessage);
        }
    });
}

// ================================================================
//  Form reset helpers
// ================================================================
function resetToWalkingCustomer() {
    const customerSelect = $('#customer-id');

    if (window.isSalesRep) {
        customerSelect.val('').trigger('change');

        window.preventAutoSelection = true;
        window.salesRepCustomerResetInProgress = true;
        window.lastCustomerResetTime = Date.now();

        setTimeout(() => {
            window.preventAutoSelection = false;
            window.salesRepCustomerResetInProgress = false;
        }, 3000);
    } else {
        if (customerSelect.find('option[value="1"]').length > 0) {
            customerSelect.val('1').trigger('change');
        } else {
            const walkingCustomer = customerSelect.find('option').filter(function() {
                return $(this).text().toLowerCase().includes('walk-in');
            });

            if (walkingCustomer.length > 0) {
                customerSelect.val(walkingCustomer.val()).trigger('change');
            } else {
                console.warn('Walk-in customer not found, resetting to empty');
                customerSelect.val('').trigger('change');
            }
        }
    }
}

function resetForm() {
    // Reset editing mode
    window.isEditing = false;
    window.currentEditingSaleId = null;
    window.isEditingFinalizedSale = false;
    window._editModeToastShown = false;

    resetToWalkingCustomer();

    // For sales reps, ensure customer stays reset
    if (window.isSalesRep) {
        setTimeout(() => {
            const customerSelect = $('#customer-id');
            if (customerSelect.val() && customerSelect.val() !== '') {
                customerSelect.val('').trigger('change');
            }

            window.salesRepCustomerResetInProgress = true;
            window.lastCustomerResetTime = Date.now();
            setTimeout(() => {
                window.salesRepCustomerResetInProgress = false;
            }, 3000);
        }, 500);
    }

    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.value = 1;
    });

    const billingBodyRows = document.querySelectorAll('#billing-body tr');
    billingBodyRows.forEach(row => {
        row.remove();
    });

    document.getElementById('amount-given').value = '';

    // Reset discount fields
    document.getElementById('global-discount').value = '';
    document.getElementById('discount-type').value = 'fixed';

    // Reset shipping data
    clearShippingData();

    if (PosCart.updateTotals) PosCart.updateTotals();
}

// ================================================================
//  Event wiring — all inside $(document).ready
// ================================================================
$(document).ready(function() {

    // Add event listeners for quantity input changes
    $(document).on('input change', '.quantity-input', function() {
        if (PosCart.updatePaymentButtonsState) PosCart.updatePaymentButtonsState();
    });

    // Initial validation on page load
    $(document).ready(function() {
        if (PosCart.updatePaymentButtonsState) PosCart.updatePaymentButtonsState();
    });

    // ==================== CASH BUTTON ====================
    $('#cashButton').on('click', function() {
        const button = this;
        $(button).html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        window.preventDoubleClick(button, () => {
            if (window.isEditing) {
                const saleData = {
                    payment_status: window.originalSaleData?.payment_status,
                    total_paid: window.originalSaleData?.total_paid,
                    final_total: window.originalSaleData?.final_total
                };

                if (!window.validatePaymentMethodCompatibility('cash', saleData)) {
                    window.enableButton(button);
                    return;
                }
            }

            if (!PosCart.validateAllQuantities || !PosCart.validateAllQuantities()) {
                toastr.error('Please fix the invalid quantities (red borders) before processing the payment.');
                window.enableButton(button);
                return;
            }

            const saleData = gatherSaleData('final');
            if (!saleData) {
                window.enableButton(button);
                return;
            }

            const customerId = $('#customer-id').val();
            const totalAmount = saleData.final_total;
            let amountGiven = parseFormattedAmount($('#amount-given').val().trim());

            const isWalkInCustomer = customerId == 1;

            if (isNaN(amountGiven) || amountGiven <= 0) {
                amountGiven = totalAmount;
            }


            let paidAmount = amountGiven;
            let balance = amountGiven - totalAmount;

            if (amountGiven > totalAmount) {
                paidAmount = totalAmount;
                balance = amountGiven - totalAmount;
            }

            saleData.amount_given = amountGiven;
            saleData.balance_amount = Math.max(0, balance);

            const excessPaymentAction = sessionStorage.getItem('excessPaymentAction');
            const excessPaymentAmount = parseFloat(sessionStorage.getItem('excessPaymentAmount')) || 0;

            if (excessPaymentAction === 'save_advance' && excessPaymentAmount > 0 && balance > 0) {
                saleData.save_excess_as_advance = true;
                saleData.excess_amount = excessPaymentAmount;
            } else {
                saleData.save_excess_as_advance = false;
            }

            sessionStorage.removeItem('excessPaymentAction');
            sessionStorage.removeItem('excessPaymentAmount');

            if (isWalkInCustomer && paidAmount < totalAmount) {
                toastr.error("Partial payment is not allowed for Walk-In Customer.");
                window.enableButton(button);
                return;
            }

            saleData.payments = [{
                payment_method: 'cash',
                payment_date: new Date().toISOString().slice(0, 10),
                amount: paidAmount
            }];

            if (paidAmount >= totalAmount) {
                sendSaleData(saleData, null, () => window.enableButton(button));
            } else {
                swal({
                    title: "Partial Payment",
                    text: "You're making a partial payment of Rs. " +
                        formatAmountWithSeparators(paidAmount.toFixed(2)) +
                        ". The remaining Rs. " +
                        formatAmountWithSeparators((totalAmount - paidAmount).toFixed(2)) +
                        " will be due later.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Proceed",
                    cancelButtonText: "Cancel"
                }, function(isConfirm) {
                    if (isConfirm) {
                        sendSaleData(saleData, null, () => window.enableButton(button));
                    } else {
                        window.enableButton(button);
                    }
                });
            }
        });
    });

    // ==================== SHIPPING ====================
    $('#shippingButton').on('click', function() {
        openShippingModal();
    });

    $('#shippingCharges').on('input', function() {
        const shippingCharges = parseFloat($(this).val()) || 0;
        const totalWithCurrentShipping = parseFormattedAmount($('#final-total-amount').text());
        const currentShippingCharges = parseFloat(window.shippingData.shipping_charges) || 0;
        const subtotalWithoutShipping = totalWithCurrentShipping - currentShippingCharges;

        $('#modalShippingCharges').text(formatCurrency(shippingCharges));
        $('#modalTotalWithShipping').text(formatCurrency(subtotalWithoutShipping + shippingCharges));
    });

    $('#updateShipping').on('click', function() {
        updateShippingData();
    });

    // ==================== CARD BUTTON ====================
    $('#cardButton').on('click', function() {
        $('#cardModal').modal('show');
    });

    $('#confirmCardPayment').on('click', function() {
        const button = this;
        window.preventDoubleClick(button, () => {
            if (window.isEditing) {
                const saleData = {
                    payment_status: window.originalSaleData?.payment_status,
                    total_paid: window.originalSaleData?.total_paid,
                    final_total: window.originalSaleData?.final_total
                };

                if (!window.validatePaymentMethodCompatibility('card', saleData)) {
                    window.enableButton(button);
                    return;
                }
            }

            if (!PosCart.validateAllQuantities || !PosCart.validateAllQuantities()) {
                toastr.error('Please fix the invalid quantities (red borders) before processing the payment.');
                window.enableButton(button);
                return;
            }

            const saleData = gatherSaleData('final');
            if (!saleData) {
                window.enableButton(button);
                return;
            }

            const excessPaymentAction = sessionStorage.getItem('excessPaymentAction');
            const excessPaymentAmount = parseFloat(sessionStorage.getItem('excessPaymentAmount')) || 0;

            if (excessPaymentAction === 'save_advance' && excessPaymentAmount > 0) {
                saleData.save_excess_as_advance = true;
                saleData.excess_amount = excessPaymentAmount;
            }

            sessionStorage.removeItem('excessPaymentAction');
            sessionStorage.removeItem('excessPaymentAmount');

            saleData.payments = gatherCardPaymentData();
            sendSaleData(saleData, null, () => {
                $('#cardModal').modal('hide');
                resetCardModal();
                window.enableButton(button);
            });
        });
    });

    // ==================== CHEQUE BUTTON ====================
    $('#chequeButton').on('click', function() {
        const customerId = $('#customer-id').val();
        if (customerId == 1) {
            toastr.error('Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.');
            return;
        }
        $('#chequeModal').modal('show');
    });

    $('#confirmChequePayment').on('click', function() {
        const button = this;
        window.preventDoubleClick(button, () => {
            if (!PosCart.validateAllQuantities || !PosCart.validateAllQuantities()) {
                toastr.error('Please fix the invalid quantities (red borders) before processing the payment.');
                window.enableButton(button);
                return;
            }

            const customerId = $('#customer-id').val();
            if (customerId == 1) {
                toastr.error('Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.');
                window.enableButton(button);
                return;
            }

            if (!validateChequeFields()) {
                window.enableButton(button);
                return;
            }

            const saleData = gatherSaleData('final');
            if (!saleData) {
                window.enableButton(button);
                return;
            }

            saleData.payments = gatherChequePaymentData();
            sendSaleData(saleData, null, () => {
                $('#chequeModal').modal('hide');
                resetChequeModal();
                window.enableButton(button);
            });
        });
    });

    // ==================== CREDIT SALE ====================
    $('#creditSaleButton').on('click', function() {
        const button = this;
        window.preventDoubleClick(button, () => {
            if (!PosCart.validateAllQuantities || !PosCart.validateAllQuantities()) {
                toastr.error('Please fix the invalid quantities (red borders) before processing the payment.');
                window.enableButton(button);
                return;
            }

            const customerId = $('#customer-id').val();
            if (customerId == 1) {
                toastr.error('Credit sale is not allowed for Walking Customer. Please choose another customer.');
                window.enableButton(button);
                return;
            }

            const saleData = gatherSaleData('final');
            if (!saleData) {
                window.enableButton(button);
                return;
            }

            const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());

            saleData.payments = [];
            saleData.amount_given = 0;
            saleData.balance_amount = 0;

            sendSaleData(saleData, null, () => window.enableButton(button));
        });
    });

    // ==================== SUSPEND ====================
    $('#suspendModal').on('click', '#confirmSuspend', function() {
        const saleData = gatherSaleData('suspend');
        if (!saleData) {
            return;
        }

        sendSaleData(saleData);
        let modal = bootstrap.Modal.getInstance(document.getElementById("suspendModal"));
        modal.hide();
    });

    // ==================== JOB TICKET ====================
    $('#jobTicketButton').on('click', function() {
        const customerId = $('#customer-id').val();
        if (!customerId || customerId === "1") {
            toastr.error('Please select a valid customer (not Walk-In) before creating a job ticket.');
            return;
        }

        $.ajax({
            url: '/customer-get-by-id/' + customerId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 200 && response.customer) {
                    const customer = response.customer;
                    const fullName = [customer.prefix, customer.first_name, customer.last_name].filter(Boolean).join(' ');
                    $('#customerName').val(fullName || '');
                    $('#customerMobile').val(customer.mobile_no || '');
                    $('#customerEmail').val(customer.email || '');
                    $('#customerAddress').val(customer.address || '');
                } else {
                    toastr.error('Failed to fetch customer details.');
                    $('#customerName').val('');
                    $('#customerMobile').val('');
                    $('#customerEmail').val('');
                    $('#customerAddress').val('');
                }

                const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());
                $('#totalAmountInput').val(totalAmount.toFixed(2));

                let amountGiven = parseFormattedAmount($('#amount-given').val().trim());
                $('#advanceAmountInput').val((isNaN(amountGiven) ? 0 : amountGiven).toFixed(2));

                calculateJobTicketBalance();
                $('#jobTicketModal').modal('show');
            },
            error: function() {
                toastr.error('Error fetching customer details.');
                $('#customerName').val('');
                $('#customerMobile').val('');
                $('#customerEmail').val('');
                $('#customerAddress').val('');
            }
        });
    });

    $('#advanceAmountInput').on('input', function() {
        calculateJobTicketBalance();
    });

    $('#submitJobTicket').on('click', function() {
        const saleData = gatherSaleData('jobticket');
        if (!saleData) {
            return;
        }

        saleData.customer_name = $('#customerName').val();
        saleData.customer_mobile = $('#customerMobile').val();
        saleData.customer_email = $('#customerEmail').val();
        saleData.customer_address = $('#customerAddress').val();

        saleData.advance_amount = parseFloat($('#advanceAmountInput').val()) || 0;
        let balanceAmount = parseFloat($('#balanceAmountInput').val()) || 0;
        if (balanceAmount < 0) balanceAmount = 0;
        saleData.balance_amount = balanceAmount;
        saleData.total_paid = saleData.advance_amount;
        saleData.amount_given = saleData.advance_amount;
        saleData.jobticket_description = $('#description').val();

        sendSaleData(saleData, null, function() {
            $('#jobTicketModal').modal('hide');
        });
    });

    // ==================== FINALIZE PAYMENT (Multiple Pay) ====================
    document.getElementById('finalize_payment').addEventListener('click', function() {
        if (!PosCart.validateAllQuantities || !PosCart.validateAllQuantities()) {
            toastr.error('Please fix the invalid quantities (red borders) before processing the payment.');
            return;
        }

        const saleData = gatherSaleData('final');
        if (!saleData) {
            return;
        }

        let paymentData = gatherPaymentData();
        let amountGiven = paymentData.reduce((sum, pay) => sum + (parseFloat(pay.amount) || 0), 0);
        const finalTotal = parseFormattedAmount(document.getElementById('modal-total-payable').textContent);
        const excess = amountGiven - finalTotal;
        const customerId = $('#customer-id').val();
        const isWalkInCustomer = customerId == 1;

        // If there is no excess, proceed as usual
        if (excess <= 0.0001) {
            const totalPaid = Math.min(amountGiven, finalTotal);
            const balanceAmount = Math.max(0, finalTotal - amountGiven);

            saleData.payments = paymentData;
            saleData.amount_given = amountGiven;
            saleData.total_paid = totalPaid;
            saleData.balance_amount = balanceAmount;

            sendSaleData(saleData);
            let modal = bootstrap.Modal.getInstance(document.getElementById("paymentModal"));
            if (modal) modal.hide();
            return;
        }

        const formattedBalance = formatAmountWithSeparators(excess.toFixed(2));

        if (isWalkInCustomer) {
            // For Walk‑In customer, always treat extra as change to return,
            // and only record the actual bill amount as paid.
            swal({
                title: "Return Amount",
                text: `<div style="text-align: center; font-size: 24px; font-weight: bold; color: #2ecc71; margin: 20px 0;">
                          <div style="font-size: 18px; color: #7f8c8d; margin-bottom: 10px;">Balance amount to be returned</div>
                          <div style="font-size: 32px; color: #e74c3c;">Rs. ${formattedBalance}</div>
                       </div>`,
                html: true,
                type: "info",
                showCancelButton: false,
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: true,
                closeOnEsc: true
            }, function() {
                const adjusted = adjustCashPaymentForExcess(paymentData, finalTotal);
                paymentData = adjusted.paymentData;
                amountGiven = adjusted.amountGiven;

                const totalPaid = Math.min(amountGiven, finalTotal);

                saleData.payments = paymentData;
                saleData.amount_given = totalPaid;
                saleData.total_paid = totalPaid;
                saleData.balance_amount = 0;

                sendSaleData(saleData);
                let modal = bootstrap.Modal.getInstance(document.getElementById("paymentModal"));
                if (modal) modal.hide();
            });
        } else {
            // Named customer: let user choose between returning cash or saving as advance
            let userMadeChoice = false;

            swal({
                title: "Excess Payment Received",
                text: `<div style="text-align: center; margin: 20px 0;">
                          <div style="font-size: 18px; color: #7f8c8d; margin-bottom: 10px;">Excess Amount Received</div>
                          <div style="font-size: 32px; color: #e74c3c; margin-bottom: 20px;">Rs. ${formattedBalance}</div>
                          <div style="font-size: 16px; color: #34495e; margin-top: 15px; margin-bottom: 20px;">
                              How would you like to handle this amount?
                          </div>
                          <div style="display: flex; gap: 12px; justify-content: center; margin-top: 20px;">
                              <button id="btnReturnCashMultiple" type="button" style="padding: 12px 24px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; background-color: #27ae60; color: white; font-weight: bold;">
                                  Return Cash
                              </button>
                              <button id="btnSaveAdvanceMultiple" type="button" style="padding: 12px 24px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; background-color: #3498db; color: white; font-weight: bold;">
                                  Save as Advance
                              </button>
                          </div>
                       </div>`,
                html: true,
                type: "warning",
                showConfirmButton: false,
                showCancelButton: false,
                allowOutsideClick: false,
                allowEscapeKey: true,
                closeOnEsc: true
            });

            setTimeout(function() {
                $('#btnReturnCashMultiple').on('click', function() {
                    if (userMadeChoice) return;
                    userMadeChoice = true;
                    swal.close();

                    const adjusted = adjustCashPaymentForExcess(paymentData, finalTotal);
                    paymentData = adjusted.paymentData;
                    amountGiven = adjusted.amountGiven;
                    const totalPaid = Math.min(amountGiven, finalTotal);

                    saleData.payments = paymentData;
                    saleData.amount_given = totalPaid;
                    saleData.total_paid = totalPaid;
                    saleData.balance_amount = 0;
                    saleData.save_excess_as_advance = false;

                    toastr.info(`Rs. ${formattedBalance} will be returned to customer`, 'Return Cash');

                    sendSaleData(saleData);
                    let modal = bootstrap.Modal.getInstance(document.getElementById("paymentModal"));
                    if (modal) modal.hide();
                });

                $('#btnSaveAdvanceMultiple').on('click', function() {
                    if (userMadeChoice) return;
                    userMadeChoice = true;
                    swal.close();

                    const totalPaid = Math.min(amountGiven, finalTotal);

                    saleData.payments = paymentData;
                    saleData.amount_given = amountGiven;
                    saleData.total_paid = totalPaid;
                    saleData.balance_amount = Math.max(0, amountGiven - finalTotal);
                    saleData.save_excess_as_advance = true;
                    saleData.excess_amount = Math.max(0, amountGiven - finalTotal);

                    toastr.success(`Rs. ${formattedBalance} will be saved as customer advance credit`, 'Advance Credit');

                    sendSaleData(saleData);
                    let modal = bootstrap.Modal.getInstance(document.getElementById("paymentModal"));
                    if (modal) modal.hide();
                });
            }, 100);
        }
    });

    // Payment modal open listener
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('show.bs.modal', function() {
            if (PosCart.updateTotals) PosCart.updateTotals();
        });
    }

    // ==================== SUSPENDED SALES ====================
    $('#pauseCircleButton').on('click', function() {
        fetchSuspendedSales();
    });

    // ==================== AMOUNT GIVEN ====================
    $('#amount-given').on('input', function() {
        let amountGiven = parseFormattedAmount($(this).val().trim());

        if (isNaN(amountGiven) || amountGiven < 0) {
            amountGiven = 0;
        }

        const allPaymentButtons = [
            '#cardButton',
            '#chequeButton',
            '#creditSaleButton',
            '#multiplePayButton',
            '#pauseCircleButton'
        ].join(',');

        if (amountGiven === 0) {
            $(this).val('');
            $(allPaymentButtons).prop('disabled', false);
        } else {
            $(this).val(formatAmountWithSeparators(amountGiven));
            $(allPaymentButtons).prop('disabled', true);
        }
    });

    $('#amount-given').on('keyup', function(event) {
        if (event.key === 'Enter') {
            if (isProcessingAmountGiven) {
                return;
            }
            isProcessingAmountGiven = true;

            const totalAmount = parseFormattedAmount($('#final-total-amount').text().trim());
            const amountGiven = parseFormattedAmount($('#amount-given').val().trim());

            if (isNaN(amountGiven) || amountGiven <= 0) {
                toastr.error('Please enter a valid amount given by the customer.');
                isProcessingAmountGiven = false;
                return;
            }

            const balance = amountGiven - totalAmount;

            if (balance > 0) {
                const formattedBalance = formatAmountWithSeparators(balance.toFixed(2));
                const customerId = $('#customer-id').val();
                const isWalkInCustomer = customerId == 1;

                if (isWalkInCustomer) {
                    swal({
                        title: "Return Amount",
                        text: `<div style="text-align: center; font-size: 24px; font-weight: bold; color: #2ecc71; margin: 20px 0;">
                                  <div style="font-size: 18px; color: #7f8c8d; margin-bottom: 10px;">Balance amount to be returned</div>
                                  <div style="font-size: 32px; color: #e74c3c;">Rs. ${formattedBalance}</div>
                               </div>`,
                        html: true,
                        type: "info",
                        showCancelButton: false,
                        confirmButtonText: "OK",
                        allowOutsideClick: false,
                        allowEscapeKey: true,
                        closeOnEsc: true
                    }, function(isConfirm) {
                        isProcessingAmountGiven = false;
                        if (isConfirm === true) {
                            $('#cashButton').trigger('click');
                        } else {
                        }
                    });
                } else {
                    let userMadeChoice = false;

                    swal({
                        title: "Excess Payment Received",
                        text: `<div style="text-align: center; margin: 20px 0;">
                                  <div style="font-size: 18px; color: #7f8c8d; margin-bottom: 10px;">Excess Amount Received</div>
                                  <div style="font-size: 32px; color: #e74c3c; margin-bottom: 20px;">Rs. ${formattedBalance}</div>
                                  <div style="font-size: 16px; color: #34495e; margin-top: 15px; margin-bottom: 20px;">
                                      How would you like to handle this amount?
                                  </div>
                                  <div style="display: flex; gap: 12px; justify-content: center; margin-top: 20px;">
                                      <button id="btnReturnCash" type="button" style="padding: 12px 24px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; background-color: #27ae60; color: white; font-weight: bold;">
                                          💵 Return Cash
                                      </button>
                                      <button id="btnSaveAdvance" type="button" style="padding: 12px 24px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; background-color: #3498db; color: white; font-weight: bold;">
                                          💰 Save as Advance
                                      </button>
                                  </div>
                               </div>`,
                        html: true,
                        type: "warning",
                        showConfirmButton: false,
                        showCancelButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: true,
                        closeOnEsc: true
                    });

                    const swalOverlay = document.querySelector('.sweet-overlay');
                    if (swalOverlay) {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'style' || mutation.attributeName === 'class') {
                                    const overlay = document.querySelector('.sweet-overlay');
                                    const isHidden = !overlay || overlay.style.display === 'none' ||
                                                     !overlay.classList.contains('sweet-overlay-visible') ||
                                                     getComputedStyle(overlay).display === 'none';
                                    if (isHidden && !userMadeChoice) {
                                        isProcessingAmountGiven = false;
                                        sessionStorage.removeItem('excessPaymentAction');
                                        sessionStorage.removeItem('excessPaymentAmount');
                                        observer.disconnect();
                                    }
                                }
                            });
                        });
                        observer.observe(swalOverlay, { attributes: true });
                    }

                    setTimeout(function() {
                        $('#btnReturnCash').on('click', function() {
                            userMadeChoice = true;
                            swal.close();
                            isProcessingAmountGiven = false;
                            sessionStorage.setItem('excessPaymentAction', 'return_cash');
                            sessionStorage.setItem('excessPaymentAmount', balance);
                            toastr.info(`Rs. ${formattedBalance} will be returned to customer`, '💵 Return Cash');
                            $('#cashButton').trigger('click');
                        });

                        $('#btnSaveAdvance').on('click', function() {
                            userMadeChoice = true;
                            swal.close();
                            isProcessingAmountGiven = false;
                            sessionStorage.setItem('excessPaymentAction', 'save_advance');
                            sessionStorage.setItem('excessPaymentAmount', balance);
                            toastr.success(`Rs. ${formattedBalance} will be saved as customer advance credit`, '💰 Advance Credit');
                            $('#cashButton').trigger('click');
                        });
                    }, 100);
                }
            } else {
                isProcessingAmountGiven = false;
                $('#cashButton').trigger('click');
            }
        }
    });

    // ==================== QUOTATION / DRAFT / SALE ORDER / CANCEL ====================
    const quotationButton = document.getElementById('quotationButton');
    if (quotationButton) {
        quotationButton.addEventListener('click', function() {
            if (window.isEditing && window.isEditingFinalizedSale) {
                toastr.error('Cannot create Quotation while editing a finalized sale with invoice. Please save or cancel the edit first.', 'Finalized Sale');
                return;
            }

            const saleData = gatherSaleData('quotation');
            if (!saleData) return;
            sendSaleData(saleData, window.currentEditingSaleId);
        });
    }

    const draftButton = document.getElementById('draftButton');
    if (draftButton) {
        draftButton.addEventListener('click', function() {
            if (window.isEditing && window.isEditingFinalizedSale) {
                toastr.error('Cannot create Draft while editing a finalized sale with invoice. Please save or cancel the edit first.', 'Finalized Sale');
                return;
            }

            const saleData = gatherSaleData('draft');
            if (!saleData) return;
            sendSaleData(saleData, window.currentEditingSaleId);
        });
    }

    // Sale Order Button Handler
    const saleOrderButton = document.getElementById('saleOrderButton');
    if (saleOrderButton) {
        saleOrderButton.addEventListener('click', function() {
            if (window.isEditing && window.isEditingFinalizedSale) {
                toastr.error('Cannot create Sale Order while editing a finalized sale with invoice. Please save or cancel the edit first.', 'Finalized Sale');
                return;
            }

            const productRows = $('#billing-body tr');
            if (productRows.length === 0) {
                toastr.error('Please add at least one product to create a sale order.');
                return;
            }

            const customerId = $('#customer-id').val();
            const customerText = $('#customer-id option:selected').text();

            if (!customerId || customerId == '1' || customerText.toLowerCase().includes('walk-in')) {
                toastr.error('Sale Orders cannot be created for Walk-In customers. Please select a valid customer.');
                return;
            }

            const saleOrderModal = new bootstrap.Modal(document.getElementById('saleOrderModal'));
            saleOrderModal.show();
        });
    }

    // Confirm Sale Order Button Handler
    const confirmSaleOrderButton = document.getElementById('confirmSaleOrder');
    if (confirmSaleOrderButton) {
        confirmSaleOrderButton.addEventListener('click', function() {
            const expectedDeliveryDate = document.getElementById('expectedDeliveryDate').value;
            const orderNotes = document.getElementById('orderNotes').value.trim();

            if (!expectedDeliveryDate) {
                toastr.error('Please select an expected delivery date.');
                return;
            }

            const selectedDate = new Date(expectedDeliveryDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                toastr.error('Expected delivery date cannot be in the past.');
                return;
            }

            const saleData = gatherSaleData('final', {
                allowStockOverflow: canUseSaleOrderBackorderValidationBypass(),
            });
            if (!saleData) return;

            saleData.transaction_type = 'sale_order';
            saleData.order_status = 'pending';
            saleData.expected_delivery_date = expectedDeliveryDate;
            saleData.order_notes = orderNotes;
            saleData.status = 'final';

            delete saleData.payments;

            sendSaleData(saleData, window.currentEditingSaleId);

            const saleOrderModal = bootstrap.Modal.getInstance(document.getElementById('saleOrderModal'));
            if (saleOrderModal) {
                saleOrderModal.hide();
            }

            document.getElementById('expectedDeliveryDate').value = '';
            document.getElementById('orderNotes').value = '';
        });
    }

    const cancelButton = document.getElementById('cancelButton');
    if (cancelButton) {
        cancelButton.addEventListener('click', resetForm);
    }

}); // end $(document).ready

// ================================================================
//  Window bridges for external callers
// ================================================================
window.fetchEditSale = fetchEditSale;
window.resetForm = resetForm;
