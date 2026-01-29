/**
 * Product Search Module
 * Handles jQuery UI autocomplete for product search
 */

import { posState } from '../state/index.js';
import { apiClient } from '../api/client.js';
import { billingManager } from './billing.js';

export class ProductSearchManager {
    constructor() {
        this.searchInput = null;
        this.autocompleteState = {
            isRequesting: false,
            adding: false,
            currentTerm: '',
            lastResults: [],
            debounceTimer: null
        };
    }

    /**
     * Initialize product search
     */
    initialize() {
        this.searchInput = document.getElementById('productSearchInput');
        if (this.searchInput) {
            this.initAutocomplete();
            this.setupAutoFocus();
        } else {
            console.warn('Product search input not found');
        }
    }

    /**
     * Initialize jQuery UI autocomplete
     */
    initAutocomplete() {
        const self = this;

        $(this.searchInput).autocomplete({
            position: { my: "left top", at: "left bottom", collision: "none" },
            minLength: 1,
            delay: 0,
            source: function(request, response) {
                const locationId = posState.get('selectedLocationId');
                if (!locationId) {
                    console.warn('No location selected');
                    return response([]);
                }

                self.autocompleteState.currentTerm = request.term;
                self.resetAutocompleteState();

                // Debounce requests
                self.autocompleteState.debounceTimer = setTimeout(async () => {
                    if (!self.autocompleteState.isRequesting) {
                        self.autocompleteState.isRequesting = true;
                        await self.searchProducts(request.term, locationId, response);
                    }
                }, 100);
            },
            select: function(event, ui) {
                console.log('Product selected:', ui.item);

                // Handle quick add option
                if (ui.item.showQuickAdd && ui.item.searchTerm) {
                    event.preventDefault();
                    $(self.searchInput).val('');
                    self.showQuickAddOption(ui.item.searchTerm);
                    return false;
                }

                // Handle not available products
                if (ui.item.notAvailable) {
                    event.preventDefault();
                    $(self.searchInput).val('');
                    return false;
                }

                if (!ui.item.product || self.autocompleteState.adding) return false;

                self.autocompleteState.adding = true;
                $(self.searchInput).val("");
                self.addProductFromAutocomplete(ui.item);
                setTimeout(() => self.autocompleteState.adding = false, 50);
                return false;
            },
            focus: function(event, ui) {
                event.preventDefault();
                if (ui.item && ui.item.product) {
                    self.showSearchIndicator("↵ Press Enter to add");
                }
                return false;
            },
            open: function(event, ui) {
                console.log('Autocomplete menu opened');
                const instance = $(self.searchInput).autocomplete("instance");
                if (instance && instance.menu && instance.menu.element) {
                    instance.menu.element.css({
                        'max-height': '350px',
                        'overflow-y': 'auto',
                        'overflow-x': 'hidden',
                        'z-index': 10000
                    });
                }
            },
            close: function() {
                console.log('Autocomplete menu closed');
                self.hideSearchIndicator();
            }
        });

        // Setup custom rendering
        this.setupCustomRendering();

        console.log('✅ Product autocomplete initialized');
    }

    /**
     * Search products via API
     * EXACT copy from pos_ajax.blade.php logic
     */
    async searchProducts(term, locationId, response) {
        try {
            const data = await apiClient.get('/products/stocks/autocomplete', {
                search: term,
                location_id: locationId,
                per_page: 50
            });

            this.autocompleteState.isRequesting = false;

            if (data.status !== 200 || !Array.isArray(data.data)) {
                this.autocompleteState.lastResults = [];
                return response([{ label: "No results found", value: "" }]);
            }

            console.log(`Autocomplete API returned ${data.data.length} products for search term: "${term}"`);

            // Filter stocks (remove out of stock items)
            const filtered = this.filterStockData(data.data);

            // Map to autocomplete format
            const results = this.mapSearchResults(filtered, term);

            console.log(`After filtering: ${results.length} products will be shown in autocomplete dropdown`);

            if (results.length === 0) {
                // No results - could show quick add option
                return response([{
                    label: "No results found",
                    value: "",
                    showQuickAdd: true,
                    searchTerm: term
                }]);
            }

            this.autocompleteState.lastResults = results.filter(r => r.product);

            // Return results to autocomplete
            response(results);

        } catch (error) {
            console.error('Product search error:', error);
            this.autocompleteState.isRequesting = false;
            response([{ label: "Error loading products", value: "" }]);
        }
    }

