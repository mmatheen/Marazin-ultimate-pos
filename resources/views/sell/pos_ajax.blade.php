<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let selectedLocationId = null;
        let currentProductsPage = 1;
        let hasMoreProducts = true;
        let isLoadingProducts = false;
        let allProducts = []; // paginated products for card display
        let stockData = []; // not used for cards/autocomplete in new version
        let isEditing = false;

        const posProduct = document.getElementById('posProduct');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        const finalValue = document.getElementById('total');
        const categoryBtn = document.getElementById('category-btn');
        const allProductsBtn = document.getElementById('allProductsBtn');
        const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');

        // ---- INIT ----
        fetchAllLocations();
        $('#locationSelect').on('change', handleLocationChange);
        fetchCategories();
        fetchBrands();
        initAutocomplete();

        // ---- Loader helpers ----
        function showLoader() {
            posProduct.innerHTML = `
        <div class="loader-container">
            <div class="loader">
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
            </div>
        </div>`;
        }

        function hideLoader() {
            posProduct.innerHTML = '';
        }

        // ---- CATEGORY/SUBCATEGORY/BRAND (unchanged) ----
        function fetchCategories() {
            fetch('/main-category-get-all')
                .then(response => response.json())
                .then(data => {
                    const categories = data.message;
                    const categoryContainer = document.getElementById('categoryContainer');
                    if (Array.isArray(categories)) {
                        categories.forEach(category => {
                            const card = document.createElement('div');
                            card.classList.add('category-card');
                            card.setAttribute('data-id', category.id);

                            const cardTitle = document.createElement('h6');
                            cardTitle.textContent = category.mainCategoryName;
                            card.appendChild(cardTitle);

                            const buttonContainer = document.createElement('div');
                            buttonContainer.classList.add('category-footer');

                            const allButton = document.createElement('button');
                            allButton.textContent = 'All';
                            allButton.classList.add('btn', 'btn-outline-green', 'me-2');
                            allButton.addEventListener('click', () => {
                                filterProductsByCategory(category.id);
                                closeOffcanvas('offcanvasCategory');
                            });

                            const nextButton = document.createElement('button');
                            nextButton.textContent = 'Next >>';
                            nextButton.classList.add('btn', 'btn-outline-purple');
                            nextButton.addEventListener('click', () => {
                                fetchSubcategories(category.id);
                            });

                            buttonContainer.appendChild(allButton);
                            buttonContainer.appendChild(nextButton);
                            card.appendChild(buttonContainer);

                            categoryContainer.appendChild(card);
                        });
                    } else {
                        console.error('Categories not found:', categories);
                    }
                })
                .catch(error => {
                    console.error('Error fetching categories:', error);
                });
        }

        function fetchSubcategories(categoryId) {
            fetch(`/sub_category-details-get-by-main-category-id/${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const subcategories = data.message;
                    const subcategoryContainer = document.getElementById('subcategoryContainer');
                    subcategoryContainer.innerHTML = '';
                    if (Array.isArray(subcategories)) {
                        subcategories.forEach(subcategory => {
                            const card = document.createElement('div');
                            card.classList.add('card', 'subcategory-card', 'mb-3');
                            card.setAttribute('data-id', subcategory.id);

                            const cardBody = document.createElement('div');
                            cardBody.classList.add('card-body');

                            const cardTitle = document.createElement('h6');
                            cardTitle.classList.add('card-title');
                            cardTitle.textContent = subcategory.subCategoryname;
                            cardBody.appendChild(cardTitle);

                            card.appendChild(cardBody);

                            card.addEventListener('click', () => {
                                filterProductsBySubCategory(subcategory.id);
                                closeOffcanvas('offcanvasSubcategory');
                            });

                            subcategoryContainer.appendChild(card);
                        });
                    } else {
                        console.error('Subcategories not found:', subcategories);
                    }
                    // Show/hide offcanvas
                    const subcategoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                        'offcanvasSubcategory'));
                    subcategoryOffcanvas.show();
                    const categoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                        'offcanvasCategory'));
                    categoryOffcanvas.hide();
                })
                .catch(error => console.error('Error fetching subcategories:', error));
        }
        subcategoryBackBtn.addEventListener('click', () => {
            const categoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                'offcanvasCategory'));
            categoryOffcanvas.show();
            const subcategoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                'offcanvasSubcategory'));
            subcategoryOffcanvas.hide();
        });

        function fetchBrands() {
            fetch('/brand-get-all')
                .then(response => response.json())
                .then(data => {
                    const brands = data.message;
                    const brandContainer = document.getElementById('brandContainer');
                    if (Array.isArray(brands)) {
                        brands.forEach(brand => {
                            const brandCard = document.createElement('div');
                            brandCard.classList.add('brand-card');
                            brandCard.setAttribute('data-id', brand.id);

                            const brandName = document.createElement('h6');
                            brandName.textContent = brand.name;
                            brandCard.appendChild(brandName);

                            brandCard.addEventListener('click', () => {
                                filterProductsByBrand(brand.id);
                                closeOffcanvas('offcanvasBrand');
                            });

                            brandContainer.appendChild(brandCard);
                        });
                    } else {
                        console.error('Brands not found:', brands);
                    }
                })
                .catch(error => {
                    console.error('Error fetching brands:', error);
                });
        }

        // // ---- LOCATION ----
        // function fetchAllLocations() {
        //     $.ajax({
        //         url: '/location-get-all',
        //         method: 'GET',
        //         success: function(data) {
        //             if (data.status === 200) populateLocationDropdown(data.message);
        //             else console.error('Error fetching locations:', data.message);
        //         },
        //         error: function(jqXHR, textStatus, errorThrown) {
        //             console.error('AJAX Error:', textStatus, errorThrown);
        //         }
        //     });
        // }

        // function populateLocationDropdown(locations) {
        //     const locationSelect = $('#locationSelect');
        //     locationSelect.empty();
        //     locationSelect.append('<option value="" disabled selected>Select Location</option>');
        //     locations.forEach((location, index) => {
        //         const option = $('<option></option>').val(location.id).text(location.name);
        //         if (index === 0) option.attr('selected', 'selected');
        //         locationSelect.append(option);
        //     });
        //     locationSelect.trigger('change');
        // }

        // ---- LOCATION ----
        function fetchAllLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                success: function(response) {
                    // Check for status = true and data exists
                    if (response.status && Array.isArray(response.data)) {
                        populateLocationDropdown(response.data);
                    } else {
                        console.error('Error fetching locations:', response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                }
            });
        }

        function populateLocationDropdown(locations) {
            const locationSelect = $('#locationSelect');
            locationSelect.empty(); // Clear existing options

            // Add default prompt
            locationSelect.append('<option value="" disabled selected>Select Location</option>');

            locations.forEach((location, index) => {
                const option = $('<option></option>').val(location.id).text(location.name);
                if (index === 0) option.attr('selected', 'selected');
                locationSelect.append(option);
            });

            // Trigger change event (optional: useful if other logic depends on it)
            locationSelect.trigger('change');
        }

        // ---- PAGINATED PRODUCT FETCH ----
        function handleLocationChange(event) {
            selectedLocationId = $(event.target).val();
            currentProductsPage = 1;
            hasMoreProducts = true;
            allProducts = [];
            posProduct.innerHTML = '';
            if (selectedLocationId) fetchPaginatedProducts(true);
            if (!isEditing) {
                billingBody.innerHTML = '';
            }
            updateTotals();
        }

        function fetchPaginatedProducts(reset = false) {
            if (isLoadingProducts || !selectedLocationId || !hasMoreProducts) return;
            isLoadingProducts = true;
            if (reset) showLoader();
            fetch(
                    `/api/products/stocks?location_id=${selectedLocationId}&page=${currentProductsPage}&per_page=24`)
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.status !== 200 || !Array.isArray(data.data)) {
                        if (reset) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                        isLoadingProducts = false;
                        return;
                    }
                    if (reset) {
                        allProducts = [];
                        posProduct.innerHTML = '';
                        stockData = []; // Reset stockData on reset
                    }
                    data.data.forEach(stock => allProducts.push(stock));
                    // Always keep stockData in sync with allProducts
                    stockData = [...allProducts];
                    displayProducts(allProducts);
                    if (data.data.length === 0 || data.data.length < 24) hasMoreProducts = false;
                    else hasMoreProducts = true;
                    isLoadingProducts = false;
                    currentProductsPage++;
                })
                .catch(e => {
                    hideLoader();
                    isLoadingProducts = false;
                    if (reset) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                    console.error('Error fetching products:', e);
                });
        }
        // Infinite scroll (using posProduct for lazy loading)
        function setupLazyLoad() {
            let productPage = 1;
            let productLoading = false;
            posProduct.addEventListener('scroll', () => {
                // Scroll down: fetch next page
                if (
                    hasMoreProducts &&
                    !productLoading &&
                    posProduct.scrollTop + posProduct.clientHeight >= posProduct.scrollHeight - 100
                ) {
                    productPage += 1;
                    fetchPaginatedProducts();
                }
                // Scroll up: fetch previous page (if needed)
                // Uncomment below if you want to fetch previous products when scrolling up
                /*
                if (
                    productPage > 1 &&
                    !productLoading &&
                    posProduct.scrollTop <= 100
                ) {
                    productPage -= 1;
                    fetchPaginatedProducts(true); // true to reset and load previous page
                }
                */
            });
        }
        // Call setupLazyLoad after posProduct is initialized
        setupLazyLoad();
        allProductsBtn.onclick = function() {
            currentProductsPage = 1;
            hasMoreProducts = true;
            allProducts = [];
            posProduct.innerHTML = '';
            fetchPaginatedProducts(true);
        };

        // ---- DISPLAY PRODUCTS ----
        function displayProducts(products) {
            posProduct.innerHTML = '';
            if (!selectedLocationId || products.length === 0) {
                posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                return;
            }
            // Only show products with stock in selected location, or unlimited stock
            const filteredProducts = products.filter(stock =>
                Array.isArray(stock.batches) && stock.batches.some(batch =>
                    Array.isArray(batch.location_batches) && batch.location_batches.some(lb =>
                        lb.location_id == selectedLocationId &&
                        (
                            // If allow_decimal, check for > 0 as float (including decimals)
                            (stock.product.unit && (stock.product.unit.allow_decimal === true || stock
                                    .product.unit.allow_decimal === 1) ?
                                parseFloat(lb.quantity) > 0 :
                                parseInt(lb.quantity) > 0
                            ) ||
                            stock.product.stock_alert === 0
                        )
                    )
                )
            );
            filteredProducts.forEach(stock => {
                const product = stock.product;
                let locationQty = 0;
                stock.batches.forEach(batch => {
                    batch.location_batches.forEach(lb => {
                        if (lb.location_id == selectedLocationId) locationQty += lb
                            .quantity;
                    });
                });
                stock.total_stock = product.stock_alert === 0 ? 0 : locationQty;
                // Show unit name (e.g., "Pc(s)", "kg", etc.) based on product.unit
                const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
                // Format quantity: decimal if allowed, else integer
                let quantityDisplay;
                if (product.stock_alert === 0) {
                    quantityDisplay = `Unlimited`;
                } else if (product.unit && (product.unit.allow_decimal === true || product.unit
                        .allow_decimal === 1)) {
                    quantityDisplay =
                        `${parseFloat(stock.total_stock).toFixed(4).replace(/\.?0+$/, '')} ${unitName} in stock`;
                } else {
                    quantityDisplay = `${parseInt(stock.total_stock, 10)} ${unitName} in stock`;
                }
                const cardHTML = `
            <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-3">
            <div class="product-card" data-id="${product.id}">
                <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" alt="${product.product_name}">
                <div class="product-card-body">
                <h6>${product.product_name} <br>
                    <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span>
                </h6>
                <h6>
                    <span class="badge ${product.stock_alert === 0 ? 'bg-info' : stock.total_stock > 0 ? 'bg-success' : 'bg-warning'}">
                    ${quantityDisplay}
                    </span>
                </h6>
                </div>
            </div>
            </div>`;
                posProduct.insertAdjacentHTML('beforeend', cardHTML);
            });
            // Add click event to product cards
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', () => {
                    const productId = card.getAttribute('data-id');
                    const productStock = allProducts.find(stock => String(stock.product.id) ===
                        productId);
                    if (productStock) addProductToTable(productStock.product);
                });
            });
        }
        // ---- AUTOCOMPLETE (server driven, optimized for your controller) ----
        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    if (!selectedLocationId) return response([]);
                    $.ajax({
                        url: '/api/products/stocks/autocomplete',
                        data: {
                            location_id: selectedLocationId,
                            search: request.term,
                            per_page: 15
                        },
                        success: function(data) {
                            if (data.status === 200 && Array.isArray(data.data)) {
                                // Only show products with stock > 0 (including decimals if allow_decimal) for the selected location, or unlimited stock
                                const filtered = data.data.filter(stock =>
                                    stock.product &&
                                    (
                                        stock.product.stock_alert == 0 ||
                                        (
                                            stock.product.unit && (stock.product
                                                .unit.allow_decimal === true ||
                                                stock.product.unit.allow_decimal ===
                                                1) ?
                                            parseFloat(stock.total_stock) > 0 :
                                            parseInt(stock.total_stock) > 0
                                        )
                                    )
                                );
                                const results = filtered.map(stock => ({
                                    label: `${stock.product.product_name} (${stock.product.sku || ''}) [Stock: ${stock.product.stock_alert == 0 ? 'Unlimited' : stock.total_stock}]`,
                                    value: stock.product.product_name,
                                    product: stock.product,
                                    stockData: stock
                                }));
                                if (results.length === 0) results.push({
                                    label: "No results found",
                                    value: ""
                                });
                                response(results);
                            } else {
                                response([{
                                    label: "No results found",
                                    value: ""
                                }]);
                            }
                        },
                        error: function() {
                            response([{
                                label: "No results found",
                                value: ""
                            }]);
                        }
                    });
                },
                select: function(event, ui) {
                    if (!ui.item.product) return false;
                    $("#productSearchInput").val("");
                    // Try to find the stock entry in stockData
                    let stockEntry = stockData.find(stock => stock.product.id === ui.item.product
                        .id);
                    if (!stockEntry && ui.item.stockData) {
                        // If not found, but stockData is present from autocomplete, add it
                        stockData.push(ui.item.stockData);
                        allProducts.push(ui.item.stockData);
                        stockEntry = ui.item.stockData;
                    }
                    if (!stockEntry) {
                        // If still not found, fetch the full stock entry from the server
                        fetch(
                                `/api/products/stocks?location_id=${selectedLocationId}&product_id=${ui.item.product.id}`
                            )
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 200 && Array.isArray(data.data) && data.data
                                    .length > 0) {
                                    stockData.push(data.data[0]);
                                    allProducts.push(data.data[0]);
                                    addProductToTable(data.data[0].product);
                                } else {
                                    toastr.error('Stock entry not found for the product',
                                        'Error');
                                }
                            })
                            .catch(() => {
                                toastr.error('Error fetching product stock data', 'Error');
                            });
                        return false;
                    }
                    addProductToTable(ui.item.product);
                    return false;
                },
                minLength: 1
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append(`<div style="${item.product ? '' : 'color: red;'}">${item.label}</div>`)
                    .appendTo(ul);
            };

            $("#productSearchInput").removeAttr("aria-live aria-autocomplete");
            $("#productSearchInput").autocomplete("instance").liveRegion.remove();


            // Custom _move to keep focus highlight in sync with up/down keys
            $("#productSearchInput").autocomplete("instance")._move = function(direction, event) {
                if (!this.menu.element.is(":visible")) {
                    this.search(null, event);
                    return;
                }
                if ((this.menu.isFirstItem() && /^previous/.test(direction)) ||
                    (this.menu.isLastItem() && /^next/.test(direction))) {
                    this._value(this.term);
                    this.menu.blur();
                    return;
                }
                this.menu[direction](event);
                this.menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                this.menu.active.addClass("ui-state-focus");
            };
        }
        // Re-init autocomplete when location changes
        $('#locationSelect').on('change', () => {
            $("#productSearchInput").val('');
            if ($("#productSearchInput").data('ui-autocomplete')) {
                $("#productSearchInput").autocomplete('destroy');
            }
            initAutocomplete();
        });


        function formatAmountWithSeparators(amount) {
            return new Intl.NumberFormat().format(amount);
        }

        function parseFormattedAmount(formattedAmount) {
            if (typeof formattedAmount !== 'string' && typeof formattedAmount !== 'number') {
                return 0;
            }
            const cleaned = String(formattedAmount).replace(/[^0-9.-]/g, '');
            const parsed = parseFloat(cleaned);
            return isNaN(parsed) ? 0 : parsed;
        }

        // Filter products by category
        function filterProductsByCategory(categoryId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.main_category_id ===
                    categoryId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Filter products by subcategory
        function filterProductsBySubCategory(subCategoryId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.sub_category_id ===
                    subCategoryId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Filter products by brand
        function filterProductsByBrand(brandId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.brand_id === brandId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Function to close the offcanvas
        function closeOffcanvas(offcanvasId) {
            const offcanvasElement = document.getElementById(offcanvasId);
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (bsOffcanvas) {
                bsOffcanvas.hide();
            }
        }

        let locationId = null;
        let priceType = 'retail';
        let selectedRow;

        function addProductToTable(product) {
            console.log("Product to be added:", product);

            if (!stockData || stockData.length === 0) {
                console.error('stockData is not defined or empty');
                toastr.error('Stock data is not available', 'Error');
                return;
            }

            const stockEntry = stockData.find(stock => stock.product.id === product.id);
            console.log("stockEntry", stockEntry);

            if (!stockEntry) {
                toastr.error('Stock entry not found for the product', 'Error');
                return;
            }

            const totalQuantity = stockEntry.total_stock;

            // If product is unlimited stock (stock_alert === 0), allow sale even if quantity is 0
            if (product.stock_alert === 0) {
                // Proceed to add product with batch "all" and quantity 0 (unlimited)
                let batchesArray = [];
                if (Array.isArray(stockEntry.batches)) {
                    batchesArray = stockEntry.batches;
                } else if (typeof stockEntry.batches === 'object' && stockEntry.batches !== null) {
                    batchesArray = Object.values(stockEntry.batches);
                }
                // Use latest batch's retail price for "All"
                const latestBatch = batchesArray.length > 0 ? batchesArray[0] : {
                    retail_price: product.retail_price || product.max_retail_price || 0
                };
                locationId = selectedLocationId;
                addProductToBillingBody(
                    product,
                    stockEntry,
                    latestBatch.retail_price,
                    "all", // batchId is "all"
                    0, // unlimited stock, so quantity is 0
                    'retail'
                );
                return;
            }

            // Check if product requires IMEI
            if (product.is_imei_or_serial_no === 1) {
                const availableImeis = stockEntry.imei_numbers?.filter(imei => imei.status === "available") ||
                [];
                console.log("Available IMEIs:", availableImeis);

                const billingBody = document.getElementById('billing-body');
                const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row =>
                    row.querySelector('.product-id')?.textContent == product.id
                );

                if (existingRows.length > 0) {
                    showImeiSelectionModal(product, stockEntry, availableImeis);
                    return;
                }

                showImeiSelectionModal(product, stockEntry, availableImeis);
                return;
            }

            // If no IMEI required, proceed normally
            if ((totalQuantity === 0 || totalQuantity === "0" || totalQuantity === "0.00") && product
                .stock_alert !== 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }

            // Ensure batches is always an array
            let batchesArray = [];
            if (Array.isArray(stockEntry.batches)) {
                batchesArray = stockEntry.batches;
            } else if (typeof stockEntry.batches === 'object' && stockEntry.batches !== null) {
                batchesArray = Object.values(stockEntry.batches);
            }

            // Filter batches by selected location and available quantity
            batchesArray = batchesArray.filter(batch =>
                Array.isArray(batch.location_batches) &&
                batch.location_batches.some(lb =>
                    String(lb.location_id) == String(selectedLocationId) &&
                    parseFloat(lb.quantity) > 0
                )
            );

            if (batchesArray.length === 0) {
                toastr.error('No batches with available quantity found in this location', 'Error');
                return;
            }

            // Sort batches by id descending (latest batch first)
            batchesArray = batchesArray.sort((a, b) => parseInt(b.id) - parseInt(a.id));

            // Get unique retail prices across batches in this location
            const retailPrices = [
                ...new Set(
                    batchesArray.map(batch => parseFloat(batch.retail_price))
                )
            ];

            // If there's only one price, add the latest batch (highest id)
            if (retailPrices.length <= 1) {
                // Default: select "All" batch (not a real batch, but for all available)
                // Calculate total quantity for all batches in this location
                let totalQty = 0;
                batchesArray.forEach(batch => {
                    batch.location_batches.forEach(lb => {
                        if (String(lb.location_id) == String(selectedLocationId)) {
                            totalQty += parseFloat(lb.quantity);
                        }
                    });
                });
                // Use latest batch's retail price for "All"
                const latestBatch = batchesArray[0];
                locationId = selectedLocationId;
                addProductToBillingBody(
                    product,
                    stockEntry,
                    latestBatch.retail_price,
                    "all", // batchId is "all"
                    totalQty,
                    'retail'
                );
            } else {
                // Multiple prices found → show modal (user must select batch)
                showBatchPriceSelectionModal(product, stockEntry, batchesArray);
            }
        }


        // Global variable to track currently opened modal product
        let activeModalProductId = null;

        function showBatchPriceSelectionModal(product, stockEntry, batches) {
            const tbody = document.getElementById('batch-price-list');
            const modalElement = document.getElementById('batchPriceModal');
            const modal = new bootstrap.Modal(modalElement);

            // Prevent opening modal again for same product
            if (activeModalProductId === product.id) {
                toastr.info('Batch selection already in progress for this product.');
                return;
            }
            activeModalProductId = product.id;

            // Reset modal content
            tbody.innerHTML = '';
            const batchRows = [];

            // Filter and sort batches
            const validBatches = batches.filter(batch => {
                const locationBatch = batch.location_batches.find(lb => lb.location_id ==
                    selectedLocationId);
                return locationBatch && parseFloat(locationBatch.quantity) > 0;
            }).sort((a, b) => parseInt(b.id) - parseInt(a.id));

            if (validBatches.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="5" class="text-center text-danger">No batches available</td></tr>`;
                modal.show();
                setTimeout(() => modal.hide(), 1500);
                activeModalProductId = null;
                return;
            }

            // Populate modal with batches
            validBatches.forEach((batch, index) => {
                const locationBatch = batch.location_batches.find(lb => lb.location_id ==
                    selectedLocationId);
                const batchMrp = batch.max_retail_price !== undefined && batch.max_retail_price !==
                    null ?
                    parseFloat(batch.max_retail_price) : (product.max_retail_price || 0);
                const batchRetailPrice = batch.retail_price !== undefined && batch.retail_price !==
                    null ?
                    parseFloat(batch.retail_price) : (product.retail_price || 0);

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td><strong>[${index + 1}]</strong></td>
            <td>${batch.batch_no}</td>
            <td>MRP: Rs ${batchMrp.toFixed(2)}<br>Retail: Rs ${batchRetailPrice.toFixed(2)}</td>
            <td>${locationBatch.quantity} PC(s)</td>
            <td>
                <button class="btn btn-sm btn-primary select-batch-btn"
                    data-batch-id="${batch.id}"
                    data-retail-price="${batchRetailPrice}"
                    data-max-retail-price="${batchMrp}"
                    data-batch-json='${JSON.stringify(batch)}'>
                    Select
                </button>
            </td>
        `;
                tbody.appendChild(tr);
                batchRows.push(tr);
            });

            let isModalOpen = false;

            // Handle batch selection
            function handleBatchSelect(e) {
                if (e.target.classList.contains('select-batch-btn')) {
                    const batchJson = e.target.dataset.batchJson;
                    const selectedBatch = JSON.parse(batchJson);
                    const locationBatch = selectedBatch.location_batches.find(lb => lb.location_id ==
                        selectedLocationId);
                    const qty = locationBatch?.quantity || 0;

                    // Use batch-specific prices
                    const batchRetailPrice = selectedBatch.retail_price !== undefined && selectedBatch
                        .retail_price !== null ?
                        parseFloat(selectedBatch.retail_price) : (product.retail_price || 0);

                    const batchMrp = selectedBatch.max_retail_price !== undefined && selectedBatch
                        .max_retail_price !== null ?
                        parseFloat(selectedBatch.max_retail_price) : (product.max_retail_price || 0);

                    const productWithBatchPrices = {
                        ...product,
                        retail_price: batchRetailPrice,
                        max_retail_price: batchMrp
                    };

                    // Add product to billing with quantity 1
                    addProductToBillingBody(
                        productWithBatchPrices,
                        stockEntry,
                        batchRetailPrice,
                        selectedBatch.id,
                        qty,
                        'retail',
                        1, // Quantity is 1 when selecting from modal
                        [],
                        null,
                        null,
                        selectedBatch
                    );

                    if (isModalOpen) {
                        modal.hide();
                        isModalOpen = false;
                    }
                }
            }

            tbody.addEventListener('click', handleBatchSelect);

            // Keyboard navigation
            const handleKeyDown = function(event) {
                const key = event.key;
                if (!/^[1-9]$/.test(key)) return;

                const selectedIndex = parseInt(key, 10) - 1;
                if (batchRows[selectedIndex]) {
                    const selectBtn = batchRows[selectedIndex].querySelector('.select-batch-btn');
                    if (selectBtn) {
                        selectBtn.click();
                        if (isModalOpen) {
                            modal.hide();
                            isModalOpen = false;
                        }
                    }
                }
            };

            // Modal lifecycle
            const shownHandler = () => {
                document.addEventListener('keydown', handleKeyDown);
                isModalOpen = true;
            };

            const hiddenHandler = () => {
                document.removeEventListener('keydown', handleKeyDown);
                isModalOpen = false;
                activeModalProductId = null;
                tbody.removeEventListener('click', handleBatchSelect);
                modalElement.removeEventListener('shown.bs.modal', shownHandler);
                modalElement.removeEventListener('hidden.bs.modal', hiddenHandler);
            };

            modalElement.addEventListener('shown.bs.modal', shownHandler, {
                once: true
            });
            modalElement.addEventListener('hidden.bs.modal', hiddenHandler, {
                once: true
            });

            modal.show();
        }


        let selectedImeisInBilling = [];
        let currentImeiProduct = null;
        let currentImeiStockEntry = null;

        function showImeiSelectionModal(product, stockEntry, imeis) {
            currentImeiProduct = product;
            currentImeiStockEntry = stockEntry;

            const availableImeis = (stockEntry.imei_numbers || []).filter(imei =>
                imei.status === "available" && imei.location_id == selectedLocationId
            );

            const selectedBatch = stockEntry.batches.find(b =>
                b.location_batches.some(lb => lb.location_id == selectedLocationId)
            );
            const batchQty = selectedBatch ? selectedBatch.total_batch_quantity : 0;
            let missingImeiCount = batchQty - availableImeis.length;

            // Collect already selected IMEIs in billing
            selectedImeisInBilling = [];
            const billingBody = document.getElementById('billing-body');
            const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row => {
                return row.querySelector('.product-id')?.textContent == product.id;
            });
            existingRows.forEach(row => {
                const imei = row.querySelector('.imei-data')?.textContent.trim();
                if (imei) selectedImeisInBilling.push(imei);
            });

            const tbody = document.getElementById('imei-table-body');
            if (!tbody) {
                toastr.error("IMEI table body not found");
                return;
            }
            tbody.innerHTML = '';
            const imeiRows = [];

            // Populate existing IMEIs
            availableImeis.forEach((imei, index) => {
                const isChecked = selectedImeisInBilling.includes(imei.imei_number);
                const row = document.createElement('tr');
                row.dataset.imei = imei.imei_number;
                row.dataset.imeiId = imei.id; // <-- Store primary key for edit
                row.innerHTML = `
            <td>${index + 1}</td>
            <td><input type="checkbox" class="imei-checkbox" value="${imei.imei_number}" ${isChecked ? 'checked' : ''} data-status="${imei.status}" /></td>
            <td class="imei-display">${imei.imei_number}</td>
            <td><span class="badge ${imei.status === 'available' ? 'bg-success' : 'bg-danger'}">${imei.status}</span></td>
            <td>
                <button class="btn btn-sm btn-warning edit-imei-btn">Edit</button>
                <button class="btn btn-sm btn-danger remove-imei-btn">Remove</button>
            </td>
        `;
                row.classList.add('clickable-row');
                row.addEventListener('click', function(event) {
                    if (event.target.type !== 'checkbox') {
                        const checkbox = row.querySelector('.imei-checkbox');
                        checkbox.checked = !checkbox.checked;
                    }
                });
                tbody.appendChild(row);
                imeiRows.push(row);
            });

            // Add initial manual IMEI row
            if (missingImeiCount > 0) {
                addNewImeiRow(missingImeiCount, tbody, imeiRows);
            }

            // Show modal
            const modalElement = document.getElementById('imeiModal');
            if (!modalElement) {
                toastr.error("IMEI modal not found");
                return;
            }
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            setupSearchAndFilter(tbody, imeiRows);
            setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows);
            setupAddButtonContainer(missingImeiCount, tbody, imeiRows);
            attachEditRemoveHandlers();
        }

        // --- Helper Functions ---

        function setupSearchAndFilter(tbody, imeiRows) {
            const searchInput = document.getElementById('imeiSearch');
            const filterSelect = document.getElementById('checkboxFilter');

            function applyFilters() {
                const searchTerm = (searchInput?.value || '').toLowerCase();
                const filterType = filterSelect?.value || 'all';

                imeiRows.forEach(row => {
                    const isManual = !row.dataset.imei;
                    const imeiNumber = isManual ?
                        (row.querySelector('.new-imei-input')?.value || '').toLowerCase() :
                        row.dataset.imei.toLowerCase();

                    const checkbox = row.querySelector('.imei-checkbox');
                    const isChecked = checkbox?.checked || false;

                    let matchesSearch = imeiNumber.includes(searchTerm);
                    let matchesFilter = true;

                    if (filterType === 'checked') {
                        matchesFilter = isChecked;
                    } else if (filterType === 'unchecked') {
                        matchesFilter = !isChecked;
                    }

                    row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
                });
            }

            searchInput?.addEventListener('input', applyFilters);
            filterSelect?.addEventListener('change', applyFilters);
        }

        function setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows) {
            document.getElementById('confirmImeiSelection').onclick = function() {
                const checkboxes = document.querySelectorAll('.imei-checkbox:not(.manual-checkbox)');
                const manualInputs = document.querySelectorAll('.new-imei-input');

                const selectedImeis = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                const newImeis = Array.from(manualInputs).map(input => input.value.trim()).filter(val =>
                    val);

                const allImeis = [...selectedImeis, ...newImeis];
                const uniqueImeis = [...new Set(allImeis)];

                highlightDuplicates(allImeis, tbody);

                if (allImeis.length !== uniqueImeis.length) {
                    toastr.error("Duplicate IMEI found. Please enter unique IMEIs.");
                    return;
                }

                if (uniqueImeis.length === 0) {
                    toastr.warning("Please select or enter at least one IMEI.");
                    return;
                }

                modal.hide();
                const batchId = selectedBatch ? selectedBatch.id : "all";
                const price = product.retail_price;
                const imeiLocationId = selectedBatch?.location_batches[0]?.location_id ?? 1;

                if (newImeis.length > 0) {
                    fetch('/save-or-update-imei', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                product_id: product.id,
                                batches: [{
                                    batch_id: batchId,
                                    location_id: imeiLocationId,
                                    qty: newImeis.length
                                }],
                                imeis: newImeis
                            })
                        }).then(response => response.json())
                        .then(data => {
                            if (data.status === 200) {
                                const message = data.message ||
                                    `${newImeis.length} IMEI(s) added successfully.`;
                                toastr.success(message);
                                updateBilling(uniqueImeis, product, stockEntry, price, batchId);
                            } else {
                                toastr.error(data.message || "Failed to save new IMEIs");
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            toastr.error("Error saving new IMEIs");
                        });
                } else {
                    updateBilling(uniqueImeis, product, stockEntry, price, batchId);
                }
            };
        }

        function highlightDuplicates(imeis, tbody) {
            const counts = {};
            imeis.forEach(imei => counts[imei] = (counts[imei] || 0) + 1);

            tbody.querySelectorAll('tr').forEach(row => {
                const isManual = !row.dataset.imei;
                const imei = isManual ? row.querySelector('.new-imei-input')?.value.trim() : row.dataset
                    .imei;

                if (counts[imei] > 1) {
                    row.style.backgroundColor = "#fff3cd"; // Light Yellow
                } else {
                    row.style.backgroundColor = ""; // Reset
                }
            });
        }

        function updateBilling(imeis, product, stockEntry, price, batchId) {
            const existingRows = Array.from(document.querySelectorAll('#billing-body tr'))
                .filter(row => row.querySelector('.product-id')?.textContent == product.id);

            existingRows.forEach(row => row.remove());

            imeis.forEach(imei => {
                addProductToBillingBody(
                    product, stockEntry, price, batchId, 1, 'retail', 1, [imei]
                );
            });

            updateTotals();
            fetchPaginatedProducts(true);
        }

        function addNewImeiRow(count, tbody, imeiRows) {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${tbody.querySelectorAll('tr').length + 1}</td>
                <td><input type="checkbox" class="imei-checkbox manual-checkbox" disabled /></td>
                <td>
                    <div class="input-group">
                        <input type="text" class="form-control new-imei-input" placeholder="Enter IMEI" maxlength="15" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
                        <button type="button" class="btn btn-danger btn-sm remove-imei-row">&times;</button>
                    </div>
                </td>
                <td><span class="badge bg-secondary">Manual</span></td>
                <td></td>
            `;

            const removeBtn = row.querySelector('.remove-imei-row');
            removeBtn?.addEventListener('click', function(e) {
                e.stopPropagation();
                row.remove();
                count++;
                toggleAddButton(count);
            });

            const input = row.querySelector('.new-imei-input');
            const checkbox = row.querySelector('.imei-checkbox');

            input.addEventListener('input', () => {
                checkbox.checked = input.value.trim() !== "";
            });

            tbody.appendChild(row);
            imeiRows.push(row);
            input.focus();
            count--;
            toggleAddButton(count);
        }

        function setupAddButtonContainer(count) {
            const container = document.getElementById('add-button-container') || (() => {
                const el = document.createElement('div');
                el.id = 'add-button-container';
                document.getElementById('imeiModalFooter').appendChild(el);
                return el;
            })();

            toggleAddButton(count);
        }

        function toggleAddButton(count) {
            const container = document.getElementById('add-button-container');
            if (!container) return;

            if (count > 0) {
                container.innerHTML =
                    `<button id="add-new-imei-btn" class="btn btn-sm btn-primary mt-2">+ Add New IMEI</button>`;
                document.getElementById('add-new-imei-btn').addEventListener('click', () => {
                    addNewImeiRow(count, document.getElementById('imei-table-body'), []);
                });
            } else {
                container.innerHTML = '';
            }
        }

        function attachEditRemoveHandlers() {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('edit-imei-btn')) handleEditImei(e);
                if (e.target.classList.contains('remove-imei-btn')) handleDeleteImei(e);
            });
        }

        function handleEditImei(e) {
            const row = e.target.closest('tr');
            const displayCell = row.querySelector('.imei-display');
            const originalImei = displayCell.textContent.trim();
            const imeiId = row.dataset.imeiId;

            if (!imeiId) return toastr.error("IMEI ID not found. Can't update.");

            displayCell.innerHTML =
                `<input type="text" class="form-control edit-imei-input" value="${originalImei}" />`;
            e.target.textContent = "Update";
            e.target.classList.replace("btn-warning", "btn-success");

            e.target.onclick = function() {
                const newImei = row.querySelector('.edit-imei-input').value.trim();
                if (!newImei) return toastr.error("IMEI cannot be empty.");

                fetch('/update-imei', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            id: imeiId,
                            new_imei: newImei
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if (data.status === 200) {
                            displayCell.textContent = newImei;
                            row.dataset.imei = newImei;
                            row.querySelector('.imei-checkbox').value = newImei;
                            e.target.textContent = "Edit";
                            e.target.classList.replace("btn-success", "btn-warning");
                            toastr.success("IMEI updated successfully!");
                        } else {
                            toastr.error(data.message || "Failed to update IMEI");
                        }
                    }).catch(() => toastr.error("Network error updating IMEI"));
            };
        }

        function handleDeleteImei(e) {
            const row = e.target.closest('tr');
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            document.getElementById('confirmDeleteBtn').onclick = function() {
                const imeiId = row.dataset.imeiId;
                fetch('/delete-imei', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            id: imeiId
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if (data.status === 200) {
                            row.remove();
                            toastr.success("IMEI deleted successfully!");
                            fetchPaginatedProducts(true);
                        } else {
                            toastr.error(data.message || "Failed to delete IMEI");
                        }
                    }).catch(() => toastr.error("Network error deleting IMEI"));

                modal.hide();
            };
            modal.show();
        }

        function showProductModal(product, stockEntry, row) {
            const modalBody = document.getElementById('productModalBody');
            const basePrice = product.retail_price;
            const discountAmount = product.discount_amount || 0;
            const finalPrice = product.discount_type === 'percentage' ?
                basePrice * (1 - discountAmount / 100) :
                basePrice - discountAmount;

            let batchOptions = '';
            let locationBatches = [];

            // Normalize batches to array if it's an object
            let batchesArray = [];
            if (stockEntry && stockEntry.batches) {
                if (Array.isArray(stockEntry.batches)) {
                    batchesArray = stockEntry.batches;
                } else if (typeof stockEntry.batches === 'object' && stockEntry.batches !== null) {
                    batchesArray = Object.values(stockEntry.batches);
                }
            }

            // Only show batches for the selected location
            locationBatches = batchesArray
                .filter(batch =>
                    Array.isArray(batch.location_batches) &&
                    batch.location_batches.some(lb => String(lb.location_id) == String(selectedLocationId))
                )
                .map(batch => {
                    // Find the location batch for the selected location
                    const locationBatch = batch.location_batches.find(lb => String(lb.location_id) ==
                        String(selectedLocationId));
                    return {
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        retail_price: parseFloat(batch.retail_price),
                        wholesale_price: parseFloat(batch.wholesale_price),
                        special_price: parseFloat(batch.special_price),
                        batch_quantity: locationBatch ? parseFloat(locationBatch.quantity) : 0,
                        created_at: batch.created_at || null // If available
                    };
                })
                .filter(batch => batch.batch_quantity > 0);

            // Calculate total quantity for all batches in the selected location
            let totalQuantity = 0;
            if (batchesArray.length > 0) {
                totalQuantity = batchesArray.reduce((sum, batch) => {
                    if (Array.isArray(batch.location_batches)) {
                        return sum + batch.location_batches
                            .filter(lb => String(lb.location_id) == String(selectedLocationId))
                            .reduce((s, lb) => s + (parseFloat(lb.quantity) || 0), 0);
                    }
                    return sum;
                }, 0);
            }

            // Find latest batch by created_at or by highest batch_id
            let latestBatch = null;
            if (locationBatches.length > 0) {
                latestBatch = locationBatches.reduce((latest, current) => {
                    if (current.created_at && latest.created_at) {
                        return new Date(current.created_at) > new Date(latest.created_at) ?
                            current : latest;
                    }
                    // fallback: use batch_id as number
                    return (parseInt(current.batch_id) > parseInt(latest.batch_id)) ? current :
                        latest;
                }, locationBatches[0]);
            }

            // Always show "All" option, default selected, with latest batch price
            let allOptionRetailPrice = latestBatch ? latestBatch.retail_price : finalPrice;

            if (locationBatches.length > 0) {
                batchOptions = locationBatches.map((batch, idx) => `
                    <option value="${batch.batch_id}" 
                    data-retail-price="${batch.retail_price}" 
                    data-wholesale-price="${batch.wholesale_price}" 
                    data-special-price="${batch.special_price}" 
                    data-quantity="${batch.batch_quantity}">
                    ${batch.batch_no} - Qty: ${formatAmountWithSeparators(batch.batch_quantity)} - 
                    R: ${formatAmountWithSeparators(batch.retail_price.toFixed(2))} - 
                    W: ${formatAmountWithSeparators(batch.wholesale_price.toFixed(2))} - 
                    S: ${formatAmountWithSeparators(batch.special_price.toFixed(2))}
                    </option>
                `).join('');

                modalBody.innerHTML = `
                    <div class="d-flex align-items-center">
                    <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;"/>
                    <div>
                        <div class="font-weight-bold">${product.product_name}</div>
                        <div class="text-muted">${product.sku}</div>
                        ${product.description ? `<div class="text-muted small">${product.description}</div>` : ''}
                    </div>
                    </div>
                    <div class="btn-group btn-group-toggle mt-3" data-toggle="buttons">
                    <label class="btn btn-outline-primary active">
                        <input type="radio" name="modal-price-type" value="retail" checked hidden> <i class="fas fa-star"></i> R
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="modal-price-type" value="wholesale" hidden> <i class="fas fa-star"></i><i class="fas fa-star"></i> W
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="modal-price-type" value="special" hidden> <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> S
                    </label>
                    </div>
                    <select id="modalBatchDropdown" class="form-select mt-3">
                    <option value="all" data-retail-price="${allOptionRetailPrice}" data-quantity="${totalQuantity}" selected>
                        All - Qty: ${formatAmountWithSeparators(totalQuantity)} - Price: ${formatAmountWithSeparators(allOptionRetailPrice.toFixed(2))}
                    </option>
                    ${batchOptions}
                    </select>
                `;
            } else {
                // No valid batches
                modalBody.innerHTML = `<div>No valid batches found for the product in this location.</div>`;
            }

            selectedRow = row;
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();

            const radioButtons = document.querySelectorAll('input[name="modal-price-type"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.btn-group-toggle .btn').forEach(btn => btn
                        .classList.remove('active'));
                    this.parentElement.classList.add('active');
                });
            });

            // Attach change handler on dropdown to update max quantity
            const batchDropdown = document.getElementById('modalBatchDropdown');
            if (batchDropdown) {
                batchDropdown.addEventListener('change', () => {
                    const selectedOption = batchDropdown.selectedOptions[0];
                    if (!selectedOption) return;

                    const maxQty = parseFloat(selectedOption.getAttribute('data-quantity'));
                    const qtyInput = selectedRow?.querySelector('.quantity-input');

                    if (qtyInput) {
                        qtyInput.setAttribute('max', maxQty);
                        qtyInput.setAttribute('title', `Available: ${maxQty}`);
                    }
                });
            }
        }

        function addProductToBillingBody(product, stockEntry, price, batchId, batchQuantity, priceType,
            saleQuantity = 1, imeis = [], discountType = null, discountAmount = null, selectedBatch = null) {

            const billingBody = document.getElementById('billing-body');
            locationId = selectedLocationId || 1;

            // Use selectedBatch if provided; fallback to stockEntry batch
            const batch = selectedBatch || (Array.isArray(stockEntry.batches) ? stockEntry.batches.find(b => b
                .id === parseInt(batchId)) : undefined);

            // Use batch retail price if valid, else product retail price, then MRP
            let batchRetailPrice = batch && parseFloat(batch.retail_price) > 0 ? parseFloat(batch
                .retail_price) : 0;
            price = batchRetailPrice || product.retail_price || product.max_retail_price;
            price = parseFloat(price);

            if (isNaN(price)) {
                console.error('Invalid price for product:', product.product_name);
                toastr.error(`Invalid price for ${product.product_name}. Using default price.`, 'Error');
                price = 0;
            }

            const activeDiscount = stockEntry.discounts?.find(d => d.is_active && !d.is_expired) || null;

            let finalPrice = price;
            let discountFixed = 0;
            let discountPercent = 0;

            // Helper: Calculate default discount using selected batch's retail price
            const defaultFixedDiscount = product.max_retail_price - price;

            // Priority order:
            // 1. Manual discount
            // 2. Active discount
            // 3. Default (MRP - batch retail price)
            if (discountType && discountAmount !== null) {
                if (discountType === 'fixed') {
                    discountFixed = parseFloat(discountAmount);
                    finalPrice = product.max_retail_price - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                } else if (discountType === 'percentage') {
                    discountPercent = parseFloat(discountAmount);
                    finalPrice = product.max_retail_price * (1 - discountPercent / 100);
                }
            } else if (activeDiscount) {
                if (activeDiscount.type === 'percentage') {
                    discountPercent = activeDiscount.amount;
                    finalPrice = product.max_retail_price * (1 - discountPercent / 100);
                } else if (activeDiscount.type === 'fixed') {
                    discountFixed = activeDiscount.amount;
                    finalPrice = product.max_retail_price - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                }
            } else {
                discountFixed = defaultFixedDiscount;
                discountPercent = (discountFixed / product.max_retail_price) * 100;
                finalPrice = price; // Use selected batch price
            }

            let adjustedBatchQuantity = batchQuantity;
            if (batchId === "all") {
                adjustedBatchQuantity = stockEntry.total_stock;
            } else if (batch && batch.location_batches) {
                const locationBatch = batch.location_batches.find(lb => lb.location_id === locationId);
                if (locationBatch) {
                    adjustedBatchQuantity = parseFloat(locationBatch.quantity);
                }
            }

            // Get unit name and allow_decimal from product.unit (if available)
            const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
            const allowDecimal = product.unit && (product.unit.allow_decimal === true || product.unit
                .allow_decimal === 1);

            // Format adjustedBatchQuantity based on allowDecimal (rounded to 2 decimals)
            if (allowDecimal) {
                adjustedBatchQuantity = parseFloat(adjustedBatchQuantity).toFixed(2).replace(/\.?0+$/, '');
            } else {
                adjustedBatchQuantity = parseInt(adjustedBatchQuantity, 10);
            }

            // If allowDecimal, use step="any" and allow decimal input, else step="1"
            const qtyInputStep = allowDecimal ? 'any' : '1';
            const qtyInputPattern = allowDecimal ? '[0-9]+([.][0-9]{1,2})?' : '[0-9]*';

            // Determine initial quantity value for input
            let initialQuantityValue;
            if (imeis.length > 0) {
                initialQuantityValue = imeis.length;
            } else if (allowDecimal) {
                // For decimal units, use the available stock as default if less than 1, else 1
                let availableQty = parseFloat(adjustedBatchQuantity);
                if (availableQty < 1 && availableQty > 0) {
                    initialQuantityValue = availableQty.toFixed(2).replace(/\.?0+$/, '');
                } else {
                    initialQuantityValue = '1.00';
                }
            } else {
                initialQuantityValue = 1;
            }

            // If not IMEI, try to merge row
            if (imeis.length === 0) {
                const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
                    const rowProductId = row.querySelector('.product-id').textContent.trim();
                    const rowBatchId = row.querySelector('.batch-id').textContent.trim();
                    const rowPrice = row.querySelector('.price-input')?.value.trim();

                    return (
                        rowProductId == product.id &&
                        rowBatchId == batchId &&
                        parseFloat(rowPrice).toFixed(2) === finalPrice.toFixed(2)
                    );
                });
                if (existingRow) {
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    let currentQty = allowDecimal ? parseFloat(quantityInput.value) : parseInt(quantityInput
                        .value, 10);
                    let newQuantity = currentQty + saleQuantity;

                    // Use parseFloat for decimal allowed, parseInt for integer
                    if (newQuantity > adjustedBatchQuantity && product.stock_alert !== 0) {
                        toastr.error(`You cannot add more than ${adjustedBatchQuantity} units of this product.`,
                            'Warning');
                        return;
                    }

                    quantityInput.value = allowDecimal ? newQuantity.toFixed(4).replace(/\.?0+$/, '') :
                        newQuantity;
                    const subtotalElement = existingRow.querySelector('.subtotal');
                    const updatedSubtotal = newQuantity * finalPrice;
                    subtotalElement.textContent = formatAmountWithSeparators(updatedSubtotal.toFixed(2));

                    updateTotals();
                    return;
                }
            }

            const row = document.createElement('tr');

            row.innerHTML = `
        <td>
            <div class="d-flex align-items-start">
            <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;" class="product-image"/>
            <div class="product-info" style="min-width: 0; flex: 1;">
            <div class="font-weight-bold product-name" style="word-break: break-word; max-width: 260px; line-height: 1.2;">
            ${product.product_name}
            <span class="badge bg-info ms-1">MRP: ${product.max_retail_price}</span>
            ${product.is_imei_or_serial_no === 1 ? '<span class="badge bg-warning ms-1">IMEI Product</span>' : ''}
            </div>
            <div class="d-flex flex-wrap align-items-center mt-1" style="gap: 10px;">
            <span class="text-muted product-sku" style="font-size: 0.95em; word-break: break-all;">
            SKU: ${product.sku}
            </span>
            <span class="quantity-display ms-2" style="font-size: 0.95em;">
             ${adjustedBatchQuantity} ${unitName}
            </span>
            ${product.is_imei_or_serial_no === 1 ? `<span class="badge bg-info ms-2">IMEI: ${imeis[0]}</span>
              <i class="fas fa-info-circle show-imei-btn ms-1" style="cursor: pointer;" title="View/Edit IMEI"></i>` : ''}
            </div>
            </div>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
            <button class="btn btn-danger quantity-minus btn">-</button>
            <input type="number" value="${initialQuantityValue}" max="${adjustedBatchQuantity}" class="form-control quantity-input text-center" title="Available: ${adjustedBatchQuantity}" ${imeis.length > 0 ? 'readonly' : ''} step="${qtyInputStep}" pattern="${qtyInputPattern}">
            <button class="btn btn-success quantity-plus btn">+</button>
            </div>
            <div style="font-size: 0.85em; color: #888; text-align:center;">${unitName}</div>
        </td>
        <td><input type="number" name="discount_fixed[]" class="form-control fixed_discount" value="${discountFixed.toFixed(2)}"></td>
        <td><input type="number" name="discount_percent[]" class="form-control percent_discount" value="${discountPercent.toFixed(2)}"></td>
        <td><input type="number" value="${finalPrice.toFixed(2)}" class="form-control price-input text-center" data-quantity="${adjustedBatchQuantity}" min="0" readonly></td>
        <td class="subtotal">${formatAmountWithSeparators((parseFloat(initialQuantityValue) * finalPrice).toFixed(2))}</td>
        <td><button class="btn btn-danger btn-sm remove-btn">×</button></td>
        <td class="product-id d-none">${product.id}</td>
        <td class="location-id d-none">${locationId}</td>
        <td class="batch-id d-none">${batchId}</td>
        <td class="discount-data d-none">${JSON.stringify(activeDiscount || {})}</td>
        <td class="d-none imei-data">${imeis.join(',') || ''}</td>
        `;

            // Append the row first to ensure elements are available
            billingBody.insertBefore(row, billingBody.firstChild);

            // Now query the elements after inserting into DOM
            const qtyDisplayCell = row.querySelector('.quantity-display');
            const quantityInput = row.querySelector('.quantity-input');
            const plusBtn = row.querySelector('.quantity-plus');
            const minusBtn = row.querySelector('.quantity-minus');
            const showImeiBtn = row.querySelector('.show-imei-btn');

            // Handle IMEI display and input restrictions
            if (imeis.length > 0) {
                if (qtyDisplayCell) {
                    qtyDisplayCell.textContent = `${imeis.length} of ${adjustedBatchQuantity} ${unitName}`;
                }
                if (quantityInput) quantityInput.readOnly = true;
                if (plusBtn) plusBtn.disabled = true;
                if (minusBtn) minusBtn.disabled = true;
            }

            attachRowEventListeners(row, product, stockEntry);

            // Focus search input on Enter key
            if (quantityInput) {
                quantityInput.focus();
                quantityInput.select();

                quantityInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        const searchInput = document.getElementById('productSearchInput');
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.focus();
                        }
                    }
                });
            }

            disableConflictingDiscounts(row);
            updateTotals();
        }
        // Global flag to throttle error display
        let isErrorShown = false;

        // Throttled function to show error only once within a time window
        function showQuantityLimitError(maxQuantity) {
            if (!isErrorShown) {
                const errorSound = document.getElementsByClassName('errorSound')[0];
                if (errorSound) {
                    errorSound.play(); // Play sound only once
                }

                toastr.error(`You cannot add more than ${maxQuantity} units of this product.`, 'Error');

                isErrorShown = true;

                // Allow error to be shown again after 2 seconds
                setTimeout(() => {
                    isErrorShown = false;
                }, 2000); // Adjust this duration as needed
            }
        }

        function attachRowEventListeners(row, product, stockEntry) {
            const quantityInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('.price-input');
            const quantityMinus = row.querySelector('.quantity-minus');
            const quantityPlus = row.querySelector('.quantity-plus');
            const removeBtn = row.querySelector('.remove-btn');
            const productImage = row.querySelector('.product-image');
            const productName = row.querySelector('.product-name');
            const fixedDiscountInput = row.querySelector(".fixed_discount");
            const percentDiscountInput = row.querySelector(".percent_discount");

            // Get allowDecimal for this product
            const allowDecimal = product.unit && (product.unit.allow_decimal === true || product.unit
                .allow_decimal === 1);

            // // Set input attributes for decimal support
            // if (allowDecimal) {
            //     quantityInput.setAttribute('step', 'any');
            //     quantityInput.setAttribute('pattern', '[0-9]+([.][0-9]{1,4})?');
            //     quantityInput.setAttribute('inputmode', 'decimal');
            // } else {
            //     quantityInput.setAttribute('step', '1');
            //     quantityInput.setAttribute('pattern', '[0-9]*');
            //     quantityInput.setAttribute('inputmode', 'numeric');
            // }

            // Handle discount inputs
            if (fixedDiscountInput) {
                fixedDiscountInput.addEventListener('input', () => {
                    handleDiscountToggle(fixedDiscountInput);
                    updateTotals();
                });
            }
            if (percentDiscountInput) {
                percentDiscountInput.addEventListener('input', () => {
                    handleDiscountToggle(percentDiscountInput);
                    updateTotals();
                });
            }

            // Price input change → Recalculate discount
            priceInput.addEventListener('input', () => {
                const mrpElement = row.querySelector('.product-name .badge.bg-info');
                const mrpText = mrpElement ? mrpElement.textContent.trim() : '';
                const mrp = parseFloat(mrpText.replace(/[^0-9.-]/g, '')) || 0;
                let priceValue = parseFloat(priceInput.value);
                if (isNaN(priceValue) || priceValue < 0) {
                    toastr.error('Invalid price entered.', 'Error');
                    priceValue = 0;
                    priceInput.value = '0.00';
                }
                const discountAmount = mrp - priceValue;
                fixedDiscountInput.value = discountAmount > 0 ? discountAmount.toFixed(2) : '0.00';
                disableConflictingDiscounts(row); // Ensure conflict check
                updateTotals();
            });

            // quantityInput.addEventListener('blur', () => {
            //     let value = quantityInput.value;
            //     let quantityValue = parseFloat(value);

            //     if (isNaN(quantityValue)) {
            //         quantityValue = allowDecimal ? 0.01 : 1;
            //     } else {
            //         // Clamp min value
            //         if (allowDecimal && quantityValue < 0.01) quantityValue = 0.01;
            //         if (!allowDecimal && quantityValue < 1) quantityValue = 1;

            //         // Clamp max value
            //         const maxQuantity = parseFloat(priceInput.getAttribute('data-quantity'));
            //         if (quantityValue > maxQuantity && product.stock_alert !== 0) {
            //             showQuantityLimitError(maxQuantity);
            //             quantityValue = maxQuantity;
            //         }
            //     }

            //     quantityInput.value = allowDecimal ?
            //         quantityValue.toFixed(4).replace(/\.?0+$/, '') :
            //         quantityValue;
            //     quantityInput.classList.remove('is-invalid');

            //     updateTotals();
            // });


            quantityInput.addEventListener('input', () => {
                const value = quantityInput.value.trim();
                const maxQuantity = parseFloat(priceInput.getAttribute('data-quantity'));

                if (allowDecimal) {
                    const validDecimalPattern = /^\d*\.?\d{0,2}$/; // Only allow up to 2 decimals

                    if (value === '' || validDecimalPattern.test(value)) {
                        quantityInput.classList.remove('is-invalid');

                        updateTotals();

                        if (value !== '' && !value.endsWith('.')) {
                            const quantityValue = parseFloat(value);
                            if (!isNaN(quantityValue)) {
                                if (quantityValue > maxQuantity && product.stock_alert !== 0) {
                                    toastr.warning(
                                        `You are entering more than available stock for ${product.product_name}.`
                                    );
                                }

                                const subtotalElement = row.querySelector('.subtotal');
                                const price = parseFloat(priceInput.value);
                                subtotalElement.textContent = formatAmountWithSeparators((price *
                                    quantityValue).toFixed(2));

                                updateTotals();
                            }
                        }
                    } else {
                        quantityInput.classList.add('is-invalid');
                    }
                } else {
                    // Integer-only validation
                    const validIntegerPattern = /^\d+$/;

                    if (value === '' || validIntegerPattern.test(value)) {
                        quantityInput.classList.remove('is-invalid');
                        updateTotals();

                        const quantityValue = parseInt(value, 10);
                        if (!isNaN(quantityValue)) {
                            if (quantityValue > maxQuantity && product.stock_alert !== 0) {
                                toastr.warning(
                                    `You are entering more than available stock for ${product.product_name}.`
                                );
                            }

                            const subtotalElement = row.querySelector('.subtotal');
                            const price = parseFloat(priceInput.value);
                            subtotalElement.textContent = formatAmountWithSeparators((price *
                                quantityValue).toFixed(2));

                            updateTotals();
                        }
                    } else {
                        quantityInput.classList.add('is-invalid');
                    }
                }
            });

            // Minus button
            quantityMinus.addEventListener('click', () => {
                let currentQuantity = allowDecimal ? parseFloat(quantityInput.value) : parseInt(
                    quantityInput.value, 10);
                if (allowDecimal) {
                    if (currentQuantity > 0.01) {
                        currentQuantity = parseFloat((currentQuantity - 0.01).toFixed(2));
                        if (currentQuantity < 0.01) currentQuantity = 0.01;
                        quantityInput.value = currentQuantity.toFixed(2).replace(/\.?0+$/, '');
                        updateTotals();
                    }
                } else {
                    if (currentQuantity > 1) {
                        currentQuantity = currentQuantity - 1;
                        if (currentQuantity < 1) currentQuantity = 1;
                        quantityInput.value = currentQuantity;
                        updateTotals();
                    }
                }
            });

            // Plus button
            quantityPlus.addEventListener('click', () => {
                let currentQuantity = allowDecimal ? parseFloat(quantityInput.value) : parseInt(
                    quantityInput.value, 10);
                const maxQuantity = parseFloat(priceInput.getAttribute('data-quantity'));
                if (allowDecimal) {
                    if (currentQuantity < maxQuantity || product.stock_alert === 0) {
                        currentQuantity = parseFloat((currentQuantity + 0.01).toFixed(2));
                        quantityInput.value = currentQuantity.toFixed(2).replace(/\.?0+$/, '');
                        updateTotals();
                    } else {
                        showQuantityLimitError(maxQuantity);
                    }
                } else {
                    if (currentQuantity < maxQuantity || product.stock_alert === 0) {
                        currentQuantity = currentQuantity + 1;
                        quantityInput.value = currentQuantity;
                        updateTotals();
                    } else {
                        showQuantityLimitError(maxQuantity);
                    }
                }
            });

            // Event listener for the remove button
            removeBtn.addEventListener('click', () => {
                row.remove();
                updateTotals();
            });

            // Event listener for product image click
            productImage.addEventListener('click', () => {
                showProductModal(product, stockEntry, row);
            });

            // Event listener for product name click
            productName.addEventListener('click', () => {
                showProductModal(product, stockEntry, row);
            });

            const showImeiBtn = row.querySelector('.show-imei-btn');
            if (showImeiBtn) {
                showImeiBtn.addEventListener('click', function() {
                    const imeiDataCell = row.querySelector('.imei-data');
                    const imeis = imeiDataCell ? imeiDataCell.textContent.trim().split(',').filter(
                        Boolean) : [];
                    if (imeis.length === 0) {
                        toastr.warning("No IMEIs found for this product.");
                        return;
                    }
                    // Re-populate IMEI modal with current IMEIs
                    showImeiSelectionModal(product, stockEntry, imeis.map(imei => ({
                        imei_number: imei
                    })));
                });
            }

            // Newly added: Disable conflicting discounts when product is added
            disableConflictingDiscounts(row);
        }

        document.getElementById('saveProductChanges').onclick = function() {
            const selectedPriceType = document.querySelector('input[name="modal-price-type"]:checked')
                .value;
            const selectedBatch = document.getElementById('modalBatchDropdown').selectedOptions[0];
            const price = parseFloat(selectedBatch.getAttribute(`data-${selectedPriceType}-price`));
            const batchId = selectedBatch.value;
            const batchQuantity = parseFloat(selectedBatch.getAttribute('data-quantity'));

            if (selectedRow) {
                const quantityInput = selectedRow.querySelector('.quantity-input');
                const priceInput = selectedRow.querySelector('.price-input');
                const productNameCell = selectedRow.querySelector('.product-name');
                const productSkuCell = selectedRow.querySelector('.product-sku');

                priceInput.value = price.toFixed(2);
                priceInput.setAttribute('data-quantity', batchQuantity);

                // Recalculate discount based on new price
                const mrpElement = productNameCell.querySelector('.badge.bg-info');
                const mrpText = mrpElement ? mrpElement.textContent.trim() : '';
                const mrp = parseFloat(mrpText.replace(/[^0-9.-]/g, '')) || 0;

                const discountAmount = mrp - price;
                const fixedDiscountInput = selectedRow.querySelector(".fixed_discount");
                const percentDiscountInput = selectedRow.querySelector(".percent_discount");

                // Reset previous discount inputs
                fixedDiscountInput.value = discountAmount > 0 ? discountAmount.toFixed(2) : '0.00';
                percentDiscountInput.value = '';

                // Disable conflicting discounts
                disableConflictingDiscounts(selectedRow);

                // Update subtotal
                const qtyVal = quantityInput.value === "" ? 0 : parseFloat(quantityInput.value);
                const subtotal = qtyVal * price;
                selectedRow.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));

                // Update batch ID and show stars
                selectedRow.querySelector('.batch-id').textContent = batchId;
                const stars = selectedPriceType === 'retail' ? '<i class="fas fa-star"></i>' :
                    selectedPriceType === 'wholesale' ?
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i>' :
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>';
                productSkuCell.innerHTML = `${productSkuCell.textContent.trim()} ${stars}`;

                updateTotals();
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
        };

        function disableConflictingDiscounts(row) {
            const fixed = row.querySelector(".fixed_discount");
            const percent = row.querySelector(".percent_discount");

            if (!fixed || !percent) return;

            const fixedVal = parseFloat(fixed.value) || 0;
            const percentVal = parseFloat(percent.value) || 0;

            if (fixedVal > 0) {
                percent.disabled = true;
                percent.value = '';
            } else if (percentVal > 0) {
                fixed.disabled = true;
                fixed.value = '';
            } else {
                fixed.disabled = false;
                percent.disabled = false;
            }
        }

        function handleDiscountToggle(input) {
            const row = input.closest('tr');
            const fixedDiscountInput = row.querySelector('.fixed_discount');
            const percentDiscountInput = row.querySelector('.percent_discount');
            const priceInput = row.querySelector('.price-input');

            // Get MRP
            const mrpElement = row.querySelector('.product-name .badge.bg-info');
            const mrpText = mrpElement ? mrpElement.textContent.trim() : '0';
            const mrp = parseFloat(mrpText.replace(/[^0-9.-]/g, '')) || 0;

            // Disable conflicting inputs
            if (fixedDiscountInput === input && fixedDiscountInput.value !== '') {
                percentDiscountInput.disabled = true;
                percentDiscountInput.value = '';
            } else if (percentDiscountInput === input && percentDiscountInput.value !== '') {
                fixedDiscountInput.disabled = true;
                fixedDiscountInput.value = '';
            } else {
                fixedDiscountInput.disabled = false;
                percentDiscountInput.disabled = false;
            }

            // Recalculate unit price
            if (fixedDiscountInput.value !== '') {
                const discountAmount = parseFloat(fixedDiscountInput.value);
                const calculatedPrice = mrp - discountAmount;
                priceInput.value = calculatedPrice > 0 ? calculatedPrice.toFixed(2) : '0.00';
            } else if (percentDiscountInput.value !== '') {
                const discountPercent = parseFloat(percentDiscountInput.value);
                const calculatedPrice = mrp * (1 - discountPercent / 100);
                priceInput.value = calculatedPrice > 0 ? calculatedPrice.toFixed(2) : '0.00';
            } else {
                priceInput.value = mrp.toFixed(2);
            }

            updateTotals();
        }

        function updateTotals() {
            const billingBody = document.getElementById('billing-body');
            let totalItems = 0;
            let totalAmount = 0;

            // Calculate total items and total amount from each row
            billingBody.querySelectorAll('tr').forEach(row => {
                const quantityInput = row.querySelector('.quantity-input');
                const priceInput = row.querySelector('.price-input');
                const fixedDiscountInput = row.querySelector('.fixed_discount');
                const percentDiscountInput = row.querySelector('.percent_discount');
                let quantity = 0;
                if (quantityInput) {
                    quantity = quantityInput.value === "" ? 0 : parseFloat(quantityInput.value);
                }
                const basePrice = parseFloat(priceInput.value) || 0;

                // Recalculate subtotal based on unit price
                const subtotal = quantity * basePrice;

                // Update UI
                row.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));
                totalItems += quantity;
                totalAmount += subtotal;
            });

            // Global discount
            const discountElement = document.getElementById('global-discount');
            const discountTypeElement = document.getElementById('discount-type');
            const globalDiscount = discountElement && discountElement.value ? parseFloat(discountElement
                .value) || 0 : 0;
            const globalDiscountType = discountTypeElement ? discountTypeElement.value : 'fixed';

            let totalAmountWithDiscount = totalAmount;

            if (globalDiscount > 0) {
                if (globalDiscountType === 'percentage') {
                    totalAmountWithDiscount -= totalAmount * (globalDiscount / 100);
                } else {
                    totalAmountWithDiscount -= globalDiscount;
                }
            }

            // Prevent negative totals
            totalAmountWithDiscount = Math.max(0, totalAmountWithDiscount);

            // Update UI
            // Calculate total quantity and build unit summary for all products in billing
            let unitSummary = {};
            billingBody.querySelectorAll('tr').forEach(row => {
                const productId = row.querySelector('.product-id')?.textContent;
                const quantityInput = row.querySelector('.quantity-input');
                let quantity = quantityInput ? parseFloat(quantityInput.value) : 0;
                if (productId && quantity > 0) {
                    // Find the product in stockData or allProducts
                    let stock = stockData.find(s => String(s.product.id) === productId) || allProducts
                        .find(s =>
                            String(s.product.id) === productId);
                    if (stock && stock.product && stock.product.unit) {
                        // Prefer short_name, fallback to name, fallback to 'pcs'
                        let unitShort = stock.product.unit.short_name || stock.product.unit.name ||
                            'pcs';
                        if (!unitSummary[unitShort]) unitSummary[unitShort] = 0;
                        unitSummary[unitShort] += quantity;
                    }
                }
            });
            // Build display string like "4 kg, 2 pcs, 1 pack"
            let unitDisplay = Object.entries(unitSummary)
                .map(([unit, qty]) => `${qty % 1 === 0 ? qty : qty.toFixed(4).replace(/\.?0+$/, '')} ${unit}`)
                .join(', ');
            document.getElementById('items-count').textContent = unitDisplay || totalItems.toFixed(2);
            document.getElementById('modal-total-items').textContent = unitDisplay || totalItems.toFixed(2);
            document.getElementById('total-amount').textContent = formatAmountWithSeparators(totalAmount
                .toFixed(2));
            document.getElementById('final-total-amount').textContent = formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
            document.getElementById('total').textContent = formatAmountWithSeparators(totalAmountWithDiscount
                .toFixed(2));
            document.getElementById('payment-amount').textContent = 'Rs ' + formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
        }

        //chanege event global discount
        const globalDiscountInput = document.getElementById('global-discount');
        const globalDiscountTypeInput = document.getElementById('discount-type');
        if (globalDiscountInput) {
            globalDiscountInput.addEventListener('input', function() {
                const discountValue = parseFloat(this.value) || 0;
                const discountType = globalDiscountTypeInput.value;
                if (discountType === 'percentage') {
                    this.value = Math.min(discountValue, 100); // Limit to 100%
                }
                updateTotals();
            });
            globalDiscountInput.addEventListener('input', function() {
                const discountValue = parseFloat(this.value) || 0;
                if (globalDiscountTypeInput.value === 'percentage') {
                    this.value = Math.min(discountValue, 100);
                }
                updateTotals();

            });




        }





        let saleId = null;
        const pathSegments = window.location.pathname.split('/');
        saleId = pathSegments[pathSegments.length - 1];

        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            fetchEditSale(saleId);
        } else {
            // console.warn('Invalid or missing saleId:', saleId);
        }

        function fetchEditSale(saleId) {
            fetch(`/sales/edit/${saleId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    const contentType = response.headers.get('Content-Type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Invalid response format. Expected JSON.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 200) {
                        const saleDetails = data.sale_details;

                        // Update invoice number
                        const saleInvoiceElement = document.getElementById('sale-invoice-no');
                        if (saleInvoiceElement && saleDetails.sale) {
                            saleInvoiceElement.textContent = `Invoice No: ${saleDetails.sale.invoice_no}`;
                        }

                        // Set the locationId based on the sale's location_id
                        if (saleDetails.sale && saleDetails.sale.location_id) {
                            locationId = saleDetails.sale.location_id;
                            selectedLocationId = saleDetails.sale
                                .location_id; // Ensure global variable is updated
                            // Update the location dropdown
                            const locationSelect = document.getElementById('locationSelect');
                            if (locationSelect) {
                                locationSelect.value = saleDetails.sale.location_id
                                    .toString(); // Ensure value matches option value type
                                console.log('Location ID set to:', saleDetails.sale.location_id);
                                // Manually trigger the change event to refresh products
                                $(locationSelect).trigger('change'); // Use jQuery to trigger the event
                            }
                        }

                        // Populate sale products
                        saleDetails.sale_products.forEach(saleProduct => {
                            const price = saleProduct.price || saleProduct.product.retail_price;
                            const stockEntry = stockData.find(stock =>
                                stock.product.id === saleProduct.product.id
                            );
                            let batches = [];
                            if (stockEntry && Array.isArray(stockEntry.batches)) {
                                batches = [...stockEntry.batches];
                            }

                            // Add sold quantity back to batch temporarily
                            const originalBatchExists = batches.some(batch =>
                                batch.id === saleProduct.batch?.id
                            );
                            if (originalBatchExists && saleProduct.batch) {
                                batches = batches.map(batch => {
                                    if (batch.id === saleProduct.batch.id) {
                                        const updatedLocationBatches = batch
                                            .location_batches.map(lb => {
                                                if (lb.location_id === saleProduct
                                                    .location_id) {
                                                    return {
                                                        ...lb,
                                                        quantity: lb.quantity +
                                                            saleProduct.quantity
                                                    };
                                                }
                                                return lb;
                                            });
                                        return {
                                            ...batch,
                                            location_batches: updatedLocationBatches
                                        };
                                    }
                                    return batch;
                                });
                            } else if (saleProduct.batch) {
                                batches.push({
                                    id: saleProduct.batch.id,
                                    batch_no: saleProduct.batch.batch_no,
                                    retail_price: parseFloat(saleProduct.batch
                                        .retail_price),
                                    wholesale_price: parseFloat(saleProduct.batch
                                        .wholesale_price),
                                    special_price: parseFloat(saleProduct.batch
                                        .special_price),
                                    location_batches: [{
                                        location_id: saleProduct.location_id,
                                        quantity: saleProduct.total_quantity +
                                            saleProduct.quantity
                                    }],
                                });
                            }

                            let totalStock = saleProduct.total_quantity + saleProduct.quantity;
                            if (stockEntry) {
                                totalStock = batches.reduce((sum, batch) => {
                                    return sum + batch.location_batches.reduce((batchSum,
                                        lb) => {
                                        return batchSum + lb.quantity;
                                    }, 0);
                                }, 0);
                            }

                            const normalizedStockEntry = {
                                batches: batches,
                                total_stock: totalStock,
                                product: saleProduct.product
                            };

                            // Pass only saleProduct.quantity as saleQuantity
                            addProductToBillingBody(
                                saleProduct.product,
                                normalizedStockEntry,
                                price,
                                saleProduct.batch_id,
                                saleProduct.quantity, // Batch quantity
                                saleProduct.price_type,
                                saleProduct
                                .quantity, // Sale quantity (this is what should be shown)
                                saleProduct.imei_numbers || [],
                                saleProduct.discount_type,
                                saleProduct.discount_amount
                            );

                            // Apply product-level discount
                            const productRow = $('#billing-body tr:last-child');
                            const fixedDiscountInput = productRow.find('.fixed_discount');
                            const percentDiscountInput = productRow.find('.percent_discount');

                            if (saleProduct.discount_type === 'fixed') {
                                const fixedAmount = parseFloat(saleProduct.discount_amount) || 0;
                                fixedDiscountInput.val(fixedAmount.toFixed(2));
                                percentDiscountInput.val('');
                            } else if (saleProduct.discount_type === 'percentage') {
                                const percentAmount = parseFloat(saleProduct.discount_amount) || 0;
                                percentDiscountInput.val(percentAmount.toFixed(2));
                                fixedDiscountInput.val('');
                            }
                        });

                        // If the sale has a customer_id, trigger customer data fetch
                        if (saleDetails.sale && saleDetails.sale.customer_id) {
                            console.log('Fetching customer data for customer_id:', saleDetails.sale
                                .customer_id);
                            const $customerSelect = $('#customer-id');
                            if ($customerSelect.length) {
                                $customerSelect.val(saleDetails.sale.customer_id.toString());

                                // Wait for the customer select2 to finish loading (if async), then trigger change
                                setTimeout(() => {
                                    $customerSelect.trigger(
                                        'change'); // Use jQuery to trigger the event

                                    // Now call fetchCustomerData if available
                                    if (window.customerFunctions && typeof window.customerFunctions
                                        .fetchCustomerData === 'function') {
                                        window.customerFunctions.fetchCustomerData().then(() => {
                                            // After fetching, set the value and trigger change again to ensure due is updated
                                            $customerSelect.val(saleDetails.sale.customer_id
                                                .toString());
                                            $customerSelect.trigger('change');
                                            console.log(
                                                'Customer select and fetchCustomerData triggered for customer_id:',
                                                saleDetails.sale.customer_id);
                                        });
                                    } else {
                                        console.log(
                                            'Customer select and fetchCustomerData triggered for customer_id:',
                                            saleDetails.sale.customer_id);
                                    }
                                }, 200); // Adjust delay if needed for your UI
                            }
                        }

                        // Set global discount values
                        const discountElement = document.getElementById('global-discount');
                        const discountTypeElement = document.getElementById('discount-type');
                        if (discountElement && saleDetails.sale) {
                            discountElement.value = saleDetails.sale.discount_amount || 0;
                        }
                        if (discountTypeElement && saleDetails.sale) {
                            discountTypeElement.value = saleDetails.sale.discount_type || 'fixed';
                        }

                        // Update totals
                        updateTotals();
                    } else {
                        console.error('Invalid sale data:', data);
                        toastr.error('Failed to fetch sale data.', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching sale data:', error);
                    toastr.error('An error occurred while fetching sale data.', 'Error');
                });
        }



        $(document).ready(function() {

            function gatherSaleData(status) {
                const uniqueNumber = new Date().getTime() % 10000;
                const customerId = $('#customer-id').val();
                const salesDate = new Date().toISOString().slice(0, 10);

                if (!locationId) {
                    toastr.error('Location ID is required.');
                    return null;
                }

                // Get discount values
                const discountType = $('#discount-type').val() || 'fixed';
                const discountAmount = parseFormattedAmount($('#global-discount').val()) || 0;

                // Calculate total amount and final amount
                const totalAmount = parseFormattedAmount($('#total-amount').text()) || 0;
                let finalAmount = totalAmount;

                // Apply discount
                if (discountType === 'percentage') {
                    finalAmount -= totalAmount * (discountAmount / 100);
                } else {
                    finalAmount -= discountAmount;
                }

                // Ensure final amount doesn't go negative
                finalAmount = Math.max(0, finalAmount);

                const saleData = {
                    customer_id: customerId,
                    sales_date: salesDate,
                    location_id: locationId,
                    status: status,
                    sale_type: "POS",
                    products: [],
                    discount_type: discountType,
                    discount_amount: discountAmount,
                    total_amount: totalAmount,
                    final_total: finalAmount,
                };

                const productRows = $('#billing-body tr');
                if (productRows.length === 0) {
                    toastr.error('At least one product is required.');
                    return null;
                }

                productRows.each(function() {
                    const productRow = $(this);
                    const batchId = productRow.find('.batch-id').text().trim();
                    const locationId = productRow.find('.location-id').text().trim();
                    const discountFixed = parseFloat(productRow.find('.fixed_discount').val()
                        .trim()) || 0;
                    const discountPercent = parseFloat(productRow.find('.percent_discount')
                        .val().trim()) || 0;
                    const isImeiProduct = productRow.find('.imei-data').text().trim() !== '';

                    // Determine which discount is active
                    const discountType = discountFixed > 0 ? 'fixed' : 'percentage';
                    const discountAmount = discountFixed > 0 ? discountFixed : discountPercent;

                    // Get IMEI numbers if any
                    const imeiData = productRow.find('.imei-data').text().trim();
                    const imeis = imeiData ? [imeiData] : []; // Create array with single IMEI

                    if (!locationId) {
                        toastr.error('Location ID is missing for a product.');
                        return;
                    }

                    const productData = {
                        product_id: parseInt(productRow.find('.product-id').text().trim(),
                            10),
                        location_id: parseInt(locationId, 10),
                        quantity: isImeiProduct ?
                            1 : (() => {
                                // Find the product in stockData or allProducts
                                const productId = parseInt(productRow.find(
                                    '.product-id').text().trim(), 10);
                                let stock = stockData.find(s => String(s.product.id) ===
                                        String(productId)) ||
                                    allProducts.find(s => String(s.product.id) ===
                                        String(productId));
                                const allowDecimal = stock && stock.product && (stock
                                    .product.unit?.allow_decimal === true || stock
                                    .product.unit?.allow_decimal === 1);
                                const qtyVal = productRow.find('.quantity-input').val()
                                    .trim();
                                if (allowDecimal) {
                                    const parsed = parseFloat(qtyVal);
                                    return isNaN(parsed) ? 0 : parsed;
                                } else {
                                    const parsed = parseInt(qtyVal, 10);
                                    return isNaN(parsed) ? 0 : parsed;
                                }
                            })(),
                        price_type: priceType,
                        unit_price: parseFormattedAmount(productRow.find('.price-input')
                            .val().trim()),
                        subtotal: parseFormattedAmount(productRow.find('.subtotal').text()
                            .trim()),
                        discount_amount: discountAmount,
                        discount_type: discountType,
                        tax: 0,
                        batch_id: batchId === "all" ? "all" : batchId,
                        imei_numbers: imeis,
                    };

                    saleData.products.push(productData);
                });

                return saleData;
            }


            function sendSaleData(saleData, saleId = null, onComplete = () => {}) {
                // Extract saleId from the URL if not provided
                if (!saleId) {
                    const pathSegments = window.location.pathname.split('/');
                    const possibleSaleId = pathSegments[pathSegments.length - 1];
                    if (!isNaN(possibleSaleId) && possibleSaleId !== 'pos' && possibleSaleId !==
                        'list-sale') {
                        saleId = possibleSaleId;
                    }
                }

                const url = saleId ? `/sales/update/${saleId}` : '/sales/store';
                const method = 'POST';



                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    data: JSON.stringify(saleData),
                    success: function(response) {
                        if (response.message && response.invoice_html) {
                            document.getElementsByClassName('successSound')[0].play();
                            toastr.success(response.message);

                            // Create a hidden iframe for printing
                            const iframe = document.createElement('iframe');
                            iframe.style.position = 'fixed';
                            iframe.style.width = '0';
                            iframe.style.height = '0';
                            iframe.style.border = 'none';
                            document.body.appendChild(iframe);

                            // Write the receipt content to the iframe
                            iframe.contentDocument.open();
                            iframe.contentDocument.write(response.invoice_html);
                            iframe.contentDocument.close();

                            iframe.onload = function() {
                                // Trigger the print dialog from the iframe
                                iframe.contentWindow.print();

                                iframe.contentWindow.onafterprint = function() {
                                    // Remove the iframe after printing
                                    document.body.removeChild(iframe);

                                    // Only redirect for edit sales, not for new sales
                                    if (saleId) {
                                        window.location.href = '/pos-create';
                                    }
                                };
                            };

                            // Reset the form and refresh products
                            resetForm();
                            fetchPaginatedProducts(true);
                            fetchSalesData();

                            if (onComplete) onComplete();

                        } else {
                            toastr.error('Failed to record sale: ' + response.message);
                            if (onComplete) onComplete();
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred: ' + xhr.responseText);
                        if (onComplete) onComplete();
                    }
                });
            }

            function gatherCashPaymentData() {
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                // console.log("final_amount " + totalAmount);
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'cash',
                    payment_date: today,
                    amount: totalAmount
                }];
            }

            $('#cashButton').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    const customerId = $('#customer-id').val();
                    const totalAmount = parseFormattedAmount($('#final-total-amount')
                        .text().trim());
                    let amountGiven = parseFormattedAmount($('#amount-given').val()
                        .trim());

                    // Default to full payment if empty or invalid
                    const isWalkInCustomer = customerId == 1;

                    if (isNaN(amountGiven) || amountGiven <= 0) {
                        amountGiven = totalAmount;
                    }

                    let paidAmount = amountGiven;
                    let balance = amountGiven - totalAmount;

                    // If amountGiven is greater than totalAmount, settle only up to totalAmount
                    if (amountGiven > totalAmount) {
                        paidAmount = totalAmount;
                        balance = amountGiven - totalAmount;
                    }

                    saleData.amount_given = amountGiven;
                    saleData.balance_amount = Math.max(0, balance); // Prevent negatives

                    // Block partial payment for Walk-In Customer
                    if (isWalkInCustomer && paidAmount < totalAmount) {
                        toastr.error(
                            "Partial payment is not allowed for Walk-In Customer.");
                        enableButton(button);
                        return;
                    }

                    saleData.payments = [{
                        payment_method: 'cash',
                        payment_date: new Date().toISOString().slice(0, 10),
                        amount: paidAmount // Only settle up to total amount
                    }];

                    if (paidAmount >= totalAmount) {
                        sendSaleData(saleData, null, () => enableButton(button));
                    } else {
                        // Partial payment (non-Walk-In)
                        swal({
                            title: "Partial Payment",
                            text: "You're making a partial payment of Rs. " +
                                formatAmountWithSeparators(paidAmount.toFixed(
                                    2)) +
                                ". The remaining Rs. " +
                                formatAmountWithSeparators((totalAmount -
                                    paidAmount).toFixed(2)) +
                                " will be due later.",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Proceed",
                            cancelButtonText: "Cancel"
                        }, function(isConfirm) {
                            if (isConfirm) {
                                sendSaleData(saleData, null, () => enableButton(
                                    button));
                            } else {
                                enableButton(button);
                            }
                        });
                    }
                });
            });

            $('#cardButton').on('click', function() {
                $('#cardModal').modal('show');
            });

            function gatherCardPaymentData() {
                const cardNumber = $('#card_number').val().trim();
                const cardHolderName = $('#card_holder_name').val().trim();
                const cardExpiryMonth = $('#card_expiry_month').val().trim();
                const cardExpiryYear = $('#card_expiry_year').val().trim();
                const cardSecurityCode = $('#card_security_code').val().trim();
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'card',
                    payment_date: today,
                    amount: totalAmount,
                    card_number: cardNumber,
                    card_holder_name: cardHolderName,
                    card_expiry_month: cardExpiryMonth,
                    card_expiry_year: cardExpiryYear,
                    card_security_code: cardSecurityCode
                }];
            }

            $('#confirmCardPayment').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    saleData.payments = gatherCardPaymentData();
                    sendSaleData(saleData, null, () => {
                        $('#cardModal').modal('hide');
                        resetCardModal();
                        enableButton(button);
                    });
                });
            });

            $('#chequeButton').on('click', function() {
                $('#chequeModal').modal('show');
            });

            function gatherChequePaymentData() {
                const chequeNumber = $('#cheque_number').val().trim();
                const bankBranch = $('#cheque_bank_branch').val().trim();
                const chequeReceivedDate = $('#cheque_received_date').val().trim();
                const chequeValidDate = $('#cheque_valid_date').val().trim();
                const chequeGivenBy = $('#cheque_given_by').val().trim();
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'cheque',
                    payment_date: today,
                    amount: totalAmount,
                    cheque_number: chequeNumber,
                    bank_branch: bankBranch,
                    cheque_received_date: chequeReceivedDate,
                    cheque_valid_date: chequeValidDate,
                    cheque_given_by: chequeGivenBy
                }];
            }

            function validateChequeFields() {
                let isValid = true;

                if ($('#cheque_number').val().trim() === '') {
                    $('#chequeNumberError').text('Cheque Number is required.');
                    isValid = false;
                } else {
                    $('#chequeNumberError').text('');
                }

                if ($('#cheque_received_date').val().trim() === '') {
                    $('#chequeReceivedDateError').text('Cheque Received Date is required.');
                    isValid = false;
                } else {
                    $('#chequeReceivedDateError').text('');
                }

                if ($('#cheque_valid_date').val().trim() === '') {
                    $('#chequeValidDateError').text('Cheque Valid Date is required.');
                    isValid = false;
                } else {
                    $('#chequeValidDateError').text('');
                }

                return isValid;
            }

            $('#confirmChequePayment').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    if (!validateChequeFields()) {
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    saleData.payments = gatherChequePaymentData();
                    sendSaleData(saleData, null, () => {
                        $('#chequeModal').modal('hide');
                        resetChequeModal();
                        enableButton(button);
                    });
                });
            });

            function resetCardModal() {
                $('#card_number').val('');
                $('#card_holder_name').val('');
                $('#card_type').val('visa');
                $('#card_expiry_month').val('');
                $('#card_expiry_year').val('');
                $('#card_security_code').val('');
            }

            function resetChequeModal() {
                $('#cheque_number').val('');
                $('#bank_branch').val('');
                $('#cheque_received_date').val('');
                $('#cheque_valid_date').val('');
                $('#cheque_given_by').val('');
                $('.error-message').text('');
            }

            $('#creditSaleButton').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    const customerId = $('#customer-id').val();
                    if (customerId == 1) {
                        toastr.error(
                            'Credit sale is not allowed for Walking Customer. Please choose another customer.'
                        );
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    sendSaleData(saleData, null, () => enableButton(button));
                });
            });

            $('#suspendModal').on('click', '#confirmSuspend', function() {
                const saleData = gatherSaleData('suspend');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                sendSaleData(saleData);
                let modal = bootstrap.Modal.getInstance(document.getElementById(
                    "suspendModal"));
                modal.hide();
            });

            $('#jobTicketButton').on('click', function() {
                const customerId = $('#customer-id').val();
                if (!customerId || customerId === "1") {
                    toastr.error(
                        'Please select a valid customer (not Walk-In) before creating a job ticket.'
                    );
                    return;
                }

                // Fetch customer details from API using customerId
                $.ajax({
                    url: '/customer-get-by-id/' + customerId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200 && response.customer) {
                            const customer = response.customer;
                            // Compose full name with prefix, first_name, last_name
                            const fullName = [customer.prefix, customer.first_name,
                                customer.last_name
                            ].filter(Boolean).join(' ');
                            $('#customerName').val(fullName || '');
                            $('#customerMobile').val(customer.mobile_no || '');
                            $('#customerEmail').val(customer.email || '');
                            $('#customerAddress').val(customer.address || '');
                        } else {
                            toastr.error('Failed to fetch customer details.');
                            $('#customerName').val('');
                            $('#customerMobile').val('');
                            $('#customerEmail').val('');
                            $('#customerAddress').val('');
                        }

                        // Populate total amount
                        const totalAmount = parseFormattedAmount($(
                            '#final-total-amount').text().trim());
                        $('#totalAmountInput').val(totalAmount.toFixed(2));

                        // Set default advance amount from #amount-given (if any)
                        let amountGiven = parseFormattedAmount($('#amount-given')
                            .val().trim());
                        $('#advanceAmountInput').val((isNaN(amountGiven) ? 0 :
                            amountGiven).toFixed(2));

                        // Calculate balance
                        calculateJobTicketBalance();

                        // Show modal
                        $('#jobTicketModal').modal('show');
                    },
                    error: function() {
                        toastr.error('Error fetching customer details.');
                        $('#customerName').val('');
                        $('#customerMobile').val('');
                        $('#customerEmail').val('');
                        $('#customerAddress').val('');
                    }
                });
            });

            // 2. Recalculate balance on Advance Amount change
            $('#advanceAmountInput').on('input', function() {
                calculateJobTicketBalance();
            });

            function calculateJobTicketBalance() {
                const totalAmount = parseFloat($('#totalAmountInput').val()) || 0;
                const advanceAmount = parseFloat($('#advanceAmountInput').val()) || 0;
                let balance = totalAmount - advanceAmount;
                if (balance < 0) balance = 0; // Prevent negative balance
                $('#balanceAmountInput').val(balance.toFixed(2));
            }

            $('#submitJobTicket').on('click', function() {
                // Gather sale data
                const saleData = gatherSaleData('jobticket');
                if (!saleData) {
                    toastr.error(
                        'Please add at least one product before submitting the job ticket.');
                    return;
                }

                // Attach customer details to saleData (optional, if needed by backend)
                saleData.customer_name = $('#customerName').val();
                saleData.customer_mobile = $('#customerMobile').val();
                saleData.customer_email = $('#customerEmail').val();
                saleData.customer_address = $('#customerAddress').val();

                // Attach advance and balance
                saleData.advance_amount = parseFloat($('#advanceAmountInput').val()) || 0;
                let balanceAmount = parseFloat($('#balanceAmountInput').val()) || 0;
                if (balanceAmount < 0) balanceAmount = 0; // Prevent negative balance
                saleData.balance_amount = balanceAmount;
                saleData.total_paid = saleData
                    .advance_amount; // Set total_paid to advance amount
                saleData.amount_given = saleData
                    .advance_amount; // <-- Ensure amount_given is set for backend
                saleData.jobticket_description = $('#description').val();

                // Send to backend with status "jobticket"
                sendSaleData(saleData, null, function() {
                    $('#jobTicketModal').modal('hide');
                });
            });




            // document.getElementById('finalize_payment').addEventListener('click', function() {
            //     const saleData = gatherSaleData('final');
            //     if (!saleData) {
            //         toastr.error('Please add at least one product before completing the sale.');
            //         return;
            //     }

            //     const paymentData = gatherPaymentData();
            //     saleData.payments = paymentData;
            //     sendSaleData(saleData);
            //     let modal = bootstrap.Modal.getInstance(document.getElementById(
            //         "paymentModal"));
            //     modal.hide();
            // });

            // function gatherPaymentData() {
            //     const paymentData = [];
            //     document.querySelectorAll('.payment-row').forEach(row => {
            //         const paymentMethod = row.querySelector('.payment-method').value;
            //         const paymentDate = row.querySelector('.payment-date').value;
            //         const amount = parseFormattedAmount(row.querySelector('.payment-amount')
            //             .value);
            //         const conditionalFields = {};

            //         row.querySelectorAll('.conditional-fields input').forEach(input => {
            //             conditionalFields[input.name] = input.value;
            //         });

            //         paymentData.push({
            //             payment_method: paymentMethod,
            //             payment_date: paymentDate,
            //             amount: amount,
            //             ...conditionalFields
            //         });
            //     });
            //     return paymentData;
            // }

            document.getElementById('finalize_payment').addEventListener('click', function() {
                const saleData = gatherSaleData('final');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                const paymentData = gatherPaymentData();

                // Calculate sum of all payment rows (what customer gave in total)
                const amountGiven = paymentData.reduce((sum, pay) => sum + (parseFloat(pay
                    .amount) || 0), 0);
                // Calculate final total (bill)
                const finalTotal = parseFormattedAmount(document.getElementById(
                    'modal-total-payable').textContent);

                // Calculate totalPaid: should not exceed finalTotal
                let totalPaid = Math.min(amountGiven, finalTotal);
                let balanceAmount = Math.max(0, amountGiven - finalTotal);

                // If amountGiven is less than finalTotal, balanceAmount is 0 (no change to give)
                // If amountGiven is more, balanceAmount is the change to give

                // Attach these to saleData for backend saving
                saleData.payments = paymentData;
                saleData.amount_given = amountGiven;
                saleData.total_paid = totalPaid;
                saleData.balance_amount = balanceAmount;

                // Log for debugging
                console.log("Payment Data:", paymentData);
                console.log("Sale Data:", saleData);

                // Send to server
                sendSaleData(saleData);

                // Hide modal
                let modal = bootstrap.Modal.getInstance(document.getElementById(
                    "paymentModal"));
                if (modal) modal.hide();
            });

            function gatherPaymentData() {
                const paymentData = [];
                document.querySelectorAll('.payment-row').forEach(row => {
                    const paymentMethod = row.querySelector('.payment-method').value;
                    const paymentDate = row.querySelector('.payment-date').value;
                    const amountInput = row.querySelector('.payment-amount').value;
                    let amount = parseFormattedAmount(amountInput);

                    const conditionalFields = {};
                    row.querySelectorAll(
                        '.conditional-fields input, .conditional-fields select').forEach(
                        input => {
                            conditionalFields[input.name] = input.value.trim();
                        });

                    if (!isNaN(amount) && amount > 0) {
                        paymentData.push({
                            payment_method: paymentMethod,
                            payment_date: paymentDate,
                            amount: amount,
                            ...conditionalFields
                        });
                    }
                });

                // Log each payment row for debugging
                console.log("Each Payment Row:", paymentData);

                return paymentData;
            }



            function fetchSuspendedSales() {
                $.ajax({
                    url: '/sales/suspended',
                    type: 'GET',
                    success: function(response) {
                        displaySuspendedSales(response);
                        $('#suspendSalesModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Failed to fetch suspended sales: ' + xhr
                            .responseText);
                    }
                });
            }

            function displaySuspendedSales(sales) {
                const suspendedSalesContainer = $('#suspendedSalesContainer');
                suspendedSalesContainer.empty();

                sales.forEach(sale => {
                    const finalTotal = parseFormattedAmount(sale.final_total);
                    const saleRow = `
                <tr>
                    <td>${sale.invoice_no}</td>
                    <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
                    <td>${sale.customer ? sale.customer.name : 'Walk-In Customer'}</td>
                    <td>${sale.products.length}</td>
                    <td>$${formatAmountWithSeparators(finalTotal.toFixed(2))}</td>
                    <td>
                        <a href="/sales/edit/${sale.id}" class="btn btn-success editSaleButton" data-sale-id="${sale.id}">Edit</a>
                        <button class="btn btn-danger deleteSuspendButton" data-sale-id="${sale.id}">Delete</button>
                    </td>
                </tr>`;
                    suspendedSalesContainer.append(saleRow);
                });

                $('.editSaleButton').on('click', function() {
                    const saleId = $(this).data('sale-id');
                    // editSale(saleId);
                });

                $('.deleteSuspendButton').on('click', function() {
                    const saleId = $(this).data('sale-id');
                    deleteSuspendedSale(saleId);
                });
            }

            // Function to delete a suspended sale
            function deleteSuspendedSale(saleId) {
                $.ajax({
                    url: `/sales/delete-suspended/${saleId}`,
                    type: 'DELETE',
                    success: function(response) {
                        toastr.success(response.message);
                        // Code to update the POS page after deletion
                        fetchSuspendedSales(); // Refresh suspended sales list
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Failed to delete suspended sale: ' + xhr
                            .responseText);
                    }
                });
            }

            // Event listener for the pause circle button to fetch and show suspended sales
            $('#pauseCircleButton').on('click', function() {
                fetchSuspendedSales();
            });

            $('#amount-given').on('input', function() {
                let amountGiven = parseFormattedAmount($(this).val().trim());

                if (isNaN(amountGiven) || amountGiven < 0) {
                    amountGiven = 0;
                }

                const allPaymentButtons = [
                    '#cardButton',
                    '#chequeButton',
                    '#creditSaleButton',
                    '#multiplePayButton',
                    '#pauseCircleButton'
                ].join(',');

                // Optional: Set placeholder or clear if empty
                if (amountGiven === 0) {
                    $(this).val('');
                    // Enable all payment buttons when cleared
                    $(allPaymentButtons).prop('disabled', false); // To enable

                } else {
                    $(this).val(formatAmountWithSeparators(amountGiven));

                    $(allPaymentButtons).prop('disabled', true); // To disable
                }
            });


            $('#amount-given').on('keyup', function(event) {
                if (event.key === 'Enter') {
                    const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                        .trim());
                    const amountGiven = parseFormattedAmount($('#amount-given').val().trim());

                    if (isNaN(amountGiven) || amountGiven <= 0) {
                        toastr.error('Please enter a valid amount given by the customer.');
                        return;
                    }

                    const balance = amountGiven - totalAmount;

                    if (balance > 0) {
                        swal({
                            title: "Return Amount",
                            text: "The balance amount to be returned is Rs. " +
                                formatAmountWithSeparators(balance.toFixed()),
                            type: "info",
                            showCancelButton: false,
                            confirmButtonText: "OK",
                            customClass: {
                                title: 'swal-title-large',
                                text: 'swal-title-large'
                            }
                        }, function() {
                            $('#cashButton').trigger('click');
                        });
                    } else {

                        $('#cashButton').trigger('click');
                    }
                }
            });

            // Fetch suspended sales when the POS page loads
            // fetchSuspendedSales();


            document.getElementById('quotationButton').addEventListener('click', function() {
                const saleData = gatherSaleData('quotation');
                if (!saleData) return;
                sendSaleData(saleData);
            });

            document.getElementById('draftButton').addEventListener('click', function() {
                const saleData = gatherSaleData('draft');
                if (!saleData) return;
                sendSaleData(saleData);
            });




        });



        document.getElementById('cancelButton').addEventListener('click', resetForm);

        function resetToWalkingCustomer() {
            const customerSelect = $('#customer-id');
            const walkingCustomer = customerSelect.find('option').filter(function() {
                return $(this).text().startsWith('Walk-in');
            });

            if (walkingCustomer.length > 0) {
                customerSelect.val(walkingCustomer.val());
                customerSelect.trigger('change');
            }
        }

        function resetForm() {
            resetToWalkingCustomer();
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.value = 1;
            });

            const billingBodyRows = document.querySelectorAll('#billing-body tr');
            billingBodyRows.forEach(row => {
                row.remove();
            });

            document.getElementById('amount-given').value = ''; // Reset the amount given field

            // Reset discount fields
            document.getElementById('global-discount').value = '';
            document.getElementById('discount-type').value = 'fixed';

            updateTotals();
        }






    });
    $(document).ready(function() {
        // Initialize DataTable
        $('#transactionTable').DataTable();

        // Fetch sales data on page load
        fetchSalesData();
    });

    let sales = [];

    // Function to fetch sales data from the server using AJAX
    function fetchSalesData() {
        $.ajax({
            url: '/sales',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (Array.isArray(data)) {
                    sales = data;
                } else if (data.sales && Array.isArray(data.sales)) {
                    sales = data.sales;
                } else {
                    console.error('Unexpected data format:', data);
                }
                // Load the default tab data (e.g., 'final')
                loadTableData('final');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching sales data:', error);
            }
        });
    }

    function loadTableData(status) {
        const table = $('#transactionTable').DataTable();
        table.clear().draw(); // Clear existing data

        // Filter by status
        const filteredSales = sales.filter(sale => sale.status === status);

        if (filteredSales.length === 0) {
            table.row.add([
                '', 'No records found', '', '', '', '', ''
            ]).draw(false);
        } else {
            // Sort by id descending (latest ID first)
            const sortedSales = filteredSales.sort((a, b) => b.id - a.id);

            // Add each row in sorted order
            sortedSales.forEach((sale, index) => {
                let customerName = [
                    sale.customer?.prefix,
                    sale.customer?.first_name,
                    sale.customer?.last_name
                ].filter(Boolean).join(' ');

                table.row.add([
                    index + 1,
                    sale.invoice_no,
                    customerName || 'Walk-In Customer',
                    sale.sales_date,
                    sale.final_total,
                    `<button class='btn btn-outline-success btn-sm' onclick="printReceipt(${sale.id})">Print</button>
                 <button class='btn btn-outline-primary btn-sm' onclick="navigateToEdit(${sale.id})">Edit</button>`,
                    '' // Extra column if needed
                ]);
            });

            table.draw(); // Draw all rows at once for performance
        }
    }

    // Function to navigate to the edit page
    function navigateToEdit(saleId) {
        window.location.href = "/sales/edit/" + saleId;
    }

    // Function to print the receipt for the sale
    function printReceipt(saleId) {
        fetch(`/sales/print-recent-transaction/${saleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.invoice_html) {
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = 'none';
                    document.body.appendChild(iframe);

                    iframe.contentDocument.open();
                    iframe.contentDocument.write(data.invoice_html);
                    iframe.contentDocument.close();

                    iframe.onload = function() {
                        iframe.contentWindow.print();
                        iframe.contentWindow.onafterprint = function() {
                            document.body.removeChild(iframe);
                        };
                    };
                } else {
                    // alert('Failed to fetch the receipt. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error fetching the receipt:', error);
                // alert('An error occurred while fetching the receipt. Please try again.');
            });
    }
