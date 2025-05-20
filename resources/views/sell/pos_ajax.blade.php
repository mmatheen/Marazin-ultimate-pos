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
                        // alert('Failed to load product data.');
                    }
                })
                .catch(error => {
                    hideLoader();
                    console.error('Error fetching data:', error);
                    // alert('An error occurred while fetching product data.');
                });
        }

        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();

                    // Filter products by:
                    // - product name or SKU or IMEI number (partial match)
                    // - total_stock > 0
                    const filteredProducts = allProducts.filter(product => {
                        const matchesNameOrSku = (
                            (product.product_name && product.product_name.toLowerCase()
                                .includes(searchTerm)) ||
                            (product.sku && product.sku.toLowerCase().includes(
                                searchTerm))
                        );

                        // Search through all IMEIs across batches and location_batches
                        const imeiMatch = product.batches?.some(batch =>
                            batch.imei_numbers?.some(imeiObj =>
                                imeiObj.imei_number?.toLowerCase().includes(searchTerm)
                            )
                        );

                        return (matchesNameOrSku || imeiMatch) && product.total_stock > 0;
                    });

                    // Sort alphabetically by product name
                    filteredProducts.sort((a, b) => {
                        const nameA = a.product_name?.toLowerCase() || '';
                        const nameB = b.product_name?.toLowerCase() || '';
                        return nameA.localeCompare(nameB);
                    });

                    // Map results for UI
                    const autoCompleteResults = filteredProducts.length ?
                        filteredProducts.map(p => {
                            // Find matching IMEI if any
                            let matchedImei = null;

                            for (const batch of p.batches || []) {
                                for (const imei of batch.imei_numbers || []) {
                                    if (imei.imei_number?.toLowerCase().includes(searchTerm)) {
                                        matchedImei = imei.imei_number;
                                        break;
                                    }
                                }
                                if (matchedImei) break;
                            }

                            const label = matchedImei ?
                                `${p.product_name} (${p.sku}) [IMEI: ${matchedImei}]` :
                                `${p.product_name} (${p.sku}) [Total Stock: ${p.total_stock}]`;

                            return {
                                label: label,
                                value: p.product_name,
                                product: p,
                                imei: matchedImei
                            };
                        }) :
                        [{
                            label: "No products found",
                            value: ""
                        }];

                    response(autoCompleteResults);

                    // Auto-add product if exactly one match and term is long enough
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

            // Check if product requires IMEI
            if (product.is_imei_or_serial_no === 1) {
                const availableImeis = stockEntry.imei_numbers?.filter(imei => imei.status === "available") ||
                [];

                console.log("Available IMEIs:", availableImeis);
                if (availableImeis.length === 0) {
                    toastr.warning("No available IMEIs for this product.");
                    return;
                }

                // Check if this product already exists in billing
                const billingBody = document.getElementById('billing-body');
                const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row => {
                    return row.querySelector('.product-id').textContent == product.id;
                });

                if (existingRows.length > 0) {
                    // Product exists - show modal with current selections
                    showImeiSelectionModal(product, stockEntry, availableImeis);
                    return;
                }

                // Product doesn't exist - show modal to select IMEIs
                showImeiSelectionModal(product, stockEntry, availableImeis);
                return;
            }

            // If no IMEI required, proceed normally
            if (totalQuantity === 0 && product.stock_alert !== 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }

            if (!Array.isArray(stockEntry.batches) || stockEntry.batches.length === 0) {
                locationId = product.location_id || 1;
                const price = product.retail_price;
                const qty = product.stock_alert === 0 ? Infinity : totalQuantity;
                addProductToBillingBody(product, stockEntry, price, "all", qty, 'retail');
                return;
            }

            const locationBatches = stockEntry.batches.flatMap(batch => batch.location_batches).filter(lb => lb
                .quantity > 0);
            if (locationBatches.length === 0) {
                toastr.error('No batches with available quantity found', 'Error');
                return;
            }

            locationId = locationBatches[0].location_id;
            addProductToBillingBody(product, stockEntry, product.retail_price, "all", totalQuantity, 'retail');
        }

        // Global variables to track IMEI selections
        let currentImeiProduct = null;
        let currentImeiStockEntry = null;
        let selectedImeisInBilling = [];

        function showImeiSelectionModal(product, stockEntry, imeis) {
            currentImeiProduct = product;
            currentImeiStockEntry = stockEntry;

            // Existing logic...
            selectedImeisInBilling = [];
            const billingBody = document.getElementById('billing-body');
            const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row => {
                return row.querySelector('.product-id').textContent == product.id;
            });

            existingRows.forEach(row => {
                const imei = row.querySelector('.imei-data').textContent;
                if (imei) selectedImeisInBilling.push(imei);
            });

            const tbody = document.getElementById('imei-table-body');
            tbody.innerHTML = '';

            const availableImeis = imeis.filter(imei => imei.status === "available");

            if (availableImeis.length === 0) {
                toastr.warning("No available IMEIs for this product.");
                return;
            }

            // Store all created rows for filtering later
            const imeiRows = [];

            availableImeis.forEach((imei, index) => {
                const isChecked = selectedImeisInBilling.includes(imei.imei_number);
                const row = document.createElement('tr');
                row.dataset.imei = imei.imei_number;
                row.innerHTML = `
            <td>${index + 1}</td>
            <td><input type="checkbox" class="imei-checkbox" value="${imei.imei_number}" 
                ${isChecked ? 'checked' : ''} data-status="${imei.status}" /></td>
            <td>${imei.imei_number}</td>
            <td><span class="badge ${imei.status === 'available' ? 'bg-success' : 'bg-danger'}">${imei.status}</span></td>
        `;

                row.classList.add('clickable-row');

                row.addEventListener('click', function(event) {
                    if (event.target.type !== 'checkbox') {
                        const checkbox = row.querySelector('.imei-checkbox');
                        checkbox.checked = !checkbox.checked;
                    }
                });

                tbody.appendChild(row);
                imeiRows.push(row); // Save reference
            });

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('imeiModal'));
            modal.show();

            // Attach event listeners for filtering
            const searchInput = document.getElementById('imeiSearch');
            const filterSelect = document.getElementById('checkboxFilter');

            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterType = filterSelect.value;

                imeiRows.forEach(row => {
                    const imeiNumber = row.dataset.imei.toLowerCase();
                    const checkbox = row.querySelector('.imei-checkbox');
                    const isChecked = checkbox.checked;

                    const matchesSearch = imeiNumber.includes(searchTerm);

                    let matchesFilter = true;
                    if (filterType === 'checked') {
                        matchesFilter = isChecked;
                    } else if (filterType === 'unchecked') {
                        matchesFilter = !isChecked;
                    }

                    row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', applyFilters);
            filterSelect.addEventListener('change', applyFilters);

            // Clear previous click handler
            document.getElementById('confirmImeiSelection').onclick = null;

            document.getElementById('confirmImeiSelection').onclick = function() {
                const checkboxes = document.querySelectorAll('.imei-checkbox');
                const selectedImeis = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                modal.hide();

                const batchId = stockEntry.batches.length > 0 ? stockEntry.batches[0].id : "all";
                const price = product.retail_price;

                // Get the correct locationId for the IMEI/batch
                let imeiLocationId = stockEntry.batches.length > 0 ?
                    stockEntry.batches[0].location_batches.length > 0 ?
                    stockEntry.batches[0].location_batches[0].location_id :
                    1 :
                    1;

                locationId = imeiLocationId; // set global if function uses it

                existingRows.forEach(row => row.remove());

                selectedImeis.forEach(imei => {
                    addProductToBillingBody(
                        currentImeiProduct,
                        currentImeiStockEntry,
                        price,
                        batchId,
                        1,
                        'retail',
                        1,
                        [imei]
                    );
                });

                tbody.innerHTML = '';
                updateTotals();
            };
        }

        // Then use it
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('imeiSearch').value = '';
            document.getElementById('checkboxFilter').value = 'all';
            applyFilters(); // Trigger initial display
        });


        function showProductModal(product, stockEntry, row) {
            const modalBody = document.getElementById('productModalBody');
            const basePrice = product.retail_price;
            const discountAmount = product.discount_amount || 0;
            const finalPrice = product.discount_type === 'percentage' ?
                basePrice * (1 - discountAmount / 100) :
                basePrice - discountAmount;

            let batchOptions = ''; // Initialize as empty in case no valid batches exist

            if (stockEntry && Array.isArray(stockEntry.batches)) {
                // Safely process batches only if it's an array
                batchOptions = stockEntry.batches
                    .filter(batch => batch.location_batches && Array.isArray(batch.location_batches))
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
                  ${batch.batch_no} - Qty: ${formatAmountWithSeparators(batch.batch_quantity)} - 
                  R: ${formatAmountWithSeparators(batch.retail_price.toFixed(2))} - 
                  W: ${formatAmountWithSeparators(batch.wholesale_price.toFixed(2))} - 
                  S: ${formatAmountWithSeparators(batch.special_price.toFixed(2))}
                </option>
            `)
                    .join('');
            } else {
                console.warn("No valid batches found for the product.");
            }

            const totalQuantity = stockEntry?.total_stock ?? 0;

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
            const batchDropdown = document.getElementById('modalBatchDropdown');
            if (batchDropdown) {
                batchDropdown.addEventListener('change', () => {
                    const selectedOption = batchDropdown.selectedOptions[0];
                    if (!selectedOption) return;

                    const maxQty = parseInt(selectedOption.getAttribute('data-quantity'), 10);
                    const qtyInput = selectedRow?.querySelector('.quantity-input');

                    if (qtyInput) {
                        qtyInput.setAttribute('max', maxQty);
                        qtyInput.setAttribute('title', `Available: ${maxQty}`);
                    }
                });
            }
        }

        function addProductToBillingBody(product, stockEntry, price, batchId, batchQuantity, priceType,
            saleQuantity = 1, imeis = []) {
            // Parse and validate price
            price = parseFloat(price);
            if (isNaN(price)) {
                console.error('Invalid price for product:', product.product_name);
                toastr.error(`Invalid price for ${product.product_name}. Using default price.`, 'Error');
                price = 0;
            }

            const billingBody = document.getElementById('billing-body');

            // Check for active discounts
            const activeDiscount = stockEntry.discounts && stockEntry.discounts.length > 0 ?
                stockEntry.discounts.find(d => d.is_active && !d.is_expired) : null;

            // Calculate default fixed discount (MRP - Retail Price)
            const defaultFixedDiscount = product.max_retail_price - product.retail_price;

            // Calculate final price and discount values
            let finalPrice = price;
            let discountFixed = 0;
            let discountPercent = 0;

            if (activeDiscount) {
                if (activeDiscount.type === 'percentage') {
                    discountPercent = activeDiscount.amount;
                    finalPrice = product.max_retail_price * (1 - (discountPercent / 100));
                } else if (activeDiscount.type === 'fixed') {
                    discountFixed = activeDiscount.amount;
                    finalPrice = product.max_retail_price - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                }
            } else {
                // Apply default discount when no active discount
                discountFixed = defaultFixedDiscount;
                finalPrice = product.retail_price;
                discountPercent = (discountFixed / product.max_retail_price) * 100;
            }

            // Adjust batch quantity
            let adjustedBatchQuantity = batchQuantity;
            if (batchId === "all") {
                adjustedBatchQuantity = stockEntry.total_stock;
            } else {
                const selectedBatch = stockEntry.batches.find(batch => batch.id === parseInt(batchId));
                if (selectedBatch) {
                    const locationBatch = selectedBatch.location_batches.find(lb => lb.location_id ===
                        locationId);
                    if (locationBatch) {
                        adjustedBatchQuantity = locationBatch.quantity;
                    }
                }
            }

            // Check if product exists (only for non-IMEI products)
            if (imeis.length === 0) {
                const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
                    const rowProductId = row.querySelector('.product-id').textContent;
                    const rowBatchId = row.querySelector('.batch-id').textContent;
                    return rowProductId == product.id && rowBatchId == batchId;
                });

                if (existingRow) {
                    // Update existing row
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    let currentQty = parseInt(quantityInput.value, 10);
                    let newQuantity = currentQty + saleQuantity;

                    if (newQuantity > adjustedBatchQuantity && product.stock_alert !== 0) {
                        toastr.error(`You cannot add more than ${adjustedBatchQuantity} units of this product.`,
                            'Warning');
                        return;
                    }

                    quantityInput.value = newQuantity;
                    const subtotalElement = existingRow.querySelector('.subtotal');
                    const updatedSubtotal = newQuantity * finalPrice;
                    subtotalElement.textContent = formatAmountWithSeparators(updatedSubtotal.toFixed(2));

                    const quantityDisplay = existingRow.querySelector('.quantity-display');
                    if (quantityDisplay) {
                        quantityDisplay.textContent = `${newQuantity} of ${adjustedBatchQuantity} PC(s)`;
                    }

                    quantityInput.focus();
                    quantityInput.select();
                    updateTotals();
                    return;
                }
            }

            // Create new row for IMEI products or new non-IMEI products
            const row = document.createElement('tr');

            // IMEI display logic
            let imeiDisplay = '';
            if (imeis.length > 0) {
                imeiDisplay = `
            <div class="imei-display">
                <span class="badge bg-info">IMEI: ${imeis[0]}</span>
            </div>
        `;
            }

            row.innerHTML = `
        <td>
            <div class="d-flex align-items-center">
                <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;" class="product-image"/>
                <div class="product-info">
                    <div class="font-weight-bold product-name" style="word-wrap: break-word; max-width: 200px;">
                        ${product.product_name} 
                        <span class="badge bg-info"> MRP: ${product.max_retail_price}</span>
                        ${product.is_imei_or_serial_no === 1 ? '<span class="badge bg-warning ms-1">IMEI Product</span>' : ''}
                    </div>
                    <div class="text-muted me-2 product-sku">${product.sku}
                        ${imeis.length > 0 ? `<i class="fas fa-info-circle show-imei-btn" style="cursor: pointer;" title="View IMEI"></i>` : ''}
                        <span class="quantity-display ms-2">${saleQuantity} of ${adjustedBatchQuantity} PC(s)</span>
                    </div>
                    ${imeiDisplay}
                </div>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
                <button class="btn btn-danger quantity-minus btn">-</button>
                <input type="number" value="${saleQuantity}" min="1" max="${adjustedBatchQuantity}" class="form-control quantity-input text-center" title="Available: ${adjustedBatchQuantity}" ${imeis.length > 0 ? 'readonly' : ''}>
                <button class="btn btn-success quantity-plus btn">+</button>
            </div>
        </td>
        <td>
            <input type="number" name="discount_fixed[]" class="form-control fixed_discount" value="${discountFixed.toFixed(2)}">
        </td>
        <td>
            <input type="number" name="discount_percent[]" class="form-control percent_discount" value="${discountPercent.toFixed(2)}">
        </td>
        <td><input type="number" value="${finalPrice.toFixed(2)}" class="form-control price-input text-center" data-quantity="${adjustedBatchQuantity}" min="0" ${imeis.length > 0 ? 'readonly' : ''}></td>
        <td class="subtotal text-center mt-2">${formatAmountWithSeparators((saleQuantity * finalPrice).toFixed(2))}</td>
        <td><button class="btn btn-danger btn-sm remove-btn">×</button></td>
        <td class="product-id d-none">${product.id}</td>
        <td class="location-id d-none">${locationId}</td>
        <td class="batch-id d-none">${batchId}</td>
        <td class="discount-data d-none">${JSON.stringify(activeDiscount || {})}</td>
        <td class="d-none imei-data">${imeis.length > 0 ? imeis.join(',') : ''}</td>
    `;

            // Update quantity display for IMEI products
            const qtyDisplayCell = row.querySelector('.quantity-display');
            if (imeis.length > 0) {
                qtyDisplayCell.textContent = `${imeis.length} of ${adjustedBatchQuantity} PC(s)`;
            }


            // With this:
            if (imeis.length > 0) {
                const quantityInput = row.querySelector('.quantity-input');
                const plusBtn = row.querySelector('.quantity-plus');
                const minusBtn = row.querySelector('.quantity-minus');

                if (quantityInput) quantityInput.readOnly = true;
                if (plusBtn) plusBtn.disabled = true;
                if (minusBtn) minusBtn.disabled = true;
            } else {
                // Ensure inputs and buttons are enabled for non-IMEI products
                const quantityInput = row.querySelector('.quantity-input');
                const plusBtn = row.querySelector('.quantity-plus');
                const minusBtn = row.querySelector('.quantity-minus');

                if (quantityInput) quantityInput.readOnly = false;
                if (plusBtn) plusBtn.disabled = false;
                if (minusBtn) minusBtn.disabled = false;
            }


            // Add row to billing body
            billingBody.insertBefore(row, billingBody.firstChild);

            // Attach event listeners
            attachRowEventListeners(row, product, stockEntry);

            // Focus on quantity input
            const quantityInput = row.querySelector('.quantity-input');
            quantityInput.focus();
            quantityInput.select();

            // Handle Enter key to focus search input
            quantityInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    document.getElementById('productSearchInput').focus();
                }
            });

            // Update UI
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

            const validateAndUpdateQuantity = (newQuantity) => {
                const priceInput = row.querySelector('.price-input');
                const maxQuantity = parseInt(priceInput.getAttribute('data-quantity'), 10);

                if (newQuantity > maxQuantity && product.stock_alert !== 0) {
                    showQuantityLimitError(maxQuantity);
                    return false;
                }

                quantityInput.value = newQuantity;
                updateTotals();
                return true;
            };

            // Minus button
            quantityMinus.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value, 10);
                if (currentQuantity > 1) {
                    validateAndUpdateQuantity(currentQuantity - 1);
                }
            });

            // Plus button
            quantityPlus.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value, 10);
                validateAndUpdateQuantity(currentQuantity + 1);
            });

            // Input change
            quantityInput.addEventListener('input', () => {
                let quantityValue = parseInt(quantityInput.value, 10);
                if (isNaN(quantityValue) || quantityValue < 1) quantityValue = 1;
                const maxQuantity = parseInt(row.querySelector('.price-input').getAttribute(
                    'data-quantity'), 10);
                quantityValue = Math.min(quantityValue, maxQuantity); // Clamp
                validateAndUpdateQuantity(quantityValue);
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

            // Newly added: Disable conflicting discounts when product is added
            disableConflictingDiscounts(row);
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
                const subtotal = parseFloat(quantityInput.value) * price;
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
                const quantity = parseInt(quantityInput.value, 10) || 0;
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

        // Extract saleId from the URL path
        const pathSegments = window.location.pathname.split('/');
        saleId = pathSegments[pathSegments.length - 1];

        // Validate saleId to ensure it is a numeric value
        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            fetchEditSale(saleId);
        } else {
            // console.warn('Invalid or missing saleId:', saleId);
        }

        function fetchEditSale(saleId) {
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
                                    retail_price: parseFloat(saleProduct.batch
                                        .retail_price),
                                    wholesale_price: parseFloat(saleProduct.batch
                                        .wholesale_price),
                                    special_price: parseFloat(saleProduct.batch
                                        .special_price),
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
                        quantity: isImeiProduct ? 1 : parseInt(productRow.find(
                            '.quantity-input').val().trim(), 10),
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
                // console.log("final_amount " + totalAmount);
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
                }

                // Get amount given (optional)
                const amountGiven = parseFormattedAmount($('#amount-given').val().trim()) || 0;

                // Only calculate balance if amount was actually given
                if (amountGiven > 0) {
                    const balance = amountGiven - saleData.final_total;

                    if (balance < 0) {
                        toastr.error('Amount given is less than the total amount due.');
                        return;
                    }

                    // Only add these fields if amount was given
                    saleData.balance_amount = balance;
                    saleData.amount_given = amountGiven;
                }

                // Always include cash payment (amount will be final_total if no amount given)
                saleData.payments = [{
                    payment_method: 'cash',
                    payment_date: new Date().toISOString().slice(0, 10),
                    amount: amountGiven > 0 ? amountGiven : saleData.final_total
                }];

                // Extract sale ID from URL
                const pathSegments = window.location.pathname.split('/');
                const saleId = pathSegments[pathSegments.length - 1];

                sendSaleData(saleData, !isNaN(saleId) ? saleId : null);
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
                table.row.add([
                    index + 1,
                    sale.invoice_no,
                    `${sale.customer.prefix} ${sale.customer.first_name} ${sale.customer.last_name}`,
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
