<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {


        let selectedLocationId = null;

        const posProduct = document.getElementById('posProduct');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        // const taxInput = document.getElementById('order-tax');
        // const shippingInput = document.getElementById('shipping');
        const finalValue = document.getElementById('total');
        const categoryBtn = document.getElementById('category-btn');
        const allProductsBtn = document.getElementById('allProductsBtn');
        const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');



        // Utility: Show Loader
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

        // Utility: Hide Loader
        function hideLoader() {
            posProduct.innerHTML = '';
        }

        allProductsBtn.onclick = function() {
            fetchAllProducts();
        };

        // Fetch and display all products initially
        fetchAllProducts();

        // Fetch categories and brands from APIs and populate the offcanvas
        fetchCategories();
        fetchBrands();

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
                    subcategoryContainer.innerHTML = ''; // Clear previous subcategories

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

                    // Show the subcategory offcanvas
                    const subcategoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                        'offcanvasSubcategory'));
                    subcategoryOffcanvas.show();

                    // Hide the category offcanvas
                    const categoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                        'offcanvasCategory'));
                    categoryOffcanvas.hide();
                })
                .catch(error => console.error('Error fetching subcategories:', error));
        }

        // Handle back button click in subcategory offcanvas
        subcategoryBackBtn.addEventListener('click', () => {
            // Show the category offcanvas
            const categoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                'offcanvasCategory'));
            categoryOffcanvas.show();

            // Hide the subcategory offcanvas
            const subcategoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                'offcanvasSubcategory'));
            subcategoryOffcanvas.hide();
        });

        function fetchBrands() {
            fetch('/brand-get-all')
                .then(response => response.json())
                .then(data => {
                    const brands = data.message; // Get the array of brands
                    const brandContainer = document.getElementById(
                        'brandContainer'); // Get the container to append brand cards

                    if (Array.isArray(brands)) {
                        // Loop through the brands array and create brand cards
                        brands.forEach(brand => {
                            const brandCard = document.createElement('div');
                            brandCard.classList.add('brand-card');
                            brandCard.setAttribute('data-id', brand.id);

                            const brandName = document.createElement('h6');
                            brandName.textContent = brand.name; // Use brand's name
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

        let isEditing = false;

        $(document).ready(function() {

            fetchAllLocations();
            $('#locationSelect').on('change', handleLocationChange);

            // Detect if we're in edit mode
            const pathSegments = window.location.pathname.split('/');
            const saleId = pathSegments[pathSegments.length - 1];

            if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
                isEditing = true;
                fetchEditSale(saleId);
            } else {
                console.warn('Invalid or missing saleId:', saleId);
            }
        });

        // Fetch all locations via AJAX
        function fetchAllLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                success: function(data) {
                    if (data.status === 200) {
                        populateLocationDropdown(data.message);
                    } else {
                        console.error('Error fetching locations:', data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                }
            });
        }

        // Populate dropdown and select first location by default
        function populateLocationDropdown(locations) {
            const locationSelect = $('#locationSelect');
            locationSelect.empty(); // Clear existing options

            locationSelect.append('<option value="" disabled>Select Location</option>');

            locations.forEach((location, index) => {
                const option = $('<option></option>')
                    .val(location.id)
                    .text(location.name);

                if (index === 0) {
                    option.attr('selected', 'selected');
                }

                locationSelect.append(option);
            });

            // Trigger change to load products for the first location
            locationSelect.trigger('change');
        }

        function handleLocationChange(event) {
            selectedLocationId = $(event.target).val(); // Update global variable
            if (selectedLocationId) {
                if (!isEditing) {
                    billingBody.innerHTML = '';
                }
                updateTotals();
                fetchAllProducts(selectedLocationId); // Still fetch products for autocomplete
            } else {
                console.warn("No location selected");
            }
        }
        // Global arrays to store products
        let allProducts = [];
        let stockData = [];

        // Fetch all products from the server based on selected location
        function fetchAllProducts() {
            console.log("Fetching products for Location ID:", selectedLocationId); // DEBUG

            showLoader();

            let url = '/products/stocks';

            if (selectedLocationId) {
                url += `?location_id=${selectedLocationId}`;
            }

            console.log('Final URL:', url); // DEBUG

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    hideLoader();

                    if (data.status === 200 && Array.isArray(data.data)) {
                        stockData = data.data;

                        allProducts = stockData.map(stock => {
                            const firstLocationBatch = stock.batches?.[0]?.location_batches?.[0] ||
                                null;

                            return {
                                ...stock.product,
                                total_stock: stock.total_stock,
                                location_id: firstLocationBatch ? firstLocationBatch.location_id :
                                    null
                            };
                        });

                        displayProducts(stockData);
                        initAutocomplete();
                    } else {
                        console.error('Invalid data:', data);
                        alert('Failed to load product data.');
                    }
                })
                .catch(error => {
                    hideLoader();
                    console.error('Error fetching data:', error);
                    alert('An error occurred while fetching product data.');
                });
        }

        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();

                    // Filter products that:
                    // 1. Match the search term (product name or SKU)
                    // 2. Have total_stock > 0
                    const filteredProducts = allProducts.filter(product =>
                        ((product.product_name && product.product_name.toLowerCase().startsWith(
                                searchTerm)) ||
                            (product.sku && product.sku.toLowerCase().startsWith(searchTerm))
                        ) &&
                        product.total_stock > 0 // 👈 Only show products with stock > 0
                    ).sort((a, b) => {
                        const nameA = a.product_name?.toLowerCase() || '';
                        const nameB = b.product_name?.toLowerCase() || '';
                        return nameA.localeCompare(nameB);
                    });

                    // Map for autocomplete UI
                    const autoCompleteResults = filteredProducts.length ?
                        filteredProducts.map(p => ({
                            label: `${p.product_name} (${p.sku || 'No SKU'}) [Total Stock: ${p.total_stock || 0}]`,
                            value: p.product_name,
                            product: p
                        })) : [{
                            label: "No products found",
                            value: ""
                        }];

                    response(autoCompleteResults);

                    // If exactly one match and search term is long enough, add to table
                    if (filteredProducts.length === 1 && searchTerm.length >= 2) {
                        addProductToTable(filteredProducts[0]);
                    }
                },
                select: function(event, ui) {
                    if (!ui.item.product) return false;
                    $("#productSearchInput").val("");
                    addProductToTable(ui.item.product);
                    return false;
                },
                focus: function(event, ui) {
                    $("#productSearchInput").val(ui.item.value);
                    return false;
                },
                minLength: 1,
                open: function() {
                    $(this).autocomplete("widget").find("li").removeClass("ui-state-focus");
                },
                close: function() {
                    $(this).autocomplete("widget").find("li").removeClass("ui-state-focus");
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                const $li = $("<li>").append(
                    `<div style="${item.product ? '' : 'color: red;'}">${item.label}</div>`
                ).appendTo(ul);

                $li.data("ui-autocomplete-item", item);
                $li.on("mouseenter", function() {
                    $(this).addClass("ui-state-focus");
                }).on("mouseleave", function() {
                    $(this).removeClass("ui-state-focus");
                });

                return $li;
            };

            $("#productSearchInput").removeAttr("aria-live aria-autocomplete");
            $("#productSearchInput").autocomplete("instance").liveRegion.remove();

            $("#productSearchInput").autocomplete("instance")._move = function(direction, event) {
                if (!this.menu.element.is(":visible")) {
                    this.search(null, event);
                    return;
                }
                if (this.menu.isFirstItem() && /^previous/.test(direction) ||
                    this.menu.isLastItem() && /^next/.test(direction)) {
                    this._value(this.term);
                    this.menu.blur();
                    return;
                }
                this.menu[direction](event);
                this.menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                this.menu.active.addClass("ui-state-focus");
            };
        }

        function displayProducts(products) {
            posProduct.innerHTML = ''; // Clear previous products

            // Filter products to show:
            // 1. Products with stock_alert === 0 (unlimited stock, even if total_stock is 0)
            // 2. Products with total_stock > 0 (available stock)
            const filteredProducts = products.filter(stock =>
                stock.total_stock > 0 || stock.product.stock_alert === 0
            );

            if (filteredProducts.length === 0) {
                posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                return;
            }

            filteredProducts.forEach(stock => {
                const product = stock.product;
                const totalQuantity = stock.total_stock;
                const price = product.retail_price;
                const batchNo = stock.batches.length > 0 ? stock.batches[0].batch_no : 'N/A';

                // Check if stock_alert is 0, if so, set totalQuantity to "Unlimited"
                const quantityDisplay = product.stock_alert === 0 ? 'Unlimited' :
                    `${totalQuantity} Pc(s) in stock`;

                const cardHTML = `
            <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-3">
                <div class="product-card"> 
                    <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" alt="${product.product_name}">
                    <div class="product-card-body">
                        <h6>${product.product_name} <br>
                            <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span>
                        </h6>
                        <h6>
                            <span class="badge ${product.stock_alert === 0 ? 'bg-info' : totalQuantity > 0 ? 'bg-success' : 'bg-warning'}">
                                ${quantityDisplay}
                            </span>
                        </h6>
                    </div>
                </div>
            </div>
        `;
                posProduct.insertAdjacentHTML('beforeend', cardHTML);
            });

            // Add click event to product cards
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.addEventListener('click', () => {
                    const productName = card.querySelector('img').getAttribute('alt');
                    const selectedProduct = stockData.find(stock =>
                        stock.product.product_name === productName
                    ).product;
                    addProductToTable(selectedProduct);
                });
            });
        }

        // Function to format amounts with separators for display
        function formatAmountWithSeparators(amount) {
            return new Intl.NumberFormat().format(amount);
        }

        // Function to parse formatted amounts back to numbers
        function parseFormattedAmount(formattedAmount) {
            return parseFloat(formattedAmount.replace(/,/g, ''));
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

        let locationId;
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
            if (totalQuantity === 0 && product.stock_alert !== 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }
            if (!Array.isArray(stockEntry.batches) || stockEntry.batches.length === 0) {
                if (product.stock_alert === 0) {
                    locationId = product.location_id || 1;
                    addProductToBillingBody(product, stockEntry, product.retail_price, "all", Infinity,
                        'retail');
                } else {
                    locationId = product.location_id || 1;
                    addProductToBillingBody(product, stockEntry, product.retail_price, "all", totalQuantity,
                        'retail');
                }
                return;
            }

            const locationBatches = stockEntry.batches.flatMap(batch => batch.location_batches).filter(lb => lb
                .quantity > 0);
            if (locationBatches.length === 0) {
                toastr.error('No batches with available quantity found', 'Error');
                return;
            }

            locationId = locationBatches[0].location_id; // Set from first available batch
            addProductToBillingBody(product, stockEntry, product.retail_price, "all", totalQuantity, 'retail');
        }

        function showProductModal(product, stockEntry, row) {
            const modalBody = document.getElementById('productModalBody');
            const basePrice = product.retail_price;
            const discountAmount = product.discount_amount || 0;
            const finalPrice = product.discount_type === 'percentage' ? basePrice * (1 - discountAmount / 100) :
                basePrice - discountAmount;

            const batchOptions = stockEntry.batches
                .flatMap(batch => {
                    return batch.location_batches.map(locationBatch => ({
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        retail_price: parseFloat(batch.retail_price),
                        wholesale_price: parseFloat(batch.wholesale_price),
                        special_price: parseFloat(batch.special_price),
                        batch_quantity: locationBatch.quantity
                    }));
                })
                .filter(batch => batch.batch_quantity > 0)
                .map(batch => `
            <option value="${batch.batch_id}" 
                data-retail-price="${batch.retail_price}" 
                data-wholesale-price="${batch.wholesale_price}" 
                data-special-price="${batch.special_price}" 
                data-quantity="${batch.batch_quantity}">
              ${batch.batch_no} - Qty: ${formatAmountWithSeparators(batch.batch_quantity)} - R: ${formatAmountWithSeparators(batch.retail_price.toFixed(2))} - W: ${formatAmountWithSeparators(batch.wholesale_price.toFixed(2))} - S: ${formatAmountWithSeparators(batch.special_price.toFixed(2))}
            </option>
        `)
                .join('');

            const totalQuantity = stockEntry.total_stock;

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
            <option value="all" data-retail-price="${finalPrice}" data-quantity="${totalQuantity}">
                All - Qty: ${formatAmountWithSeparators(totalQuantity)} - Price: ${formatAmountWithSeparators(finalPrice.toFixed(2))}
            </option>
            ${batchOptions}
        </select>
    `;
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
            document.getElementById('modalBatchDropdown').addEventListener('change', () => {
                const selectedOption = document.getElementById('modalBatchDropdown').selectedOptions[0];
                const maxQty = parseInt(selectedOption.getAttribute('data-quantity'), 10);
                const qtyInput = selectedRow.querySelector('.quantity-input');
                qtyInput.setAttribute('max', maxQty);
                qtyInput.setAttribute('title', `Available: ${maxQty}`);
            });
        }

        function addProductToBillingBody(product, stockEntry, price, batchId, batchQuantity, priceType, saleQuantity = 1) {
    price = parseFloat(price);
    if (isNaN(price)) {
        console.error('Invalid price for product:', product.product_name);
        toastr.error(`Invalid price for ${product.product_name}. Using default price.`, 'Error');
        price = 0;
    }

    const billingBody = document.getElementById('billing-body');

    let adjustedBatchQuantity = batchQuantity;

    if (batchId === "all") {
        adjustedBatchQuantity = stockEntry.total_stock;
    } else {
        const selectedBatch = stockEntry.batches.find(batch => batch.id === parseInt(batchId));
        if (selectedBatch) {
            const locationBatch = selectedBatch.location_batches.find(lb => lb.location_id === locationId);
            if (locationBatch) {
                adjustedBatchQuantity = locationBatch.quantity;
            }
        }
    }

    const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
        const rowProductId = row.querySelector('.product-id').textContent;
        const rowBatchId = row.querySelector('.batch-id').textContent;
        return rowProductId == product.id && rowBatchId == batchId;
    });

    if (existingRow) {
        // Handle existing row
        const quantityInput = existingRow.querySelector('.quantity-input');
        let currentQty = parseInt(quantityInput.value, 10);
        let newQuantity = currentQty + saleQuantity;

        if (newQuantity > adjustedBatchQuantity && product.stock_alert !== 0) {
            toastr.error(`You cannot add more than ${adjustedBatchQuantity} units of this product.`, 'Warning');
            return;
        }

        quantityInput.value = newQuantity;
        const subtotalElement = existingRow.querySelector('.subtotal');
        const updatedSubtotal = newQuantity * price;
        subtotalElement.textContent = formatAmountWithSeparators(updatedSubtotal.toFixed(2));

        updateTotals();
    } else {
        // Calculate MRP - Retail Price discount
        const mrp = parseFloat(product.max_retail_price || 0);
        const retailPrice = parseFloat(product.retail_price || 0);

        let discountType = 'fixed';
        let discountAmount = 0;

        if (mrp > retailPrice) {
            discountAmount = mrp - retailPrice;
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;" class="product-image"/>
                    <div class="product-info">
                        <div class="font-weight-bold product-name" style="word-wrap: break-word; max-width: 200px;">
                            ${product.product_name}<span class="badge bg-info">Max: ${product.max_retail_price || ''}</span>
                        </div>
                        <div class="text-muted me-2">${product.sku}
                            <span class="badge bg-secondary ms-1">Total: ${stockEntry.total_stock} Pc(s)</span>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div class="d-flex justify-content-center">
                    <button class="btn btn-danger quantity-minus btn-sm">-</button>
                    <input type="number" value="${saleQuantity}" min="1" max="${adjustedBatchQuantity}" class="form-control quantity-input text-center" title="Available: ${adjustedBatchQuantity}">
                    <button class="btn btn-success quantity-plus btn-sm">+</button>
                </div>
            </td>
            <td><input type="number" value="${price.toFixed(2)}" class="form-control price-input text-center" data-quantity="${adjustedBatchQuantity}" min="0"></td>
            <td>
                <select class="form-select discount-type">
                    <option value="fixed" ${discountType === 'fixed' ? 'selected' : ''}>Fixed</option>
                    <option value="percentage" ${discountType === 'percentage' ? 'selected' : ''}>Percentage</option>
                </select>
            </td>
            <td>
                <input type="number" value="${discountAmount.toFixed(2)}" class="form-control discount-amount text-center" placeholder="0" min="0" max="${discountType === 'percentage' ? 100 : ''}">
            </td>
            <td class="subtotal text-center mt-2">${formatAmountWithSeparators((saleQuantity * price).toFixed(2))}</td>
            <td><button class="btn btn-danger btn-sm remove-btn">×</button></td>
            <td class="product-id d-none">${product.id}</td>
            <td class="location-id d-none">${locationId}</td>
            <td class="batch-id d-none">${batchId}</td>
            <td class="discount-data d-none">${JSON.stringify({ type: discountType, amount: discountAmount })}</td>
        `;

        billingBody.insertBefore(row, billingBody.firstChild);
        attachRowEventListeners(row, product, stockEntry);
        const quantityInput = row.querySelector('.quantity-input');
        quantityInput.focus();
        quantityInput.select();

        updateTotals();
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

            // Helper function to validate and update quantities
            const validateAndUpdateQuantity = (newQuantity) => {
                const maxQuantity = parseInt(priceInput.getAttribute('data-quantity'), 10);
                if (newQuantity > maxQuantity && product.stock_alert !== 0) {
                    document.getElementsByClassName('errorSound')[0]?.play();
                    toastr.error(
                        `You cannot add more than ${maxQuantity} units of this product.`,
                        'Error'
                    );
                    return false;
                }
                quantityInput.value = newQuantity;
                updateTotals();
                return true;
            };

            // Event listener for the minus button
            quantityMinus.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value, 10);
                if (currentQuantity > 1) {
                    validateAndUpdateQuantity(currentQuantity - 1);
                }
            });

            // Event listener for the plus button
            quantityPlus.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value, 10);
                validateAndUpdateQuantity(currentQuantity + 1);
            });

            // Event listener for direct input in the quantity field
            quantityInput.addEventListener('input', () => {
                let quantityValue = parseInt(quantityInput.value, 10);
                if (isNaN(quantityValue) || quantityValue < 1) {
                    quantityValue = 1; // Default to 1 if invalid input
                }
                validateAndUpdateQuantity(quantityValue);
            });

            // Event listener for price input field
            priceInput.addEventListener('input', () => {
                const priceValue = parseFloat(priceInput.value);
                if (isNaN(priceValue) || priceValue < 0) {
                    toastr.error('Invalid price entered.', 'Error');
                    priceInput.value = '0.00'; // Default to 0 if invalid input
                }
                updateTotals();
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
        }

        document.getElementById('saveProductChanges').onclick = function() {
            const selectedPriceType = document.querySelector('input[name="modal-price-type"]:checked')
                .value;
            const selectedBatch = document.getElementById('modalBatchDropdown').selectedOptions[0];

            const price = parseFloat(selectedBatch.getAttribute(`data-${selectedPriceType}-price`));
            const batchId = selectedBatch.value;
            const batchQuantity = parseInt(selectedBatch.getAttribute('data-quantity'), 10);

            if (selectedRow) {
                const quantityInput = selectedRow.querySelector('.quantity-input');
                const priceInput = selectedRow.querySelector('.price-input');
                const productNameCell = selectedRow.querySelector('.product-name');

                priceInput.value = price.toFixed(2);
                priceInput.setAttribute('data-quantity', batchQuantity);

                const subtotal = parseFloat(quantityInput.value) * price;
                selectedRow.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));

                selectedRow.querySelector('.batch-id').textContent = batchId;

                // Update product name cell with stars based on selected price type
                const stars = selectedPriceType === 'retail' ? '<i class="fas fa-star"></i>' :
                    selectedPriceType === 'wholesale' ?
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i>' :
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>';

                productNameCell.innerHTML = `${productNameCell.textContent.trim()} ${stars}`;

                updateTotals();
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
        };

        function updateTotals() {
            const billingBody = document.getElementById('billing-body');
            let totalItems = 0;
            let totalAmount = 0;

            // Calculate total items and total amount
            billingBody.querySelectorAll('tr').forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value, 10) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const subtotal = quantity * price;

                row.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));

                totalItems += quantity;
                totalAmount += subtotal;
            });

            const discountElement = document.getElementById('discount');
            const discountTypeElement = document.getElementById('discount-type');

            // Get discount value and type
            const discount = discountElement ? parseFloat(discountElement.value) || 0 : 0;
            const discountType = discountTypeElement ? discountTypeElement.value : 'fixed';

            let totalAmountWithDiscount;

            // Apply discount logic
            if (discountType === 'percentage') {
                totalAmountWithDiscount = totalAmount - (totalAmount * discount / 100);
            } else {
                totalAmountWithDiscount = totalAmount - discount;
            }

            // Ensure totals are not negative
            totalAmountWithDiscount = Math.max(0, totalAmountWithDiscount);

            // Update UI
            document.getElementById('items-count').textContent = `${totalItems} Pc(s)`;
            document.getElementById('modal-total-items').textContent = totalItems.toFixed(2);
            document.getElementById('total-amount').textContent = formatAmountWithSeparators(totalAmount
                .toFixed(2));
            document.getElementById('final-total-amount').textContent = formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
            document.getElementById('total').textContent = formatAmountWithSeparators(totalAmountWithDiscount
                .toFixed(2));
            document.getElementById('payment-amount').textContent = 'Rs ' + formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
        }

        // Attach event listeners for discount input and type dropdown
        const discountElement = document.getElementById('discount');
        const discountTypeElement = document.getElementById('discount-type');

        if (discountElement) {
            discountElement.addEventListener('input', () => {
                // Ensure percentage discount does not exceed 100
                if (discountTypeElement && discountTypeElement.value === 'percentage') {
                    const discountValue = parseFloat(discountElement.value) || 0;
                    if (discountValue > 100) {
                        discountElement.value = 100; // Reset to maximum allowed percentage
                        toastr.warning('Percentage discount cannot exceed 100%', 'Warning');
                    }
                }
                updateTotals();
            });
        }

        if (discountTypeElement) {
            discountTypeElement.addEventListener('change', () => {
                // Clear the discount value when toggling the discount type
                if (discountElement) discountElement.value = '';
                updateTotals();
            });
        }


        function fetchEditSale(saleId) {
            // fetchAllProducts();
            fetch(`/api/sales/edit/${saleId}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.status === 200) {
                        const saleDetails = data.sale_details;

                        // Update the sale invoice number
                        const saleInvoiceElement = document.getElementById('sale-invoice-no');
                        if (saleInvoiceElement && saleDetails.sale) {
                            saleInvoiceElement.textContent = `Invoice No: ${saleDetails.sale.invoice_no}`;
                        }

                        saleDetails.sale_products.forEach(saleProduct => {
                            const price = saleProduct.price || saleProduct.product.retail_price;

                            const stockEntry = stockData.find(stock =>
                                stock.product.id === saleProduct.product.id
                            );

                            if (saleDetails.sale && saleDetails.sale.location_id) {
                                locationId = saleDetails.sale.location_id;
                            }

                            let batches = [];
                            if (stockEntry && Array.isArray(stockEntry.batches)) {
                                batches = [...stockEntry.batches];
                            }

                            // Add sold quantity back to the batch to temporarily restore the original state
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
                                                            saleProduct
                                                            .quantity // Add sold quantity back
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
                                    retail_price: saleProduct.batch.retail_price,
                                    wholesale_price: saleProduct.batch.wholesale_price,
                                    special_price: saleProduct.batch.special_price,
                                    location_batches: [{
                                        location_id: saleProduct.location_id,
                                        quantity: saleProduct.total_quantity +
                                            saleProduct
                                            .quantity // Add sold quantity back
                                    }]
                                });
                            }

                            let totalStock = saleProduct.total_quantity + saleProduct
                                .quantity; // Add sold quantity back
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

                            addProductToBillingBody(
                                saleProduct.product,
                                normalizedStockEntry,
                                price,
                                saleProduct.batch_id,
                                saleProduct.quantity,
                                saleProduct.price_type,
                                saleProduct.quantity
                            );
                        });

                        // Fetch customer data directly here
                        fetch('/customer-get-all')
                            .then(response => response.json())
                            .then(customerData => {
                                if (customerData && customerData.status === 200 && Array.isArray(
                                        customerData.message)) {
                                    const customerSelect = $('#customer-id');
                                    customerSelect.empty();

                                    const sortedCustomers = customerData.message.sort((a, b) => {
                                        if (a.first_name === 'Walking') return -1;
                                        if (b.first_name === 'Walking') return 1;
                                        return 0;
                                    });

                                    sortedCustomers.forEach(customer => {
                                        const option = $('<option></option>');
                                        option.val(customer.id);
                                        option.text(
                                            `${customer.first_name} ${customer.last_name} (${customer.mobile_no})`
                                        );
                                        option.data('due', customer
                                            .current_due); // Store the due amount in the option
                                        customerSelect.append(option);
                                    });

                                    const walkingCustomer = sortedCustomers.find(customer => customer
                                        .first_name === 'Walking');
                                    if (walkingCustomer) {
                                        customerSelect.val(walkingCustomer.id);
                                        updateDueAmount(walkingCustomer.current_due);
                                    }

                                    // Now set the customer ID after dropdown is populated
                                    if (saleDetails.sale) {
                                        customerSelect.val(saleDetails.sale.customer_id);
                                        customerSelect.trigger(
                                            'change'); // Trigger change event to update other fields
                                    }
                                } else {
                                    console.error('Failed to fetch customer data:', customerData ?
                                        customerData.message : 'No data received');
                                }
                            });

                        // Update discount and total fields
                        const discountElement = document.getElementById('discount');
                        const discountTypeElement = document.getElementById('discount-type');

                        if (discountElement && saleDetails.sale) {
                            discountElement.value = saleDetails.sale.discount_amount || 0;
                        }

                        if (discountTypeElement && saleDetails.sale) {
                            discountTypeElement.value = saleDetails.sale.discount_type || 'fixed';
                        }

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

        function updateDueAmount(dueAmount) {
            // Ensure dueAmount is a valid number before calling toFixed
            dueAmount = isNaN(dueAmount) ? 0 : dueAmount;
            $('#total-due-amount').text(`Total due amount: Rs. ${dueAmount.toFixed(2)}`);
        }

        $('#customer-id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const dueAmount = selectedOption.data('due');
            updateDueAmount(dueAmount);
        });



        $(document).ready(function() {

            function gatherSaleData(status) {
                const uniqueNumber = new Date().getTime() % 10000;
                const customerId = $('#customer-id').val();
                const salesDate = new Date().toISOString().slice(0, 10);

                if (!locationId) {
                    toastr.error('Location ID is required.');
                    return null;
                }

                const saleData = {
                    customer_id: customerId,
                    sales_date: salesDate,
                    location_id: locationId,
                    status: status,
                    sale_type: "POS",
                    products: [],
                    discount_type: $('#discount-type').val(),
                    discount_amount: parseFormattedAmount($('#discount').val()) || 0,
                    total_amount: parseFormattedAmount($('#total-amount').text()) || 0,
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

                    if (!locationId) {
                        toastr.error('Location ID is missing for a product.');
                        return;
                    }

                    const productData = {
                        product_id: parseInt(productRow.find('.product-id').text().trim(),
                            10),
                        location_id: parseInt(locationId, 10),
                        quantity: parseInt(productRow.find('.quantity-input').val().trim(),
                            10),
                        price_type: priceType,
                        unit_price: parseFormattedAmount(productRow.find('.price-input')
                            .val().trim()),
                        subtotal: parseFormattedAmount(productRow.find('.subtotal').text()
                            .trim()),
                        discount: parseFloat(productRow.find('.discount-data').data(
                            'amount')) || 0,
                        tax: 0,
                        batch_id: batchId === "all" ? "all" : batchId,
                    };
                    saleData.products.push(productData);
                });

                return saleData;
            }


            function sendSaleData(saleData, saleId = null) {
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
                            fetchAllProducts();
                            fetchSalesData();

                        } else {
                            toastr.error('Failed to record sale: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred: ' + xhr.responseText);
                    }
                });
            }

            function gatherCashPaymentData() {
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                console.log("final_amount " + totalAmount);
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'cash',
                    payment_date: today,
                    amount: totalAmount
                }];
            }

            $('#cashButton').on('click', function() {
                const saleData = gatherSaleData('final');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                } else {
                    saleData.payments = gatherCashPaymentData();

                    // Calculate balance amount
                    const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                        .trim());
                    const amountGiven = parseFormattedAmount($('#amount-given').val().trim());
                    const balance = amountGiven - totalAmount;
                    saleData.balance_amount = balance; // Add balance amount to saleData
                    saleData.amount_given = amountGiven; // Add amount given to saleData

                    // Extract sale ID from URL
                    const pathSegments = window.location.pathname.split('/');
                    const saleId = pathSegments[pathSegments.length - 1];

                    sendSaleData(saleData, !isNaN(saleId) ? saleId : null);

                }
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
                const saleData = gatherSaleData('final');

                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                saleData.payments = gatherCardPaymentData();
                sendSaleData(saleData);
                $('#cardModal').modal('hide');
                resetCardModal();
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
                if (!validateChequeFields()) {
                    return;
                }

                const saleData = gatherSaleData('final');

                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                saleData.payments = gatherChequePaymentData();
                sendSaleData(saleData);
                $('#chequeModal').modal('hide');
                resetChequeModal();
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
                const customerId = $('#customer-id').val();
                if (customerId == 1) {
                    toastr.error(
                        'Credit sale is not allowed for Walking Customer. Please choose another customer.'
                    );
                    return;
                }

                const saleData = gatherSaleData('final');

                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                sendSaleData(saleData);
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

            document.getElementById('finalize_payment').addEventListener('click', function() {
                const saleData = gatherSaleData('final');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                const paymentData = gatherPaymentData();
                saleData.payments = paymentData;
                sendSaleData(saleData);
                let modal = bootstrap.Modal.getInstance(document.getElementById(
                    "paymentModal"));
                modal.hide();
            });

            function gatherPaymentData() {
                const paymentData = [];
                document.querySelectorAll('.payment-row').forEach(row => {
                    const paymentMethod = row.querySelector('.payment-method').value;
                    const paymentDate = row.querySelector('.payment-date').value;
                    const amount = parseFormattedAmount(row.querySelector('.payment-amount')
                        .value);
                    const conditionalFields = {};

                    row.querySelectorAll('.conditional-fields input').forEach(input => {
                        conditionalFields[input.name] = input.value;
                    });

                    paymentData.push({
                        payment_method: paymentMethod,
                        payment_date: paymentDate,
                        amount: amount,
                        ...conditionalFields
                    });
                });
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
                    url: `/api/sales/delete-suspended/${saleId}`,
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
                let amountGiven = parseFormattedAmount($(this).val()) ||
                    0; // Default to 0 if empty
                $(this).val(amountGiven ? formatAmountWithSeparators(amountGiven) :
                    ''); // Show empty when cleared
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
                    if (balance < 0) {
                        toastr.error('The given amount is less than the total amount.');
                        return;
                    }


                    swal({
                        title: "Balance Amount  Rs. " + formatAmountWithSeparators(
                            balance.toFixed()),
                        // text: "The balance amount to be returned is Rs. " +

                        type: "info",
                        showCancelButton: false,
                        confirmButtonText: "OK",
                        customClass: {
                            title: 'swal-title-large',
                            text: 'swal-title-large' // Use the same class as title for larger text
                        }
                    }, function() {
                        $('#cashButton').trigger('click');
                    });
                }
            });

            // Fetch suspended sales when the POS page loads
            // fetchSuspendedSales();





        });



        document.getElementById('cancelButton').addEventListener('click', resetForm);

        function resetForm() {
            document.getElementById('customer-id').value = 1;
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
            document.getElementById('discount').value = '';
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

    // Function to load the sales data into the DataTable
    function loadTableData(status) {
        const table = $('#transactionTable').DataTable();
        table.clear().draw(); // Clear existing data

        const filteredSales = sales
            .filter(sale => sale.status === status)
            .sort((a, b) => parseInt(b.invoice_no.split('-')[1]) - parseInt(a.invoice_no.split('-')[1]));

        if (filteredSales.length === 0) {
            table.row.add([
                '', 'No records found', '', '', ''
            ]).draw();
        } else {
            filteredSales.forEach((sale, index) => {
                table.row.add([
                    index + 1,
                    sale.invoice_no,
                    `${sale.customer.prefix} ${sale.customer.first_name} ${sale.customer.last_name}`,
                    sale.sales_date,
                    sale.final_total,
                    `<button class='btn btn-outline-success btn-sm' onclick="printReceipt(${sale.id})">Print</button>
                     <button class='btn btn-outline-primary btn-sm' onclick="navigateToEdit(${sale.id})">Edit</button>
                     <button class='btn btn-outline-danger btn-sm' onclick="deleteSale(${sale.id})">Delete</button>`
                ]).draw();
            });
        }
    }

    // Function to navigate to the edit page
    function navigateToEdit(saleId) {
        window.location.href = "{{ route('sales.edit', '') }}/" + saleId;
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
                    alert('Failed to fetch the receipt. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error fetching the receipt:', error);
                alert('An error occurred while fetching the receipt. Please try again.');
            });
    }

    function deleteSale(saleId) {
        swal({
            title: "Are you sure?",
            text: "Do you really want to delete this sale? This action cannot be undone.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: `/sales/delete/${saleId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            // First close the swal
                            swal.close();

                            // Use setTimeout to ensure swal is fully closed before proceeding
                            setTimeout(function() {
                                toastr.success(response.message ||
                                    "Sale deleted successfully!");
                                const successSound = document.querySelector(
                                    '.successSound');
                                if (successSound) {
                                    successSound.play();
                                }
                                // Refresh the sales data
                                loadTableData('final');
                                fetchSalesData();
                            }, 100); // small delay to allow swal to visually close
                        } else {
                            swal("Error!", response.message ||
                                "An error occurred while deleting the sale.", "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = "Unable to delete the sale. Please try again later.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        swal("Error!", errorMessage, "error");
                        console.error('Delete error:', error);
                    }
                });
            }
        });
    }

    // // Event listener to load sales data when the page is loaded
    // document.addEventListener('DOMContentLoaded', function() {
    //     fetchSalesData();


    // });
