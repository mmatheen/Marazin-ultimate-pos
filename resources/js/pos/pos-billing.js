'use strict';

/**
 * POS Billing Body — Phase 17
 *
 * Extracted from pos_ajax.blade.php.
 * Responsible for adding product rows to the billing table,
 * including discount logic, IMEI handling, price-history icon,
 * merge-vs-new-row logic, and edit-mode preservation.
 *
 * Dependencies (all on window):
 *   pos-config.blade.php  — miscItemProductId, showFreeQtyColumn,
 *                            priceValidationEnabled, canEditDiscount, canEditUnitPrice
 *   pos-customer.js       — getCurrentCustomer, logPricingError, getCustomerTypePrice
 *   pos-product-select.js — normalizeBatches, getCustomerPreviousPrice
 *   pos-ui.js             — getSafeImageUrl
 *   pos-utils.js          — formatAmountWithSeparators
 *   pos-cart.js            — attachRowEventListeners, disableConflictingDiscounts, updateTotals
 *   pos_ajax (block 5)    — showPriceHistoryModal
 *
 * Public API:
 *   window.addProductToBillingBody              (legacy)
 *   window.Pos.Billing.addProductToBillingBody  (preferred for new code)
 */

'use strict';

/**
 * POS Billing Body — Phase 17
 *
 * Extracted from pos_ajax.blade.php.
 * Responsible for adding product rows to the billing table,
 * including discount logic, IMEI handling, price-history icon,
 * merge-vs-new-row logic, and edit-mode preservation.
 *
 * Dependencies (all on window):
 *   pos-config.blade.php  — miscItemProductId, showFreeQtyColumn,
 *                            priceValidationEnabled, canEditDiscount, canEditUnitPrice
 *   pos-customer.js       — getCurrentCustomer, logPricingError, getCustomerTypePrice
 *   pos-product-select.js — normalizeBatches, getCustomerPreviousPrice
 *   pos-ui.js             — getSafeImageUrl
 *   pos-utils.js          — formatAmountWithSeparators
 *   pos-cart.js            — attachRowEventListeners, disableConflictingDiscounts, updateTotals
 *   pos_ajax (block 5)    — showPriceHistoryModal
 *
 * Public API:
 *   window.addProductToBillingBody              (legacy)
 *   window.Pos.Billing.addProductToBillingBody  (preferred for new code)
 */

// POS namespace (safe to call multiple times)
window.Pos = window.Pos || {};
window.Pos.Billing = window.Pos.Billing || {};

// Local aliases for namespaced modules
const _posCustomer = window.Pos.Customer || {};
const _posCart     = window.Pos.Cart || {};

const getCurrentCustomer   = _posCustomer.getCurrentCustomer;
const logPricingError      = _posCustomer.logPricingError;
const getCustomerTypePrice = _posCustomer.getCustomerTypePrice;
const updateTotals         = _posCart.updateTotals || function () {};

// ── Helper: compute final price & discounts ─────────────────
function computeFinalPriceAndDiscount({
    product,
    batch,
    price,
    discountType,
    discountAmount,
    activeDiscount,
    isEditing,
    currentEditingSaleId
}) {
    let finalPrice      = parseFloat(price);
    let discountFixed   = 0;
    let discountPercent = 0;

    let effectiveMRP = (batch && batch.max_retail_price)
        ? parseFloat(batch.max_retail_price)
        : parseFloat(product.max_retail_price);

    if (!isFinite(effectiveMRP) || isNaN(effectiveMRP)) {
        effectiveMRP = finalPrice;
    }

    const defaultFixedDiscount = effectiveMRP - finalPrice;

    if (isEditing && currentEditingSaleId && (discountType && discountAmount !== null)) {
        if (discountType === 'fixed') {
            discountFixed = parseFloat(discountAmount);
            finalPrice    = finalPrice;
        } else if (discountType === 'percentage') {
            discountPercent = parseFloat(discountAmount) || 0;
            finalPrice      = finalPrice;
        }
    } else {
        if (discountType && discountAmount !== null) {
            if (discountType === 'fixed') {
                discountFixed = parseFloat(discountAmount);
                finalPrice    = effectiveMRP - discountFixed;
                if (finalPrice < 0) finalPrice = 0;
            } else if (discountType === 'percentage') {
                discountPercent = parseFloat(discountAmount) || 0;
                finalPrice      = effectiveMRP * (1 - (discountPercent || 0) / 100);
            }
        } else if (activeDiscount) {
            if (activeDiscount.type === 'percentage') {
                discountPercent = activeDiscount.amount || 0;
                finalPrice      = effectiveMRP * (1 - (discountPercent || 0) / 100);
            } else if (activeDiscount.type === 'fixed') {
                discountFixed = activeDiscount.amount;
                finalPrice    = effectiveMRP - discountFixed;
                if (finalPrice < 0) finalPrice = 0;
            }
        } else {
            discountFixed   = defaultFixedDiscount;
            discountPercent = (effectiveMRP !== 0)
                ? (discountFixed / effectiveMRP) * 100
                : 0;
            finalPrice      = finalPrice;
        }
    }

    if (!isFinite(finalPrice) || isNaN(finalPrice)) {
        finalPrice = 0;
    }
    if (!isFinite(discountFixed) || isNaN(discountFixed)) {
        discountFixed = 0;
    }
    if (!isFinite(discountPercent) || isNaN(discountPercent)) {
        discountPercent = 0;
    }

    return { finalPrice, discountFixed, discountPercent };
}

