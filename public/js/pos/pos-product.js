/**
 * POS Product Manager
 * Handles all product display, filtering, and stock management
 */

class POSProductManager {
    constructor(cache) {
        this.cache = cache;
        this.allProducts = [];
        this.stockData = [];
        this.currentPage = 1;
        this.perPage = 50;
        this.hasMoreProducts = true;
        this.isLoading = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.baseRetryDelay = 1000;
        this.currentFilter = {
            type: null,  // 'category', 'subcategory', 'brand', or null
            id: null
        };
    }

    /**
     * Fetch paginated products
     */
    async fetchProducts(locationId, reset = false, attemptNumber = 0) {
        // Basic guards
        if (this.isLoading || !locationId || !this.hasMoreProducts) {
            console.log('⚠️ fetchProducts blocked:', {
                isLoading: this.isLoading,
                locationId,
                hasMoreProducts: this.hasMoreProducts
            });
            return;
        }

        this.isLoading = true;

        if (reset) {
            this.currentPage = 1;
            this.retryCount = 0;
            this.showLoader();
        } else {
            this.showLoaderSmall();
        }

        // Add with_stock=1 to fetch only products with stock > 0
        const url = `/products/stocks?location_id=${locationId}&page=${this.currentPage}&per_page=${this.perPage}&with_stock=1`;

        const fetchOptions = {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        };

        console.log(`Fetching products with stock: ${url} (attempt ${attemptNumber + 1})`);

        try {
            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                if (response.status === 429) {
                    // Rate limited: implement exponential backoff
                    const retryAfter = parseInt(response.headers.get('Retry-After') || '2', 10) * 1000;
                    const exponentialDelay = Math.min(this.baseRetryDelay * Math.pow(2, attemptNumber), 10000);
                    const finalDelay = Math.max(retryAfter, exponentialDelay);

                    console.warn(`Rate limited (429). Attempt ${attemptNumber + 1}/${this.maxRetries}. Retrying after ${finalDelay} ms`);

                    if (attemptNumber < this.maxRetries - 1) {
                        this.hasMoreProducts = false;
                        setTimeout(() => {
                            this.hasMoreProducts = true;
                            this.isLoading = false;
                            this.fetchProducts(locationId, reset, attemptNumber + 1);
                        }, finalDelay);
                        return;
                    } else {
                        throw new Error('Rate limit exceeded. Please try again later.');
                    }
                } else if (response.status === 419) {
                    throw new Error('Session expired. Please refresh the page.');
                } else {
                    const text = await response.text();
                    console.error(`HTTP ${response.status} error:`, text);
                    throw new Error(`Server error (${response.status}). Please try again.`);
                }
            }

            const data = await response.json();
            this.hideLoader();
            this.isLoading = false;
            this.retryCount = 0;

            console.log('✅ Products fetched successfully:', data);

            if (!data || data.status !== 200 || !Array.isArray(data.data)) {
                console.warn('⚠️ Invalid data structure received:', data);
                if (reset) {
                    this.displayEmptyState();
                }
                return;
            }

            if (reset) {
                this.allProducts = [];
                this.stockData = [];
                this.clearProductContainer();
            }

            // Add new products
            data.data.forEach(stock => this.allProducts.push(stock));
            this.stockData = [...this.allProducts];

            console.log(`📊 Page ${this.currentPage}: Received ${data.data.length} products`);

            // Display products
            this.displayProducts(reset ? this.allProducts : data.data, !reset);

            // Check if there are more pages
            if (data.data.length === 0 || data.data.length < this.perPage) {
                this.hasMoreProducts = false;
                console.log('📍 Reached last page of products');
            } else {
                this.hasMoreProducts = true;
                this.currentPage++;
            }

            // Show product list area
            this.showProductListArea();

        } catch (error) {
            this.hideLoader();
            this.isLoading = false;
            console.error('Error fetching products:', error);

            if (reset) {
                this.displayErrorState(error.message);
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Failed to load products. Please try again.', 'Error');
            }
        }
    }

    /**
     * Display products
     */
    displayProducts(products, append = false) {
        const posProduct = this.cache.getCachedElement('posProduct');

        if (!posProduct) {
            console.error('❌ posProduct element not found in DOM!');
            return;
        }

        if (!append) {
            posProduct.innerHTML = '';
        }

        console.log(`DisplayProducts called: ${products.length} products, append=${append}`);

        if (products.length === 0) {
            if (!append) {
                posProduct.innerHTML = '<p class="text-center">No products found.</p>';
            }
            return;
        }

        // Filter products with stock
        const filteredProducts = products.filter(stock => {
            const product = stock.product;

            // Check for unlimited stock
            if (product.stock_alert === 0) {
                return true;
            }

            // Get total_stock from the response
            const stockLevel = parseFloat(stock.total_stock) || 0;
            return stockLevel > 0;
        });

        console.log(`📊 Filtered: ${filteredProducts.length} out of ${products.length} products have stock`);

        if (filteredProducts.length === 0) {
            if (!append) {
                posProduct.innerHTML = '<p class="text-center text-warning">No products with stock available at this location.</p>';
            }
            return;
        }

        const newlyAddedCards = [];

        filteredProducts.forEach(stock => {
            const product = stock.product;

            // Check if product already exists (prevent duplicates)
            if (append && document.querySelector(`[data-id="${product.id}"]`)) {
                return;
            }

            // Show unit name
            const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';

            let quantityDisplay;
            if (product.stock_alert === 0) {
                quantityDisplay = `Unlimited`;
            } else if (product.unit && (product.unit.allow_decimal === true || product.unit.allow_decimal === 1)) {
                quantityDisplay = `${parseFloat(stock.total_stock).toFixed(4).replace(/\.?0+$/, '')} ${unitName} in stock`;
            } else {
                quantityDisplay = `${parseInt(stock.total_stock, 10)} ${unitName} in stock`;
            }

            // Create card element
            const cardDiv = document.createElement('div');
            cardDiv.className = 'col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12';

            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.setAttribute('data-id', product.id);

            // Create safe image
            const img = this.createSafeImage(product, 'width: 100%; height: auto; object-fit: cover;');

            const cardBody = document.createElement('div');
            cardBody.className = 'product-card-body';
            cardBody.innerHTML = `
                <h6>${product.product_name} <br>
                    <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span>
                </h6>
                <h6>
                    <span class="badge ${product.stock_alert === 0 ? 'bg-info' : stock.total_stock > 0 ? 'bg-success' : 'bg-warning'}">
                    ${quantityDisplay}
                    </span>
                </h6>
            `;

            productCard.appendChild(img);
            productCard.appendChild(cardBody);
            cardDiv.appendChild(productCard);
            posProduct.appendChild(cardDiv);

            newlyAddedCards.push(productCard);
        });

        // Add click events to new cards
        newlyAddedCards.forEach(card => {
            card.addEventListener('click', () => {
                const productId = card.getAttribute('data-id');
                const productStock = this.allProducts.find(stock => String(stock.product.id) === productId);
                if (productStock && typeof addProductToTable === 'function') {
                    addProductToTable(productStock.product);
                }
            });
        });

        console.log(`✅ DisplayProducts: Added ${newlyAddedCards.length} new product cards to DOM`);
    }

    /**
     * Create safe image with error handling
     */
    createSafeImage(product, styles = '', className = '', title = '') {
        const fallbackImage = '/assets/images/No Product Image Available.png';
        const img = document.createElement('img');

        img.src = this.getSafeImageUrl(product);
        if (styles) img.style.cssText = styles;
        if (className) img.className = className;
        if (title) img.title = title;
        img.alt = product?.product_name || 'Product';
        img.loading = 'lazy';

        img.onerror = () => {
            const imageName = product.product_image || '';
            const attempts = this.cache.incrementImageAttempts(imageName);

            if (attempts === 1 && !imageName.startsWith('http')) {
                // Try fallback path
                img.src = `/storage/products/${imageName}`;
            } else {
                // Use fallback image
                img.src = fallbackImage;
                this.cache.markImageFailed(imageName);
            }
        };

        return img;
    }

    /**
     * Get safe image URL
     */
    getSafeImageUrl(product) {
        const fallbackImage = '/assets/images/No Product Image Available.png';

        if (!product || !product.product_image || product.product_image.trim() === '') {
            return fallbackImage;
        }

        const imageName = product.product_image.trim();

        if (this.cache.hasImageFailed(imageName)) {
            return fallbackImage;
        }

        if (imageName.startsWith('http') || imageName.startsWith('/')) {
            return imageName;
        }

        return `/assets/images/${imageName}`;
    }

    /**
     * Filter products by category
     */
    async filterByCategory(categoryId, locationId) {
        this.currentFilter = { type: 'category', id: categoryId };
        await this.fetchFilteredProducts('category', categoryId, locationId);
    }

    /**
     * Filter products by subcategory
     */
    async filterBySubCategory(subCategoryId, locationId) {
        this.currentFilter = { type: 'subcategory', id: subCategoryId };
        await this.fetchFilteredProducts('subcategory', subCategoryId, locationId);
    }

    /**
     * Filter products by brand
     */
    async filterByBrand(brandId, locationId) {
        this.currentFilter = { type: 'brand', id: brandId };
        await this.fetchFilteredProducts('brand', brandId, locationId);
    }

    /**
     * Fetch filtered products from server
     */
    async fetchFilteredProducts(filterType, filterId, locationId) {
        if (!locationId) {
            toastr.warning('Please select a location first', 'No Location Selected');
            return;
        }

        this.showLoader();
        this.allProducts = [];
        this.currentPage = 1;
        this.hasMoreProducts = true;

        const params = {
            location_id: locationId,
            with_stock: 1,
            page: 1,
            per_page: this.perPage
        };

        if (filterType === 'category') params.category_id = filterId;
        if (filterType === 'subcategory') params.sub_category_id = filterId;
        if (filterType === 'brand') params.brand_id = filterId;

        const queryString = new URLSearchParams(params).toString();
        const url = `/products/stocks?${queryString}`;

        try {
            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const data = await response.json();
            this.hideLoader();

            if (data.status === 200 && Array.isArray(data.data)) {
                this.allProducts = data.data;
                this.stockData = [...this.allProducts];
                this.displayProducts(this.allProducts, false);

                console.log(`✅ Filtered products loaded: ${this.allProducts.length} products`);
            } else {
                this.displayEmptyState();
            }
        } catch (error) {
            this.hideLoader();
            console.error('Error fetching filtered products:', error);
            this.displayErrorState('Failed to load products');
        }
    }

    /**
     * Show all products (reset filters)
     */
    showAllProducts(locationId) {
        this.currentFilter = { type: null, id: null };
        this.allProducts = [];
        this.currentPage = 1;
        this.hasMoreProducts = true;
        this.fetchProducts(locationId, true);
    }

    /**
     * Search products by term
     */
    searchProducts(searchTerm) {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (!posProduct) return;

        const allCards = posProduct.querySelectorAll('.product-card');
        const term = searchTerm.toLowerCase().trim();

        if (!term) {
            // Show all products
            allCards.forEach(card => {
                card.parentElement.style.display = '';
            });
            return;
        }

        let visibleCount = 0;

        allCards.forEach(card => {
            const productName = card.querySelector('h6')?.textContent.toLowerCase() || '';
            const matches = productName.includes(term);

            if (matches) {
                card.parentElement.style.display = '';
                visibleCount++;
            } else {
                card.parentElement.style.display = 'none';
            }
        });

        console.log(`Search: "${term}" - ${visibleCount} products match`);
    }

    /**
     * UI Helper methods
     */
    showLoader() {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            posProduct.innerHTML = `
                <div class="loader-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading products...</p>
                </div>`;
        }
    }

    showLoaderSmall() {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            const loader = document.createElement('div');
            loader.className = 'col-12 text-center my-3 small-loader';
            loader.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading more...</span>
                </div>`;
            posProduct.appendChild(loader);
        }
    }

    hideLoader() {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            const loaders = posProduct.querySelectorAll('.loader-container, .small-loader');
            loaders.forEach(loader => loader.remove());
        }
    }

    clearProductContainer() {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            posProduct.innerHTML = '';
        }
    }

    displayEmptyState() {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            posProduct.innerHTML = '<p class="text-center">No products found.</p>';
        }
    }

    displayErrorState(message) {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (posProduct) {
            posProduct.innerHTML = `
                <div class="text-center p-4">
                    <p class="text-danger">${message}</p>
                    <button onclick="window.posProduct.showAllProducts(window.selectedLocationId)" class="btn btn-primary btn-sm">Retry</button>
                </div>`;
        }
    }

    showProductListArea() {
        const productListArea = document.getElementById('productListArea');
        const mainContent = document.getElementById('mainContent');

        if (productListArea && mainContent) {
            productListArea.classList.remove('d-none');
            productListArea.classList.add('show');
            mainContent.classList.remove('col-md-12');
            mainContent.classList.add('col-md-7');
        }
    }

    /**
     * Setup infinite scroll
     */
    setupInfiniteScroll(locationId) {
        const posProduct = this.cache.getCachedElement('posProduct');
        if (!posProduct) return;

        let scrollTimeout;

        posProduct.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const scrollTop = posProduct.scrollTop;
                const scrollHeight = posProduct.scrollHeight;
                const clientHeight = posProduct.clientHeight;

                // Load more when 80% scrolled
                if (scrollTop + clientHeight >= scrollHeight * 0.8) {
                    if (this.hasMoreProducts && !this.isLoading) {
                        this.fetchProducts(locationId, false);
                    }
                }
            }, 200);
        });
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSProductManager;
} else {
    window.POSProductManager = POSProductManager;
}
