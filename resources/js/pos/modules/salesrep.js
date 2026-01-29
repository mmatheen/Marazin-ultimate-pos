/**
 * Sales Rep Module
 * Handles sales representative restrictions and filtering
 */

import { posState } from '../state/index.js';
import { customersAPI } from '../api/customers.js';
import { locationsAPI } from '../api/locations.js';

export class SalesRepManager {
    constructor() {
        this.isSalesRep = false;
        this.salesRepId = null;
    }

    initialize() {
        this.checkSalesRepStatus();
        this.setupEventListeners();
    }

    checkSalesRepStatus() {
        // Check if current user is sales rep
        const userData = window.userData || {};
        this.isSalesRep = userData.is_sales_rep || false;
        this.salesRepId = userData.sales_rep_id || null;

        posState.update({
            isSalesRep: this.isSalesRep,
            salesRepId: this.salesRepId
        });

        if (this.isSalesRep) {
            this.applySalesRepRestrictions();
        }
    }

    async applySalesRepRestrictions() {
        // Restrict locations
        await this.restrictLocations();

        // Restrict customers
        await this.restrictCustomers();
    }

    async restrictLocations() {
        try {
            const locations = await locationsAPI.getLocationsBySalesRep(this.salesRepId);

            // Update location dropdown
            const locationSelect = document.getElementById('location-select');
            if (locationSelect) {
                locationSelect.innerHTML = locations.map(loc =>
                    `<option value="${loc.id}">${loc.name}</option>`
                ).join('');
            }
        } catch (error) {
            console.error('Error restricting locations:', error);
        }
    }

    async restrictCustomers() {
        try {
            const customers = await customersAPI.getCustomersBySalesRep(this.salesRepId);

            // Store in state for filtering
            posState.set('salesRepCustomers', customers);
            posState.set('salesRepCustomersFiltered', true);
        } catch (error) {
            console.error('Error restricting customers:', error);
        }
    }

    filterCustomersByRoute(routeId) {
        if (!this.isSalesRep) return;

        customersAPI.getCustomersByRoute(routeId).then(customers => {
            // Update customer dropdown
            const customerSelect = $('#customer-select');
            if (customerSelect.length) {
                customerSelect.empty();
                customers.forEach(customer => {
                    customerSelect.append(new Option(customer.name, customer.id));
                });
            }
        });
    }

    setupEventListeners() {
        // Route selection for filtering
        const routeSelect = document.getElementById('route-select');
        if (routeSelect) {
            routeSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.filterCustomersByRoute(e.target.value);
                }
            });
        }
    }
}

export const salesRepManager = new SalesRepManager();
export default salesRepManager;