</script>


{{-- For jQuery --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Include Mousetrap library -->
{{-- <script src="{{ asset('assets/js/mousetrap.js') }}"></script> --}}
<script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        let currentRowIndex = 0;

        // Enable hotkeys inside input, textarea, and select fields
        hotkeys.filter = function(event) {
            return true; // Allow shortcuts in any element
        };

        function focusQuantityInput() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            if (quantityInputs.length > 0) {
                quantityInputs[currentRowIndex].focus();
                quantityInputs[currentRowIndex].select();
                currentRowIndex = (currentRowIndex + 1) % quantityInputs.length;
            }
        }

        // F2 - Focus next quantity input
        hotkeys('f2', function(event) {
            event.preventDefault();
            focusQuantityInput();
        });

        // F4 - Focus product search
        hotkeys('f4', function(event) {
            event.preventDefault();
            const productSearchInput = document.getElementById('productSearchInput');
            if (productSearchInput) {
                productSearchInput.focus();
                productSearchInput.select();
            }
        });

        // F5 - Refresh page
        hotkeys('f5', function(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to refresh the page?')) {
                location.reload();
            }
        });

        // F6 - Click cash button
        hotkeys('f6', function(event) {
            event.preventDefault();
            const cashBtn = document.querySelector('#cashButton');
            if (cashBtn) {
                cashBtn.click();
            }
        });

        // F7 - Focus amount given input
        hotkeys('f7', function(event) {
            event.preventDefault();
            const amountInput = document.querySelector('#amount-given');
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        });

        // F8 - Focus discount input
        hotkeys('f8', function(event) {
            event.preventDefault();
            const discountInput = document.querySelector('#global-discount');
            if (discountInput) {
                discountInput.focus();
                discountInput.select();
            }
        });

        // F9 - Open customer Select2 and focus search
        hotkeys('f9', function(event) {
            event.preventDefault();
            const customerSelect = $('#customer-id');
            if (customerSelect.length) {
                customerSelect.select2('open');
                setTimeout(() => {
                    $('.select2-search__field').focus();
                }, 100);
            }
        });

        // Initial focus
        focusQuantityInput();
    });