// ── Helper: build FREE qty display HTML ─────────────────────
function buildFreeQtyDisplay(batch) {
    if (batch && batch.free_qty && parseFloat(batch.free_qty) > 0) {
        const freeQtyValue = parseFloat(batch.free_qty);
        const displayValue = freeQtyValue % 1 === 0
            ? Math.floor(freeQtyValue)
            : freeQtyValue.toFixed(2).replace(/\.?0+$/, '');
        return `<div style="font-size: 0.75em; color: #28a745; font-weight: 600; margin-top: 2px;">FREE: ${displayValue}</div>`;
    }

    return `<div style="font-size: 0.75em; color: #888; margin-top: 2px;">Free</div>`;
}

// ── Helper: try merge with existing row (non-IMEI) ──────────
function mergeExistingRowIfPossible({
    billingBody,
    product,
    batchId,
    finalPrice,
    saleQuantity,
    allowDecimal,
    adjustedBatchQuantity,
    isEditing
}) {
    const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
        const rowProductId = row.getAttribute('data-product-id')
            ?? row.querySelector('.product-id')?.textContent?.trim();
        const batchIdElement     = row.querySelector('.batch-id');
        const priceInputElement  = row.querySelector('.price-input');

        if (!rowProductId || !batchIdElement || !priceInputElement) return false;

        const rowBatchId = batchIdElement.textContent.trim();
        const rowPrice   = priceInputElement.value.trim();

        // Cash Items never merge
        if (miscItemProductId && rowProductId == String(miscItemProductId)) return false;

        return (
            rowProductId == product.id &&
            rowBatchId == batchId &&
            parseFloat(rowPrice).toFixed(2) === finalPrice.toFixed(2)
        );
    });

    if (!existingRow) return false;

    const quantityInput = existingRow.querySelector('.quantity-input');
    let currentQty = allowDecimal
        ? parseFloat(quantityInput.value)
        : parseInt(quantityInput.value, 10);

    let newQuantity;
    const isMobileUpdate = saleQuantity > 1 && currentQty > 0;
    if (isMobileUpdate) {
        newQuantity = saleQuantity;
    } else {
        newQuantity = currentQty + saleQuantity;
    }

    let maxAllowed = adjustedBatchQuantity;
    if (isEditing) {
        const rowMaxQty = existingRow.getAttribute('data-max-quantity');
        if (rowMaxQty) {
            maxAllowed = allowDecimal ? parseFloat(rowMaxQty) : parseInt(rowMaxQty, 10);
        }
    }

    if (newQuantity > maxAllowed && product.stock_alert !== 0) {
        toastr.error(`You cannot add more than ${maxAllowed} units of this product.`, 'Warning');
        return true;
    }

    quantityInput.value = allowDecimal
        ? newQuantity.toFixed(4).replace(/\.?0+$/, '')
        : newQuantity;

    const subtotalElement = existingRow.querySelector('.subtotal');
    const updatedSubtotal = newQuantity * finalPrice;
    subtotalElement.textContent = formatAmountWithSeparators(updatedSubtotal.toFixed(2));

    billingBody.insertBefore(existingRow, billingBody.firstChild);

    existingRow.style.transition = 'background-color 0.3s ease';
    existingRow.style.backgroundColor = '#fff3cd';
    setTimeout(() => { existingRow.style.backgroundColor = ''; }, 800);

    updateTotals();
    return true;
}

