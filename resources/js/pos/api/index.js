/**
 * API Index
 * Central export for all API modules
 */

// Import all APIs first
import { apiClient, APIClient } from './client.js';
import { productsAPI, ProductsAPI } from './products.js';
import { customersAPI, CustomersAPI } from './customers.js';
import { salesAPI, SalesAPI } from './sales.js';
import { locationsAPI, LocationsAPI } from './locations.js';

// Re-export individual modules
export { apiClient, APIClient };
export { productsAPI, ProductsAPI };
export { customersAPI, CustomersAPI };
export { salesAPI, SalesAPI };
export { locationsAPI, LocationsAPI };

// Create a unified API object
export const api = {
    products: productsAPI,
    customers: customersAPI,
    sales: salesAPI,
    locations: locationsAPI
};

export default api;
