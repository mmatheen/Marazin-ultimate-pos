/**
 * Sales API
 * All sales-related API calls
 */

import { apiClient } from './client.js';

export class SalesAPI {
    constructor(client) {
        this.client = client;
    }

    /**
     * Create new sale
     * @param {Object} saleData - Sale data
     * @returns {Promise<Object>} Created sale
     */
    async createSale(saleData) {
        return this.client.post('/sell/pos/store', saleData);
    }

    /**
     * Update existing sale
     * @param {number} saleId - Sale ID
     * @param {Object} saleData - Updated sale data
     * @returns {Promise<Object>} Updated sale
     */
    async updateSale(saleId, saleData) {
        return this.client.put(`/sell/pos/update/${saleId}`, saleData);
    }

    /**
     * Get sale by ID
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Sale data
     */
    async getSale(saleId) {
        return this.client.get(`/sell/pos/get-sale/${saleId}`);
    }

    /**
     * Delete sale
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Delete response
     */
    async deleteSale(saleId) {
        return this.client.delete(`/sell/pos/delete/${saleId}`);
    }

    /**
     * Get recent transactions
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Transactions data
     */
    async getRecentTransactions(params = {}) {
        const defaultParams = {
            limit: 50,
            location_id: null,
            customer_id: null,
            start_date: null,
            end_date: null
        };

        const queryParams = { ...defaultParams, ...params };

        return this.client.get('/sell/pos/recent-transactions', queryParams);
    }

    /**
     * Get sale for editing
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Sale data with items
     */
    async getSaleForEdit(saleId) {
        return this.client.get(`/sell/pos/edit/${saleId}`);
    }

    /**
     * Suspend sale (draft)
     * @param {Object} saleData - Sale data to suspend
     * @returns {Promise<Object>} Suspended sale
     */
    async suspendSale(saleData) {
        return this.client.post('/sell/pos/suspend', saleData);
    }

    /**
     * Get suspended sales
     * @param {Object} params - Query parameters
     * @returns {Promise<Array>} Suspended sales array
     */
    async getSuspendedSales(params = {}) {
        return this.client.get('/sell/pos/suspended-sales', params);
    }

    /**
     * Resume suspended sale
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Sale data
     */
    async resumeSuspendedSale(saleId) {
        return this.client.get(`/sell/pos/resume-suspended/${saleId}`);
    }

    /**
     * Delete suspended sale
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Delete response
     */
    async deleteSuspendedSale(saleId) {
        return this.client.delete(`/sell/pos/delete-suspended/${saleId}`);
    }

    /**
     * Print sale invoice
     * @param {number} saleId - Sale ID
     * @returns {Promise<Blob>} PDF blob
     */
    async printInvoice(saleId) {
        return this.client.get(`/sell/pos/print/${saleId}`, {}, {
            responseType: 'blob'
        });
    }

    /**
     * Email sale invoice
     * @param {number} saleId - Sale ID
     * @param {string} email - Email address
     * @returns {Promise<Object>} Email response
     */
    async emailInvoice(saleId, email) {
        return this.client.post(`/sell/pos/email-invoice/${saleId}`, {
            email: email
        });
    }

    /**
     * Get sale total by payment method
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Totals data
     */
    async getSalesTotalsByPaymentMethod(params = {}) {
        return this.client.get('/sell/pos/totals-by-payment', params);
    }

    /**
     * Check sale return eligibility
     * @param {number} saleId - Sale ID
     * @returns {Promise<Object>} Eligibility data
     */
    async checkReturnEligibility(saleId) {
        return this.client.get(`/sell/pos/check-return/${saleId}`);
    }

    /**
     * Create sale return
     * @param {Object} returnData - Return data
     * @returns {Promise<Object>} Created return
     */
    async createReturn(returnData) {
        return this.client.post('/sell/pos/create-return', returnData);
    }

    /**
     * Validate sale data before submission
     * @param {Object} saleData - Sale data to validate
     * @returns {Promise<Object>} Validation result
     */
    async validateSale(saleData) {
        return this.client.post('/sell/pos/validate', saleData);
    }

    /**
     * Get sale invoice number
     * @returns {Promise<Object>} Next invoice number
     */
    async getNextInvoiceNumber() {
        return this.client.get('/sell/pos/next-invoice-number');
    }

    /**
     * Update sale shipping details
     * @param {number} saleId - Sale ID
     * @param {Object} shippingData - Shipping data
     * @returns {Promise<Object>} Updated sale
     */
    async updateShipping(saleId, shippingData) {
        return this.client.patch(`/sell/pos/update-shipping/${saleId}`, shippingData);
    }

    /**
     * Record payment
     * @param {number} saleId - Sale ID
     * @param {Object} paymentData - Payment data
     * @returns {Promise<Object>} Payment record
     */
    async recordPayment(saleId, paymentData) {
        return this.client.post(`/sell/pos/record-payment/${saleId}`, paymentData);
    }

    /**
     * Get sale payments
     * @param {number} saleId - Sale ID
     * @returns {Promise<Array>} Payments array
     */
    async getSalePayments(saleId) {
        return this.client.get(`/sell/pos/payments/${saleId}`);
    }
}

// Create singleton instance
export const salesAPI = new SalesAPI(apiClient);

export default salesAPI;
