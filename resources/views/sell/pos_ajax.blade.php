<script>
    document.addEventListener("DOMContentLoaded", function() {
        const productContainer = document.getElementById('product-container');
    const billingBody = document.getElementById('billing-body');
    const discountInput = document.getElementById('discount');
    const taxInput = document.getElementById('order-tax');
    const shippingInput = document.getElementById('shipping');
    const finalValue = document.getElementById('total');
    const categoryBtn = document.getElementById('category-btn');
    const allProductsBtn = document.getElementById('allProductsBtn');

    // Back buttons
    const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');

    // Global array to store all products for search functionality
    let allProducts = [];
    let stockData = [];

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
            .then(subcategories => {
                const subcategoryContainer = document.getElementById('subcategoryContainer');
                subcategoryContainer.innerHTML = ''; // Clear previous subcategories

                subcategories.message.forEach(subcategory => {
                    const card = document.createElement('div');
                    card.classList.add('subcategory-card');
                    card.setAttribute('data-id', subcategory.id);

                    const cardTitle = document.createElement('h6');
                    cardTitle.textContent = subcategory.subCategoryName;
                    card.appendChild(cardTitle);

                    card.addEventListener('click', () => {
                        filterProductsBySubCategory(subcategory.id);
                        closeOffcanvas('offcanvasSubcategory');
                    });

                    subcategoryContainer.appendChild(card);
                });

                // Show the subcategory offcanvas
                const subcategoryOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasSubcategory'));
                subcategoryOffcanvas.show();

                // Hide the category offcanvas
                const categoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasCategory'));
                categoryOffcanvas.hide();
            })
            .catch(error => console.error('Error fetching subcategories:', error));
    }

    // Handle back button click in subcategory offcanvas
    subcategoryBackBtn.addEventListener('click', () => {
        // Show the category offcanvas
        const categoryOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCategory'));
        categoryOffcanvas.show();

        // Hide the subcategory offcanvas
        const subcategoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasSubcategory'));
        subcategoryOffcanvas.hide();
    });

    function fetchBrands() {
        fetch('/brand-get-all')
            .then(response => response.json())
            .then(data => {
                const brands = data.message; // Get the array of brands
                const brandContainer = document.getElementById('brandContainer'); // Get the container to append brand cards

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
        fetch('/all-stock-details')
            .then(response => response.json())
            .then(data => {
                if (data.status === 200 && Array.isArray(data.stocks)) {
                    stockData = data.stocks;
                    // Populate the global allProducts array
                    allProducts = stockData.map(stock => stock.products);
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

    function closeOffcanvas(id) {
        const offcanvasElement = document.getElementById(id);
        const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (offcanvasInstance) {
            offcanvasInstance.hide();
        } else {
            offcanvasElement.classList.remove('show');
            document.querySelector('.offcanvas-backdrop').remove();
        }
    }

    function displayProducts(products) {
        const productContainer = document.getElementById('productContainer');
        productContainer.innerHTML = ''; // Clear previous products

        if (products.length === 0) {
            productContainer.innerHTML = '<p class="text-center">No products found.</p>';
            return;
        }

        // Create a map for stock data to optimize lookups
        const stockMap = new Map();

        // Process the stocks data
        products.forEach(stock => {
            const product = stock.products;
            const totalQuantity = stock.locations.reduce((sum, location) => sum + parseInt(location.total_quantity, 10), 0);

            // Update stockMap
            if (!stockMap.has(product.id)) {
                stockMap.set(product.id, {
                    totalQuantity: 0,
                    locations: []
                });
            }
            stockMap.get(product.id).totalQuantity += totalQuantity;

            // Process locations
            stock.locations.forEach(location => {
                const locationData = {
                    location_id: location.location_id,
                    location_name: location.location_name,
                    total_quantity: location.total_quantity
                };

                stockMap.get(product.id).locations.push(locationData);
            });
        });

        // Generate and insert cards using fetched product data
        products.forEach(stock => {
            const product = stock.products;
            const stockEntry = stockMap.get(product.id);

            // Determine the quantity and price
            const quantity = stockEntry ? stockEntry.totalQuantity : 0;
            const price = product.retail_price;

            let locationName = 'N/A'; // Default location if none is found
            let locationId = null; // Default location ID if none is found
            if (stockEntry && stockEntry.locations.length > 0) {
                locationName = stockEntry.locations[0].location_name; // Use first location
                locationId = stockEntry.locations[0].location_id; // Use first location ID
            }

            // Add location to product object
            product.location = {
                name: locationName,
                id: locationId // Ensure the location ID is added
            };

            const cardHTML = `
                <div class="col-3">
                    <div class="product-card">
                        <img src="assets/images/${product.product_image}" alt="${product.product_name}" class="card-img-top p-2">
                        <div class="product-card-body">
                            <h6>${product.product_name} <br> <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span></h6>
                            <h6><span class="badge bg-success">${quantity} Pc(s) in stock</span></h6>
                            <p class="card-text">Location: ${locationName}</p>
                        </div>
                    </div>
                </div>
            `;

            // Append card to the container
            productContainer.insertAdjacentHTML('beforeend', cardHTML);
        });

        // Add click event to product cards
        const productCards = document.querySelectorAll('.product-card');

        productCards.forEach((card, index) => {
            card.addEventListener('click', () => {
                // Pass product with location name to addProductToTable
                addProductToTable(stockData[index].products);
            });
        });
    }

    // function filterProductsByCategory(categoryId) {
    //     fetch(`/product-get-by-category/${categoryId}`)
    //         .then(response => response.json())
    //         .then(data => {
    //             const products = data.message;
    //             displayProducts(products);
    //         })
    //         .catch(error => {
    //             console.error('Error fetching product data:', error);
    //         });
    // }

    // function filterProductsBySubCategory(subCategoryId) {
    //     fetch(`/product-get-by-sub-category/${subCategoryId}`)
    //         .then(response => response.json())
    //         .then(data => {
    //             const products = data.message;
    //             displayProducts(products);
    //         })
    //         .catch(error => {
    //             console.error('Error fetching product data:', error);
    //         });
    // }

    // function filterProductsByBrand(brandId) {
    //     fetch(`/product-get-by-brand/${brandId}`)
    //         .then(response => response.json())
    //         .then(data => {
    //             const products = data.message;
    //             displayProducts(products);
    //         })
    //         .catch(error => {
    //             console.error('Error fetching product data:', error);
    //         });
    // }

        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.product_name && product.product_name.toLowerCase().includes(searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );
                    response(filteredProducts.map(product => ({
                        label: `${product.product_name} (${product.sku || 'No SKU'})`,
                        value: product.product_name,
                        product: product
                    })));
                },
                select: function(event, ui) {
                    // Populate the input field with the selected product name
                    $("#productSearchInput").val(ui.item.value);
                    // Add the selected product to the data table (function to be implemented)
                    addProductToTable(ui.item.product);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append(`<div>${item.label}</div>`)
                    .appendTo(ul);
            };
        }

        function addProductToTable(product) {
            console.log("Product to be added:", product);
            console.log("Location ID for Product:", product.location.id);

            // Ensure stockMap is updated correctly
            const stockMap = new Map();
            stockData.forEach(stock => {
                const productId = stock.products.id;
                if (!stockMap.has(productId)) {
                    stockMap.set(productId, 0);
                }
                stockMap.set(productId, stockMap.get(productId) + parseInt(stock.locations.reduce((sum, location) =>
                    sum + parseInt(location.total_quantity, 10), 0)));
            });

            // Get stock entry for the current product
            const stockQuantity = stockMap.get(product.id) || 0;
            const totalQuantity = stockQuantity;

            // Determine location ID
            let locationId = product.location?.id || null;

            // Prevent adding products with zero stock quantity
            if (totalQuantity === 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }

            addProductToTableWithDetails(product, totalQuantity, locationId);
        }

        function addProductToTableWithDetails(product, totalQuantity, locationId) {
    const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row =>
        row.querySelector('.product-name').textContent === product.product_name
    );

    const batches = [];
    stockData.forEach(stock => {
        if (stock.products.id === product.id) {
            stock.locations.forEach(location => {
                if (location.location_id === locationId) {
                    location.batches.forEach(batch => {
                        batches.push(batch);
                    });
                }
            });
        }
    });

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
        const finalPrice = product.discount_type === 'percentage'
            ? basePrice * (1 - discountAmount / 100)
            : basePrice - discountAmount;

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
        const finalPrice = product.discount_type === 'percentage'
            ? basePrice * (1 - discountAmount / 100)
            : basePrice - discountAmount;

        const row = document.createElement('tr');
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
                                All Batches - Total Qty: ${totalQuantity} - Price: ${finalPrice}
                            </option>
                            ${batches.map(batch => `
                                <option value="${batch.batch_id || 'null'}" data-price="${batch.batch_price || finalPrice}" data-quantity="${batch.batch_quantity}">
                                    Batch ${batch.batch_id || 'N/A'} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price || finalPrice}
                                </option>`).join('')}
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
            <td><button class="btn btn-danger btn-sm remove-btn">X</button></td>
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
            if (newQuantity > parseInt(batchDropdown.selectedOptions[0].getAttribute('data-quantity'), 10)) {
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error(`You cannot add more than ${batchDropdown.selectedOptions[0].getAttribute('data-quantity')} units of this product.`, 'Error');
            } else {
                quantityInput.value = newQuantity;
                updateTotals();
            }
        });

        quantityInput.addEventListener('input', () => {
            const quantityValue = parseInt(quantityInput.value, 10);
            if (quantityValue > parseInt(batchDropdown.selectedOptions[0].getAttribute('data-quantity'), 10)) {
                quantityInput.value = batchDropdown.selectedOptions[0].getAttribute('data-quantity');
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error(`You cannot add more than ${batchDropdown.selectedOptions[0].getAttribute('data-quantity')} units of this product.`, 'Error');
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
                toastr.error(`You cannot add more than ${batchQuantity} units from this batch.`, 'Error');
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
            const discount = parseFloat(discountInput.value) || 0;
                const tax = parseFloat(taxInput.value) || 0;
                const shipping = parseFloat(shippingInput.value) || 0;

                const subtotalAmount = totalAmount - discount;
                const totalAmountWithTaxAndShipping = subtotalAmount + tax + shipping;

                document.getElementById('items-count').textContent = totalItems.toFixed(2);
                document.getElementById('total-amount').textContent = totalAmountWithTaxAndShipping.toFixed(2);
                document.getElementById('total').textContent = 'Rs ' + totalAmountWithTaxAndShipping.toFixed(2);
        }


        discountInput.addEventListener('input', updateTotals);
        taxInput.addEventListener('input', updateTotals);
        shippingInput.addEventListener('input', updateTotals);


        fetch('/customer-get-all')
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    const customerSelect = document.getElementById('customer-id');

                    // Loop through the customer data and create an option for each customer
                    data.message.forEach(customer => {
                        const option = document.createElement('option');
                        option.value = customer.id;
                        option.textContent =
                            `${customer.first_name} ${customer.last_name} (ID: ${customer.id})`;
                        customerSelect.appendChild(option);
                    });
                } else {
                    console.error('Failed to fetch customer data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching customer data:', error);
            });
     });


     document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('cash').addEventListener('click', function() {

        function generateCode(prefix, number) {
            const numberStr = number.toString().padStart(4, '0');
            return prefix + numberStr.slice(-4);
        }

        const uniqueNumber = new Date().getTime() % 10000;
        const invoiceNo = generateCode('INV', uniqueNumber);
        const paymentReference = generateCode('PAY', uniqueNumber);

        console.log(invoiceNo);
        console.log(paymentReference);

        const paymentMode = 'cash'; // Payment method is cash
        const paymentStatus = 'paid'; // Payment status is paid
        const customerId = document.getElementById('customer-id').value;

        if (customerId === "Please Select" || customerId === "" || customerId === null) {
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error('Please select a valid customer.', 'Error');
            return;
        }

        const productRows = document.querySelectorAll('#billing-body tr'); // Get product rows in the table
        const items = [];

        productRows.forEach(row => {
            const productName = row.querySelector('.product-name').textContent.trim();
            const price = parseFloat(row.querySelector('.price-input').value.trim());
            const subtotal = parseFloat(row.querySelector('.subtotal').textContent.trim());
            const productId = row.querySelector('.product-id').textContent.trim();
            const quantity = parseInt(row.querySelector('.quantity-input').value.trim(), 10);
            const locationId = row.querySelector('.location-id')?.textContent.trim();

            if (!locationId || locationId === 'null' || locationId === '') {
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error(`Location ID is missing for product: ${productName}.`, 'Error');
                return;
            }

            items.push({
                product_id: productId,
                product_name: productName,
                quantity: quantity,
                unit_price: price,
                subtotal: subtotal,
                location_id: locationId,
            });
        });

        // Validate unit_price for each item
        const invalidItems = items.filter(item => item.unit_price === undefined || item.unit_price === null);
        if (invalidItems.length > 0) {
            toastr.error('Unit price is missing for some items.', 'Error');
            return;
        }

        if (items.length === 0) {
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error('Please add at least one product before proceeding.', 'Error');
            return;
        }

        const totalAmount = items.reduce((sum, item) => sum + item.subtotal, 0);

        if (isNaN(totalAmount)) {
            alert("Invalid total amount." + totalAmount);
            return; // Stop if the amount is not valid
        }

        const paymentData = {
            payment_mode: paymentMode,
            payment_status: paymentStatus,
            invoice_no: invoiceNo,
            customer_id: customerId,
            items: items,
            payment_reference: paymentReference,
            amount: totalAmount
        };

        console.log("Payment Data being sent:", paymentData);

        fetch('/sell-details/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                body: JSON.stringify(paymentData), // The payment data you're sending to the backend
            })
            .then(response => response.json()) // Parse the JSON response from the server
            .then(data => {
                if (data.message === 'Invoice created successfully') {
                    // Play success sound on successful response
                    document.getElementsByClassName('successSound')[0].play();
                    toastr.success(data.message, 'Added');

                    // Clear the form and reset the UI
                    resetForm();


                    // Create a hidden div for the invoice HTML and inject it into the body
                    const invoiceContainer = document.createElement('div');
                    invoiceContainer.innerHTML = data.html; // The HTML content returned from the backend
                    document.body.appendChild(invoiceContainer);

                    // Call window.print() to open the print dialog with the invoice
                    window.print();

                    // Optionally, remove the container after printing to keep the DOM clean
                    setTimeout(() => {
                        document.body.removeChild(invoiceContainer);
                    }, 1000); // Wait a moment before removing it

                    // Fetch the updated product data
                    fetchUpdatedProductData();

                } else {
                    // Handle error response
                    document.getElementsByClassName('errorSound')[0].play();
                    toastr.error(data.message, 'Error');
                }
            })
            .catch(error => {
                console.error('Error processing payment:', error);
                toastr.error(
                    'Something went wrong while processing the payment. Please try again.',
                    'Error');
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
function fetchUpdatedProductData() {
    fetch('/all-stock-details')
        .then(response => response.json())
        .then(data => {
            if (data.status === 200 && Array.isArray(data.stocks)) {
                stockData = data.stocks;
                // Populate the global allProducts array
                allProducts = stockData.map(stock => stock.products);
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
