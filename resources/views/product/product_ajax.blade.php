<script>
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); // For CSRF token
        let allProducts = [];
        let categoryMap = {};
        let brandMap = {};
        let locationMap = {};

        // Validation options
        var addAndUpdateValidationOptions = {
            rules: {
                product_name: { required: true },
                unit_id: { required: true },
                brand_id: { required: true },
                main_category_id: { required: true },
                sub_category_id: { required: true },
                'locations[]': { required: true },
                retail_price: { required: true },
                whole_sale_price: { required: true },
                special_price: { required: true },
                original_price: { required: true }
            },
            messages: {
                product_name: { required: "Product Name is required" },
                unit_id: { required: "Product Unit is required" },
                brand_id: { required: "Product Brand is required" },
                main_category_id: { required: "Main Category is required" },
                sub_category_id: { required: "Sub Category is required" },
                'locations[]': { required: "Business Location is required" },
                retail_price: { required: "Retail Price is required" },
                whole_sale_price: { required: "Whole Sale Price is required" },
                special_price: { required: "Special Price is required" },
                original_price: { required: "Cost Price is required" }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                if (element.is("select")) {
                    error.insertAfter(element.closest('div'));
                } else if (element.is(":checkbox")) {
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to forms
        $('#addForm').validate(addAndUpdateValidationOptions);

      // Function to format the product data into table rows
function formatProductData(product) {
    let locationName = product.batches.length > 0 && product.batches[0].location_batches.length > 0 ? product.batches[0].location_batches[0].location_name : 'N/A';
    let imagePath = product.product_image ? `/assets/images/${product.product_image}` : '/assets/images/default.jpg'; // Default image if product image is not available
    return `
        <tr>
            <td><input type="checkbox" class="checked" /></td>
            <td><img src="${imagePath}" alt="${product.product_name}" width="50" height="50"></td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewProductModal" data-product-id="${product.id}"><i class="fas fa-eye"></i> View</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit"></i> Edit</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-trash-alt"></i> Delete</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-plus"></i> Add or Edit Opening Stock</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-history"></i> Product Stock History</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-clone"></i> Duplicate Product</a></li>
                    </ul>
                </div>
            </td>
            <td>${product.product_name}</td>
            <td>${locationName}</td>
            <td>${product.retail_price}</td>
            <td>${product.total_stock}</td>
            <td>${product.product_type}</td>
            <td>${categoryMap[product.main_category_id] || 'N/A'}</td>
            <td>${brandMap[product.brand_id] || 'N/A'}</td>
            <td>${product.sku}</td>
        </tr>
    `;
}



        // Function to populate filter dropdowns
        function populateProductFilter() {
            const productNameFilter = $('#productNameFilter');
            const categoryFilter = $('#categoryFilter');
            const brandFilter = $('#brandFilter');

            const productNames = [...new Set(allProducts.map(item => item.product.product_name))];
            const categories = [...new Set(allProducts.map(item => item.product.main_category_id))];
            const brands = [...new Set(allProducts.map(item => item.product.brand_id))];

            productNameFilter.empty().append('<option value="">Select Product</option>');
            categoryFilter.empty().append('<option value="">Select Category</option>');
            brandFilter.empty().append('<option value="">Select Brand</option>');

            productNames.forEach(name => {
                productNameFilter.append(`<option value="${name}">${name}</option>`);
            });

            categories.forEach(category => {
                if (categoryMap[category]) {
                    categoryFilter.append(`<option value="${category}">${categoryMap[category]}</option>`);
                }
            });

            brands.forEach(brand => {
                if (brandMap[brand]) {
                    brandFilter.append(`<option value="${brand}">${brandMap[brand]}</option>`);
                }
            });
        }

        // AJAX call to fetch the product data
        function fetchProductData() {
            $.ajax({
                url: '/products/stocks',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        allProducts = response.data; // Store all data for filtering
                        populateProductFilter(); // Populate filter options

                        // Initialize DataTable
                        $('#productTable').DataTable({ destroy: true });
                        var productTableBody = $('#productTable tbody');
                        productTableBody.empty(); // Clear existing data

                        response.data.forEach(function(item) {
                            var product = item.product;
                            product.total_stock = item.total_stock;
                            product.batches = item.batches;
                            productTableBody.append(formatProductData(product));
                        });
                    } else {
                        console.error('Failed to fetch product data.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product data:', error);
                }
            });
        }

        // Function to filter products based on selected filters
        function filterProducts() {
            const selectedProduct = $('#productNameFilter').val();
            const selectedCategory = $('#categoryFilter').val();
            const selectedBrand = $('#brandFilter').val();

            const filteredProducts = allProducts.filter(item => {
                const product = item.product;
                const matchesProduct = selectedProduct ? product.product_name === selectedProduct : true;
                const matchesCategory = selectedCategory ? product.main_category_id == selectedCategory : true;
                const matchesBrand = selectedBrand ? product.brand_id == selectedBrand : true;
                return matchesProduct && matchesCategory && matchesBrand;
            });

            var table = $('#productTable').DataTable();
            table.clear().draw();

            filteredProducts.forEach(function(item) {
                var product = item.product;
                product.total_stock = item.total_stock;
                product.batches = item.batches;
                table.row.add($(formatProductData(product))).draw();
            });
        }

        // Fetch product data on page load
        fetchProductData();

        // Apply filters on change
        $('#productNameFilter, #categoryFilter, #brandFilter').on('change', filterProducts);

        // Fetch main category, sub category, location, unit, brand details to select box
        function fetchCategoriesAndBrands() {
            $.ajax({
                url: '/main-category-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    response.message.forEach(function(category) {
                        categoryMap[category.id] = category.mainCategoryName;
                    });
                },
                error: function() {
                    console.error('Error loading categories');
                }
            });

            $.ajax({
                url: '/brand-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    response.message.forEach(function(brand) {
                        brandMap[brand.id] = brand.name;
                    });
                },
                error: function() {
                    console.error('Error loading brands');
                }
            });

           // Fetch location data
           $.ajax({
                url: '/location-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        response.message.forEach(function(location) {
                            locationMap[location.id] = location
                                .name; // Store location name with ID as key
                        });
                    } else {
                        console.error('Failed to load location data. Status: ' + response.status);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching location data:', error);
                }
            });
        }

        // Initialize fetching categories and brands
        fetchCategoriesAndBrands();


        // Other functions and event handlers...

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addForm')[0].reset();
            // Reset the validation messages and states
            $('#addForm').validate().resetForm();
            $('#addForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addForm').find('.is-validGreen').removeClass('is-validGreen');
            // Reset the image to the default
            $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png');
        }

        // Submit the only product
        $('#onlySaveProductButton').click(function(e) {
            e.preventDefault(); // Prevent default form submission

            // Gather the form data
            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Log FormData to the console
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            // Validate the form before submitting
            if (!$('#addForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            $.ajax({
                url: 'product-store',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                        fetchLastAddedProducts();
                    }
                }
            });
        });

        // Fetch last added products
        function fetchLastAddedProducts() {
            $.ajax({
                url: 'get-last-product', // Adjusted route
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        const product = response.product;
                        addProductToTable(product);
                        toastr.success('New product added to the table!', 'Success');
                    } else {
                        toastr.error(response.message || 'Unable to fetch product details.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('An error occurred while fetching product details.', 'Error');
                    console.error(xhr, status, error);
                },
            });
        }

        // Add product to table
        function addProductToTable(product) {
            const table = $("#purchase_product").DataTable();
            let existingRow = null;

            // Check if the product is already in the table
            $('#purchase_product tbody tr').each(function() {
                const rowProductId = $(this).data('id');
                if (rowProductId === product.id) {
                    existingRow = $(this);
                    return false; // Break the loop
                }
            });

            if (existingRow) {
                // Update the quantity of the existing product
                const quantityInput = existingRow.find('.purchase-quantity');
                const newQuantity = parseFloat(quantityInput.val()) + 1;
                quantityInput.val(newQuantity).trigger('input'); // Update and trigger input event
            } else {
                // Add new row if the product is not already in the table
                const price = parseFloat(product.original_price) || 0;
                const retailPrice = parseFloat(product.retail_price) || 0;
                const wholesalePrice = parseFloat(product.whole_sale_price) || 0;
                const specialPrice = parseFloat(product.special_price) || 0;
                const maxRetailPrice = parseFloat(product.max_retail_price) || 0;

                const newRow = `
                    <tr data-id="${product.id}">
                        <td>${product.id}</td>
                        <td>${product.product_name || '-'} <br><small>Stock: ${product.quantity || 0}</small></td>
                        <td><input type="number" class="form-control purchase-quantity" value="1" min="1"></td>
                        <td><input type="number" class="form-control unit-price" value="${price.toFixed(2)}" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control discount-percent" value="0" min="0" max="100"></td>
                        <td><input type="number" class="form-control product-price" value="${price.toFixed(2)}" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control retail-price" value="${retailPrice.toFixed(2)}" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control wholesale-price" value="${wholesalePrice.toFixed(2)}" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control special-price" value="${specialPrice.toFixed(2)}" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control max-retail-price" value="${maxRetailPrice.toFixed(2)}" min="0" step="0.01"></td>
                        <td class="sub-total">0</td>
                        <td><input type="number" class="form-control profit-margin" value="0" min="0"></td>
                        <td><input type="date" class="form-control expiry-date"></td>
                        <td><input type="text" class="form-control batch-id"></td>
                        <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
                    </tr>
                `;

                const $newRow = $(newRow);
                table.row.add($newRow).draw(); // Add new row to DataTable
                updateRow($newRow); // Update calculations for the new row
                updateFooter(); // Update footer after adding new row

                // Set up event listeners for quantity, discount, and price changes
                $newRow.find(".purchase-quantity, .discount-percent, .unit-price, .product-price, .retail-price, .wholesale-price, .special-price, .max-retail-price").on("input", function() {
                    updateRow($newRow); // Update calculations when values change
                    updateFooter(); // Update footer
                });

                // Set up event listener for deleting a product row
                $newRow.find(".delete-product").on("click", function() {
                    table.row($newRow).remove().draw(); // Remove row from table
                    updateFooter(); // Update footer
                });
            }
        }

        // Update row calculations
        function updateRow($row) {
            const quantity = parseFloat($row.find(".purchase-quantity").val()) || 0;
            const unitPrice = parseFloat($row.find(".unit-price").val()) || 0;
            const discountPercent = parseFloat($row.find(".discount-percent").val()) || 0;

            const discountedPrice = unitPrice - (unitPrice * discountPercent) / 100;
            const subTotal = discountedPrice * quantity;

            $row.find(".sub-total").text(subTotal.toFixed(2));
        }

        // Update footer (total items and net total)
        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#purchase_product tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
                const subTotal = parseFloat($(this).find('.sub-total').text()) || 0;

                totalItems += quantity;
                netTotalAmount += subTotal;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#total').val(netTotalAmount.toFixed(2)); // Update hidden total input

            const discountType = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#purchase-total').text(`Purchase Total: Rs ${finalTotal.toFixed(2)}`);
            $('#final-total').val(finalTotal.toFixed(2)); // Update hidden final total input
            $('#discount-display').text(`(-) Rs ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs ${taxAmount.toFixed(2)}`);

            // Calculate payment due based on advance balance
            const advanceBalance = parseFloat($('#advance-payment').val()) || 0;
            const paymentDue = finalTotal - advanceBalance;
            $('.payment-due').text(`Rs ${paymentDue.toFixed(2)}`);
        }

        // Update footer when discount or tax values change
        $('#discount-type, #discount-amount, #tax-type, #advance-payment').on('change input', updateFooter);

        // Show picture when added or edited
        $(".show-picture").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#selectedImage").attr("src", e.target.result);
                        $("#selectedImage").show();
                        $("#pdfViewer").hide();
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Clear and reset the Sub Category dropdown by Main Category ID
        $('#edit_main_category_id').change(function() {
            var main_category_id = $(this).val();
            $('#edit_sub_category_id').empty().append('<option selected disabled>Sub Category</option>');

            $.ajax({
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id,
                type: 'get',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append(`<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`);
                        });
                    } else {
                        console.log('Error: ', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ', error);
                }
            });
        });



        // Handle form submission for adding a new product
        $('#SaveProductButtonAndAnother').click(function(e) {
            e.preventDefault();

            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Validate the form before submitting
            if (!$('#addForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return;
            }

            $.ajax({
                url: 'product-store',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });


    $('#summernote').summernote({
        placeholder: 'Enter your description...',
        tabsize: 2,
        height: 40
    });

        // Global flag to track submission state
    let isSubmitting = false;

    // Handle form submission
    $('#openingStockAndProduct').click(function(e) {
        e.preventDefault(); // Prevent default form submission

        // Check if the form is already being submitted
        if (isSubmitting) {
            // If already submitting, show an alert and prevent further clicks
            toastr.error('Form is already being submitted. Please wait.', 'Warning');
            return; // Prevent further execution
        }

        // Set the flag to indicate that the form is being submitted
        isSubmitting = true;

        // Gather the form data
        let form = $('#addForm')[0];
        let formData = new FormData(form);

        // Add Summernote content to form data
        formData.append('description', $('#summernote').val());

        // Log FormData to the console (for debugging)
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // Validate the form before submitting
        if (!$('#addForm').valid()) {
            document.getElementsByClassName('warningSound')[0].play(); // Play warning sound
            toastr.error('Invalid inputs, Check & try again!!', 'Warning');

            // Reset the flag to allow future submissions
            isSubmitting = false;
            return; // Return if form is not valid
        }

        $.ajax({
            url: 'product-store',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                    });
                } else {
                    document.getElementsByClassName('successSound')[0].play(); // Play success sound
                    toastr.success(response.message, 'Added');
                    resetFormAndValidation();

                    // Redirect to the opening stock page
                    window.location.href = `/opening-stock/${response.product_id}`;
                }
            },
            error: function(xhr) {
                toastr.error('Failed to add product. Please try again.', 'Error');
            },
            complete: function() {
                // Reset the flag after the request completes (success or failure)
                isSubmitting = false;
            }
        });
    });


    $('#submitOpeningStock').click(function(e) {
                e.preventDefault();

                // let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                let form = $('#openingStockForm')[0];
                let formData = new FormData(form);


            // Additional validation for batch numbers
                let isValidBatchNo = true;

                $('.batch-no-input').each(function () {
                    let batchNo = $(this).val();
                    if (batchNo && !/^BATCH[0-9]{3,}$/.test(batchNo)) {
                        isValidBatchNo = false;
                    }
                });

                // If any batch number is invalid, show the error and stop submission
                if (!isValidBatchNo) {
                    document.getElementsByClassName('warningSound')[0].play(); // Play warning sound
                    toastr.error('Invalid Batch Number. It should start with "BATCH" followed by at least 3 digits.', 'Warning');
                    return; // Stop submission
                }




                $.ajax({
                    url: `/opening-stock-store/${$('#product_id').val()}`, // Pass the product ID dynamically
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success(response.message, 'Success');
                            window.location.href = '/add-product';
                        } else {

                            toastr.error(response.message, 'Error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                        } else {
                            toastr.error('Unexpected error occurred', 'Error');
                        }
                    }
                });
            });




        $.ajax({
            url: 'initial-product-details', // Replace with your endpoint URL
            type: 'GET',
            success: function(response) {
                if (response.status === 200) {

                    const brandSelect = $('#edit_brand_id');
                    const mainCategorySelect = $('#edit_main_category_id');
                    const subCategorySelect = $('#edit_sub_category_id');
                    const unitSelect = $('#edit_unit_id');
                    const locationSelect = $('#edit_location_id');


                    brandSelect.empty(); // Clear existing options
                    mainCategorySelect.empty(); // Clear existing options
                    subCategorySelect.empty(); // Clear existing options
                    unitSelect.empty(); // Clear existing options
                    locationSelect.empty(); // Clear existing options

                    // Access brands and subcategories from response data
                    const brands = response.message.brands;
                    const mainCategories = response.message.mainCategories;
                    const subCategories = response.message.subCategories;
                    const units = response.message.units;
                    const locations = response.message.locations;

                    if ((brands && brands.length > 0) || (mainCategories && mainCategories.length >
                            0) || (subCategories && subCategories.length > 0) || (units && units
                            .length > 0) || (locations && locations.length > 0)) {

                        // If there are brands or subcategories, add the default options and populate with data
                        brandSelect.append('<option selected disabled>Product Brand</option>');
                        mainCategorySelect.append(
                            '<option selected disabled>Main Category</option>');
                        subCategorySelect.append(
                            '<option selected disabled>Sub Category </option>');
                        unitSelect.append('<option selected disabled>Unit</option>');
                        // locationSelect.append('<option selected disabled>Location</option>');

                        brands.forEach(brand => {
                            brandSelect.append(
                                `<option value="${brand.id}">${brand.name}</option>`);
                        });

                        mainCategories.forEach(mainCategory => {
                            mainCategorySelect.append(
                                `<option value="${mainCategory.id}">${mainCategory.mainCategoryName}</option>`
                            );
                        });

                        subCategories.forEach(subCategory => {
                            subCategorySelect.append(
                                `<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`
                            );
                        });

                        units.forEach(unit => {
                            unitSelect.append(
                                `<option value="${unit.id}">${unit.name}</option>`);
                        });

                        locations.forEach(location => {
                            locationSelect.append(
                                `<option value="${location.id}">${location.name}</option>`
                            );
                        });
                    } else {
                        // If no records are found, show appropriate message
                        brandSelect.append(
                            '<option selected disabled>No brands available</option>');
                        mainCategorySelect.append(
                            '<option selected disabled>No main categories available</option>');
                        subCategorySelect.append(
                            '<option selected disabled>No main categories available</option>');
                        unitSelect.append('<option selected disabled>No unit available</option>');
                        locationSelect.append(
                            '<option selected disabled>No location available</option>');
                    }
                }
            },
            error: function(error) {
                console.log("Error:", error);
            }
        });

        // Function to populate dropdowns for editing
        function populateDropdowns(mainCategories, subCategories, brands, units, locations) {
            const mainCategorySelect = $('#edit_main_category_id1').empty();
            const subCategorySelect = $('#edit_sub_category_id1').empty();
            const brandSelect = $('#edit_brand_id1').empty();
            const unitSelect = $('#edit_unit_id1').empty();

            mainCategorySelect.append('<option selected disabled>Main Category</option>');
            subCategorySelect.append('<option selected disabled>Sub Category</option>');
            brandSelect.append('<option selected disabled>Product Brand</option>');
            unitSelect.append('<option selected disabled>Unit</option>');

            mainCategories.forEach(function(category) {
                mainCategorySelect.append(new Option(category.mainCategoryName, category.id));
            });

            brands.forEach(function(brand) {
                brandSelect.append(new Option(brand.name, brand.id));
            });

            units.forEach(function(unit) {
                unitSelect.append(new Option(unit.name, unit.id));
            });

            locations.forEach(function(location) {
                $('#edit_location_id').append(new Option(location.name, location.id));
            });
        }

        // Fetch product details for editing
        // function fetchProductDetails(productId) {
            const productId = window.location.pathname.split('/').pop();

            $.ajax({
                url: `/edit-product/${productId}`,
                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        const product = response.message.product;
                        const mainCategories = response.message.mainCategories;
                        const subCategories = response.message.subCategories;
                        const brands = response.message.brands;
                        const units = response.message.units;
                        const locations = response.message.locations;

                        populateProductDetails(product, mainCategories, subCategories, brands, units, locations);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching product details.');
                }
            });


        // Populate product details in the form
        function populateProductDetails(product, mainCategories, subCategories, brands, units, locations) {
            $('#edit_product_name1').val(product.product_name);
            $('#edit_sku1').val(product.sku || "");
            $('#edit_pax').val(product.pax || 0);
            $('#edit_original_price').val(product.original_price || 0);
            $('#edit_retail_price').val(product.retail_price || 0);
            $('#edit_whole_sale_price').val(product.whole_sale_price || 0);
            $('#edit_special_price').val(product.special_price || 0);
            $('#edit_alert_quantity').val(product.alert_quantity || 0);
            $('#edit_product_type').val(product.product_type || "").trigger('change');
            $('#Enable_Product_description').prop('checked', product.is_imei_or_serial_no === 1);
            $('#Not_for_selling').prop('checked', product.is_for_selling === "1");

            if (product.product_image) {
                const imagePath = `/assets/images/${product.product_image}`;
                $('#selectedImage').attr('src', imagePath).show();
            }

            const locationIds = product.locations.map(location => location.id);
            $('#edit_location_id').val(locationIds).trigger('change');

            populateDropdowns(mainCategories, subCategories, brands, units, locations);

            $('#edit_main_category_id1').val(product.main_category_id).trigger('change');
            setTimeout(() => {
                populateSubCategories(product.main_category_id, subCategories);
                $('#edit_sub_category_id1').val(product.sub_category_id).trigger('change');
            }, 300);
        }

        // Populate subcategories based on the selected main category
        function populateSubCategories(selectedMainCategoryId, subCategories) {
            const subCategorySelect = $('#edit_sub_category_id1').empty();
            subCategorySelect.append('<option selected disabled>Sub Category</option>');

            subCategories
                .filter(subCategory => subCategory.main_category_id == selectedMainCategoryId)
                .forEach(subCategory => {
                    subCategorySelect.append(new Option(subCategory.subCategoryname, subCategory.id));
                });

            subCategorySelect.trigger('change');
        }

        // Handle form submission for updating a product
        $('#onlyUpdateProductButton').click(function(e) {
            e.preventDefault();

            let productId = $('#product_id').val();
            let form = $('#UpdateForm')[0];
            let formData = new FormData(form);

            if (!$('#UpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0]?.play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return;
            }

            $.ajax({
                url: '/product-update/' + productId,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value || '');
                        });
                    } else if (response.status === 200) {
                        document.getElementsByClassName('successSound')[0]?.play();
                        toastr.success(response.message, 'Updated');
                        resetFormAndValidation();
                        setTimeout(() => {
                            window.location.href = '/list-product';
                        }, 1500);
                    }
                },
                error: function() {
                    toastr.error('Something went wrong!', 'Error');
                }
            });
        });

        // Handle row click events to show product details
        $('#productTable').on('click', 'tr', function(e) {
            if (!$(e.target).closest('button').length) {
                const productId = $(this).data('id');
                $('#viewProductModal').modal('show');
                fetchProductDetails(productId);
            }
        });

        // Prevent row click event when clicking on buttons inside the rows
        $('#productTable').on('click', '.view_btn, .edit-product, .delete_btn', function(e) {
            e.stopPropagation();
        });

        // Handle product deletion
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Product');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: '/delete-product/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.status == 404) {
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        fetchProductData();
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });

