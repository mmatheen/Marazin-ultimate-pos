/**
 * Sale Service
 * All sale-related API calls
 * Extracted from lines 7401-8300 of monolithic pos_ajax.blade.php
 */

import apiClient from './apiClient.js';
import { POSState } from '../core/config.js';

class SaleService {
    /**
     * Create new sale
     */
    async createSale(saleData) {
        const url = '/sales/create';

        try {
            console.log('📝 Creating sale:', saleData);
            return await apiClient.post(url, saleData);
        } catch (error) {
            console.error('Failed to create sale:', error);
            throw error;
        }
    }

    /**
     * Update existing sale
     */
    async updateSale(saleId, saleData) {
        const url = `/sales/update/${saleId}`;

        try {
            console.log('📝 Updating sale:', saleId, saleData);
            return await apiClient.put(url, saleData);
        } catch (error) {
            console.error('Failed to update sale:', error);
            throw error;
        }
    }

    /**
     * Fetch sale by ID (for edit mode)
     */
    async fetchSaleById(saleId) {
        const url = `/sales/${saleId}`;

        try {
            const data = await apiClient.get(url);
            console.log('📊 Sale data fetched:', data);
            return data;
        } catch (error) {
            console.error('Failed to fetch sale:', error);
            throw error;
        }
    }

    /**
     * Fetch recent sales for transaction modal
     */
    async fetchRecentSales() {
        // Check if data is fresh enough
        if (POSState.sales.length > 0 &&
            (Date.now() - POSState.lastSalesDataFetch) < 30000) {
            console.log('✅ Using cached sales data');
            return POSState.sales;
        }

        const url = '/sales/recent';

        try {
            const data = await apiClient.get(url);

            // Cache the results
            POSState.sales = data;
            POSState.lastSalesDataFetch = Date.now();

            console.log('📊 Recent sales fetched:', data.length);
            return data;
        } catch (error) {
            console.error('Failed to fetch recent sales:', error);
            return [];
        }
    }

    /**
     * Suspend current sale
     */
    async suspendSale(saleData) {
        const url = '/sales/suspend';

        try {
            return await apiClient.post(url, saleData);
        } catch (error) {
            console.error('Failed to suspend sale:', error);
            throw error;
        }
    }

    /**
     * Resume suspended sale
     */
    async resumeSale(saleId) {
        const url = `/sales/resume/${saleId}`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to resume sale:', error);
            throw error;
        }
    }

    /**
     * Create draft sale
     */
    async createDraft(saleData) {
        const url = '/sales/draft';

        try {
            return await apiClient.post(url, saleData);
        } catch (error) {
            console.error('Failed to create draft:', error);
            throw error;
        }
    }

    /**
     * Create quotation
     */
    async createQuotation(saleData) {
        const url = '/sales/quotation';

        try {
            return await apiClient.post(url, saleData);
        } catch (error) {
            console.error('Failed to create quotation:', error);
            throw error;
        }
    }

    /**
     * Create sale order
     */
    async createSaleOrder(saleData) {
        const url = '/sales/sale-order';

        try {
            return await apiClient.post(url, saleData);
        } catch (error) {
            console.error('Failed to create sale order:', error);
            throw error;
        }
    }

    /**
     * Create job ticket
     */
    async createJobTicket(jobData) {
        const url = '/sales/job-ticket';

        try {
            return await apiClient.post(url, jobData);
        } catch (error) {
            console.error('Failed to create job ticket:', error);
            throw error;
        }
    }

    /**
     * Delete sale
     */
    async deleteSale(saleId) {
        const url = `/sales/delete/${saleId}`;

        try {
            return await apiClient.delete(url);
        } catch (error) {
            console.error('Failed to delete sale:', error);
            throw error;
        }
    }

    /**
     * Print receipt
     */
    printReceipt(saleId) {
        const printUrl = `/sales/print/${saleId}`;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');

        if (!printWindow) {
            console.error('Failed to open print window - popup blocked?');
            alert('Please allow popups to print receipts');
        }

        return printWindow;
    }

    /**
     * Log pricing error
     */
    async logPricingError(errorData) {
        const url = '/pos/log-pricing-error';

        try {
            await apiClient.post(url, errorData);
        } catch (error) {
            console.error('Failed to log pricing error:', error);
        }
    }

    /**
     * Clear sales cache
     */
    async clearCache() {
        const url = '/api/sales/clear-cache';

        try {
            return await apiClient.post(url);
        } catch (error) {
            console.error('Failed to clear cache:', error);
            throw error;
        }
    }
}

const saleService = new SaleService();
export default saleService;
