/**
 * API Client Base
 * Centralized fetch wrapper with retry logic, error handling, and CSRF protection
 * Extracted from lines 2900-3100 (fetch logic) of monolithic pos_ajax.blade.php
 */

import { POSConfig } from '../core/config.js';
import { ERROR_MESSAGES } from '../core/constants.js';
import { showToast } from '../core/utils.js';

class APIClient {
    constructor() {
        this.retryCount = new Map();
        this.maxRetries = POSConfig.api.maxRetries;
        this.baseRetryDelay = POSConfig.api.baseRetryDelay;
    }

    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Build request headers
     */
    buildHeaders(customHeaders = {}) {
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.getCSRFToken(),
            ...customHeaders
        };
    }

    /**
     * Handle HTTP response with error checking
     */
    async handleResponse(response, url, attemptNumber = 0) {
        // Rate limiting (429) - implement exponential backoff
        if (response.status === 429) {
            const retryAfter = parseInt(response.headers.get('Retry-After') || '2', 10) * 1000;
            const exponentialDelay = Math.min(this.baseRetryDelay * Math.pow(2, attemptNumber), 10000);
            const finalDelay = Math.max(retryAfter, exponentialDelay);

            console.warn(`Rate limited (429). Attempt ${attemptNumber + 1}/${this.maxRetries}. Retrying after ${finalDelay}ms`);

            if (attemptNumber < this.maxRetries - 1) {
                await this.sleep(finalDelay);
                throw {
                    isRetryable: true,
                    attemptNumber: attemptNumber + 1,
                    message: '429 - Retrying'
                };
            } else {
                throw {
                    isHandled: true,
                    message: 'Rate limit exceeded. Please try again later.',
                    status: 429
                };
            }
        }

        // CSRF token mismatch (419)
        if (response.status === 419) {
            console.error('CSRF token mismatch (419)');
            showToast(ERROR_MESSAGES.SESSION_EXPIRED, 'error');
            throw {
                isHandled: true,
                message: ERROR_MESSAGES.SESSION_EXPIRED,
                status: 419
            };
        }

        // Other HTTP errors
        if (!response.ok) {
            const text = await response.text();
            console.error(`HTTP ${response.status} error:`, text);
            throw {
                isHandled: true,
                status: response.status,
                text,
                message: `Server error (${response.status}). Please try again.`
            };
        }

        // Validate JSON response
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response received:', text.substring(0, 200));
            throw {
                isHandled: true,
                text,
                message: 'Invalid response format. Please check server configuration.'
            };
        }

        return await response.json();
    }

    /**
     * Sleep helper for retry delays
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * GET request with retry logic
     */
    async get(url, options = {}, attemptNumber = 0) {
        const fetchOptions = {
            method: 'GET',
            cache: 'no-store',
            headers: this.buildHeaders(options.headers),
            ...options
        };

        try {
            console.log(`GET ${url} (attempt ${attemptNumber + 1})`);
            const response = await fetch(url, fetchOptions);
            return await this.handleResponse(response, url, attemptNumber);
        } catch (error) {
            if (error.isRetryable && error.attemptNumber < this.maxRetries) {
                return this.get(url, options, error.attemptNumber);
            }

            if (!error.isHandled) {
                console.error('GET request failed:', error);
                showToast(ERROR_MESSAGES.NETWORK_ERROR, 'error');
            }

            throw error;
        }
    }

    /**
     * POST request with retry logic
     */
    async post(url, data = {}, options = {}, attemptNumber = 0) {
        const fetchOptions = {
            method: 'POST',
            cache: 'no-store',
            headers: this.buildHeaders(options.headers),
            body: JSON.stringify(data),
            ...options
        };

        try {
            console.log(`POST ${url} (attempt ${attemptNumber + 1})`);
            const response = await fetch(url, fetchOptions);
            return await this.handleResponse(response, url, attemptNumber);
        } catch (error) {
            if (error.isRetryable && error.attemptNumber < this.maxRetries) {
                return this.post(url, data, options, error.attemptNumber);
            }

            if (!error.isHandled) {
                console.error('POST request failed:', error);
                showToast(ERROR_MESSAGES.NETWORK_ERROR, 'error');
            }

            throw error;
        }
    }

    /**
     * PUT request
     */
    async put(url, data = {}, options = {}) {
        const fetchOptions = {
            method: 'PUT',
            cache: 'no-store',
            headers: this.buildHeaders(options.headers),
            body: JSON.stringify(data),
            ...options
        };

        try {
            const response = await fetch(url, fetchOptions);
            return await this.handleResponse(response, url);
        } catch (error) {
            if (!error.isHandled) {
                console.error('PUT request failed:', error);
                showToast(ERROR_MESSAGES.NETWORK_ERROR, 'error');
            }
            throw error;
        }
    }

    /**
     * DELETE request
     */
    async delete(url, options = {}) {
        const fetchOptions = {
            method: 'DELETE',
            cache: 'no-store',
            headers: this.buildHeaders(options.headers),
            ...options
        };

        try {
            const response = await fetch(url, fetchOptions);
            return await this.handleResponse(response, url);
        } catch (error) {
            if (!error.isHandled) {
                console.error('DELETE request failed:', error);
                showToast(ERROR_MESSAGES.NETWORK_ERROR, 'error');
            }
            throw error;
        }
    }

    /**
     * jQuery AJAX wrapper (for compatibility with existing code)
     */
    async jQueryAjax(options) {
        return new Promise((resolve, reject) => {
            $.ajax({
                ...options,
                headers: {
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                    ...options.headers
                },
                success: (data) => resolve(data),
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error('jQuery AJAX error:', textStatus, errorThrown);
                    reject({ jqXHR, textStatus, errorThrown });
                }
            });
        });
    }
}

// Create singleton instance
const apiClient = new APIClient();

export default apiClient;
