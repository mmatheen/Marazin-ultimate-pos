/**
 * @file pos-cart.js
 * @module POS.Cart
 * @description
 *   Cart row management, totals calculation, price/discount validation,
 *   and quantity button handlers for the POS billing table.
 *
 *   Extracted from resources/views/sell/pos_ajax.blade.php — Phase 10.
 *   Must be loaded BEFORE pos_ajax.blade.php (see @vite order in pos.blade.php).
 *
 * ─────────────────────────────────────────────────────────────────────
 * TABLE OF CONTENTS
 * ─────────────────────────────────────────────────────────────────────
 *  §1  State & Config Accessors
 *  §2  Quantity Helpers            (showQuantityLimitError, validateAllQuantities)
 *  §3  Payment Button State        (updatePaymentButtonsState)
 *  §4  Totals                      (updateTotals)
 *  §5  Discount Helpers            (disableConflictingDiscounts, handleDiscountToggle,
 *                                   updatePriceEditability, recalculateDiscountsFromPrice)
 *  §6  Price Validation            (validatePriceInput, validateDiscountInput)
 *  §7  Row Event Listeners         (attachRowEventListeners)
 *  §8  Global Discount Setup       (setupGlobalDiscountListeners)
 *  §9  Public API
 * ─────────────────────────────────────────────────────────────────────
 *
 * Cross-boundary reads (window.*)
 *   window.PosConfig                 — priceValidationEnabled, miscItemProductId
 *   window.getPosState()             — { isEditing, isEditingFinalizedSale, shippingData }
 *   window.stockData                 — stock array (pos_ajax closure)
 *   window.allProducts               — products array (pos_ajax closure)
 *   window.selectedLocationId        — active location (pos-location.js)
 *   window.formatAmountWithSeparators — formatter (pos-utils.js)
 *   window.debounce                  — debounce helper (pos-utils.js)
 *   window.showValidationToast       — toast helper (pos-utils.js)
 *   window.isValidationToastActive   — toast guard (pos-utils.js)
 *   window.showProductModal          — product modal (pos_ajax closure)
 *   window.showImeiSelectionModal    — IMEI modal (pos_ajax closure)
 *
 * Public exports (window.*)
 *   window.updateTotals
 *   window.attachRowEventListeners
 *   window.handleDiscountToggle
 *   window.disableConflictingDiscounts
 *   window.validatePriceInput
 *   window.validateDiscountInput
 *   window.updatePriceEditability
 *   window.recalculateDiscountsFromPrice
 *   window.showQuantityLimitError
 *   window.validateAllQuantities
 *   window.updatePaymentButtonsState
 */

'use strict';

// POS namespace (safe to call multiple times)
window.Pos = window.Pos || {};
window.Pos.Cart = window.Pos.Cart || {};

// Local alias to formatting helper
const _posCartUtils = window.Pos.Utils || {};
const _fmtAmount = _posCartUtils.formatAmountWithSeparators || (v => v);

/* ═══════════════════════════════════════════════════════════════════
   §1  STATE & CONFIG ACCESSORS
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Return a snapshot of POS live state (isEditing, shippingData, etc.)
 * supplied by pos_ajax via window.getPosState.
 * @returns {{ isEditing:boolean, isEditingFinalizedSale:boolean, shippingData:Object }}
 */
function _state() {
    return (typeof window.getPosState === 'function') ? window.getPosState() : {};
}

/**
 * Return the POS configuration object set by pos-config.blade.php.
 * @returns {{ priceValidationEnabled:number, miscItemProductId:number, [key:string]:* }}
 */
function _cfg() {
    return window.PosConfig || {};
}

/* ═══════════════════════════════════════════════════════════════════
   §2  QUANTITY HELPERS
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Play error sound and show a stock-limit toast once.
 * @param {number} maxQuantity
 */
function showQuantityLimitError(maxQuantity) {
    const errorSound = document.getElementsByClassName('errorSound')[0];
    if (errorSound && !window.isValidationToastActive) {
        errorSound.play();
    }
    window.showValidationToast(
        `You cannot add more than ${maxQuantity} units of this product.`,
        'Error',
        'error'
    );
}

/**
 * Validate all quantity inputs in the billing table.
 * Adds/removes `.quantity-error` class on each input.
 * @returns {boolean}  true if all inputs are valid
 */
function validateAllQuantities() {
    let isValid = true;
    document.querySelectorAll('#billing-body tr').forEach(function (row) {
        const quantityInput = row.querySelector('.quantity-input');
        if (!quantityInput) return;

        const maxQuantity    = parseFloat(quantityInput.getAttribute('max')) || Infinity;
        const currentQty     = parseFloat(quantityInput.value) || 0;
        const isUnlimited    = quantityInput.getAttribute('title') &&
                               quantityInput.getAttribute('title').includes('Unlimited');

        if (currentQty <= 0) {
            quantityInput.classList.add('quantity-error');
            isValid = false;
        } else if (!isUnlimited && currentQty > maxQuantity) {
            quantityInput.classList.add('quantity-error');
            isValid = false;
        } else {
            quantityInput.classList.remove('quantity-error');
        }
    });
    return isValid;
}

