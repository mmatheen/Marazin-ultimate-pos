/**
 * Payments Module
 * Handles payment processing and payment methods
 */

import { posState } from '../state/index.js';
import { salesAPI } from '../api/sales.js';
import { validateSaleData } from '../utils/validation.js';
import { billingManager } from './billing.js';

export class PaymentManager {
    constructor() {
        this.paymentModal = null;
    }

    initialize() {
        this.paymentModal = document.getElementById('payment-modal');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.handlePaymentMethodChange(e.target.value));
        });

        // Finalize payment button
        const finalizeBtn = document.getElementById('finalize-payment-btn');
        if (finalizeBtn) {
            finalizeBtn.addEventListener('click', () => this.processSale());
        }
    }

    handlePaymentMethodChange(method) {
        posState.set('selectedPaymentMethod', method);

        // Show/hide relevant payment fields
        document.querySelectorAll('.payment-fields').forEach(el => el.style.display = 'none');
        const methodFields = document.getElementById(`${method}-fields`);
        if (methodFields) methodFields.style.display = 'block';
    }

    async processSale() {
        const saleData = this.gatherSaleData();

        // Validate
        const validation = validateSaleData(saleData);
        if (!validation.valid) {
            toastr.error(validation.errors.join('<br>'), 'Validation Error');
            return;
        }

        try {
            const isEditing = posState.get('isEditing');
            let response;

            if (isEditing) {
                const saleId = posState.get('currentEditingSaleId');
                response = await salesAPI.updateSale(saleId, saleData);
            } else {
                response = await salesAPI.createSale(saleData);
            }

            if (response.success) {
                toastr.success('Sale processed successfully', 'Success');
                this.resetPOS();

                // Print invoice
                if (confirm('Print invoice?')) {
                    window.open(`/sell/pos/print/${response.sale_id}`, '_blank');
                }
            }
        } catch (error) {
            toastr.error(error.message, 'Error');
        }
    }

    gatherSaleData() {
        return {
            location_id: posState.get('selectedLocationId'),
            customer_id: posState.get('currentCustomer')?.id,
            payment_method: posState.get('selectedPaymentMethod'),
            items: billingManager.getBillingData(),
            subtotal: posState.get('subtotal'),
            discount: posState.get('totalDiscount'),
            final_total: posState.get('finalTotal'),
            shipping: posState.get('shippingData'),
            notes: document.getElementById('sale-notes')?.value || ''
        };
    }

    resetPOS() {
        posState.reset();
        billingManager.clearBillingTable();
        if (this.paymentModal) {
            $(this.paymentModal).modal('hide');
        }
    }
}

export const paymentManager = new PaymentManager();
export default paymentManager;
