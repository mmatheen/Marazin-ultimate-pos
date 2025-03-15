<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {




        // Elements
        const posProduct = document.getElementById('posProduct');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        // const taxInput = document.getElementById('order-tax');
        // const shippingInput = document.getElementById('shipping');
        const finalValue = document.getElementById('total');
        const categoryBtn = document.getElementById('category-btn');
        const allProductsBtn = document.getElementById('allProductsBtn');
        const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');
        // const baseUrl = "http://127.0.0.1:8000"; // Base URL for the images

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
            posProduct.innerHTML = loaderHTML;
            posProduct.style.position = 'relative';
        }

        // Hide loader
        function hideLoader() {
            posProduct.innerHTML = '';
            posProduct.style.position = '';
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
                (product.product_name && product.product_name.toLowerCase().includes(searchTerm)) ||
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

                // If exactly one product matches, trigger addProductToTable
                if (filteredProducts.length === 1) {
                    addProductToTable(filteredProducts[0]);
                }
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
    posProduct.innerHTML = ''; // Clear previous products

    if (products.length === 0) {
        posProduct.innerHTML = '<p class="text-center">No products found.</p>';
        return;
    }

    products.forEach(stock => {
        const product = stock.product;
        const totalQuantity = stock.total_stock;
        const price = product.retail_price;
        const batchNo = stock.batches.length > 0 ? stock.batches[0].batch_no : 'N/A';

        // Check if stock_alert is 0, if so, set totalQuantity to "Unlimited"
        const quantityDisplay = product.stock_alert === 0 ? 'Unlimited' : `${totalQuantity} Pc(s) in stock`;

        const cardHTML = `
            <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-3">
                <div class="product-card"> <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" alt="${product.product_name}">

                    <div class="product-card-body">
                        <h6>${product.product_name} <br>
                            <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span>
                        </h6>
                        <h6>
                            <span class="badge bg-success">${quantityDisplay}</span>
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
            const productId = card.querySelector('img').getAttribute('alt'); // Get the product ID from the alt attribute
            const selectedProduct = stockData.find(stock => stock.product.product_name === productId).product;
            addProductToTable(selectedProduct);
        });
    });
}

// Filter products by category
function filterProductsByCategory(categoryId) {
    showLoader();
    setTimeout(() => {
        const filteredProducts = stockData.filter(stock => stock.product.main_category_id === categoryId);
        displayProducts(filteredProducts);
    }, 500);
}

// Filter products by subcategory
function filterProductsBySubCategory(subCategoryId) {
    showLoader();
    setTimeout(() => {
        const filteredProducts = stockData.filter(stock => stock.product.sub_category_id === subCategoryId);
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
            // Product does not have batches but has unlimited stock
            addProductToBillingBody(product, stockEntry, product.retail_price, "all", Infinity, 'retail');
            return;
        } else {
            toastr.error('No batches found for the product', 'Error');
            return;
        }
    }

    const locationBatches = stockEntry.batches.flatMap(batch => batch.location_batches).filter(lb => lb.quantity > 0);
    if (locationBatches.length === 0) {
        toastr.error('No batches with quantity found', 'Error');
        return;
    }

    locationId = locationBatches[0].location_id;

    addProductToBillingBody(product, stockEntry, product.retail_price, "all", totalQuantity, 'retail');
}

function showProductModal(product, stockEntry, row) {
    const modalBody = document.getElementById('productModalBody');
    const basePrice = product.retail_price;
    const discountAmount = product.discount_amount || 0;
    const finalPrice = product.discount_type === 'percentage' ? basePrice * (1 - discountAmount / 100) : basePrice - discountAmount;

    const batches = Array.isArray(stockEntry.batches) ? stockEntry.batches.flatMap(batch => batch.location_batches.map(locationBatch => ({
        batch_id: batch.id,
        batch_no: batch.batch_no,
        retail_price: parseFloat(batch.retail_price),
        wholesale_price: parseFloat(batch.wholesale_price),
        special_price: parseFloat(batch.special_price),
        batch_quantity: locationBatch.quantity
    }))) : [];

    const batchOptions = batches
        .filter(batch => batch.batch_quantity > 0)
        .map(batch => `
            <option value="${batch.batch_id}" data-retail-price="${batch.retail_price}" data-wholesale-price="${batch.wholesale_price}" data-special-price="${batch.special_price}" data-quantity="${batch.batch_quantity}">
              ${batch.batch_no} - Qty: ${batch.batch_quantity} - R: ${batch.retail_price.toFixed(2)} - W: ${batch.wholesale_price.toFixed(2)} - S: ${batch.special_price.toFixed(2)}
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
            <option value="all" data-retail-price="${finalPrice}" data-quantity="${stockEntry.total_stock}">
                All - Qty: ${stockEntry.total_stock} - Price: ${finalPrice.toFixed(2)}
            </option>
            ${batchOptions}
        </select>
    `;

    selectedRow = row;
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();

    // Add event listeners for the radio buttons to change the active class
    const radioButtons = document.querySelectorAll('input[name="modal-price-type"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.btn-group-toggle .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.parentElement.classList.add('active');
        });
    });
}

function addProductToBillingBody(product, stockEntry, price, batchId, batchQuantity, priceType) {
    const billingBody = document.getElementById('billing-body');
    const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
        const productNameCell = row.querySelector('.product-name');
        return productNameCell && productNameCell.textContent === product.product_name;
    });

    if (existingRow) {
        const quantityInput = existingRow.querySelector('.quantity-input');
        let newQuantity = parseInt(quantityInput.value, 10) + 1;

        if (newQuantity > batchQuantity && product.stock_alert !== 0) {
            toastr.error(`You cannot add more than ${batchQuantity} units of this product.`, 'Warning');
            return;
        }

        quantityInput.value = newQuantity;
        existingRow.querySelector('.price-input').value = price.toFixed(2);
        existingRow.querySelector('.subtotal').textContent = (newQuantity * price).toFixed(2);

        // Focus on the quantity input field
        quantityInput.focus();
        quantityInput.select();
    } else {
        const row = document.createElement('tr');
        row.innerHTML = `
        <td>
            <div class="d-flex align-items-center">
                <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;" class="product-image"/>
                <div class="product-info">
                    <div class="font-weight-bold product-name" style="word-wrap: break-word; max-width: 200px; overflow-wrap: break-word; white-space: normal;">${product.product_name}</div>
                    <div class="text-muted">${product.sku}</div>
                </div>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
                <button class="btn btn-danger quantity-minus btn-sm">-</button>
                <input type="number" value="1" min="1" max="${batchQuantity}" class="form-control quantity-input text-center">
                <button class="btn btn-success quantity-plus btn-sm">+</button>
            </div>
        </td>
        <td><input type="number" value="${price.toFixed(2)}" class="form-control price-input text-center" data-quantity="${batchQuantity}"></td>
        <td class="subtotal text-center mt-2">${price.toFixed(2)}</td>
        <td><button class="btn btn-danger btn-sm remove-btn" style="cursor: pointer;">x</button></td>
        <td class="product-id d-none">${product.id}</td>
        <td class="location-id d-none">${locationId}</td>
        <td class="batch-id d-none">${batchId}</td>
        <td class="discount-data d-none">${JSON.stringify({ type: product.discount_type, amount: product.discount_amount })}</td>
        `;

        billingBody.insertBefore(row, billingBody.firstChild);
        attachRowEventListeners(row, product, stockEntry);

        // Focus on the quantity input field and select the text
        const quantityInput = row.querySelector('.quantity-input');
        quantityInput.focus();
        quantityInput.select();

        // Add event listener for Enter key to focus back on search input
        quantityInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                const searchInput = document.getElementById('productSearchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                } else {
                    console.error("Element with ID 'productSearchInput' not found.");
                }
            }
        });

    }

    updateTotals();
}

function attachRowEventListeners(row, product, stockEntry) {
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput = row.querySelector('.price-input');
    const quantityMinus = row.querySelector('.quantity-minus');
    const quantityPlus = row.querySelector('.quantity-plus');
    const removeBtn = row.querySelector('.remove-btn');
    const productImage = row.querySelector('.product-image');
    const productName = row.querySelector('.product-name');

    quantityMinus.addEventListener('click', () => {
        if (quantityInput.value > 1) {
            quantityInput.value--;
            updateTotals();
        }
    });

    quantityPlus.addEventListener('click', () => {
        let newQuantity = parseInt(quantityInput.value, 10) + 1;
        if (newQuantity > parseInt(priceInput.getAttribute('data-quantity'), 10) && product.stock_alert !== 0) {
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error(`You cannot add more than ${priceInput.getAttribute('data-quantity')} units of this product.`, 'Error');
        } else {
            quantityInput.value = newQuantity;
            updateTotals();
        }
    });

    quantityInput.addEventListener('input', () => {
        const quantityValue = parseInt(quantityInput.value, 10);
        if (quantityValue > parseInt(priceInput.getAttribute('data-quantity'), 10) && product.stock_alert !== 0) {
            quantityInput.value = priceInput.getAttribute('data-quantity');
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error(`You cannot add more than ${priceInput.getAttribute('data-quantity')} units of this product.`, 'Error');
        }
        updateTotals();
    });

    priceInput.addEventListener('input', () => {
        updateTotals();
    });

    removeBtn.addEventListener('click', () => {
        row.remove();
        updateTotals();
    });

    productImage.addEventListener('click', () => {
        showProductModal(product, stockEntry, row);
    });

    productName.addEventListener('click', () => {
        showProductModal(product, stockEntry, row);
    });
}

document.getElementById('saveProductChanges').onclick = function() {
    const selectedPriceType = document.querySelector('input[name="modal-price-type"]:checked').value;
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
        selectedRow.querySelector('.subtotal').textContent = subtotal.toFixed(2);

        selectedRow.querySelector('.batch-id').textContent = batchId;

        // Update product name cell with stars based on selected price type
        const stars = selectedPriceType === 'retail' ? '<i class="fas fa-star"></i>' :
                      selectedPriceType === 'wholesale' ? '<i class="fas fa-star"></i><i class="fas fa-star"></i>' :
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

    billingBody.querySelectorAll('tr').forEach(row => {
        const quantity = parseInt(row.querySelector('.quantity-input').value);
        const price = parseFloat(row.querySelector('.price-input').value);
        const subtotal = quantity * price;

        row.querySelector('.subtotal').textContent = subtotal.toFixed(2);

        totalItems += quantity;
        totalAmount += subtotal;
    });

    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const discountType = document.getElementById('discount-type').value;

    let totalAmountWithDiscount;

    if (discountType === 'percentage') {
        totalAmountWithDiscount = totalAmount - (totalAmount * discount / 100);
    } else {
        totalAmountWithDiscount = totalAmount - discount;
    }

    document.getElementById('items-count').textContent = totalItems.toFixed(2);
    document.getElementById('modal-total-items').textContent = totalItems.toFixed(2);
    document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
    document.getElementById('final-total-amount').textContent = totalAmountWithDiscount.toFixed(2);
    document.getElementById('total').textContent = totalAmountWithDiscount.toFixed(2);
    document.getElementById('payment-amount').textContent = 'Rs ' + totalAmountWithDiscount.toFixed(2);
}

document.getElementById('discount').addEventListener('input', updateTotals);
document.getElementById('discount-type').addEventListener('change', updateTotals);

function formatAmount(input) {
    let value = parseFloat(input.value);
    if (isNaN(value)) {
        input.value = '0.00';
    } else {
        input.value = value.toFixed(2);
    }
}

$(document).ready(function() {
    function gatherSaleData(status) {
        const uniqueNumber = new Date().getTime() % 10000;
        const customerId = $('#customer-id').val();
        const salesDate = new Date().toISOString().slice(0, 10);

        if (!locationId) {
            toastr.error('Location ID is required.');
            return;
        }

        const saleData = {
            customer_id: customerId,
            sales_date: salesDate,
            location_id: locationId,
            status: status,
            sale_type: "POS",
            products: [],
            discount_type: $('#discount-type').val(),
            discount_amount: parseFloat($('#discount').val()) || 0,
            total_amount: parseFloat($('#total-amount').text()) || 0,
        };

        $('#billing-body tr').each(function() {
            const productRow = $(this);
            const batchId = productRow.find('.batch-id').text().trim();
            const productData = {
                product_id: parseInt(productRow.find('.product-id').text().trim(), 10),
                location_id: parseInt(productRow.find('.location-id').text().trim(), 10),
                quantity: parseInt(productRow.find('.quantity-input').val().trim(), 10),
                price_type: priceType,
                unit_price: parseFloat(productRow.find('.price-input').val().trim()),
                subtotal: parseFloat(productRow.find('.subtotal').text().trim()),
                discount: parseFloat(productRow.find('.discount-data').data('amount')) || 0,
                tax: 0,
                batch_id: batchId === "all" ? "all" : batchId,
            };
            saleData.products.push(productData);
        });

        if (saleData.products.length === 0) {
            toastr.error('At least one product is required.');
            return null;
        }

        return saleData;
    }


    function sendSaleData(saleData, saleId = null) {
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

                // Create a print area dynamically
                var printArea = document.createElement('div');
                printArea.id = 'printArea';
                printArea.innerHTML = response.invoice_html;
                document.body.appendChild(printArea);

                // Apply print styles
                var printStyles = document.createElement('style');
                printStyles.id = 'printStyles';
                printStyles.innerHTML = `
                    @media print {
                        body * {
                            visibility: hidden;
                        }
                        #printArea, #printArea * {
                            visibility: visible;
                        }
                        #printArea {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            height: 100%;
                            font-size: 14px;
                            font-weight: bold;
                        }
                    }
                `;
                document.head.appendChild(printStyles);

                // Print the invoice
                window.print();

                // Remove print area and styles after printing
                document.body.removeChild(printArea);
                document.head.removeChild(printStyles);

                // Reset the form and refresh products
                resetForm();
                fetchAllProducts();
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
        const totalAmount = parseFloat($('#final-total-amount').text().trim()); // Ensure #total-amount element exists
        const today = new Date().toISOString().slice(0, 10);

        return [{
            payment_method: 'cash',
            payment_date: today,
            amount: totalAmount
        }];
    }

    $('#cashButton').on('click', function() {
        const saleData = gatherSaleData('completed');
        console.log(saleData);
        if (!saleData) {
            toastr.error('Please add at least one product before completing the sale.');
            return;
        }
        else{
            saleData.payments = gatherCashPaymentData();
            sendSaleData(saleData);
        }

    });

    $('#cardButton').on('click', function() {
        $('#cardModal').modal('show');
    });

    function gatherCardPaymentData() {
        const cardNumber = $('#card_number').val().trim();
        const cardHolderName = $('#card_holder_name').val().trim();
        // const cardType = $('#card_type').val().trim();
        const cardExpiryMonth = $('#card_expiry_month').val().trim();
        const cardExpiryYear = $('#card_expiry_year').val().trim();
        const cardSecurityCode = $('#card_security_code').val().trim();
        const totalAmount = parseFloat($('#final-total-amount').text().trim()); // Ensure #total-amount element exists
        const today = new Date().toISOString().slice(0, 10);

        return [{
            payment_method: 'card',
            payment_date: today,
            amount: totalAmount,
            card_number: cardNumber,
            card_holder_name: cardHolderName,
            // card_type: cardType,
            card_expiry_month: cardExpiryMonth,
            card_expiry_year: cardExpiryYear,
            card_security_code: cardSecurityCode
        }];
    }


    $('#confirmCardPayment').on('click', function() {
        // if (!validateCartFields()) {
        //     return;
        // }
        const saleData = gatherSaleData('completed');

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
        const totalAmount = parseFloat($('#final-total-amount').text().trim()); // Ensure #total-amount element exists
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

        // if ($('#cheque_bank_branch').val().trim() === '') {
        //     $('#bankBranchError').text('Bank Branch is required.');
        //     isValid = false;
        // } else {
        //     $('#bankBranchError').text('');
        // }

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

        const saleData = gatherSaleData('completed');

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
            toastr.error('Credit sale is not allowed for Walking Customer. Please choose another customer.');
            return;
        }

        const saleData = gatherSaleData('completed');

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
            let modal = bootstrap.Modal.getInstance(document.getElementById("suspendModal"));
            modal.hide();

    });

    document.getElementById('finalize_payment').addEventListener('click', function() {
        const saleData = gatherSaleData('completed');
        if (!saleData) {
            toastr.error('Please add at least one product before completing the sale.');
            return;
        }

            const paymentData = gatherPaymentData();
            saleData.payments = paymentData;
            sendSaleData(saleData);
            let modal = bootstrap.Modal.getInstance(document.getElementById("paymentModal"));
            modal.hide();

    });

    function gatherPaymentData() {
        const paymentData = [];
        document.querySelectorAll('.payment-row').forEach(row => {
            const paymentMethod = row.querySelector('.payment-method').value;
            const paymentDate = row.querySelector('.payment-date').value;
            const amount = parseFloat(row.querySelector('.payment-amount').value);
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

    $('#holdButton').on('click', function() {
        const saleData = gatherSaleData('hold');
        if (saleData) {
            sendSaleData(saleData);
        }
    });

    function fetchSuspendedSales() {
        $.ajax({
            url: '/sales/suspended',
            type: 'GET',
            success: function(response) {
                displaySuspendedSales(response);
                $('#suspendSalesModal').modal('show');
            },
            error: function(xhr, status, error) {
                toastr.error('Failed to fetch suspended sales: ' + xhr.responseText);
            }
        });
    }

    function displaySuspendedSales(sales) {
        const suspendedSalesContainer = $('#suspendedSalesContainer');
        suspendedSalesContainer.empty();

        sales.forEach(sale => {
            const finalTotal = parseFloat(sale.final_total);
            const saleRow = `
            <tr>
                <td>${sale.invoice_no}</td>
                <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
                <td>${sale.customer ? sale.customer.name : 'Walk-In Customer'}</td>
                <td>${sale.products.length}</td>
                <td>$${finalTotal.toFixed(2)}</td>
                <td>
                    <a href="pos/sales/edit/${sale.id}" class="btn btn-success editSaleButton" data-sale-id="${sale.id}">Edit</a>
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
                toastr.error('Failed to delete suspended sale: ' + xhr.responseText);
            }
        });
    }

    // Event listener for the pause circle button to fetch and show suspended sales
    $('#pauseCircleButton').on('click', function() {
        fetchSuspendedSales();
    });

    $('#amount-given').on('keyup', function(event) {
        if (event.key === 'Enter') {
            const totalAmount = parseFloat($('#total-amount').text().trim());
            const amountGiven = parseFloat($('#amount-given').val().trim());

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
                title: "Balance Amount",
                text: "The balance amount to be returned is Rs. " + balance.toFixed(2),
                type: "info",
                showCancelButton: false,
                confirmButtonText: "OK",
            }, function() {
                $('#cashButton').trigger('click');
            });
        }
    });

    // Fetch suspended sales when the POS page loads
    // fetchSuspendedSales();
});

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


            updateTotals();
        }

    })

   function fetchAllSales() {
    fetch('/sales')
        .then(response => response.json())
        .then(response => {
            var salesData = response.sales.filter(sale => sale.sale_type === 'POS');
            var tableBody = document.querySelector('#posTable tbody');
            tableBody.innerHTML = ''; // Clear existing table data

            salesData.forEach(sale => {
                var customerName = `${sale.customer.prefix} ${sale.customer.first_name} ${sale.customer.last_name}`;
                var row = `
                    <tr>
                        <td><input type="checkbox" class="checked" /></td>
                        <td>
                            <div class="dropdown dropdown-action">
                                <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item view-sale-return" href="#" data-id="${sale.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                    <a class="dropdown-item edit-link" href="/sale-return/edit/${sale.id}" data-id="${sale.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                    <a class="dropdown-item add-payment-btn" href="" data-id="${sale.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                    <a class="dropdown-item view-payment-btn" href="" data-id="${sale.id}" data-bs-toggle="modal" data-bs-target="#viewPaymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;View Payment</a>
                                </div>
                            </div>
                        </td>
                        <td>${sale.sales_date}</td>
                        <td>${sale.invoice_no || ''}</td>
                        <td>${customerName}</td>
                        <td>${sale.customer.mobile_no}</td>
                        <td>${sale.location.name}</td>
                        <td>
                            ${(() => {
                                let paymentStatusBadge = '';
                                if (sale.payment_status === 'Due') {
                                    paymentStatusBadge = '<span class="badge bg-danger">Due</span>';
                                } else if (sale.payment_status === 'Partial') {
                                    paymentStatusBadge = '<span class="badge bg-warning">Partial</span>';
                                } else if (sale.payment_status === 'Paid') {
                                    paymentStatusBadge = '<span class="badge bg-success">Paid</span>';
                                } else {
                                    paymentStatusBadge = `<span class="badge bg-secondary">${sale.payment_status}</span>`;
                                }
                                return paymentStatusBadge;
                            })()}
                        </td>
                        <td>${sale.payments.length > 0 ? sale.payments[0].payment_method : ''}</td>
                        <td>${sale.final_total}</td>
                        <td>${sale.total_paid}</td>
                        <td>${sale.total_due}</td>
                        <td>${sale.products.length}</td>
                        <td>${sale.customer.first_name}</td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });

            // Initialize DataTable
            $('#posTable').DataTable();
            feather.replace(); // Initialize Feather icons

            // Attach event listeners to dynamically created buttons
            document.querySelectorAll('.view-sale-return').forEach(button => {
                button.addEventListener('click', function () {
                    console.log('View details for sale ID:', this.dataset.id);
                });
            });

            document.querySelectorAll('.edit-link').forEach(button => {
                button.addEventListener('click', function () {
                    console.log('Edit sale ID:', this.dataset.id);
                });
            });

            document.querySelectorAll('.add-payment-btn').forEach(button => {
                button.addEventListener('click', function () {
                    console.log('Add payment for sale ID:', this.dataset.id);
                });
            });

            document.querySelectorAll('.view-payment-btn').forEach(button => {
                button.addEventListener('click', function () {
                    console.log('View payments for sale ID:', this.dataset.id);
                });
            });

        })
        .catch(error => console.error('Error fetching sales data:', error));
}


    $(document).ready(function() {
        fetchAllSales();

    });

    function formatAmount(input) {
        let value = input.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            let formattedValue = parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            let cursorPosition = input.selectionStart;
            input.value = formattedValue;
            input.setSelectionRange(cursorPosition, cursorPosition);
        }
    }

</script>



{{-- For jQuery --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
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