/* ═══════════════════════════════════════════════════════════════════
   §3  PAYMENT BUTTON STATE
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Enable/disable payment and action buttons based on:
 *   • quantity validity (all rows must have qty > 0 and within stock)
 *   • edit mode: Draft / Quotation / Sale Order disabled when editing a finalised sale
 */
function updatePaymentButtonsState() {
    const isQuantityValid  = validateAllQuantities();
    const paymentSelectors = ['#cashButton', '#cardButton', '#chequeButton',
                              '#creditSaleButton', '#multiplePayButton'];

    // Double-click protection
    paymentSelectors.forEach(sel => {
        $(document).off('click.payment-protection', sel);
        $(document).on('click.payment-protection', sel, function (e) {
            if (this.dataset.isProcessing === 'true' || $(this).prop('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });

    paymentSelectors.forEach(id => $(id).prop('disabled', !isQuantityValid));

    const { isEditing, isEditingFinalizedSale } = _state();
    const draftActions = '#draftButton, #quotationButton, #saleOrderButton';
    const mobileActions = '.mobile-action-btn[data-action="draft"], ' +
                          '.mobile-action-btn[data-action="quotation"], ' +
                          '.mobile-action-btn[data-action="sale-order"]';

    if (isEditing && isEditingFinalizedSale) {
        $(draftActions).prop('disabled', true)
            .css({ opacity: '0.5', cursor: 'not-allowed', 'pointer-events': 'none' });
        $(mobileActions).prop('disabled', true)
            .css({ opacity: '0.5', cursor: 'not-allowed', 'pointer-events': 'none' });
    } else {
        $(draftActions).prop('disabled', false)
            .css({ opacity: '1', cursor: 'pointer', 'pointer-events': 'auto' })
            .removeAttr('title');
        $(mobileActions).prop('disabled', false)
            .css({ opacity: '1', cursor: 'pointer', 'pointer-events': 'auto' });
    }
}

/* ═══════════════════════════════════════════════════════════════════
   §4  TOTALS
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Recalculate and update all billing totals in the UI.
 *
 * Steps:
 *   1. Sum qty × price for every row; update each row's subtotal cell and counter
 *   2. Apply global discount (% or fixed)
 *   3. Add shipping charges from live state
 *   4. Build a unit summary string (e.g. "3 + 1 free = 4 pcs")
 *   5. Push all values into the DOM
 *   6. Call updatePaymentButtonsState()
 */

// --- Totals helpers (internal) --------------------------------------

function calculateRowTotals(billingBody) {
    let totalItems     = 0;
    let totalFreeItems = 0;
    let totalAmount    = 0;

    billingBody.querySelectorAll('tr').forEach((row, index) => {
        const qtyInput      = row.querySelector('.quantity-input');
        const freeQtyInput  = row.querySelector('.free-quantity-input');
        const priceInput    = row.querySelector('.price-input');
        const counterCell   = row.querySelector('.counter-cell');

        const qty      = qtyInput     ? (qtyInput.value     === '' ? 0 : parseFloat(qtyInput.value))     : 0;
        const freeQty  = freeQtyInput ? (freeQtyInput.value === '' ? 0 : parseFloat(freeQtyInput.value)) : 0;
        const price    = priceInput   ? (parseFloat(priceInput.value) || 0) : 0;

        if (counterCell) counterCell.textContent = index + 1;

        const subtotal   = qty * price;
        const subtotalEl = row.querySelector('.subtotal');
        if (subtotalEl) {
            subtotalEl.textContent = _fmtAmount(subtotal.toFixed(2));
            subtotalEl.setAttribute('data-total', subtotal.toFixed(2));
        }

        totalItems     += qty;
        totalFreeItems += freeQty;
        totalAmount    += subtotal;
    });

    return { totalItems, totalFreeItems, totalAmount };
}

function applyGlobalDiscount(totalAmount) {
    const discountEl = document.getElementById('global-discount');
    const discTypeEl = document.getElementById('discount-type');

    const globalDisc = (discountEl && discountEl.value.trim() !== '')
        ? (parseFloat(discountEl.value) || 0)
        : 0;
    const discType = discTypeEl ? discTypeEl.value : 'fixed';

    let totalWithDisc = totalAmount;
    if (globalDisc > 0) {
        totalWithDisc -= (discType === 'percentage')
            ? totalAmount * (globalDisc / 100)
            : globalDisc;
    }

    return Math.max(0, totalWithDisc);
}

function computeFinalTotal(totalWithDisc) {
    const state = _state();
    const shippingCharges = (state.shippingData && state.shippingData.shipping_charges)
        ? (parseFloat(state.shippingData.shipping_charges) || 0)
        : 0;

    return totalWithDisc + shippingCharges;
}

function buildUnitSummary(billingBody, totalItems, totalFreeItems) {
    let unitSummary     = {};
    let freeUnitSummary = {};

    try {
        billingBody.querySelectorAll('tr').forEach(row => {
            const productId    = row.querySelector('.product-id')?.textContent;
            const qtyInput     = row.querySelector('.quantity-input');
            const freeQtyInput = row.querySelector('.free-quantity-input');
            const qty          = qtyInput     ? parseFloat(qtyInput.value)     : 0;
            const freeQty      = freeQtyInput ? parseFloat(freeQtyInput.value) : 0;
            if (!productId || (qty <= 0 && freeQty <= 0)) return;

            let stock = null;
            if (window.stockData && Array.isArray(window.stockData)) {
                stock = window.stockData.find(s => s && s.product && String(s.product.id) === productId);
            }
            if (!stock && window.allProducts && Array.isArray(window.allProducts)) {
                stock = window.allProducts.find(s => s && s.product && String(s.product.id) === productId);
                if (!stock) {
                    const direct = window.allProducts.find(s => s && s.id && String(s.id) === productId);
                    if (direct) stock = { product: direct };
                }
            }

            const unitShort = (stock && stock.product && stock.product.unit)
                ? (stock.product.unit.short_name || stock.product.unit.name || 'pcs')
                : 'pcs';

            unitSummary[unitShort]     = (unitSummary[unitShort]     || 0) + qty;
            freeUnitSummary[unitShort] = (freeUnitSummary[unitShort] || 0) + freeQty;
        });
    } catch (e) {
        unitSummary     = { pcs: totalItems };
        freeUnitSummary = { pcs: totalFreeItems };
    }

    const fmtQty = v => (v % 1 === 0) ? v : v.toFixed(4).replace(/\.?0+$/, '');

    let unitDisplay = '';
    if (totalFreeItems > 0) {
        unitDisplay = Object.entries(unitSummary).map(([unit, qty]) => {
            const freeQty  = freeUnitSummary[unit] || 0;
            const totalQty = qty + freeQty;
            return freeQty > 0
                ? `${fmtQty(qty)} + ${fmtQty(freeQty)} free = ${fmtQty(totalQty)} ${unit}`
                : `${fmtQty(qty)} ${unit}`;
        }).join(', ');
    } else {
        unitDisplay = Object.entries(unitSummary)
            .map(([unit, qty]) => `${fmtQty(qty)} ${unit}`)
            .join(', ');
    }

    return unitDisplay;
}

function writeTotalsToDom(billingBody, totals) {
    const { totalItems, totalFreeItems = 0, totalAmount, finalTotal, unitDisplay, totalDiscount = 0 } = totals;

    const fmt   = _fmtAmount;
    const setId = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };

    const itemDisplay = unitDisplay || totalItems.toFixed(2);

    setId('items-count',       itemDisplay);
    setId('modal-total-items', itemDisplay);
    setId('total-amount',      fmt(totalAmount.toFixed(2)));
    setId('final-total-amount', fmt(finalTotal.toFixed(2)));

    const totalEl  = document.getElementById('total');
    const payAmtEl = document.getElementById('payment-amount');

    if (totalEl) {
        const amountVal = totalEl.querySelector('.pos-footer-amount-value');
        if (amountVal) amountVal.textContent = fmt(finalTotal.toFixed(2));
        else totalEl.textContent = 'Rs ' + fmt(finalTotal.toFixed(2));
    }
    if (payAmtEl) payAmtEl.textContent = 'Rs ' + fmt(finalTotal.toFixed(2));

    setId('modal-total-payable', fmt(finalTotal.toFixed(2)));

    const rowCountEl = document.getElementById('total-items-count');
    if (rowCountEl) {
        rowCountEl.textContent = billingBody.querySelectorAll('tr').length;
    }

    const mobileTotalItemsEl = document.getElementById('mobile-total-items-text');
    const rowCount = billingBody.querySelectorAll('tr').length;
    if (mobileTotalItemsEl) {
        const totalUnits = totalItems + (totals.totalFreeItems || 0);
        const unitStr = totalUnits % 1 === 0 ? totalUnits : totalUnits.toFixed(2);
        mobileTotalItemsEl.textContent = rowCount + ' (' + unitStr + ' units)';
    }
    const mobileSummaryItemsEl = document.getElementById('mobile-summary-items-count');
    if (mobileSummaryItemsEl) mobileSummaryItemsEl.textContent = rowCount;

    /* Mobile Order Summary card (target design) */
    const mobileTotalAmountEl = document.getElementById('mobile-total-amount-text');
    if (mobileTotalAmountEl) mobileTotalAmountEl.textContent = 'Rs. ' + fmt(totalAmount.toFixed(2));
    const mobileTotalDiscountEl = document.getElementById('mobile-total-discount-text');
    if (mobileTotalDiscountEl) {
        mobileTotalDiscountEl.textContent = totalDiscount > 0 ? '-Rs. ' + fmt(totalDiscount.toFixed(2)) : 'Rs. 0.00';
        mobileTotalDiscountEl.classList.toggle('text-danger', totalDiscount > 0);
    }
    const mobileFinalInlineEl = document.getElementById('mobile-final-total-inline');
    if (mobileFinalInlineEl) mobileFinalInlineEl.textContent = 'Rs. ' + fmt(finalTotal.toFixed(2));
    /* Single source: also update mobile footer so it matches order summary (no separate setInterval overwrite) */
    const mobileFooterTotalEl = document.getElementById('mobile-final-total');
    if (mobileFooterTotalEl) mobileFooterTotalEl.textContent = 'Rs. ' + fmt(finalTotal.toFixed(2));
}

function updateTotals() {
    const billingBody = document.getElementById('billing-body');
    if (!billingBody) return;

    const { totalItems, totalFreeItems, totalAmount } = calculateRowTotals(billingBody);
    const totalWithDisc = applyGlobalDiscount(totalAmount);
    const finalTotal    = computeFinalTotal(totalWithDisc);
    const unitDisplay   = buildUnitSummary(billingBody, totalItems, totalFreeItems);
    const totalDiscount = Math.max(0, totalAmount - totalWithDisc);

    writeTotalsToDom(billingBody, {
        totalItems,
        totalFreeItems,
        totalAmount,
        finalTotal,
        unitDisplay,
        totalDiscount
    });

    updatePaymentButtonsState();
}

/* ═══════════════════════════════════════════════════════════════════
   §5  DISCOUNT HELPERS
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Disable the opposing discount field when one has a value.
 * In flexible mode (priceValidationEnabled === 0) both fields are always enabled.
 * @param {HTMLElement} row
 */
function disableConflictingDiscounts(row) {
    const fixed   = row.querySelector('.fixed_discount');
    const percent = row.querySelector('.percent_discount');
    if (!fixed || !percent) return;

    if (_cfg().priceValidationEnabled === 0) {
        fixed.disabled   = false;
        percent.disabled = false;
        return;
    }

    const fixedVal   = parseFloat(fixed.value)   || 0;
    const percentVal = parseFloat(percent.value) || 0;

    if (fixedVal > 0) {
        percent.disabled = true;
        percent.value    = '';
    } else if (percentVal > 0) {
        fixed.disabled = true;
        fixed.value    = '';
    } else {
        fixed.disabled   = false;
        percent.disabled = false;
    }
}

/**
 * Recalculate unit price when a discount field changes.
 * Mutually disables the opposing discount field.
 * @param {HTMLInputElement} input  - the discount field that triggered the event
 */
function handleDiscountToggle(input) {
    const row          = input.closest('tr');
    const fixedInput   = row.querySelector('.fixed_discount');
    const percentInput = row.querySelector('.percent_discount');
    const priceInput   = row.querySelector('.price-input');
    const mrp          = parseFloat(priceInput.getAttribute('data-max-retail-price')) || 0;

    const hasValue = el => {
        const v = el.value.trim();
        return v !== '' && !isNaN(parseFloat(v)) && parseFloat(v) > 0;
    };

    if (fixedInput === input) {
        if (hasValue(fixedInput)) {
            percentInput.disabled = true;
            percentInput.value    = '';
        } else {
            percentInput.disabled = false;
        }
    } else if (percentInput === input) {
        if (hasValue(percentInput)) {
            fixedInput.disabled = true;
            fixedInput.value    = '';
        } else {
            fixedInput.disabled = false;
        }
    }

    if (hasValue(fixedInput)) {
        priceInput.value = Math.max(0, mrp - parseFloat(fixedInput.value)).toFixed(2);
    } else if (hasValue(percentInput)) {
        priceInput.value = Math.max(0, mrp * (1 - parseFloat(percentInput.value) / 100)).toFixed(2);
    } else {
        priceInput.value = mrp.toFixed(2);
    }

    updateTotals();
}

/**
 * Lock the price input as read-only when either discount field has a value
 * (strict mode only; flexible mode always allows editing).
 * @param {HTMLElement} row
 */
function updatePriceEditability(row) {
    const priceInput   = row.querySelector('.price-input');
    const fixedInput   = row.querySelector('.fixed_discount');
    const percentInput = row.querySelector('.percent_discount');
    if (!priceInput || !fixedInput || !percentInput) return;

    if (_cfg().priceValidationEnabled === 0) {
        priceInput.removeAttribute('readonly');
        priceInput.style.backgroundColor = '#fff';
        priceInput.style.cursor          = 'text';
        priceInput.title                 = 'Flexible mode: Edit freely';
        return;
    }

    const hasFixed   = (parseFloat(fixedInput.value)   || 0) > 0;
    const hasPercent = (parseFloat(percentInput.value) || 0) > 0;

    if (!hasFixed && !hasPercent) {
        priceInput.removeAttribute('readonly');
        priceInput.style.backgroundColor = '#fff';
        priceInput.style.cursor          = 'text';
        priceInput.title                 = 'Price is editable when no discounts are applied';
    } else {
        priceInput.setAttribute('readonly', true);
        priceInput.style.backgroundColor = '#f8f9fa';
        priceInput.style.cursor          = 'not-allowed';
        priceInput.title                 = 'Remove discounts to edit price manually';
    }
}

/**
 * After the cashier edits the price directly, recalculate both discount fields.
 * In flexible mode only fixed discount is updated; percent stays blank.
 * @param {HTMLElement} row
 */
function recalculateDiscountsFromPrice(row) {
    const priceInput   = row.querySelector('.price-input');
    const fixedInput   = row.querySelector('.fixed_discount');
    const percentInput = row.querySelector('.percent_discount');
    if (!priceInput || !fixedInput || !percentInput) return;

    const mrp          = parseFloat(priceInput.getAttribute('data-max-retail-price')) || 0;
    let   enteredPrice = parseFloat(priceInput.value) || 0;

    if (mrp > 0 && enteredPrice > mrp) {
        enteredPrice     = mrp;
        priceInput.value = mrp.toFixed(2);
    }

    if (mrp > 0) {
        const discAmt = mrp - enteredPrice;
        if (_cfg().priceValidationEnabled === 0) {
            fixedInput.value   = discAmt > 0 ? discAmt.toFixed(2) : '0.00';
            percentInput.value = '';
        } else {
            const discPct = ((mrp - enteredPrice) / mrp) * 100;
            fixedInput.value   = discAmt > 0 ? discAmt.toFixed(2) : '0.00';
            percentInput.value = discPct > 0 ? discPct.toFixed(2) : '0.00';
        }
    }

    updateTotals();
}

/* ═══════════════════════════════════════════════════════════════════
   §6  PRICE VALIDATION
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Validate the unit price input on change/blur.
 * Enforces MRP ceiling and minimum price floor (strict mode).
 * Cash/misc items bypass all validation.
 * @param {HTMLElement}      row
 * @param {HTMLInputElement} priceInput
 */
function validatePriceInput(row, priceInput) {
    const cfg          = _cfg();
    const miscItemId   = cfg.miscItemProductId;
    const rowProductId = row.getAttribute('data-product-id');

    // Cash/misc items — skip validation, update data attributes
    if (miscItemId && rowProductId == String(miscItemId)) {
        const newPrice = parseFloat(priceInput.value) || 0;
        priceInput.setAttribute('data-max-retail-price', newPrice);
        priceInput.setAttribute('data-retail-price',     newPrice);
        disableConflictingDiscounts(row);
        updateTotals();
        return;
    }

    const retailPrice = parseFloat(priceInput.getAttribute('data-retail-price'))    || 0;
    const wholesale   = parseFloat(priceInput.getAttribute('data-wholesale-price')) || 0;
    const special     = parseFloat(priceInput.getAttribute('data-special-price'))   || 0;
    const mrp         = parseFloat(priceInput.getAttribute('data-max-retail-price'))|| 0;

    let enteredPrice     = parseFloat(priceInput.value) || 0;
    const originalEntered = enteredPrice;

    // Ceiling: price cannot exceed MRP
    if (enteredPrice > mrp && mrp > 0) {
        toastr.error(`Unit price cannot exceed MRP of Rs. ${mrp.toFixed(2)}`, '🚫 Price Limit Exceeded');
        priceInput.value  = mrp.toFixed(2);
        enteredPrice      = mrp;
        priceInput.style.borderColor = '#dc3545';
        setTimeout(() => { priceInput.style.borderColor = ''; }, 3000);
    }

    // Flexible mode — only MRP ceiling applies
    if (cfg.priceValidationEnabled === 0) {
        const fixedInput   = row.querySelector('.fixed_discount');
        const percentInput = row.querySelector('.percent_discount');
        if (mrp > 0 && enteredPrice !== originalEntered) {
            const discAmt = mrp - enteredPrice;
            if (fixedInput)   fixedInput.value   = discAmt > 0 ? discAmt.toFixed(2) : '0.00';
            if (percentInput) percentInput.value = '';
        }
        disableConflictingDiscounts(row);
        updateTotals();
        return;
    }

    // Strict mode — determine minimum price floor
    let minimumPrice = 0, priceTypeName = '';
    if (special > 0)         { minimumPrice = special;      priceTypeName = 'special price'; }
    else if (wholesale > 0)  { minimumPrice = wholesale;    priceTypeName = 'wholesale price'; }
    else if (retailPrice > 0){ minimumPrice = retailPrice;  priceTypeName = 'retail price'; }
    else if (mrp > 0)        { minimumPrice = mrp;          priceTypeName = 'MRP'; }

    if (enteredPrice < minimumPrice && minimumPrice > 0) {
        toastr.error(
            `Price cannot be below ${priceTypeName} of Rs. ${minimumPrice.toFixed(2)}. This prevents selling at loss.`,
            'Price Validation Error'
        );
        priceInput.value  = minimumPrice.toFixed(2);
        enteredPrice      = minimumPrice;
        priceInput.style.borderColor = '#dc3545';
        setTimeout(() => { priceInput.style.borderColor = ''; }, 3000);
    }

    const fixedInput   = row.querySelector('.fixed_discount');
    const percentInput = row.querySelector('.percent_discount');
    if (mrp > 0 && enteredPrice !== originalEntered) {
        const discAmt = mrp - enteredPrice;
        if (fixedInput)   fixedInput.value   = discAmt > 0 ? discAmt.toFixed(2) : '0.00';
        if (percentInput) percentInput.value = cfg.priceValidationEnabled === 0
            ? ''
            : (((mrp - enteredPrice) / mrp) * 100).toFixed(2);
    }

    disableConflictingDiscounts(row);
    updateTotals();
}

/**
 * Validate a discount input so the resulting price does not fall below the
 * minimum allowed price for the product/batch.
 * @param {HTMLElement}      row
 * @param {HTMLInputElement} discountInput
 * @param {'fixed'|'percent'} discountType
 */
function validateDiscountInput(row, discountInput, discountType) {
    if (_cfg().priceValidationEnabled === 0) return;

    const priceInput = row.querySelector('.price-input');
    if (!priceInput) return;

    const retail    = parseFloat(priceInput.getAttribute('data-retail-price'))    || 0;
    const wholesale = parseFloat(priceInput.getAttribute('data-wholesale-price')) || 0;
    const special   = parseFloat(priceInput.getAttribute('data-special-price'))   || 0;
    const mrp       = parseFloat(priceInput.getAttribute('data-max-retail-price'))|| 0;

    let minimumPrice = 0, priceTypeName = '';
    if (special > 0)        { minimumPrice = special;    priceTypeName = 'special price'; }
    else if (wholesale > 0) { minimumPrice = wholesale;  priceTypeName = 'wholesale price'; }
    else if (retail > 0)    { minimumPrice = retail;     priceTypeName = 'retail price'; }
    else if (mrp > 0)       { minimumPrice = mrp;        priceTypeName = 'MRP'; }

    if (minimumPrice <= 0 || mrp <= 0) return;

    const discValue  = parseFloat(discountInput.value) || 0;
    const origDisc   = discValue;

    let finalPrice = 0, maxAllowed = 0;
    if (discountType === 'fixed') {
        finalPrice = mrp - discValue;
        maxAllowed = mrp - minimumPrice;
    } else {
        finalPrice = mrp * (1 - discValue / 100);
        maxAllowed = ((mrp - minimumPrice) / mrp) * 100;
    }

    if (finalPrice < minimumPrice) {
        if (discountType === 'fixed') {
            toastr.error(
                `Fixed discount cannot exceed Rs. ${maxAllowed.toFixed(2)}. This would make selling price (Rs. ${finalPrice.toFixed(2)}) below ${priceTypeName} (Rs. ${minimumPrice.toFixed(2)}).`,
                'Discount Validation Error'
            );
        } else {
            toastr.error(
                `Percentage discount cannot exceed ${maxAllowed.toFixed(2)}%. This would make selling price (Rs. ${finalPrice.toFixed(2)}) below ${priceTypeName} (Rs. ${minimumPrice.toFixed(2)}).`,
                'Discount Validation Error'
            );
        }
        discountInput.value = maxAllowed.toFixed(2);
        discountInput.style.borderColor = '#dc3545';
        setTimeout(() => { discountInput.style.borderColor = ''; }, 3000);

        const corrected = parseFloat(discountInput.value);
        finalPrice = (discountType === 'fixed')
            ? mrp - corrected
            : mrp * (1 - corrected / 100);
    }

    if (origDisc !== parseFloat(discountInput.value) || finalPrice >= minimumPrice) {
        priceInput.value = finalPrice.toFixed(2);
    }
}

/* ═══════════════════════════════════════════════════════════════════
   §7  ROW EVENT LISTENERS
   ═══════════════════════════════════════════════════════════════════ */

/**
 * Attach all interactive event listeners to a newly created billing row.
 *
 * Handles:
 *   • Discount inputs (input/change/blur) → handleDiscountToggle + validateDiscount
 *   • Price input change/blur             → validatePriceInput + recalculateDiscounts
 *   • Quantity input                      → debounced stock validation + subtotal update
 *   • Minus / Plus buttons                → decrement / increment qty
 *   • Free quantity input                 → debounced stock validation
 *   • Remove button                       → remove row + updateTotals
 *   • Product image / name click          → showProductModal
 *   • Show IMEI button click              → fetch & showImeiSelectionModal
 *
 * @param {HTMLElement} row
 * @param {Object}      product
 * @param {Object}      stockEntry
 */
function attachRowEventListeners(row, product, stockEntry) {
    const qtyInput      = row.querySelector('.quantity-input');
    const priceInput    = row.querySelector('.price-input');
    const qtyMinus      = row.querySelector('.quantity-minus');
    const qtyPlus       = row.querySelector('.quantity-plus');
    const removeBtn     = row.querySelector('.remove-btn');
    const productImage  = row.querySelector('.product-image');
    const productName   = row.querySelector('.product-name');
    const fixedDisc     = row.querySelector('.fixed_discount');
    const percentDisc   = row.querySelector('.percent_discount');
    const freeQtyInput  = row.querySelector('.free-quantity-input');

    const allowDecimal  = product.unit &&
                          (product.unit.allow_decimal === true || product.unit.allow_decimal === 1);

    // ── Discount inputs ───────────────────────────────────────────────
    if (fixedDisc) {
        fixedDisc.addEventListener('input', () => handleDiscountToggle(fixedDisc));
        ['change', 'blur'].forEach(evt => fixedDisc.addEventListener(evt, () => {
            handleDiscountToggle(fixedDisc);
            validateDiscountInput(row, fixedDisc, 'fixed');
            updatePriceEditability(row);
            updateTotals();
        }));
    }
    if (percentDisc) {
        percentDisc.addEventListener('input', () => handleDiscountToggle(percentDisc));
        ['change', 'blur'].forEach(evt => percentDisc.addEventListener(evt, () => {
            handleDiscountToggle(percentDisc);
            validateDiscountInput(row, percentDisc, 'percent');
            updatePriceEditability(row);
            updateTotals();
        }));
    }

    // Initialise discount conflict state on row creation
    if (fixedDisc && percentDisc) {
        const hasFixed   = parseFloat(fixedDisc.value)   > 0;
        const hasPercent = parseFloat(percentDisc.value) > 0;
        if (hasFixed)        { percentDisc.disabled = true; }
        else if (hasPercent) { fixedDisc.disabled   = true; }
        else                 { fixedDisc.disabled = false; percentDisc.disabled = false; }
    }

    // ── Price input ───────────────────────────────────────────────────
    ['change', 'blur'].forEach(evt => priceInput.addEventListener(evt, () => {
        validatePriceInput(row, priceInput);
        recalculateDiscountsFromPrice(row);
    }));
    updatePriceEditability(row);

    // ── Quantity input ────────────────────────────────────────────────
    const debouncedQtyValidation = window.debounce((qty, freeQty, maxQty) => {
        const totalQty = qty + freeQty;
        if (totalQty > maxQty && product.stock_alert !== 0) {
            window.showValidationToast(
                `Total quantity (${qty} + ${freeQty} free = ${totalQty}) exceeds available stock (${maxQty}) for ${product.product_name}.`
            );
        } else if (qty > maxQty && product.stock_alert !== 0) {
            window.showValidationToast(
                `You are entering more than available stock for ${product.product_name}.`
            );
        }
    }, 500);

    qtyInput.addEventListener('input', () => {
        const value    = qtyInput.value.trim();
        const maxQty   = parseFloat(priceInput.getAttribute('data-quantity'));
        const valid    = allowDecimal
            ? (value === '' || /^\d*\.?\d{0,2}$/.test(value))
            : (value === '' || /^\d+$/.test(value));

        if (valid) {
            qtyInput.classList.remove('is-invalid');
            updateTotals();
            if (value !== '' && !value.endsWith('.')) {
                const qty     = allowDecimal ? parseFloat(value) : parseInt(value, 10);
                const freeQty = parseFloat(freeQtyInput?.value || 0);
                if (!isNaN(qty)) {
                    debouncedQtyValidation(qty, freeQty, maxQty);
                    const subtotalEl = row.querySelector('.subtotal');
                    if (subtotalEl) subtotalEl.textContent =
                        _fmtAmount((parseFloat(priceInput.value) * qty).toFixed(2));
                    updateTotals();
                }
            }
        } else {
            qtyInput.classList.add('is-invalid');
        }
    });

    // ── Minus button ──────────────────────────────────────────────────
    qtyMinus.addEventListener('click', () => {
        let qty = allowDecimal ? parseFloat(qtyInput.value) : parseInt(qtyInput.value, 10);
        if (allowDecimal) {
            if (qty > 0.01) {
                qty = Math.max(0.01, parseFloat((qty - 0.01).toFixed(2)));
                qtyInput.value = qty.toFixed(2).replace(/\.?0+$/, '');
                updateTotals();
            }
        } else {
            if (qty > 1) {
                qtyInput.value = qty - 1;
                updateTotals();
            }
        }
    });

    // ── Plus button ───────────────────────────────────────────────────
    qtyPlus.addEventListener('click', () => {
        let qty    = allowDecimal ? parseFloat(qtyInput.value) : parseInt(qtyInput.value, 10);
        const maxQty = parseFloat(priceInput.getAttribute('data-quantity'));
        if (qty < maxQty || product.stock_alert === 0) {
            if (allowDecimal) {
                qty = parseFloat((qty + 0.01).toFixed(2));
                qtyInput.value = qty.toFixed(2).replace(/\.?0+$/, '');
            } else {
                qtyInput.value = qty + 1;
            }
            updateTotals();
        } else {
            showQuantityLimitError(maxQty);
        }
    });

    // ── Free quantity input ───────────────────────────────────────────
    if (freeQtyInput) {
        const debouncedFreeVal = window.debounce((currQty, freeQty, totalQty, maxStock) => {
            if (freeQty > 0) {
                window.showValidationToast(
                    `Total quantity (${currQty} + ${freeQty} free = ${totalQty}) exceeds available stock (${maxStock})`,
                    'Stock Limit'
                );
            }
        }, 500);

        freeQtyInput.addEventListener('input', () => {
            const value    = freeQtyInput.value.trim();
            const freeQty  = parseFloat(value) || 0;
            const maxStock = parseFloat(freeQtyInput.dataset.maxStock) || 0;
            const currQty  = parseFloat(qtyInput.value) || 0;
            const totalQty = currQty + freeQty;
            const valid    = allowDecimal
                ? (value === '' || /^\d*\.?\d{0,2}$/.test(value))
                : (value === '' || /^\d+$/.test(value));
            const stockOk  = totalQty <= maxStock;

            if (valid && stockOk) {
                freeQtyInput.classList.remove('is-invalid');
                updateTotals();
            } else {
                freeQtyInput.classList.add('is-invalid');
                if (!stockOk) debouncedFreeVal(currQty, freeQty, totalQty, maxStock);
            }
        });
    }

    // ── Remove button ─────────────────────────────────────────────────
    removeBtn.addEventListener('click', () => {
        row.remove();
        updateTotals();
    });

    // ── Product image & name → open product modal ─────────────────────
    productImage.addEventListener('click', () => {
        const miscId = _cfg().miscItemProductId;
        if (miscId && product.id == miscId) return;
        if (typeof window.showProductModal === 'function') {
            window.showProductModal(product, stockEntry, row);
        }
    });

    productName.addEventListener('click', e => {
        if (e.target.classList.contains('custom-name-input')) return;
        if (typeof window.showProductModal === 'function') {
            window.showProductModal(product, stockEntry, row);
        }
    });

    // ── Show IMEI button ──────────────────────────────────────────────
    const showImeiBtn = row.querySelector('.show-imei-btn');
    if (showImeiBtn) {
        showImeiBtn.addEventListener('click', function () {
            const imeiDataCell  = row.querySelector('.imei-data');
            const productIdCell = row.querySelector('.product-id');
            const locationIdCell= row.querySelector('.location-id');
            const imeis         = imeiDataCell
                ? imeiDataCell.textContent.trim().split(',').filter(Boolean) : [];
            const productId  = productIdCell  ? productIdCell.textContent.trim()   : product.id;
            const locationId = locationIdCell ? locationIdCell.textContent.trim()  : window.selectedLocationId;

            if (imeis.length === 0) { toastr.warning('No IMEIs found for this product.'); return; }

            fetch(`/get-imeis/${productId}?location_id=${locationId}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 200) {
                    const tempStockEntry = { ...stockEntry, imei_numbers: data.data };
                    if (typeof window.showImeiSelectionModal === 'function') {
                        window.showImeiSelectionModal(product, tempStockEntry, [], '', 'EDIT', 'all');
                    }
                } else {
                    toastr.error('Failed to load IMEI data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(() => toastr.error('Network error while loading IMEI data'));
        });
    }

    // Initialise conflict state
    disableConflictingDiscounts(row);
}

/* ═══════════════════════════════════════════════════════════════════
   §8  GLOBAL DISCOUNT SETUP
   ═══════════════════════════════════════════════════════════════════
 *
 * NOTE: pos_ajax.blade.php already attaches global discount event
 * listeners at script-load time (direct $(...).addEventListener calls
 * outside any function).  Those listeners call the CLOSURE-local
 * updateTotals, which is correct for the existing code path.
 *
 * This function is provided as a fallback for pages that load
 * pos-cart.js standalone (e.g. unit tests).  It is NOT called on
 * DOMContentLoaded to avoid duplicating the pos_ajax listeners.
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Wire up input/change/blur/select-change listeners on the global
 * discount input and its type dropdown so they call updateTotals().
 * Only call this if pos_ajax has NOT already done so.
 */
function setupGlobalDiscountListeners() {
    const discountInput    = document.getElementById('global-discount');
    const discountTypeInput= document.getElementById('discount-type');
    if (!discountInput) return;

    discountInput.addEventListener('input',  () => updateTotals());
    discountInput.addEventListener('change', function () {
        if (discountTypeInput && discountTypeInput.value === 'percentage') {
            this.value = Math.min(parseFloat(this.value) || 0, 100);
        }
        updateTotals();
    });
    discountInput.addEventListener('blur', function () {
        if (discountTypeInput && discountTypeInput.value === 'percentage') {
            this.value = Math.min(parseFloat(this.value) || 0, 100);
        }
        updateTotals();
    });
    if (discountTypeInput) {
        discountTypeInput.addEventListener('change', () => updateTotals());
    }
}

/* ═══════════════════════════════════════════════════════════════════
   §9  PUBLIC API
   Expose via namespaced API + backward-compatible globals for legacy code.
   ═══════════════════════════════════════════════════════════════════ */

// Namespaced (preferred)
window.Pos.Cart.updateTotals                  = updateTotals;
window.Pos.Cart.attachRowEventListeners       = attachRowEventListeners;
window.Pos.Cart.handleDiscountToggle          = handleDiscountToggle;
window.Pos.Cart.disableConflictingDiscounts   = disableConflictingDiscounts;
window.Pos.Cart.validatePriceInput            = validatePriceInput;
window.Pos.Cart.validateDiscountInput         = validateDiscountInput;
window.Pos.Cart.updatePriceEditability        = updatePriceEditability;
window.Pos.Cart.recalculateDiscountsFromPrice = recalculateDiscountsFromPrice;
window.Pos.Cart.showQuantityLimitError        = showQuantityLimitError;
window.Pos.Cart.validateAllQuantities         = validateAllQuantities;
window.Pos.Cart.updatePaymentButtonsState     = updatePaymentButtonsState;
window.Pos.Cart.setupGlobalDiscountListeners  = setupGlobalDiscountListeners;

// Legacy globals (used by older scripts / inline handlers)
window.updateTotals                  = updateTotals;
window.attachRowEventListeners       = attachRowEventListeners;
window.handleDiscountToggle          = handleDiscountToggle;
window.disableConflictingDiscounts   = disableConflictingDiscounts;
window.validatePriceInput            = validatePriceInput;
window.validateDiscountInput         = validateDiscountInput;
window.updatePriceEditability        = updatePriceEditability;
window.recalculateDiscountsFromPrice = recalculateDiscountsFromPrice;
window.showQuantityLimitError        = showQuantityLimitError;
window.validateAllQuantities         = validateAllQuantities;
window.updatePaymentButtonsState     = updatePaymentButtonsState;
window.setupGlobalDiscountListeners  = setupGlobalDiscountListeners;