</script>


<!-- Include cleave.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
{{-- For sound --}}
<audio class="successSound" src="{{ asset('assets/sounds/success.mp3') }}"></audio>
<audio class="errorSound" src="{{ asset('assets/sounds/error.mp3') }}"></audio>
<audio class="warningSound" src="{{ asset('assets/sounds/warning.mp3') }}"></audio>


<script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.js') }}"></script>
<script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<!-- jQuery Validation Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>

<script>
    $(function() {
        $('.datetime').datetimepicker({
            format: 'hh:mm:ss a'
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("input").forEach(function(input) {
            input.setAttribute("autocomplete", "off");
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('.selectBox').select2();

        $('.selectBox').on('select2:open', function() {
            // Use setTimeout to wait for DOM update
            setTimeout(() => {
                // Get all open Select2 dropdowns
                const allDropdowns = document.querySelectorAll('.select2-container--open');

                // Get the most recently opened dropdown (last one)
                const lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];

                if (lastOpenedDropdown) {
                    // Find the search input inside this dropdown
                    const searchInput = lastOpenedDropdown.querySelector(
                        '.select2-search__field');

                    if (searchInput) {
                        searchInput.focus(); // Focus the search input
                        searchInput.select(); // Optional: select any existing text
                    }
                }
            }, 10); // Very short delay to allow DOM render
        });
    });
</script>

{{-- Toaster Notifications --}}
<script>
    $(document).ready(function() {
        var successSound = document.querySelector('.successSound');
        var errorSound = document.querySelector('.errorSound');

        @if (Session::has('toastr-success'))
            toastr.success("{{ Session::get('toastr-success') }}");
            successSound.play();
        @endif

        @if (Session::has('toastr-error'))
            toastr.error("{{ Session::get('toastr-error') }}");
            errorSound.play();
        @endif

        @if (Session::has('toastr-warning'))
            toastr.warning("{{ Session::get('toastr-warning') }}");
        @endif

        @if (Session::has('toastr-info'))
            toastr.info("{{ Session::get('toastr-info') }}");
        @endif
    });
</script>

<script>
    /**
     * Prevents multiple clicks on a button during async operations.
     * @param {HTMLElement} button - The button element to protect.
     * @param {Function} callback - The function to execute once.
     */
    function preventDoubleClick(button, callback) {
        if (button.dataset.isProcessing === "true") return;
        button.dataset.isProcessing = "true";
        button.disabled = true;

        try {
            callback();
        } catch (error) {
            console.error("Error in button callback:", error);
            enableButton(button);
        }
    }

    function enableButton(button) {
        button.disabled = false;
        button.dataset.isProcessing = "false";
    }

    // Helper: Wrap AJAX calls with button protection
    function safeAjaxCall(button, options) {
        preventDoubleClick(button, () => {
            $.ajax(options)
                .done(function(response) {
                    if (options.done) options.done(response);
                })
                .fail(function(xhr, status, error) {
                    toastr.error('An error occurred: ' + xhr.responseText);
                    if (options.fail) options.fail(xhr, status, error);
                })
                .always(function() {
                    enableButton(button);
                    if (options.always) options.always();
                });
        });
    }
</script>