</script>


{{-- For jQuery --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Include Mousetrap library -->
{{-- <script src="{{ asset('assets/js/mousetrap.js') }}"></script> --}}
<script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        let currentRowIndex = 0;

        function focusQuantityInput() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            if (quantityInputs.length > 0) {
                quantityInputs[currentRowIndex].focus();
                quantityInputs[currentRowIndex].select();
                currentRowIndex = (currentRowIndex + 1) % quantityInputs.length;
            }
        }

        hotkeys('f2', function(event) {
            event.preventDefault();
            focusQuantityInput();
        });

        hotkeys('f4', function(event) {
            event.preventDefault();
            const productSearchInput = document.getElementById('productSearchInput');
            if (productSearchInput) {
                productSearchInput.focus();
                productSearchInput.select();
            } else {
                console.warn('No product search input found.');
            }
        });

        hotkeys('f5', function(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to refresh the page?')) {
                location.reload();
            }
        });

        if (typeof hotkeys !== 'undefined') {
            hotkeys('ctrl+shift+c', function(event) {
                event.preventDefault();
                const customerSelect = $('#customer-id');
                if (customerSelect.length) {
                    customerSelect.select2('open');

                    // Wait a bit, then focus on the search input inside Select2
                    setTimeout(() => {
                        $('.select2-search__field').focus();
                    }, 100);
                } else {
                    console.warn('No customer select input found.');
                }
            });
        } else {
            console.error('Hotkeys library is not loaded.');
        }
        // Initial focus on the first quantity input if available
        focusQuantityInput();
    });
</script>
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
    // In your Javascript (external .js resource or <script> tag)
    $(document).ready(function() {
        $('.selectBox').select2();


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

{{-- Prevent Inspect Element --}}
<script>
    document.addEventListener('keydown', function(event) {
        // Prevent F12 (which opens the developer tools)
        if (event.keyCode === 123) {
            event.preventDefault();
        }

        // Prevent Ctrl+Shift+I (or Cmd+Shift+I on macOS)
        if (event.ctrlKey && event.shiftKey && event.keyCode === 73) {
            event.preventDefault();
        }

        // Prevent Ctrl+Shift+J (or Cmd+Shift+J on macOS)
        if (event.ctrlKey && event.shiftKey && event.keyCode === 74) {
            event.preventDefault();
        }

        // Prevent Ctrl+U (or Cmd+U on macOS) which opens the source code viewer
        if (event.ctrlKey && event.keyCode === 85) {
            event.preventDefault();
        }

        // Prevent Ctrl+Shift+C (or Cmd+Shift+C on macOS) which opens the inspect element tool
        if (event.ctrlKey && event.shiftKey && event.keyCode === 67) {
            event.preventDefault();
        }
    });
</script>
