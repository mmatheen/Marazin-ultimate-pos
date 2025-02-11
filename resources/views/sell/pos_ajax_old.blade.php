<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Elements
        const productContainer = document.getElementById('productContainer');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        const taxInput = document.getElementById('order-tax');
        const shippingInput = document.getElementById('shipping');
        const finalValue = document.getElementById('total');
        const categoryBtn = document.getElementById('category-btn');
        const allProductsBtn = document.getElementById('allProductsBtn');
        const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');

        // Global arrays to store products
        let allProducts = [];
        let stockData = [];

        // Show loader
        function showLoader() {
            const loaderHTML = `
        <div class="loader-container">
            <div class="loader">
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
            </div>
        </div>
    `;
            productContainer.innerHTML = loaderHTML;
            productContainer.style.position = 'relative';
        }

        // Hide loader
        function hideLoader() {
            productContainer.innerHTML = '';
            productContainer.style.position = '';
        }

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
                            nextButton.textContent = 'Next';
                            nextButton.classList.add('btn', 'btn-outline-blue');
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


        function fetchAllProducts() {
            showLoader();
            fetch('/products/stocks')
                .then(response => response.json())
                .then(data => {
                    hideLoader(); // Hide loader after fetching
                    if (data.status === 200 && Array.isArray(data.data)) {
                        stockData = data.data;
                        // Populate the global allProducts array
                        allProducts = stockData.map(stock => stock.product);
                        displayProducts(stockData);
                        initAutocomplete();
                    } else {
                        console.error('Invalid data:', data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        }

        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.product_name && product.product_name.toLowerCase().includes(
                            searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );

                    if (filteredProducts.length === 0) {
                        // Optionally display a "No products found" in the dropdown
                        response([{
                            label: "No products found",
                            value: ""
                        }]);
                    } else {
                        response(filteredProducts.map(product => ({
                            label: `${product.product_name} (${product.sku || 'No SKU'})`,
                            value: product.product_name,
                            product: product
                        })));
                    }
                },
                select: function(event, ui) {
                    // If no valid product is selected, ignore
                    if (!ui.item.product) return false;

                    // Populate the input field with the selected product name
                    $("#productSearchInput").val(ui.item.value);

                    // Add the selected product to the data table
                    addProductToTable(ui.item.product);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                // Customize the dropdown appearance
                if (!item.product) {
                    return $("<li>")
                        .append(`<div style="color: red;">${item.label}</div>`)
                        .appendTo(ul);
                }

                return $("<li>")
                    .append(`<div>${item.label}</div>`)
                    .appendTo(ul);
            };

            // Prevent default aria-live and aria-autocomplete attributes
            $("#productSearchInput").removeAttr("aria-live aria-autocomplete");

            // Remove the default autocomplete status element
            $("#productSearchInput").autocomplete("instance").liveRegion.remove();
        }

        function displayProducts(products) {
            productContainer.innerHTML = ''; // Clear previous products

            if (products.length === 0) {
                productContainer.innerHTML = '<p class="text-center">No products found.</p>';
                return;
            }

            // Generate and insert cards using fetched product data
            products.forEach(stock => {
                const product = stock.product;
                const totalQuantity = stock.total_stock
                const price = product.retail_price;
                const batchNo = stock.batches.length > 0 ? stock.batches[0].batch_no : 'N/A';

                const cardHTML = `
            <div class="col-3">
                <div class="product-card">
                    <img src="assets/images/${product.product_image}" alt="${product.product_name}" class="card-img-top p-2">
                    <div class="product-card-body">
                        <h6>${product.product_name} <br> <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span></h6>
                        <h6><span class="badge bg-success">${totalQuantity} Pc(s) in stock</span></h6>
                    </div>
                </div>
            </div>
        `;

                // Append card to the container
                productContainer.insertAdjacentHTML('beforeend', cardHTML);
            });

            // Add click event to product cards
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                card.addEventListener('click', () => {
                    const productId = card.querySelector('img').getAttribute(
                        'alt'); // Get the product ID from the alt attribute
                    const selectedProduct = stockData.find(stock => stock.product
                        .product_name === productId).product;
                    addProductToTable(selectedProduct);
                });
            });
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

        function addProductToTable(product) {
            console.log("Product to be added:", product);

            const stockEntry = stockData.find(stock => stock.product.id === product.id);
            if (!stockEntry) {
                console.error('Stock entry not found for the product');
                return;
            }

            const totalQuantity = stockEntry.total_stock;
            if (totalQuantity === 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }

            // Check if stockEntry.batches is defined and is an array
            if (!Array.isArray(stockEntry.batches)) {
                console.error('No batches found for the product');
                return;
            }

            const locationBatches = stockEntry.batches.flatMap(batch => batch.location_batches).filter(lb => lb
                .quantity > 0);
            if (locationBatches.length === 0) {
                console.error('No batches with quantity found');
                return;
            }

            let locationId = null; // Default location ID if none is found
            if (locationBatches.length > 0) {
                locationId = locationBatches[0]
                    .location_id; // Get the location ID from the first location batch
            }

            if (locationId === null) {
                console.error('No valid location ID found');
                return;
            }

            // Add the product to the table with the details
            addProductToTableWithDetails(product, totalQuantity, locationId, locationBatches, stockEntry);
        }

        function addProductToTableWithDetails(product, totalQuantity, locationId, locationBatches, stockEntry) {
            const billingBody = document.getElementById('billing-body');
            if (!billingBody) {
                console.error('billingBody element not found');
                return;
            }

            const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row =>
                row.querySelector('.product-name').textContent === product.product_name
            );

            if (existingRow) {
                const quantityInput = existingRow.querySelector('.quantity-input');
                let newQuantity = parseInt(quantityInput.value, 10) + 1;

                if (newQuantity > totalQuantity) {
                    toastr.error(`You cannot add more than ${totalQuantity} units of this product.`, 'Warning');
                    return;
                }

                quantityInput.value = newQuantity;

                const priceInput = existingRow.querySelector('.price-input');
                const basePrice = parseFloat(priceInput.value);
                const discountAmount = product.discount_amount || 0;
                const finalPrice = product.discount_type === 'percentage' ?
                    basePrice * (1 - discountAmount / 100) :
                    basePrice - discountAmount;

                const subtotal = parseFloat(quantityInput.value) * finalPrice;
                existingRow.querySelector('.subtotal').textContent = subtotal.toFixed(2);
            } else {
                document.getElementsByClassName('successSound')[0].play();
                toastr.info(
                    `<div style="display: flex; align-items: center;">
                <img src="assets/images/${product.product_image}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; border: 2px solid #ddd; padding: 4px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);"/>
                <span style="font-size: 16px; font-weight: bold; padding-right:5px;">${product.product_name} </span>Product Added!
            </div>`
                );

                const basePrice = product.retail_price;
                const discountAmount = product.discount_amount || 0;
                const finalPrice = product.discount_type === 'percentage' ?
                    basePrice * (1 - discountAmount / 100) :
                    basePrice - discountAmount;

                const row = document.createElement('tr');

                // Ensure batches is an array before accessing its properties
                const batches = Array.isArray(stockEntry.batches) ? stockEntry.batches.flatMap(batch => batch
                    .location_batches.map(locationBatch => ({
                        batch_id: batch.id,
                        batch_price: parseFloat(batch.retail_price),
                        batch_quantity: locationBatch.quantity
                    }))) : [];

                const batchOptions = batches.map(batch => `
            <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}">
                Batch ${batch.batch_id} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price.toFixed(2)}
            </option>
        `).join('');
                row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <img src="assets/images/${product.product_image}" style="width:80px; height:80px; margin-right:10px; border-radius:50%;"/>
                    <div>
                        <div class="font-weight-bold product-name">${product.product_name}</div>
                        <div class="text-muted">${product.sku}</div>
                        ${product.description ? `<div class="text-muted small">${product.description}</div>` : ''}
                        <select class="form-select batch-dropdown" aria-label="Select Batch">
                            <option value="all" data-price="${finalPrice}" data-quantity="${totalQuantity}">
                                All Batches - Total Qty: ${totalQuantity} - Price: ${finalPrice.toFixed(2)}
                            </option>
                            ${batchOptions}
                        </select>
                    </div>
                </div>
            </td>
            <td>
                <div class="quantity-container">
                    <button class="quantity-minus">-</button>
                    <input type="number" value="1" min="1" max="${totalQuantity}" class="form-control quantity-input">
                    <button class="quantity-plus">+</button>
                </div>
            </td>
            <td><input type="number" value="${finalPrice.toFixed(2)}" class="form-control price-input"></td>
            <td class="subtotal">${finalPrice.toFixed(2)}</td>
            <td><button class="btn btn-danger btn-sm remove-btn">X</td>
            <td class="product-id" style="display:none">${product.id}</td>
            <td class="location-id" style="display:none">${locationId}</td>
            <td class="discount-data" style="display:none">
                ${JSON.stringify({
                    type: product.discount_type,
                    amount: product.discount_amount
                })}
            </td>
        `;

                billingBody.insertBefore(row, billingBody.firstChild);

                const quantityInput = row.querySelector('.quantity-input');
                const priceInput = row.querySelector('.price-input');
                const quantityMinus = row.querySelector('.quantity-minus');
                const quantityPlus = row.querySelector('.quantity-plus');
                const removeBtn = row.querySelector('.remove-btn');
                const batchDropdown = row.querySelector('.batch-dropdown');

                removeBtn.addEventListener('click', () => {
                    row.remove();
                    updateTotals();
                });

                quantityMinus.addEventListener('click', () => {
                    if (quantityInput.value > 1) {
                        quantityInput.value--;
                        updateTotals();
                    }
                });

                quantityPlus.addEventListener('click', () => {
                    let newQuantity = parseInt(quantityInput.value, 10) + 1;
                    if (newQuantity > parseInt(batchDropdown.selectedOptions[0].getAttribute(
                            'data-quantity'), 10)) {
                        document.getElementsByClassName('errorSound')[0].play();
                        toastr.error(
                            `You cannot add more than ${batchDropdown.selectedOptions[0].getAttribute('data-quantity')} units of this product.`,
                            'Error'
                        );
                    } else {
                        quantityInput.value = newQuantity;
                        updateTotals();
                    }
                });

                quantityInput.addEventListener('input', () => {
                    const quantityValue = parseInt(quantityInput.value, 10);
                    if (quantityValue > parseInt(batchDropdown.selectedOptions[0].getAttribute(
                            'data-quantity'), 10)) {
                        quantityInput.value = batchDropdown.selectedOptions[0].getAttribute(
                            'data-quantity');
                        document.getElementsByClassName('errorSound')[0].play();
                        toastr.error(
                            `You cannot add more than ${batchDropdown.selectedOptions[0].getAttribute('data-quantity')} units of this product.`,
                            'Error'
                        );
                    }
                    updateTotals();
                });

                priceInput.addEventListener('input', () => {
                    updateTotals();
                });

                batchDropdown.addEventListener('change', () => {
                    const selectedOption = batchDropdown.selectedOptions[0];
                    const batchPrice = parseFloat(selectedOption.getAttribute('data-price'));
                    const batchQuantity = parseInt(selectedOption.getAttribute('data-quantity'), 10);
                    if (quantityInput.value > batchQuantity) {
                        quantityInput.value = batchQuantity;
                        toastr.error(`You cannot add more than ${batchQuantity} units from this batch.`,
                            'Error');
                    }
                    priceInput.value = batchPrice.toFixed(2);
                    const subtotal = parseFloat(quantityInput.value) * batchPrice;
                    row.querySelector('.subtotal').textContent = subtotal.toFixed(2);
                    quantityInput.setAttribute('max', batchQuantity);
                    updateTotals();
                });
            }

            updateTotals();
        }

        function updateTotals() {
            const billingBody = document.getElementById('billing-body');
            let totalItems = 0;
            let totalAmount = 0;

            billingBody.querySelectorAll('tr').forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                const price = parseFloat(row.querySelector('.price-input').value);
                const subtotal = quantity * price;

                row.querySelector('.subtotal').textContent = subtotal.toFixed(2);

                totalItems += quantity;
                totalAmount += subtotal;
            });

            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const tax = parseFloat(document.getElementById('order-tax').value) || 0;
            const shipping = parseFloat(document.getElementById('shipping').value) || 0;

            const subtotalAmount = totalAmount - discount;
            const totalAmountWithTaxAndShipping = subtotalAmount + tax + shipping;

            document.getElementById('items-count').textContent = totalItems.toFixed(2);
            document.getElementById('total-amount').textContent = totalAmountWithTaxAndShipping.toFixed(2);
            document.getElementById('total').textContent = 'Rs ' + totalAmountWithTaxAndShipping.toFixed(2);
        }

        // Add event listeners for input changes
        document.getElementById('discount').addEventListener('input', updateTotals);
        document.getElementById('order-tax').addEventListener('input', updateTotals);
        document.getElementById('shipping').addEventListener('input', updateTotals);

        // Fetch customer data and populate the customer dropdown
        fetch('/customer-get-all')
            .then(response => response.json())
            .then(data => {
                const customerSelect = document.getElementById('customer-id');

                if (data.status === 200) {
                    // Sort the customers to ensure "Walk-In Customer" is first
                    const sortedCustomers = data.message.sort((a, b) => {
                        if (a.first_name === 'Walk-In') return -1;
                        if (b.first_name === 'Walk-In') return 1;
                        return 0;
                    });

                    // Loop through the sorted customer data and create an option for each customer
                    sortedCustomers.forEach(customer => {
                        const option = document.createElement('option');
                        option.value = customer.id;
                        option.textContent = `${customer.first_name} ${customer.last_name}`;
                        customerSelect.appendChild(option);
                    });

                    // Set the default selected option to "Walk-In Customer"
                    customerSelect.value = sortedCustomers.find(customer => customer.first_name ===
                        'Walk-In').id;
                } else {
                    console.error('Failed to fetch customer data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching customer data:', error);
            });

        $(document).ready(function() {
            $('#cashButton').on('click', function() {
                // Generate a unique invoice number
                function generateCode(prefix, number) {
                    const numberStr = number.toString().padStart(4, '0');
                    return prefix + numberStr.slice(-4);
                }

                const uniqueNumber = new Date().getTime() % 10000;
                const invoiceNo = generateCode('INV', uniqueNumber);
                const customerId = $('#customer-id').val();
                const locationId = 2;
                const salesDate = new Date().toISOString().slice(0, 10); // Sales date

                if (!locationId) {
                    toastr.error('Location ID is required.');
                    return;
                }

                const saleData = {
                    customer_id: customerId,
                    sales_date: salesDate,
                    location_id: locationId,
                    status: 'completed', // Assuming the status is 'completed'
                    invoice_no: invoiceNo,
                    additional_notes: $('#additional-notes').val(),
                    shipping_details: $('#shipping-details').val(),
                    shipping_address: $('#shipping-address').val(),
                    shipping_charges: parseFloat($('#shipping-charges').val()) || 0,
                    shipping_status: $('#shipping-status').val(),
                    delivered_to: $('#delivered-to').val(),
                    delivery_person: $('#delivery-person').val(),
                    products: []
                };

                // Gather the product details
                $('#billing-body tr').each(function() {
                    const productRow = $(this);
                    const batchDropdown = productRow.find('.batch-dropdown');
                    const selectedBatch = batchDropdown.val() !== "all" ? batchDropdown
                        .val() : null;
                    const productData = {
                        product_id: parseInt(productRow.find('.product-id').text()
                            .trim(), 10),
                        batch_id: selectedBatch,
                        location_id: parseInt(productRow.find('.location-id').text()
                            .trim(), 10),
                        quantity: parseInt(productRow.find('.quantity-input').val()
                            .trim(), 10),
                        price_type: 'retail', // Assuming the price type is 'retail'
                        unit_price: parseFloat(productRow.find('.price-input').val()
                            .trim()),
                        subtotal: parseFloat(productRow.find('.subtotal').text()
                            .trim()),
                        discount: parseFloat(productRow.find('.discount-data').data(
                            'amount')) || 0,
                        tax: 0 // Assuming tax is 0 or you can get it from the product data
                    };
                    saleData.products.push(productData);
                });

                if (saleData.products.length === 0) {
                    toastr.error('At least one product is required.');
                    return;
                }

                // Determine if we are updating or storing a new sale
                const saleId = $('#sale_id')
                    .val(); // Assuming there's a hidden input field with the sale ID
                const url = saleId ? `/api/sales/update/${saleId}` : '/api/sales/store';
                const method = 'POST'; // POST is used for both storing and updating

                // Send the data via AJAX POST request
                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    data: JSON.stringify(saleData),
                    success: function(response) {
                        if (response.message) {
                            document.getElementsByClassName('successSound')[0]
                                .play();
                            toastr.success(response.message);
                            var printWindow = window.open('', '_blank');
                            printWindow.document.write(response.invoice_html);
                            printWindow.document.close();
                            printWindow.print();
                        } else {
                            toastr.error('Failed to record sale: ' + response
                                .message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred: ' + xhr.responseText);
                    }
                });
            });
        });

        function resetForm() {
            document.getElementById('customer-id').value = 'Please Select';

            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.value = 1;
            });

            const billingBodyRows = document.querySelectorAll('#billing-body tr');
            billingBodyRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    cell.innerHTML = '';
                });
            });
        }


    });


    $(document).ready(function() {
        function showFetchData() {
            $.ajax({
                url: '/getAllPosDetails',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        let table = $('#posTable').DataTable();
                        table.clear().draw();

                        response.sell_details.forEach(function(item) {
                            console.log(item); // Log individual item for debugging
                            let row = $('<tr>');
                            row.append(
                                '<td><input type="checkbox" class="checked" /></td>');

                            row.append(`
                         <td>
                             <div class="dropdown dropdown-action">
                                 <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                     <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                 </a>
                                 <div class="dropdown-menu dropdown-menu-end">
                                     <a class="dropdown-item" href="#"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                     <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewProductModal" data-id="${item.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                     <a class="dropdown-item edit-product" href="/edit-product/${item.id}" data-id="${item.id}">
                                         <i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit
                                     </a>
                                     <a class="dropdown-item delete_btn" data-id="${item.id}"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                     <a class="dropdown-item" href="#"><i class="fas fa-database"></i>&nbsp;&nbsp;Add or edit opening stock</a>
                                     <a class="dropdown-item" href="#"><i class="fas fa-history"></i>&nbsp;&nbsp;Product stock history</a>
                                     <a class="dropdown-item" href="#"><i class="far fa-copy"></i>&nbsp;&nbsp;Duplicate Product</a>
                                 </div>
                             </div>
                         </td>`);

                            // Date
                            row.append(`<td>${item.added_date}</td>`);

                            // Invoice Number
                            row.append('<td>' + item.invoice_no + '</td>');

                            // Customer Name
                            let customerName = item.customer ?
                                `${item.customer.prefix} ${item.customer.first_name} ${item.customer.last_name}` :
                                'N/A';
                            row.append('<td>' + customerName + '</td>');

                            // Contact Number
                            let contactNumber = item.customer ? item.customer.mobile_no :
                                'N/A';
                            row.append('<td>' + contactNumber + '</td>');

                            // Location
                            let locationName = item.product_orders.length > 0 ? item
                                .product_orders[0].location.name : 'N/A';
                            row.append('<td>' + locationName + '</td>');

                            // Payment Status
                            let paymentStatus = item.payment_info ? item.payment_info
                                .payment_status : 'N/A';
                            row.append('<td>' + paymentStatus + '</td>');

                            // Payment Method
                            let paymentMethod = item.payment_info ? item.payment_info
                                .payment_mode : 'N/A';
                            row.append('<td>' + paymentMethod + '</td>');

                            // Total Amount
                            let totalAmount = item.product_orders.reduce((total, order) =>
                                total + parseFloat(order.subtotal), 0);
                            row.append('<td>' + totalAmount.toFixed(2) + '</td>');

                            // Total Paid
                            let totalPaid = item.payment_info ? parseFloat(item.payment_info
                                .amount) : 0;
                            row.append('<td>' + totalPaid.toFixed(2) + '</td>');

                            // Sell Due
                            let sellDue = totalAmount - totalPaid;
                            row.append('<td>' + sellDue.toFixed(2) + '</td>');

                            // Return Due (example logic, modify as per actual need)
                            row.append('<td>0.00</td>');

                            // Shipping Status (example logic, modify as per actual need)
                            row.append('<td>Pending</td>');

                            // Total Items
                            let totalItems = item.product_orders.reduce((count, order) =>
                                count + order.quantity, 0);
                            row.append('<td>' + totalItems + '</td>');

                            // Added By
                            row.append('<td>' + item.added_by + '</td>');

                            // Sell Note (if any)
                            row.append('<td>N/A</td>');

                            // Staff Note (if any)
                            row.append('<td>N/A</td>');

                            // Shipping Details (example, modify if needed)
                            row.append('<td>N/A</td>');

                            table.row.add(row).draw(false);
                        });
                    } else {
                        console.error('Failed to load product data. Status: ' + response.status);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product data:', error);
                }
            });
        }
        showFetchData();
    });
</script>



{{-- For jQuery --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

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

<script>
    $(function() {
        $('.datetime').datetimepicker({
            format: 'hh:mm:ss a'
        });
    });
</script>

<script>
    // In your Javascript (external .js resource or <script> tag)
    $(document).ready(function() {
        $('.select2Box').select2();
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

{{-- Prevent Inspect Element
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
</script> --}}