    /**
     * Map search results to autocomplete format
     * EXACT copy from pos_ajax.blade.php
     */
    mapSearchResults(filteredStocks, term) {
        const results = [];

        for (let i = 0; i < filteredStocks.length; i++) {
            const stock = filteredStocks[i];
            const { imeiMatch, exactImeiMatch, imeiNumber } = this.findImeiMatch(stock, term);

            results.push({
                label: this.createProductLabel(stock, imeiMatch, imeiNumber),
                value: stock.product.product_name,
                product: stock.product,
                stockData: stock,
                imeiMatch: !!imeiMatch,
                exactImeiMatch: exactImeiMatch
            });
        }

        return results;
    }

    /**
     * Filter stock data - EXACT copy from pos_ajax.blade.php
     */
    filterStockData(stockArray) {
        return stockArray.filter(stock => {
            if (!stock.product) return false;

            // Fast path for unlimited stock
            if (stock.product.stock_alert == 0) return true;

            // Check stock level
            const stockLevel = stock.product.unit?.allow_decimal ?
                parseFloat(stock.total_stock) : parseInt(stock.total_stock);
            return stockLevel > 0;
        });
    }

    /**
     * Find IMEI match - EXACT copy from pos_ajax.blade.php
     */
    findImeiMatch(stock, term) {
        if (!stock.imei_numbers || stock.imei_numbers.length === 0) {
            return { imeiMatch: '', exactImeiMatch: false, imeiNumber: '' };
        }

        // Filter only available IMEI numbers
        const availableImeis = stock.imei_numbers.filter(imei =>
            imei.status === 'available' || imei.status === undefined
        );

        const matchingImei = availableImeis.find(imei =>
            imei.imei_number && imei.imei_number.toLowerCase().includes(term.toLowerCase())
        );

        if (matchingImei) {
            return {
                imeiMatch: ` 📱 IMEI: ${matchingImei.imei_number}`,
                exactImeiMatch: matchingImei.imei_number.toLowerCase() === term.toLowerCase(),
                imeiNumber: matchingImei.imei_number
            };
        }

        return { imeiMatch: '', exactImeiMatch: false, imeiNumber: '' };
    }

    /**
     * Create product label - EXACT copy from pos_ajax.blade.php
     */
    createProductLabel(stock, imeiMatch, imeiNumber) {
        const product = stock.product;
        const stockDisplay = product.stock_alert == 0 ? 'Unlimited' : stock.total_stock;
        return `${product.product_name} (${product.sku || ''})${imeiMatch} [Stock: ${stockDisplay}]`;
    }

    /**
     * Add product from autocomplete selection
     * EXACT copy from pos_ajax.blade.php
     */
    async addProductFromAutocomplete(item) {
        try {
            if (!item.product) {
                console.error('No product in item');
                return;
            }

            // Prevent duplicates
            if (this.autocompleteState.lastProduct &&
                this.autocompleteState.lastProduct.id === item.product.id) {
                console.log('Preventing duplicate add:', item.product.product_name);
                this.searchInput.focus();
                return;
            }

            this.autocompleteState.lastProduct = item.product;
            console.log('Adding product:', item.product.product_name);

            // The item already has stockData from search results
            const stockEntry = item.stockData;

            if (!stockEntry) {
                console.error('No stock data available');
                toastr.error('Stock data not available');
                return;
            }

            // Call global addProductToTable function (from pos_ajax.blade.php)
            if (typeof window.addProductToTable === 'function') {
                window.addProductToTable(item.product, '', 'MANUAL');
            } else {
                console.error('addProductToTable function not found');
                toastr.error('Cannot add product - billing function not available');
            }

            // Auto-focus search input for next product
            setTimeout(() => {
                if (this.searchInput) {
                    this.searchInput.focus();
                }
            }, 200);

        } catch (error) {
            console.error('Error adding product:', error);
            toastr.error('Failed to add product');
        }
    }

