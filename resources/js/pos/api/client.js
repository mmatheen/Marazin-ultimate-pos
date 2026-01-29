/**
 * POS API Client
 * Base API client with error handling and retries
 */

import { retryWithBackoff } from '../utils/helpers.js';
import { config } from '../state/config.js';

export class APIClient {
    constructor(baseURL = '') {
        this.baseURL = baseURL;
        this.csrfToken = this.getCSRFToken();
    }

    /**
     * Get CSRF token from meta tag
     * @returns {string} CSRF token
     */
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Make safe fetch request with error handling
     * @param {string} url - URL to fetch
     * @param {Object} options - Fetch options
     * @returns {Promise<Object>} Response data
     */
    async safeFetch(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };

        // Merge headers
        if (options.headers) {
            defaultOptions.headers = {
                ...defaultOptions.headers,
                ...options.headers
            };
        }

        try {
            const response = await fetch(this.baseURL + url, defaultOptions);

            // Handle HTTP errors
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            if (config.development.logApiCalls) {
                console.error('API Error:', error);
            }
            throw this.handleError(error);
        }
    }

    /**
     * Make GET request
     * @param {string} url - URL to fetch
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Response data
     */
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;

        return this.safeFetch(fullUrl, {
            method: 'GET'
        });
    }

    /**
     * Make POST request
     * @param {string} url - URL to post to
     * @param {Object} data - Data to send
     * @returns {Promise<Object>} Response data
     */
    async post(url, data = {}) {
        return this.safeFetch(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * Make PUT request
     * @param {string} url - URL to put to
     * @param {Object} data - Data to send
     * @returns {Promise<Object>} Response data
     */
    async put(url, data = {}) {
        return this.safeFetch(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * Make PATCH request
     * @param {string} url - URL to patch
     * @param {Object} data - Data to send
     * @returns {Promise<Object>} Response data
     */
    async patch(url, data = {}) {
        return this.safeFetch(url, {
            method: 'PATCH',
            body: JSON.stringify(data)
        });
    }

    /**
     * Make DELETE request
     * @param {string} url - URL to delete
     * @returns {Promise<Object>} Response data
     */
    async delete(url) {
        return this.safeFetch(url, {
            method: 'DELETE'
        });
    }

    /**
     * Make request with retry logic
     * @param {Function} requestFn - Request function to retry
     * @param {number} maxRetries - Maximum retry attempts
     * @returns {Promise<Object>} Response data
     */
    async withRetry(requestFn, maxRetries = config.errors.retryAttempts) {
        return retryWithBackoff(
            requestFn,
            maxRetries,
            config.errors.retryDelay
        );
    }

    /**
     * Handle API errors
     * @param {Error} error - Error object
     * @returns {Error} Formatted error
     */
    handleError(error) {
        if (config.errors.showUserFriendlyMessages) {
            // Map technical errors to user-friendly messages
            if (error.message.includes('Failed to fetch')) {
                return new Error('Network error. Please check your connection.');
            }
            if (error.message.includes('401')) {
                return new Error('Session expired. Please refresh the page.');
            }
            if (error.message.includes('403')) {
                return new Error('You do not have permission to perform this action.');
            }
            if (error.message.includes('404')) {
                return new Error('Resource not found.');
            }
            if (error.message.includes('500')) {
                return new Error('Server error. Please try again later.');
            }
        }

        return error;
    }

    /**
     * Upload file
     * @param {string} url - Upload URL
     * @param {FormData} formData - Form data with file
     * @returns {Promise<Object>} Response data
     */
    async upload(url, formData) {
        return this.safeFetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
                // Don't set Content-Type for FormData
            },
            body: formData
        });
    }
}

// Create singleton instance
export const apiClient = new APIClient();

export default apiClient;
