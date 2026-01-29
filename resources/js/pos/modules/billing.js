/**
 * Billing Module
 * Handles all billing table operations and calculations
 */

import { posState } from '../state/index.js';
import { formatAmountWithSeparators, parseFormattedAmount, roundToDecimals } from '../utils/formatters.js';
import { safeParseFloat, generateUniqueId } from '../utils/helpers.js';
import { validateQuantity, validatePrice } from '../utils/validation.js';
import { apiClient } from '../api/client.js';

export class BillingManager {
    constructor() {
        this.billingTableBody = null;
        this.rowEventHandlers = new Map();
        this.categories = [];
        this.brands = [];
    }

    /**
     * Initialize billing manager
     */
    initialize() {
        this.billingTableBody = document.getElementById('billing-body');
        this.setupEventListeners();
        this.loadCategories();
        this.loadBrands();
    }

    /**
     * Load categories from API
     */
    async loadCategories() {
        try {
            const response = await apiClient.get('/main-category-get-all');
            this.categories = response.message || [];
            console.log('Categories loaded:', this.categories.length);
        } catch (error) {
            console.error('Error loading categories:', error);
            this.categories = [];
        }
    }

    /**
     * Load brands from API
     */
    async loadBrands() {
        try {
            const response = await apiClient.get('/brand-get-all');
            this.brands = response.message || [];
            console.log('Brands loaded:', this.brands.length);
        } catch (error) {
            console.error('Error loading brands:', error);
            this.brands = [];
        }
    }

    /**
     * Get categories
     */
    getCategories() {
        return this.categories;
    }

    /**
     * Get brands
     */
    getBrands() {
        return this.brands;
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Event delegation for billing table
        if (this.billingTableBody) {
            this.billingTableBody.addEventListener('input', (e) => this.handleInput(e));
            this.billingTableBody.addEventListener('change', (e) => this.handleChange(e));
            this.billingTableBody.addEventListener('click', (e) => this.handleClick(e));
        }
    }

    /**
     * Add product to billing table
     * @param {Object} params - Product parameters
     * @returns {boolean} Success status
     */
    async addProduct(params) {
        const {
            product,
            stockEntry,
            price,
            batchId,
            batchQuantity,
            priceType,
            saleQuantity = 1,
            imeis = [],
            discountType = null,
            discountAmount = null,
            selectedBatch = null
        } = params;

        console.log('Adding product to billing:', product.product_name, 'Quantity:', saleQuantity);

        // Validate inputs
        if (!product || !product.id) {
            console.error('Invalid product');
            return false;
        }

        const locationId = posState.get('selectedLocationId');
        if (!locationId) {
            toastr.error('Please select a location first', 'Error');
            return false;
        }

        // Get batch details
        let batch = selectedBatch || this.findBatch(stockEntry, batchId);

        // Get effective MRP
        const effectiveMRP = this.getEffectiveMRP(product, batch);

        // Calculate price and discount
        const pricingData = this.calculatePricing({
            price,
            effectiveMRP,
            discountType,
            discountAmount,
            stockEntry
        });

        // Get adjusted batch quantity
        const adjustedBatchQuantity = this.getAdjustedBatchQuantity(
            stockEntry,
            batchId,
            batch,
            batchQuantity,
            locationId
        );

        // Determine unit properties
        const unitName = product.unit?.name || 'Pc(s)';
        const allowDecimal = product.unit?.allow_decimal === true || product.unit?.allow_decimal === 1;

        // For IMEI products without IMEIs selected, return false
        if (product.enable_imei && imeis.length === 0) {
            toastr.error('Please select IMEI numbers for this product', 'IMEI Required');
            return false;
        }

        // Check if we can merge with existing row (non-IMEI only)
        if (imeis.length === 0) {
            const merged = this.tryMergeRow({
                product,
                batchId,
                finalPrice: pricingData.finalPrice,
                saleQuantity,
                adjustedBatchQuantity,
                allowDecimal
            });

            if (merged) {
                this.updateTotals();
                return true;
            }
        }

        // Create new row(s)
        if (imeis.length > 0) {
            // Create separate row for each IMEI
            for (const imei of imeis) {
                this.createRow({
                    product,
                    batch,
                    batchId,
                    effectiveMRP,
                    finalPrice: pricingData.finalPrice,
                    discountFixed: pricingData.discountFixed,
                    discountPercent: pricingData.discountPercent,
                    quantity: 1, // Always 1 for IMEI
                    maxQuantity: 1,
                    unitName,
                    allowDecimal,
                    imeis: [imei],
                    priceType
                });
            }
        } else {
            // Create single row
            this.createRow({
                product,
                batch,
                batchId,
                effectiveMRP,
                finalPrice: pricingData.finalPrice,
                discountFixed: pricingData.discountFixed,
                discountPercent: pricingData.discountPercent,
                quantity: saleQuantity,
                maxQuantity: adjustedBatchQuantity,
                unitName,
                allowDecimal,
                imeis: [],
                priceType
            });
        }

        this.updateTotals();
        return true;
    }