// Fetch and show product details in the modal
$('#viewProductModal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var productId = button.data('product-id'); // Extract product ID from data-product-id attribute

    // Fetch product details by ID
    $.ajax({
        url: '/product-get-details/' + productId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 200) {
                var product = response.message;
                var imagePath = product.product_image ? `/assets/images/${product.product_image}` : '/assets/images/default.jpg'; // Default image if product image is not available
                var details = `
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td rowspan="8" class="text-center align-middle">
                                    <img src='${imagePath}' width='150' height='200' class="rounded img-fluid" />
                                </td>
                                <th scope="row">Product Name</th>
                                <td>${product.product_name}</td>
                            </tr>
                            <tr>
                                <th scope="row">SKU</th>
                                <td>${product.sku}</td>
                            </tr>
                            <tr>
                                <th scope="row">Category</th>
                                <td>${categoryMap[product.main_category_id] || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Brand</th>
                                <td>${brandMap[product.brand_id] || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Locations</th>
                                <td>${product.locations.map(loc => locationMap[loc.id] || 'N/A').join(', ')}</td>
                            </tr>
                            <tr>
                                <th scope="row">Price</th>
                                <td>$${product.retail_price}</td>
                            </tr>
                            <tr>
                                <th scope="row">Alert Quantity</th>
                                <td>${product.alert_quantity}</td>
                            </tr>
                            <tr>
                                <th scope="row">Product Type</th>
                                <td>${product.product_type}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                `;
                $('#productDetails').html(details);
            } else {
                console.error('Failed to load product details. Status: ' + response.status);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching product details:', error);
        }
    });
});

        // Fetch product data when the page is ready
        $(document).ready(function() {
            fetchProductData();
            fetchCategoriesAndBrands();
        });
    });

    </script>