// ── price-history CSS (injected once) ───────────────────────
let priceHistoryStylesInjected = false;
function injectPriceHistoryStyles() {
    if (priceHistoryStylesInjected || document.getElementById('price-history-styles')) return;
    priceHistoryStylesInjected = true;
    const style = document.createElement('style');
    style.id = 'price-history-styles';
    style.textContent = `
        .price-history-badge {
            cursor: pointer !important;
            transition: all 0.2s ease;
        }
        .price-history-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .price-history-icon {
            cursor: pointer !important;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .price-history-icon:hover {
            background-color: rgba(23, 162, 184, 0.1);
            transform: scale(1.2);
        }
    `;
    document.head.appendChild(style);
}

// ── Main function ───────────────────────────────────────────
async function addProductToBillingBody(
    product, stockEntry, price, batchId, batchQuantity, priceType,
    saleQuantity = 1, imeis = [], discountType = null, discountAmount = null,
    selectedBatch = null, editFreeQuantity = 0, isLoadingExisting = false, customName = null
) {
    // Warning for specific batch IDs that might cause stock issues
    if (batchId && batchId !== 'all' && batchId !== '' && imeis.length === 0) {
        console.warn('⚠️ NON-IMEI product using specific batch ID:', batchId, 'for product:', product.product_name);
        console.warn('This might cause "Insufficient stock" errors. Consider using "all" for FIFO method.');
    }

    // Get customer previous price data - DON'T WAIT, fetch in background
    const currentCustomer = getCurrentCustomer();

    const billingBody = document.getElementById('billing-body');
    let locationId = window.selectedLocationId || 1;

    const isEditing            = window.isEditing;
    const currentEditingSaleId = window.currentEditingSaleId;

    // Use selectedBatch if provided; fallback to stockEntry batch
    let batch = selectedBatch || window.normalizeBatches(stockEntry).find(b => b.id === parseInt(batchId));

    // If batchId is "all" or batch not found, use the latest available batch for MRP
    if (!batch && (batchId === "all" || batchId === "" || batchId === null)) {
        const batchesArray = window.normalizeBatches(stockEntry);
        batch = batchesArray.length > 0 ? batchesArray[0] : null;
    }

    // *** CRITICAL FIX: In edit mode, preserve original sale price ***
    if (isEditing && currentEditingSaleId) {
        price = parseFloat(price);
    } else {
        price = parseFloat(price);
    }

    if (isNaN(price) || price <= 0) {
        const errorContext = isEditing ? 'original sale data' : 'current customer pricing';
        console.error('Invalid price for product:', product.product_name, 'Price:', price, 'Context:', errorContext);

        if (isEditing) {
            toastr.error(
                `Invalid price data in original sale for product: ${product.product_name}. Cannot edit this product.`,
                'Edit Error');
        } else {
            const cust = getCurrentCustomer();
            toastr.error(
                `This product has no valid price configured for ${cust.customer_type} customers. Please contact admin to fix pricing.`,
                'Pricing Error');
            logPricingError(product, cust.customer_type, batch);
        }
        return;
    }

    const activeDiscount = stockEntry.discounts?.find(d => d.is_active && !d.is_expired) || null;

    const {
        finalPrice,
        discountFixed,
        discountPercent
    } = computeFinalPriceAndDiscount({
        product,
        batch,
        price,
        discountType,
        discountAmount,
        activeDiscount,
        isEditing,
        currentEditingSaleId
    });

    let adjustedBatchQuantity = batchQuantity;
    if (batchId === "all") {
        adjustedBatchQuantity = (parseFloat(stockEntry.total_stock) || 0)
            + (parseFloat(stockEntry.total_free_stock) || 0);
    } else if (batch && batch.location_batches) {
        const locationBatch = batch.location_batches.find(lb => lb.location_id === locationId);
        if (locationBatch) {
            adjustedBatchQuantity = (parseFloat(locationBatch.quantity) || 0)
                + (parseFloat(locationBatch.free_quantity) || 0);
        }
    }

    // Normalise numeric values to avoid Infinity / NaN in inputs
    if (!isFinite(finalPrice) || isNaN(finalPrice)) {
        finalPrice = 0;
    }
    if (!isFinite(discountFixed) || isNaN(discountFixed)) {
        discountFixed = 0;
    }
    if (!isFinite(discountPercent) || isNaN(discountPercent)) {
        discountPercent = 0;
    }

    // Free qty display text
    let freeQtyDisplayHtml = buildFreeQtyDisplay(batch);

    // In edit mode, trust the backend calculation
    if (isEditing) {
        adjustedBatchQuantity = batchQuantity;
    }

    // Unit metadata
    const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
    const allowDecimal = product.unit
        && (product.unit.allow_decimal === true || product.unit.allow_decimal === 1);

    if (allowDecimal) {
        adjustedBatchQuantity = parseFloat(adjustedBatchQuantity).toFixed(2).replace(/\.?0+$/, '');
    } else {
        adjustedBatchQuantity = parseInt(adjustedBatchQuantity, 10);
    }

    const loadedQtyHasDecimal = isLoadingExisting
        && saleQuantity !== undefined
        && !Number.isInteger(parseFloat(saleQuantity));
    const qtyInputStep    = (allowDecimal || loadedQtyHasDecimal) ? 'any' : '1';
    const qtyInputPattern = (allowDecimal || loadedQtyHasDecimal) ? '[0-9]+([.][0-9]{1,2})?' : '[0-9]*';

    // Initial quantity
    let initialQuantityValue;
    if (saleQuantity !== undefined && saleQuantity > 0 && imeis.length === 0) {
        if (isLoadingExisting || allowDecimal) {
            initialQuantityValue = parseFloat(saleQuantity);
        } else {
            initialQuantityValue = parseInt(saleQuantity, 10);
        }
    } else if (imeis.length > 0) {
        initialQuantityValue = 1;
    } else if (allowDecimal) {
        let availableQty = parseFloat(adjustedBatchQuantity);
        if (availableQty < 1 && availableQty > 0) {
            initialQuantityValue = availableQty.toFixed(2).replace(/\.?0+$/, '');
        } else {
            initialQuantityValue = '1.00';
        }
    } else {
        initialQuantityValue = 1;
    }

    // ── Merge-or-new-row logic (non-IMEI, non-edit only) ────
    if (imeis.length === 0 && !isLoadingExisting) {
        const merged = mergeExistingRowIfPossible({
            billingBody,
            product,
            batchId,
            finalPrice,
            saleQuantity,
            allowDecimal,
            adjustedBatchQuantity,
            isEditing
        });
        if (merged) return;
    }

    // ── Build new row ───────────────────────────────────────
    const row = document.createElement('tr');
    row.setAttribute('data-product-id', product.id);
    row.setAttribute('data-batch-id', batchId);
    row.setAttribute('data-unit-price', finalPrice);
    row.setAttribute('data-price-source', priceType);
    row.setAttribute('data-max-quantity', adjustedBatchQuantity);

    try {
        row.setAttribute('data-product-pricing', JSON.stringify({
            id: product.id,
            product_name: product.product_name,
            retail_price:     product.retail_price     || 0,
            whole_sale_price: product.whole_sale_price || product.wholesale_price || 0,
            special_price:    product.special_price    || 0,
            max_retail_price: product.max_retail_price || 0
        }));
        if (batch) {
            row.setAttribute('data-batch-pricing', JSON.stringify({
                id: batch.id,
                batch_no:         batch.batch_no         || '',
                retail_price:     batch.retail_price     || 0,
                wholesale_price:  batch.wholesale_price  || 0,
                special_price:    batch.special_price    || 0,
                max_retail_price: batch.max_retail_price || 0
            }));
        }
    } catch (e) { /* ignore JSON errors */ }

    // Cash / misc items — editable text input
    const isCashItem = miscItemProductId && product.id == miscItemProductId;
    const displayNameForInput = (customName && customName.trim()) ? customName.trim() : String(product.product_name);
    const nameDisplayHtml = isCashItem
        ? '<input type="text" class="custom-name-input border-0 fw-bold p-0"'
          + ' value="' + displayNameForInput.replace(/"/g, '&quot;') + '"'
          + ' placeholder="Item name"'
          + ' title="Tap to rename this item"'
          + ' style="background:transparent;max-width:170px;font-size:inherit;color:inherit;">'
        : product.product_name;

    row.innerHTML = `
        <td class="text-center counter-cell" style="vertical-align: middle; font-weight: bold; color: #000;"></td>
        <td>
            <div class="d-flex align-items-start">
            <img src="${getSafeImageUrl(product)}"
                 style="width:50px; height:50px; margin-right:10px; border-radius:50%;"
                 class="product-image"
                 title="Unit Cost: ${batch ? (batch.unit_cost || batch.purchase_price || 'N/A') : (product.unit_cost || product.purchase_price || 'N/A')} | Original Price: ${product.original_price || product.purchase_price || 'N/A'}"
                 alt="${product.product_name}"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top"
                 onerror="this.onerror=null; this.src='/assets/images/No Product Image Available.png';"/>
            <div class="product-info" style="min-width: 0; flex: 1;">
            <div class="font-weight-bold product-name" style="word-break: break-word; max-width: 260px; line-height: 1.2;" title="Unit Cost: ${batch ? (batch.unit_cost || batch.purchase_price || 'N/A') : (product.unit_cost || product.purchase_price || 'N/A')} | Original Price: ${product.original_price || product.purchase_price || 'N/A'}">
            ${nameDisplayHtml}
            <span class="badge bg-info ms-1">MRP: ${batch && batch.max_retail_price ? batch.max_retail_price : product.max_retail_price}</span>

            </div>
            <div class="d-flex flex-wrap align-items-center mt-1" style="gap: 10px;">
            <span class="text-muted product-sku" style="font-size: 0.95em; word-break: break-all;">
            SKU: ${product.sku}
            </span>
            <span class="quantity-display ms-2" style="font-size: 0.95em;">
             ${adjustedBatchQuantity} ${unitName}
            </span>
            ${product.is_imei_or_serial_no === 1 ? `<span class="badge bg-info ms-2">IMEI</span>
              <i class="fas fa-info-circle show-imei-btn ms-1" style="cursor: pointer;" title="View/Edit IMEI"></i>` : ''}
            </div>

            </div>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
            <button class="btn btn-danger quantity-minus btn">-</button>
            <input type="number" value="${initialQuantityValue}" max="${adjustedBatchQuantity}" class="form-control quantity-input text-center" title="Available: ${adjustedBatchQuantity}" ${imeis.length > 0 ? 'readonly' : ''} step="${qtyInputStep}" pattern="${qtyInputPattern}" data-quantity="${initialQuantityValue}">
            <button class="btn btn-success quantity-plus btn">+</button>
            </div>
            <div style="font-size: 0.85em; color: #888; text-align:center;">${unitName}</div>
        </td>
        ${showFreeQtyColumn
            ? `<td class="text-center">
            <input type="number" name="free_quantity[]" class="form-control free-quantity-input text-center" value="${editFreeQuantity || 0}" min="0" max="${adjustedBatchQuantity}" placeholder="Free" title="Free items (max: ${adjustedBatchQuantity})" step="${qtyInputStep}" data-max-stock="${adjustedBatchQuantity}" data-original-free-qty="${editFreeQuantity || 0}">
            ${freeQtyDisplayHtml}
            <small style="font-size: 0.7em; color: #666;" class="free-qty-debug">Max: ${adjustedBatchQuantity}</small>
        </td>`
            : `<td class="d-none"><input type="number" name="free_quantity[]" value="0" class="free-quantity-input"></td>`}
        <td class="text-center"><input type="number" name="discount_fixed[]" class="form-control fixed_discount text-center" value="${discountFixed.toFixed(2)}" ${(priceValidationEnabled === 1 && !canEditDiscount && !isEditing) ? 'readonly' : ''}></td>
        <td class="text-center"><input type="number" name="discount_percent[]" class="form-control percent_discount text-center" value="${priceValidationEnabled === 0 ? '' : discountPercent.toFixed(2)}" ${priceValidationEnabled === 0 ? 'readonly' : ((priceValidationEnabled === 1 && !canEditDiscount && !isEditing) ? 'readonly' : '')}></td>
        <td class="text-center">
            <input type="number" value="${finalPrice.toFixed(2)}" class="form-control price-input unit-price text-center"
                data-price="${finalPrice}"
                data-quantity="${adjustedBatchQuantity}"
                data-retail-price="${batch ? batch.retail_price : product.retail_price}"
                data-wholesale-price="${batch ? batch.wholesale_price : (stockEntry.batches?.[0]?.wholesale_price || 0)}"
                data-special-price="${batch ? batch.special_price : (stockEntry.batches?.[0]?.special_price || 0)}"
                data-max-retail-price="${batch ? batch.max_retail_price || product.max_retail_price : product.max_retail_price}"
                min="0" ${(priceValidationEnabled === 1 && !canEditUnitPrice && !isEditing) ? 'readonly' : ''}>
        </td>
        <td class="subtotal total-price text-center" data-total="${(parseFloat(initialQuantityValue) * finalPrice).toFixed(2)}">${formatAmountWithSeparators((parseFloat(initialQuantityValue) * finalPrice).toFixed(2))}</td>
        <td class="text-center"><button class="btn btn-danger btn-sm remove-btn">×</button></td>
        <td class="product-id d-none">${product.id}</td>
        <td class="location-id d-none">${locationId}</td>
        <td class="batch-id d-none">${batchId || 'all'}</td>
        <td class="discount-data d-none">${JSON.stringify(activeDiscount || {})}</td>
        <td class="d-none imei-data">${imeis.join(',') || ''}</td>
    `;

    // Append (edit load) or prepend (new product)
    if (isLoadingExisting) {
        billingBody.appendChild(row);
    } else {
        billingBody.insertBefore(row, billingBody.firstChild);
    }

    // Query elements after DOM insertion
    const qtyDisplayCell = row.querySelector('.quantity-display');
    const quantityInput  = row.querySelector('.quantity-input');
    const plusBtn         = row.querySelector('.quantity-plus');
    const minusBtn       = row.querySelector('.quantity-minus');

    // IMEI display and input restrictions
    if (imeis.length > 0) {
        if (qtyDisplayCell) {
            qtyDisplayCell.textContent = `1 ${unitName} (IMEI: ${imeis[0]})`;
        }
        if (quantityInput) quantityInput.readOnly = true;
        if (plusBtn)  plusBtn.disabled  = true;
        if (minusBtn) minusBtn.disabled = true;
    }

    attachRowEventListeners(row, product, stockEntry);

    // Focus quantity input, Enter → refocus search
    if (quantityInput) {
        quantityInput.focus();
        quantityInput.select();
        quantityInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                const searchInput = document.getElementById('productSearchInput');
                if (searchInput) { searchInput.value = ''; searchInput.focus(); }
            }
        });
    }

    disableConflictingDiscounts(row);

    // Fetch price history in background after row added
    if (currentCustomer && currentCustomer.id && currentCustomer.customer_type !== 'walk-in') {
        window.getCustomerPreviousPrice(currentCustomer.id, product.id).then(priceHistoryData => {
            if (priceHistoryData && priceHistoryData.has_previous_purchases) {
                const priceCell = row.querySelector('.price-input').parentElement;
                if (priceCell && !priceCell.querySelector('.price-history-icon')) {
                    const iconHtml = `
                        <div class="text-center mt-1">
                            <i class="fas fa-chart-line text-info cursor-pointer price-history-icon"
                               title="View price history for this customer"
                               data-product-id="${product.id}"
                               data-product-name="${product.product_name}"
                               data-customer-name="${currentCustomer.first_name || ''} ${currentCustomer.last_name || ''}"
                               style="font-size: 14px; cursor: pointer;">
                            </i>
                         </div>`;
                    priceCell.insertAdjacentHTML('beforeend', iconHtml);

                    const icon = priceCell.querySelector('.price-history-icon');
                    if (icon) {
                        icon.addEventListener('click', async () => {
                            const productId   = icon.getAttribute('data-product-id');
                            const productName = icon.getAttribute('data-product-name');
                            const customerName = icon.getAttribute('data-customer-name');

                            const cust = getCurrentCustomer();
                            if (!cust || !cust.id || cust.customer_type === 'walk-in') {
                                toastr.warning('Price history is only available for registered customers');
                                return;
                            }

                            try {
                                const freshData = await window.getCustomerPreviousPrice(cust.id, productId);
                                if (freshData && freshData.has_previous_purchases) {
                                    showPriceHistoryModal(productName, JSON.stringify(freshData), customerName);
                                } else {
                                    toastr.info('No previous purchase history found');
                                }
                            } catch (error) {
                                console.error('Error fetching price history:', error);
                                toastr.error('Could not load price history');
                            }
                        });
                    }
                }
            }
        }).catch(() => {});
    }

    updateTotals();

    // Bootstrap tooltips for the new row
    try {
        const tooltipTriggerList = row.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });
    } catch (e) { /* ignore */ }

    // Inject price-history CSS once
    injectPriceHistoryStyles();

    // Refocus search input after adding product
    setTimeout(() => {
        const productSearchInput = document.getElementById('productSearchInput');
        if (productSearchInput) { productSearchInput.focus(); productSearchInput.select(); }
    }, 100);
}

// ── Expose ──────────────────────────────────────────────────
// Namespaced API
window.Pos.Billing.addProductToBillingBody = addProductToBillingBody;