    /**
     * Find batch from stock entry
     */
    findBatch(stockEntry, batchId) {
        if (!stockEntry || !batchId || batchId === 'all') {
            return null;
        }

        const batches = this.normalizeBatches(stockEntry);
        return batches.find(b => b.id === parseInt(batchId));
    }

    /**
     * Normalize batches from stock entry
     */
    normalizeBatches(stockEntry) {
        if (!stockEntry) return [];

        if (Array.isArray(stockEntry.batches)) {
            return stockEntry.batches;
        } else if (stockEntry.batch) {
            return [stockEntry.batch];
        }

        return [];
    }

    /**
     * Get effective MRP (batch MRP or product MRP)
     */
    getEffectiveMRP(product, batch) {
        if (batch && batch.max_retail_price) {
            return safeParseFloat(batch.max_retail_price);
        }
        return safeParseFloat(product.max_retail_price);
    }

    /**
     * Calculate pricing with discounts
     */
    calculatePricing(params) {
        const { price, effectiveMRP, discountType, discountAmount, stockEntry } = params;

        const isEditing = posState.get('isEditing');

        let finalPrice = safeParseFloat(price);
        let discountFixed = 0;
        let discountPercent = 0;

        // Default discount (MRP - customer type price)
        const defaultFixedDiscount = effectiveMRP - finalPrice;

        // In edit mode with original discount, preserve it
        if (isEditing && discountType && discountAmount !== null) {
            if (discountType === 'fixed') {
                discountFixed = safeParseFloat(discountAmount);
                finalPrice = safeParseFloat(price);
            } else if (discountType === 'percentage') {
                discountPercent = safeParseFloat(discountAmount);
                finalPrice = safeParseFloat(price);
            }
        } else {
            // Normal mode: apply discount priority
            if (discountType && discountAmount !== null) {
                // Manual discount
                if (discountType === 'fixed') {
                    discountFixed = safeParseFloat(discountAmount);
                    finalPrice = effectiveMRP - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                } else if (discountType === 'percentage') {
                    discountPercent = safeParseFloat(discountAmount);
                    finalPrice = effectiveMRP * (1 - discountPercent / 100);
                }
            } else {
                // Check for active discount
                const activeDiscount = stockEntry?.discounts?.find(d => d.is_active && !d.is_expired);

                if (activeDiscount) {
                    if (activeDiscount.type === 'percentage') {
                        discountPercent = safeParseFloat(activeDiscount.amount);
                        finalPrice = effectiveMRP * (1 - discountPercent / 100);
                    } else if (activeDiscount.type === 'fixed') {
                        discountFixed = safeParseFloat(activeDiscount.amount);
                        finalPrice = effectiveMRP - discountFixed;
                        if (finalPrice < 0) finalPrice = 0;
                    }
                } else {
                    // Default: MRP - customer type price
                    discountFixed = defaultFixedDiscount;
                    discountPercent = (discountFixed / effectiveMRP) * 100;
                    finalPrice = safeParseFloat(price);
                }
            }
        }

        return {
            finalPrice: roundToDecimals(finalPrice, 2),
            discountFixed: roundToDecimals(discountFixed, 2),
            discountPercent: roundToDecimals(discountPercent, 2)
        };
    }

    /**
     * Get adjusted batch quantity
     */
    getAdjustedBatchQuantity(stockEntry, batchId, batch, batchQuantity, locationId) {
        const isEditing = posState.get('isEditing');

        // In edit mode, trust the backend-calculated quantity
        if (isEditing) {
            return safeParseFloat(batchQuantity);
        }

        if (batchId === 'all') {
            return safeParseFloat(stockEntry?.total_stock || 0);
        }

        if (batch && batch.location_batches) {
            const locationBatch = batch.location_batches.find(lb => lb.location_id === locationId);
            if (locationBatch) {
                return safeParseFloat(locationBatch.quantity);
            }
        }

        return safeParseFloat(batchQuantity);
    }