    /**
     * Reset autocomplete state
     */
    resetAutocompleteState() {
        if (this.autocompleteState.debounceTimer) {
            clearTimeout(this.autocompleteState.debounceTimer);
        }
        this.autocompleteState.isRequesting = false;
    }

    /**
     * Setup auto-focus on product search input
     */
    setupAutoFocus() {
        setTimeout(() => {
            if (this.searchInput) {
                this.searchInput.focus();
                this.searchInput.select();
                console.log('Product search input auto-focused');
            }
        }, 500);
    }

    /**
     * Show search indicator
     */
    showSearchIndicator(message) {
        // Implement visual indicator if needed
        console.log('Search indicator:', message);
    }

    /**
     * Hide search indicator
     */
    hideSearchIndicator() {
        // Implement hiding indicator
    }

    /**
     * Show quick add product modal
     */
    showQuickAddOption(searchTerm) {
        console.log('Quick add product:', searchTerm);
        toastr.info('Quick add product feature - to be implemented');
    }

    /**
     * Setup custom rendering for autocomplete items
     * EXACT copy from pos_ajax.blade.php
     */
    setupCustomRendering() {
        const instance = $(this.searchInput).autocomplete("instance");
        if (!instance) return;

        instance._renderItem = function(ul, item) {
            const li = $("<li>")
                .addClass("ui-menu-item")
                .data("ui-autocomplete-item", item);

            if (item.product) {
                if (item.imeiMatch) {
                    li.append(createImeiItemHtml(item));
                } else {
                    li.append(`<div class="autocomplete-item" style="padding: 8px 12px;">${item.label}</div>`);
                }
            } else {
                li.append(`<div class="autocomplete-item no-product" style="color: red; padding: 8px 12px; font-style: italic;">${item.label}</div>`);
            }

            return li.appendTo(ul);
        };

        instance._resizeMenu = function() {
            const isMobile = window.innerWidth <= 991;
            if (isMobile) {
                this.menu.element.css({
                    'width': (window.innerWidth - 10) + 'px',
                    'max-width': (window.innerWidth - 10) + 'px',
                    'left': '5px',
                    'max-height': '350px',
                    'overflow-y': 'auto',
                    'overflow-x': 'hidden'
                });
            } else {
                const menuWidth = Math.max(this.element.outerWidth(), 450);
                this.menu.element.css({
                    'width': menuWidth + 'px',
                    'max-height': '350px',
                    'overflow-y': 'auto',
                    'overflow-x': 'hidden'
                });
            }
        };

        console.log('✅ Custom autocomplete rendering setup');
    }
}

function createImeiItemHtml(item) {
    const productName = item.product.product_name;
    const sku = item.product.sku || '';
    const imeiInfo = item.label.match(/📱 IMEI: ([^\[]+)/);
    const imeiNumber = imeiInfo ? imeiInfo[1].trim() : '';
    const stockInfo = item.label.match(/\[Stock: ([^\]]+)\]/);
    const stock = stockInfo ? stockInfo[1] : '';

    return `
        <div style="padding: 10px 12px; background-color: #e8f4f8; border-left: 4px solid #17a2b8;">
            <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                ${productName} ${sku ? '<span style="color: #6c757d; font-size: 0.9em;">(' + sku + ')</span>' : ''}
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9em;">
                <div style="color: #17a2b8; font-weight: 500;">📱 IMEI: ${imeiNumber}</div>
                <div style="color: #28a745; font-weight: 500; padding-left: 10px;">Stock: ${stock}</div>
            </div>
        </div>
    `;
}

export const productSearchManager = new ProductSearchManager();
export default productSearchManager;
