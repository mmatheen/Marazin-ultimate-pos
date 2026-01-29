/**
 * Discounts Module
 * Handles discount calculations and conflicts
 */

import { posState } from '../state/index.js';
import { roundToDecimals } from '../utils/formatters.js';

export class DiscountManager {
    constructor() {
        this.orderDiscountEnabled = false;
        this.productDiscountEnabled = false;
    }

    initialize() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Order discount toggle
        const orderDiscountToggle = document.getElementById('order-discount-toggle');
        if (orderDiscountToggle) {
            orderDiscountToggle.addEventListener('change', (e) => {
                this.handleOrderDiscountToggle(e.target.checked);
            });
        }

        // Discount type selection
        document.querySelectorAll('input[name="discount_type"]').forEach(radio => {
            radio.addEventListener('change', () => this.handleDiscountTypeChange());
        });
    }

    handleOrderDiscountToggle(enabled) {
        this.orderDiscountEnabled = enabled;
        posState.set('hasOrderDiscount', enabled);

        if (enabled) {
            // Disable product-level discounts
            this.disableProductDiscounts();
            document.getElementById('order-discount-section').style.display = 'block';
        } else {
            document.getElementById('order-discount-section').style.display = 'none';
        }
    }

    handleDiscountTypeChange() {
        const type = document.querySelector('input[name="discount_type"]:checked')?.value;
        posState.set('isPercentageDiscount', type === 'percentage');

        // Show/hide relevant fields
        document.getElementById('percentage-discount-field').style.display =
            type === 'percentage' ? 'block' : 'none';
        document.getElementById('fixed-discount-field').style.display =
            type === 'fixed' ? 'block' : 'none';
    }

    disableProductDiscounts() {
        // Disable all product discount inputs
        document.querySelectorAll('.discount-fixed-input').forEach(input => {
            input.disabled = true;
            input.value = '0.00';
        });

        this.productDiscountEnabled = false;
    }

    enableProductDiscounts() {
        // Enable all product discount inputs
        document.querySelectorAll('.discount-fixed-input').forEach(input => {
            input.disabled = false;
        });

        this.productDiscountEnabled = true;
    }

    applyOrderDiscount(subtotal, discountType, discountValue) {
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = roundToDecimals((subtotal * discountValue) / 100, 2);
        } else {
            discountAmount = roundToDecimals(discountValue, 2);
        }

        return {
            discountAmount,
            finalAmount: roundToDecimals(subtotal - discountAmount, 2)
        };
    }

    calculateProductDiscount(mrp, price) {
        const discountFixed = mrp - price;
        const discountPercent = (discountFixed / mrp) * 100;

        return {
            discountFixed: roundToDecimals(discountFixed, 2),
            discountPercent: roundToDecimals(discountPercent, 2)
        };
    }
}

export const discountManager = new DiscountManager();
export default discountManager;