    /**
     * Try to merge with existing row
     */
    tryMergeRow(params) {
        const { product, batchId, finalPrice, saleQuantity, adjustedBatchQuantity, allowDecimal } = params;

        const rows = Array.from(this.billingTableBody.querySelectorAll('tr'));

        for (const row of rows) {
            const productIdEl = row.querySelector('.product-id');
            const priceInput = row.querySelector('.price-input');

            if (!productIdEl || !priceInput) continue;

            const rowProductId = productIdEl.textContent.trim();
            const rowPrice = safeParseFloat(priceInput.value);

            // Match by product ID and price
            if (rowProductId == product.id && Math.abs(rowPrice - finalPrice) < 0.01) {
                const quantityInput = row.querySelector('.quantity-input');
                const currentQty = safeParseFloat(quantityInput.value);

                const newQuantity = currentQty + saleQuantity;

                // Validate max quantity
                const maxAllowed = this.getRowMaxQuantity(row, adjustedBatchQuantity, allowDecimal);

                if (newQuantity > maxAllowed && product.stock_alert !== 0) {
                    toastr.error(`Maximum ${maxAllowed} units available`, 'Stock Limit');
                    return false;
                }

                // Update quantity
                quantityInput.value = allowDecimal ?
                    newQuantity.toFixed(4).replace(/\.?0+$/, '') :
                    Math.floor(newQuantity);

                // Trigger change event
                quantityInput.dispatchEvent(new Event('input', { bubbles: true }));

                return true;
            }
        }

        return false;
    }

    /**
     * Get row maximum quantity
     */
    getRowMaxQuantity(row, adjustedBatchQuantity, allowDecimal) {
        const isEditing = posState.get('isEditing');

        if (isEditing) {
            const rowMaxQty = row.getAttribute('data-max-quantity');
            if (rowMaxQty) {
                return allowDecimal ? parseFloat(rowMaxQty) : parseInt(rowMaxQty, 10);
            }
        }

        return allowDecimal ? parseFloat(adjustedBatchQuantity) : parseInt(adjustedBatchQuantity, 10);
    }

