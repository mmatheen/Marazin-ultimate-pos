<script>
    document.addEventListener("DOMContentLoaded", function() {
        const productContainer = document.getElementById('product-container');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        const taxInput = document.getElementById('order-tax');
        const shippingInput = document.getElementById('shipping');
        const finalvalue = document.getElementById('total');

        const categorySidebar = document.getElementById('category-sidebar');
        const categoryBtn = document.getElementById('category-btn');
        const categoryList = document.getElementById('category-list');

        // Toggle sidebar visibility
        categoryBtn.addEventListener('click', function() {
            categorySidebar.classList.toggle('active');
        });
       // Fetch categories and brands from APIs and populate the offcanvas
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
                allButton.classList.add('btn', 'me-2');
                allButton.addEventListener('click', () => {
                    filterProductsByCategory(category.id);

                    // Close the offcanvas
                    const offcanvasElement = document.getElementById('offcanvasCategory');
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                    offcanvas.hide();
                });

                const nextButton = document.createElement('button');
                nextButton.textContent = 'Next';
                nextButton.classList.add('btn');
                nextButton.addEventListener('click', () => {
                    fetch(`/sub_category-details-get-by-main-category-id/${category.id}`)
                        .then(response => response.json())
                        .then(subcategories => {
                            displaySubcategories(subcategories.message);
                        })
                        .catch(error => console.error('Error fetching subcategories:', error));
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

    // Fetch the brand data from the API
fetch('/brand-get-all')
  .then(response => response.json())
  .then(data => {
    const brands = data.message; // Get the array of brands
    const brandContainer = document.getElementById('brandContainer'); // Get the container to append brand cards

    if (Array.isArray(brands)) {
      // Loop through the brands array and create brand cards
      brands.forEach(brand => {
        // Create brand card element
        const brandCard = document.createElement('div');
        brandCard.classList.add('brand-card');
        brandCard.setAttribute('data-id', brand.id);

        // Add brand name
        const brandName = document.createElement('h6');
        brandName.textContent = brand.name; // Use brand's name
        brandCard.appendChild(brandName);

        // Append the brand card to the container
        brandContainer.appendChild(brandCard);
      });
    } else {
      console.error('Brands not found:', brands);
    }
  })
  .catch(error => {
    console.error('Error fetching brands:', error);
  });


// Function to display subcategories in the same offcanvas
function displaySubcategories(subcategories) {
    const categoryContainer = document.getElementById('categoryContainer');
    categoryContainer.innerHTML = ''; // Clear current categories
    subcategories.forEach(subcategory => {
        const card = document.createElement('div');
        card.classList.add('category-card');
        card.setAttribute('data-id', subcategory.id);

        const cardTitle = document.createElement('h6');
        cardTitle.textContent = subcategory.subCategoryname;
        card.appendChild(cardTitle);

        card.addEventListener('click', () => {
            filterProductsByCategory(subcategory.id);
        });

        categoryContainer.appendChild(card);
    });
}

// Function to filter products by category
function filterProductsByCategory(categoryId) {
    const productContainer = document.getElementById('productContainer');
    productContainer.innerHTML = ''; // Clear previous products

    fetch(`/product-get-by-category/${categoryId}`)
        .then(response => response.json())
        .then(data => {
            const products = data.message;

            if (Array.isArray(products)) {
                products.forEach(product => {
                    const cardHTML = `
                        <div class="col-md-3 col-sm-4">
                            <div class="product-card">
                                <img src="assets/images/${product.product_image}" alt="${product.product_name}" class="card-img-top p-2">
                                <div class="card-body">
                                    <h5 class="card-title">${product.product_name}</h5>
                                    <p class="card-text">Quantity: ${product.quantity || 0}</p>
                                    <p class="card-text">Price: ${product.price || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    productContainer.insertAdjacentHTML('beforeend', cardHTML);
                });
            } else {
                console.error('Invalid product data:', products);
            }
        })
        .catch(error => {
            console.error('Error fetching product data:', error);
        });
}

        // Fetch products, quantities, and purchase data from APIs

        let stockData = [];
        let purchaseStockData = [];

        Promise.all([
                fetch('/product-get-all').then(response => response.json()),
                fetch('/import-opening-stock-get-all').then(response => response.json()),
                fetch('/get-all-purchases').then(response => response.json())
            ])
            .then(([productsResponse, stockResponse, purchaseResponse]) => {
                console.log("Products Response:", productsResponse);
                console.log("Stock Response:", stockResponse);
                console.log("Purchase Response:", purchaseResponse);

                const products = productsResponse.message || []; // Array of products
                stockData = stockResponse.message || []; // Array of stock data
                purchaseStockData = purchaseResponse.purchases || []; // Array of purchase data

                // Check for valid data before proceeding
                if (!Array.isArray(products) || !Array.isArray(stockData) || !Array.isArray(
                        purchaseStockData)) {
                    console.error('Invalid data:', products, stockData, purchaseStockData);
                    return;
                }

                // Create maps for stock and purchase data to optimize lookups
                const stockMap = {};
                stockData.forEach(stock => {
                    const productId = stock.product_id;
                    if (!stockMap[productId]) {
                        stockMap[productId] = {
                            totalQuantity: 0,
                            location: stock.location || null,
                        };
                    }
                    stockMap[productId].totalQuantity += parseInt(stock.quantity,
                        10); // Accumulate quantities
                });

                const purchaseMap = {};
                purchaseStockData.forEach(purchase => {
                    purchase.purchase_products.forEach(purchaseProduct => {
                        const productId = purchaseProduct.product_id;
                        if (!purchaseMap[productId]) {
                            purchaseMap[productId] = {
                                totalQuantity: 0,
                                price: parseFloat(purchaseProduct.price) || 0,
                            };
                        }
                        purchaseMap[productId].totalQuantity += parseInt(purchaseProduct
                            .quantity, 10); // Accumulate quantities
                    });
                });

                // Generate and insert cards using fetched product data
                const productContainer = document.getElementById('productContainer');

                products.forEach(product => {
    const stockEntry = stockMap[product.id] || null; // Get stock data for the product if available
    const purchaseEntry = purchaseMap[product.id] || null; // Get purchase data for the product if available

    // Determine the quantity and price
    const quantity = stockEntry ? stockEntry.totalQuantity : (purchaseEntry ? purchaseEntry.totalQuantity : 0);
    const price = purchaseEntry ? purchaseEntry.price : (stockEntry ? stockEntry.retail_price : 0); // Check both stockEntry and purchaseEntry for price

    // Check the stock for location, otherwise fallback to product locations
    let locationName = 'N/A'; // Default location if none is found
    let locationId = null; // Default location ID if none is found
    if (stockEntry && stockEntry.location) {
        locationName = stockEntry.location.name; // Use stock location
        locationId = stockEntry.location.id; // Get the location ID from stock data
    } else if (product.locations && product.locations.length > 0) {
        locationName = product.locations[0].name; // Fallback to product's first location if available
        locationId = product.locations[0].id; // Get the location ID from product locations
    }

    const openingStockQty = stockEntry ? stockEntry.totalQuantity : 0;
    const purchaseQty = purchaseEntry ? purchaseEntry.totalQuantity : 0;
    const currentStock = openingStockQty + purchaseQty;

    // Add location to product object (including the ID)
    product.location = {
        name: locationName,
        id: locationId, // Assign the Location ID
    };

    const cardHTML = `
        <div class="col-3">
            <div class="product-card">
                <img src="assets/images/${product.product_image}" alt="${product.product_name}" class="card-img-top p-2">
                <div class="product-card-body">
                    <h6>${product.product_name} <br> <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span></h6>
                    <h6><span class="badge bg-success">${currentStock} Pc(s) in stock</span></h6>
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
                        // Pass product with both location name and ID to addProductToTable
                        addProductToTable(products[index]);
                    });
                });

            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });


            function addProductToTable(product) {
    console.log("Product to be added:", product);
    console.log("Location ID for Product:", product.location); // Now the product has location

    // Ensure stockMap is updated correctly
    const stockMap = new Map();
    stockData.forEach(stock => {
        const productId = stock.product_id;
        if (!stockMap.has(productId)) {
            stockMap.set(productId, 0); // Initialize quantity
        }
        stockMap.set(productId, stockMap.get(productId) + parseInt(stock.quantity, 10)); // Sum up quantities
    });

    // Ensure purchaseMap is updated correctly
    const purchaseMap = new Map();
    purchaseStockData.forEach(purchase => {
        purchase.purchase_products.forEach(purchaseProduct => {
            const productId = purchaseProduct.product_id;
            const locationId = purchaseProduct.location_id; // Ensure this is captured
            if (!purchaseMap.has(productId)) {
                purchaseMap.set(productId, {
                    quantity: 0,
                    location_id: locationId
                }); // Store quantity and location
            }
            purchaseMap.set(productId, {
                quantity: purchaseMap.get(productId).quantity + parseInt(purchaseProduct.quantity, 10),
                location_id: locationId
            });
        });
    });

    // Get stock and purchase entry for the current product
    const stockQuantity = stockMap.get(product.id) || 0;
    const purchaseInfo = purchaseMap.get(product.id) || { quantity: 0, location_id: null };
    const totalQuantity = stockQuantity + purchaseInfo.quantity;

    // Determine location ID
    let locationId = purchaseInfo.location_id || product.location?.id || null;

    console.log('Location ID (Stock or Purchase):', locationId); // Debug: Check location ID
    console.log('this is purchase product location ID:', product.location ? product.location.id : null); // Debug: Check location ID

    // Prevent adding products with zero stock quantity
    if (totalQuantity === 0) {
        toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
        return; // Exit function if the total quantity is 0
    }

    // Check if the product is already in the table
    const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => row.querySelector('.product-name').textContent === product.product_name);

    if (existingRow) {
        // Update the quantity and subtotal of the existing row
        const quantityInput = existingRow.querySelector('.quantity-input');
        let newQuantity = parseInt(quantityInput.value, 10) + 1;

        // Validate the new quantity against total stock
        if (newQuantity > totalQuantity) {
            toastr.error(`You cannot add more than ${totalQuantity} units of this product.`, 'Warning');
            return; // Exit the function if the quantity exceeds the limit
        }

        quantityInput.value = newQuantity;
        const price = parseFloat(existingRow.cells[2].textContent);
        const subtotal = parseFloat(quantityInput.value) * price;
        existingRow.cells[3].textContent = subtotal.toFixed(2);
    } else {
        // Add a new row for the product
        document.getElementsByClassName('successSound')[0].play();
        toastr.info(
            `<div style="display: flex; align-items: center;">
                <img src="assets/images/${product.product_image}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; border: 2px solid #ddd; padding: 4px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);"/>
                <span style="font-size: 16px; font-weight: bold; padding-right:5px;">${product.product_name} </span>Product Added!
            </div>`
        );

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <img src="assets/images/${product.product_image}" style="width:80px; height:100px; margin-right:10px;"/>
                    <div>
                        <div class="font-weight-bold product-name">${product.product_name}</div>
                        <div class="text-muted">${product.sku}</div>
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
            <td>${product.retail_price.toFixed(2)}</td>
            <td>${product.retail_price.toFixed(2)}</td>
            <td><button class="btn btn-danger btn-sm remove-btn">X</button></td>
            <td class="product-id" style="display:none">${product.id}</td> <!-- Add the product ID -->
            <td class="location-id" style="display:none">${locationId}</td> <!-- Add the location ID -->
        `;

        // Insert the new row at the beginning of the table body
        billingBody.insertBefore(row, billingBody.firstChild);

        row.querySelector('.remove-btn').addEventListener('click', () => {
            row.remove();
            updateTotals();
        });

        const quantityInput = row.querySelector('.quantity-input');
        const quantityMinus = row.querySelector('.quantity-minus');
        const quantityPlus = row.querySelector('.quantity-plus');

        quantityMinus.addEventListener('click', () => {
            if (quantityInput.value > 1) {
                quantityInput.value--;
                updateTotals();
            }
        });

        quantityPlus.addEventListener('click', () => {
            let newQuantity = parseInt(quantityInput.value, 10) + 1;

            // Ensure the quantity does not exceed the maximum limit
            if (newQuantity > totalQuantity) {
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error(`You cannot add more than ${totalQuantity} units of this product.`, 'Error');
            } else {
                quantityInput.value = newQuantity;
                updateTotals();
            }
        });

        quantityInput.addEventListener('input', () => {
            const quantityValue = parseInt(quantityInput.value, 10);
            if (quantityValue > totalQuantity) {
                quantityInput.value = totalQuantity;
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error(`You cannot add more than ${totalQuantity} units of this product.`, 'Error');
            }
        });

        quantityInput.addEventListener('change', updateTotals);
    }

    updateTotals();
}


        function updateTotals() {
            let totalItems = 0;
            let totalAmount = 0;

            billingBody.querySelectorAll('tr').forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                const price = parseFloat(row.cells[2].textContent);
                const subtotal = quantity * price;

                row.cells[3].textContent = subtotal.toFixed(2);

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

            const productRows = document.querySelectorAll(
                '#billing-body tr'); // Get product rows in the table
            const items = [];

            productRows.forEach(row => {
                const productName = row.querySelector('.product-name').textContent.trim();
                const price = parseFloat(row.querySelector('td:nth-child(3)').textContent
                    .trim());
                const subtotal = parseFloat(row.querySelector('td:nth-child(4)').textContent
                    .trim());
                const productId = row.querySelector('.product-id').textContent.trim();
                const quantity = parseInt(row.querySelector('.quantity-input').value.trim(),
                    10);
                const locationId = row.querySelector('.location-id')?.textContent.trim();

                if (!locationId || locationId === 'null' || locationId === '') {
                    document.getElementsByClassName('errorSound')[0].play();
                    toastr.error(`Location ID is missing for product: ${productName}.`,
                        'Error');
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

            fetch('/sell-details/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    body: JSON.stringify(
                        paymentData), // The payment data you're sending to the backend
                })
                .then(response => response.json()) // Parse the JSON response from the server
                .then(data => {
                    // Play success sound on successful response
                    document.getElementsByClassName('successSound')[0].play();

                    toastr.success(data.message, 'Added');

                    // Create a hidden div for the invoice HTML and inject it into the body
                    const invoiceContainer = document.createElement('div');
                    invoiceContainer.innerHTML = data
                        .html; // The HTML content returned from the backend
                    document.body.appendChild(invoiceContainer);

                    // Call window.print() to open the print dialog with the invoice
                    window.print();

                    // Optionally, remove the container after printing to keep the DOM clean
                    setTimeout(() => {
                        document.body.removeChild(invoiceContainer);
                    }, 1000); // Wait a moment before removing it

                    // Clear customer details (e.g., reset the customer ID)
                    document.getElementById('customer-id').value = 'Please Select';

                    // Reset all product quantities and clear the product table
                    const quantityInputs = document.querySelectorAll('.quantity-input');
                    quantityInputs.forEach(input => {
                        input.value = 1; // Reset product quantity to 1 (or desired value)
                    });


                    // Clear all rows inside #billing-body
                    const billingBodyRows = document.querySelectorAll('#billing-body tr');
                    billingBodyRows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        cells.forEach(cell => {
                            cell.innerHTML = ''; // Clear the cell content
                        });
                    });
                })
                .catch(error => {
                    console.error('Error processing payment:', error);
                    toastr.error(
                        'Something went wrong while processing the payment. Please try again.',
                        'Error');
                });


        });
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