    /**
     * Create new billing row
     */
    createRow(params) {
        const {
            product,
            batch,
            batchId,
            effectiveMRP,
            finalPrice,
            discountFixed,
            discountPercent,
            quantity,
            maxQuantity,
            unitName,
            allowDecimal,
            imeis,
            priceType
        } = params;

        const rowId = generateUniqueId('row');
        const qtyStep = allowDecimal ? 'any' : '1';
        const qtyPattern = allowDecimal ? '[0-9]+([.][0-9]{1,4})?' : '[0-9]*';

        const row = document.createElement('tr');
        row.setAttribute('data-row-id', rowId);
        row.setAttribute('data-max-quantity', maxQuantity);
        row.setAttribute('data-allow-decimal', allowDecimal);

        const imeiDisplay = imeis.length > 0 ? imeis.join(', ') : '';
        const imeiJSON = JSON.stringify(imeis);

        row.innerHTML = `
            <td class="text-center">
                <span class="product-id" style="display:none;">${product.id}</span>
                <span class="batch-id" style="display:none;">${batchId || ''}</span>
                <span class="imeis-json" style="display:none;">${imeiJSON}</span>
                ${product.product_name}
                ${imeis.length > 0 ? `<br><small class="text-muted">IMEI: ${imeiDisplay}</small>` : ''}
            </td>
            <td class="text-center">
                <input type="number"
                    class="form-control form-control-sm quantity-input text-center"
                    value="${quantity}"
                    min="0"
                    step="${qtyStep}"
                    pattern="${qtyPattern}"
                    ${imeis.length > 0 ? 'readonly' : ''}>
                <small class="text-muted">${unitName}</small>
            </td>
            <td class="text-center">
                <input type="text"
                    class="form-control form-control-sm mrp-display text-end"
                    value="${formatAmountWithSeparators(effectiveMRP)}"
                    readonly>
            </td>
            <td class="text-center">
                <input type="text"
                    class="form-control form-control-sm price-input text-end"
                    value="${finalPrice.toFixed(2)}"
                    data-original-price="${finalPrice}">
            </td>
            <td class="text-center">
                <input type="text"
                    class="form-control form-control-sm discount-fixed-input text-end"
                    value="${discountFixed.toFixed(2)}"
                    data-effective-mrp="${effectiveMRP}">
            </td>
            <td class="text-center">
                <input type="text"
                    class="form-control form-control-sm discount-percent-input text-end"
                    value="${discountPercent.toFixed(2)}"
                    readonly>
            </td>
            <td class="text-center">
                <strong class="subtotal-display">${formatAmountWithSeparators(finalPrice * quantity)}</strong>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="fas fa-trash"></i>
                </button>
                ${imeis.length > 0 ? `
                    <button type="button" class="btn btn-sm btn-info edit-imei ms-1">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
            </td>
        `;

        this.billingTableBody.appendChild(row);
        this.attachRowEventListeners(row);

        return row;
    }

    /**
     * Attach event listeners to row
     */
    attachRowEventListeners(row) {
        // Quantity change
        const qtyInput = row.querySelector('.quantity-input');
        if (qtyInput) {
            qtyInput.addEventListener('input', () => this.handleQuantityChange(row));
        }

        // Price change
        const priceInput = row.querySelector('.price-input');
        if (priceInput) {
            priceInput.addEventListener('input', () => this.handlePriceChange(row));
        }

        // Discount change
        const discountInput = row.querySelector('.discount-fixed-input');
        if (discountInput) {
            discountInput.addEventListener('input', () => this.handleDiscountChange(row));
        }

        // Remove button
        const removeBtn = row.querySelector('.remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeRow(row));
        }

        // Edit IMEI button
        const editImeiBtn = row.querySelector('.edit-imei');
        if (editImeiBtn) {
            editImeiBtn.addEventListener('click', () => this.handleEditIMEI(row));
        }
    }

    /**
     * Handle quantity change
     */
    handleQuantityChange(row) {
        const qtyInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        const subtotalDisplay = row.querySelector('.subtotal-display');

        const quantity = safeParseFloat(qtyInput.value);
        const price = safeParseFloat(priceInput.value);
        const subtotal = roundToDecimals(quantity * price, 2);

        subtotalDisplay.textContent = formatAmountWithSeparators(subtotal);

        this.updateTotals();
    }

    /**
     * Handle price change
     */
    handlePriceChange(row) {
        const priceInput = row.querySelector('.price-input');
        const qtyInput = row.querySelector('.quantity-input');
        const discountFixedInput = row.querySelector('.discount-fixed-input');
        const discountPercentInput = row.querySelector('.discount-percent-input');
        const subtotalDisplay = row.querySelector('.subtotal-display');

        const price = safeParseFloat(priceInput.value);
        const quantity = safeParseFloat(qtyInput.value);
        const effectiveMRP = safeParseFloat(discountFixedInput.getAttribute('data-effective-mrp'));

        // Recalculate discount
        const discountFixed = effectiveMRP - price;
        const discountPercent = (discountFixed / effectiveMRP) * 100;

        discountFixedInput.value = discountFixed.toFixed(2);
        discountPercentInput.value = discountPercent.toFixed(2);

        const subtotal = roundToDecimals(quantity * price, 2);
        subtotalDisplay.textContent = formatAmountWithSeparators(subtotal);

        this.updateTotals();
    }

    /**
     * Handle discount change
     */
    handleDiscountChange(row) {
        const discountFixedInput = row.querySelector('.discount-fixed-input');
        const discountPercentInput = row.querySelector('.discount-percent-input');
        const priceInput = row.querySelector('.price-input');
        const qtyInput = row.querySelector('.quantity-input');
        const subtotalDisplay = row.querySelector('.subtotal-display');

        const discountFixed = safeParseFloat(discountFixedInput.value);
        const effectiveMRP = safeParseFloat(discountFixedInput.getAttribute('data-effective-mrp'));

        const newPrice = effectiveMRP - discountFixed;
        const discountPercent = (discountFixed / effectiveMRP) * 100;

        priceInput.value = newPrice.toFixed(2);
        discountPercentInput.value = discountPercent.toFixed(2);

        const quantity = safeParseFloat(qtyInput.value);
        const subtotal = roundToDecimals(quantity * newPrice, 2);
        subtotalDisplay.textContent = formatAmountWithSeparators(subtotal);

        this.updateTotals();
    }

    /**
     * Remove row from billing table
     */
    removeRow(row) {
        row.remove();
        this.updateTotals();
        toastr.success('Item removed from bill', 'Success');
    }

    /**
     * Handle edit IMEI click
     */
    handleEditIMEI(row) {
        // Emit event for IMEI modal
        window.dispatchEvent(new CustomEvent('edit-imei', { detail: { row } }));
    }

    /**
     * Update totals
     */
    updateTotals() {
        const rows = Array.from(this.billingTableBody.querySelectorAll('tr'));

        let subtotal = 0;
        let totalDiscount = 0;

        rows.forEach(row => {
            const qtyInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('.price-input');
            const mrpDisplay = row.querySelector('.mrp-display');

            const quantity = safeParseFloat(qtyInput.value);
            const price = safeParseFloat(priceInput.value);
            const mrp = parseFormattedAmount(mrpDisplay.value);

            subtotal += quantity * price;
            totalDiscount += quantity * (mrp - price);
        });

        // Get shipping charges
        const shippingData = posState.get('shippingData');
        const shippingCharges = safeParseFloat(shippingData?.shipping_charges || 0);

        // Calculate final total
        const finalTotal = subtotal + shippingCharges;

        // Update state
        posState.update({
            billingItems: rows.length,
            subtotal: roundToDecimals(subtotal, 2),
            totalDiscount: roundToDecimals(totalDiscount, 2),
            finalTotal: roundToDecimals(finalTotal, 2)
        });

        // Update UI
        this.updateTotalsUI(subtotal, totalDiscount, shippingCharges, finalTotal);

        return { subtotal, totalDiscount, finalTotal };
    }

    /**
     * Update totals UI
     */
    updateTotalsUI(subtotal, totalDiscount, shippingCharges, finalTotal) {
        const subtotalEl = document.getElementById('subtotal-display');
        const discountEl = document.getElementById('discount-display');
        const shippingEl = document.getElementById('shipping-display');
        const finalTotalEl = document.getElementById('final-total-display');

        if (subtotalEl) subtotalEl.textContent = formatAmountWithSeparators(subtotal);
        if (discountEl) discountEl.textContent = formatAmountWithSeparators(totalDiscount);
        if (shippingEl) shippingEl.textContent = formatAmountWithSeparators(shippingCharges);
        if (finalTotalEl) finalTotalEl.textContent = formatAmountWithSeparators(finalTotal);
    }

    /**
     * Clear billing table
     */
    clearBillingTable() {
        if (this.billingTableBody) {
            this.billingTableBody.innerHTML = '';
            this.updateTotals();
        }
    }

    /**
     * Get billing data for submission
     */
    getBillingData() {
        const rows = Array.from(this.billingTableBody.querySelectorAll('tr'));

        return rows.map(row => {
            const productId = row.querySelector('.product-id').textContent.trim();
            const batchId = row.querySelector('.batch-id').textContent.trim();
            const imeisJson = row.querySelector('.imeis-json').textContent.trim();
            const quantity = safeParseFloat(row.querySelector('.quantity-input').value);
            const price = safeParseFloat(row.querySelector('.price-input').value);
            const discountFixed = safeParseFloat(row.querySelector('.discount-fixed-input').value);
            const mrp = parseFormattedAmount(row.querySelector('.mrp-display').value);

            let imeis = [];
            try {
                imeis = imeisJson ? JSON.parse(imeisJson) : [];
            } catch (e) {
                console.error('Error parsing IMEIs:', e);
            }

            return {
                product_id: productId,
                batch_id: batchId || null,
                quantity: quantity,
                unit_price: price,
                mrp: mrp,
                discount_amount: discountFixed,
                discount_type: 'fixed',
                subtotal: roundToDecimals(quantity * price, 2),
                imeis: imeis
            };
        });
    }

    /**
     * Handle general input events
     */
    handleInput(e) {
        const row = e.target.closest('tr');
        if (!row) return;

        if (e.target.classList.contains('quantity-input')) {
            this.handleQuantityChange(row);
        } else if (e.target.classList.contains('price-input')) {
            this.handlePriceChange(row);
        } else if (e.target.classList.contains('discount-fixed-input')) {
            this.handleDiscountChange(row);
        }
    }

    /**
     * Handle general change events
     */
    handleChange(e) {
        // Handle any change events
    }

    /**
     * Handle general click events
     */
    handleClick(e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('tr');
            if (row) this.removeRow(row);
        } else if (e.target.closest('.edit-imei')) {
            const row = e.target.closest('tr');
            if (row) this.handleEditIMEI(row);
        }
    }
}

// Create singleton instance
export const billingManager = new BillingManager();

export default billingManager;
