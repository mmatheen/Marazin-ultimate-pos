<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token

        // var addAndUpdateValidationOptions = {
        //     rules: {

        //         product_name: {
        //             required: true,
        //         },

        //         unit_id: {
        //             required: true,
        //         },
        //         brand_id: {
        //             required: true,
        //         },
        //         main_category_id: {
        //             required: true,
        //         },
        //         sub_category_id: {
        //             required: true,
        //         },
        //         location_id: {
        //             required: true,
        //         }

        //         ,
        //         retail_price: {
        //             required: true,
        //         },
        //         whole_sale_price: {
        //             required: true,
        //         },
        //         special_price: {
        //             required: true,
        //         },
        //         original_price: {
        //             required: true,
        //         },

        //     },
        //     messages: {

        //         product_name: {
        //             required: "Product Name is required",
        //         },
        //         unit_id: {
        //             required: "Product Unit  is required",
        //         },
        //         brand_id: {
        //             required: "Product Brand is required",
        //         },
        //         main_category_id: {
        //             required: "Main Category  is required",
        //         },
        //         sub_category_id: {
        //             required: "Sub Category  is required",
        //         },
        //         location_id: {
        //             required: "Business Location  is required",
        //         }

        //         ,
        //         retail_price: {
        //             required: "Retail Price is required",
        //         },
        //         whole_sale_price: {
        //             required: "Whole Sale Price is required",
        //         },
        //         special_price: {
        //             required: "Special Price is required",
        //         },
        //         original_price: {
        //             required: "Cost Price is required",
        //         },

        //     },
        //     errorElement: 'span',
        //     errorPlacement: function(error, element) {

        //         // error message show after selectbox
        //         if (element.is("select")) {
        //             error.addClass('text-danger');
        //             // Insert the error after the closest parent div for better placement with select
        //             error.insertAfter(element.closest('div'));
        //         }
        //         // error message show after checkbox
        //         else if (element.is(":checkbox")) {
        //             error.addClass('text-danger');
        //             // For checkboxes, place the error after the checkbox's parent container
        //             error.insertAfter(element.closest('div').find('label').last());
        //         }
        //         // error message show after inputbox
        //         else {
        //             error.addClass('text-danger');
        //             error.insertAfter(element);
        //         }
        //     },

        //     highlight: function(element, errorClass, validClass) {
        //         $(element).addClass('is-invalidRed').removeClass('is-validGreen');
        //     },
        //     unhighlight: function(element, errorClass, validClass) {
        //         $(element).removeClass('is-invalidRed').addClass('is-validGreen');
        //     }

        // };

        // // Apply validation to forms
        // $('#addPurchaseProductForm').validate(addAndUpdateValidationOptions);

        var purchaseValidationOptions = {
    rules: {
        supplier_id: {
            required: true,
        },
        reference_no: {
            required: true,
        },
        purchase_date: {
            required: true,
            date: true,
        },
        purchase_status: {
            required: true,
        },
        services: {
            required: true,
        },
        duration: {
            required: true,
            number: true,
        },
        duration_type: {
            required: true,
        },
        image: {
            required: false, // Image field is optional, can be customized if needed
            extension: "jpg|jpeg|png|gif|pdf|csv|zip|doc|docx", // Allowed file extensions
            filesize: 5242880 // Max file size 5MB (in bytes)
        }
    },
    messages: {
        supplier_id: {
            required: "Supplier is required",
        },
        reference_no: {
            required: "Reference No is required",
        },
        purchase_date: {
            required: "Purchase Date is required",
            date: "Please enter a valid date",
        },
        purchase_status: {
            required: "Purchase Status is required",
        },
        services: {
            required: "Business Location is required",
        },
        duration: {
            required: "Duration is required",
            number: "Please enter a valid number",
        },
        duration_type: {
            required: "Period is required",
        },
        image: {
            extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
            filesize: "Max file size is 5MB"
        }
    },
    errorElement: 'span',
    errorPlacement: function(error, element) {
        if (element.is("select")) {
            error.addClass('text-danger');
            error.insertAfter(element.closest('div'));
        } else if (element.is(":checkbox")) {
            error.addClass('text-danger');
            error.insertAfter(element.closest('div').find('label').last());
        } else {
            error.addClass('text-danger');
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
$('#purchaseForm').validate(purchaseValidationOptions);



        // show the image when add and edit
        $(".show-picture").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Update the src attribute of the img tag with the data URL of the selected image
                    $("#selectedImage").attr("src", e.target.result);
                    $("#selectedImage").show(); // Show the image
                };

                reader.readAsDataURL(input.files[0]);
            }
        });
        // show the image when add and edit image code end



        // sub category details show using main category id code start

        // Clear and reset the Sub Category dropdown Defaut
        $('#edit_sub_category_id').empty().append('<option selected disabled>Sub Category</option>');

        // Get value of main_category_id
        $('#edit_main_category_id').change(function() {
            var main_category_id = $(this).val();
            console.log(main_category_id);

            // Clear and reset the batch_no dropdown
            $('#edit_sub_category_id').empty().append(
                '<option selected disabled>Sub Category</option>');

            $.ajax({
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id,
                type: 'get',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        // Populate the batch_no options
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append('<option value="' +
                                subCategory.id + '">' + subCategory
                                .subCategoryname + '</option>');
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
        // sub category details show using main category id code end



        // Function to reset form and validation errors
        // function resetFormAndValidation() {
        //     // Reset the form fields
        //     $('#addPurchaseProductForm')[0].reset();
        //     // Reset the validation messages and states
        //     $('#addPurchaseProductForm').validate().resetForm();
        //     $('#addPurchaseProductForm').find('.is-invalidRed').removeClass('is-invalidRed');
        //     $('#addPurchaseProductForm').find('.is-validGreen').removeClass('is-validGreen');
        //     // Reset the image to the default
        //     $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png');

        // }
        // // Function to reset form and validation errors
        // function resetFormAndValidationPruchase() {
        //     // Reset the form fields
        //     $('#purchaseForm')[0].reset();
        //     // Reset the validation messages and states
        //     $('#purchaseForm').validate().resetForm();
        //     $('#purchaseForm').find('.is-invalidRed').removeClass('is-invalidRed');
        //     $('#purchaseForm').find('.is-validGreen').removeClass('is-validGreen');
        //     // Reset the image to the default
        //     $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png');
        // }

        $('#onlySaveProductButton').click(function(e) {
            e.preventDefault(); // Prevent default form submission

            // Validate the form before proceeding
            if (!$('#addPurchaseProductForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }



            // Gather the form data after validation
            let form = $('#addPurchaseProductForm')[0];
            let formData = new FormData(form);

            // Log FormData to the console for debugging
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            // AJAX Request
            $.ajax({
                url: 'product-store', // Endpoint to store product
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken // CSRF token for Laravel
                },
                data: formData,
                contentType: false, // Don't set content-type, as FormData handles it
                processData: false, // Prevent jQuery from processing data
                dataType: 'json', // Expecting JSON response
                success: function(response) {
                    if (response.status == 400) {
                        // Show validation errors if any
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        // Success case
                        document.getElementsByClassName('successSound')[0]
                            .play(); // for sound
                        toastr.success(response.message, 'Product Added');
                        resetFormAndValidation(); // Reset form and validation messages

                        $('#new_purchase_product').modal('hide');

                        fetchLastAddedProducts();
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Something went wrong! Please try again.', 'Error');
                }
            });
        });

        // Function to fetch and append last added products
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
                        const newRow = `
                    <tr data-id="${product.id}">
                        <td>${product.id}</td>
                        <td>${product.product_name || '-'}</td>
                        <td>
                            <input type="number" class="form-control purchase-quantity" value="1" min="1">
                        </td>
                        <td>${product.retail_price || '0'}</td>
                        <td>
                            <input type="number" class="form-control discount-percent" value="0" min="0" max="100">
                        </td>
                        <td class="retail-price">${product.retail_price || '0'}</td>
                        <td class="sub-total">0</td>
                        <td>
                            <input type="number" class="form-control product-tax" value="0" min="0">
                        </td>
                        <td class="net-cost">0</td>
                        <td class="line-total">0</td>
                        <td>${product.profit_margin || '0'}</td>
                        <td class="whole-sale-price">${product.whole_sale_price || '-'}</td>
                        <td>
                            <button class="btn btn-danger btn-sm delete-product">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                        // Destroy and reinitialize DataTables to update with new rows
                        const table = $('#purchase_product').DataTable();
                        table.destroy();
                        $('#purchase_product tbody').append(newRow);
                        $('#purchase_product').DataTable();

                        updateFooter();
                        toastr.success('New product added to the table!', 'Success');
                    } else {
                        toastr.error(response.message || 'Unable to fetch product details.',
                            'Error');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('An error occurred while fetching product details.', 'Error');
                    console.error(xhr, status, error);
                },
            });
        }

        // Event listener for dynamic table changes
        $('#purchase_product').on('input', '.purchase-quantity, .discount-percent, .product-tax', function() {
            const row = $(this).closest('tr');
            calculateRow(row);
            updateFooter();
        });

        // Event listener for deleting a row
        $('#purchase_product').on('click', '.delete-product', function() {
            $(this).closest('tr').remove();
            updateFooter();
            toastr.info('Product removed from the table.', 'Info');
        });

        function calculateRow(row) {
            const quantity = parseFloat(row.find('.purchase-quantity').val()) || 0;
            const unitCost = parseFloat(row.find('.retail-price').text()) || 0;
            const discountPercent = parseFloat(row.find('.discount-percent').val()) || 0;
            const tax = parseFloat(row.find('.product-tax').val()) || 0;

            const discount = (unitCost * discountPercent) / 100;
            const netCost = unitCost - discount;
            const subTotal = netCost * quantity;
            const lineTotal = subTotal + (subTotal * tax) / 100;

            row.find('.net-cost').text(netCost.toFixed(2));
            row.find('.sub-total').text(subTotal.toFixed(2));
            row.find('.line-total').text(lineTotal.toFixed(2));
        }

        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#purchase_product tbody tr').each(function() {
                totalItems += parseFloat($(this).find('.purchase-quantity').val()) || 0;
                netTotalAmount += parseFloat($(this).find('.line-total').text()) || 0;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));

            // Apply Discount and VAT
            const discountType = $('#discount-type').val(); // Discount Type (None, Fixed, Percentage)
            const discountInput = parseFloat($('#discount-amount').val()) || 0; // Discount Value
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            // Calculate VAT/Tax
            const taxType = $('#tax-type').val(); // Tax Type (None, VAT@10%, CGST@10%)
            let taxAmount = 0;

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            // Final Purchase Total
            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            // Update the DOM
            $('#purchase-total').text(`Purchase Total: $ ${finalTotal.toFixed(2)}`);
            $('#discount-display').text(`(-) $ ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) $ ${taxAmount.toFixed(2)}`);
        }

        // Trigger calculations on events
        $(document).on('change keyup',
            '.purchase-quantity, .discount-percent, .product-tax, #discount-amount, #discount-type, #tax-type',
            function() {
                $('#purchase_product tbody tr').each(function() {
                    calculateRow($(this));
                });
                updateFooter();
            });



        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#addPurchaseProductForm')[0].reset(); // Reset the form
            $('.error-message').html(''); // Clear error messages
        }




        // Fetch main category, sub category, location, unit, brand details to select box code start
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
                            '<option selected disabled>Main Category Brand</option>');
                        subCategorySelect.append(
                            '<option selected disabled>Sub Category Brand</option>');
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
        // Fetch main category, sub category, location, unit, brand details to select box code start



        function populateBrandDropdown(brands) {
            const brandSelect = $('#edit_brand_id');
            brandSelect.empty(); // Clear existing options

            if (brands.length > 0) {
                // If there are brands, add the default option and populate with brand options
                brandSelect.append('<option selected disabled>Product Brand</option>');
                brands.forEach(brand => {
                    brandSelect.append(`<option value="${brand.id}">${brand.name}</option>`);
                });
            } else {
                // If no brands are found, add a single disabled option indicating no records
                brandSelect.append('<option selected disabled>No brands available</option>');
            }
        }


    });

    // Fetch suppliers using AJAX
// Fetch suppliers using AJAX
$.ajax({
    url: '/supplier-get-all',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        if (data.status === 200) {
            const supplierSelect = $('#supplier-id');

            // Clear existing options before appending new ones
            supplierSelect.html('<option selected disabled>Supplier</option>');

            // Loop through the supplier data and create an option for each supplier
            data.message.forEach(function(supplier) {
                const option = $('<option></option>')
                    .val(supplier.id)
                    .text(`${supplier.first_name} ${supplier.last_name} (ID: ${supplier.id})`)
                    .data('details', supplier); // Store supplier details in data attribute

                supplierSelect.append(option);
            });
        } else {
            console.error('Failed to fetch supplier data:', data.message);
        }
    },
    error: function(xhr, status, error) {
        console.error('Error fetching supplier data:', error);
    }
});

// Handle supplier selection
$('#supplier-id').on('change', function () {
    const selectedOption = $(this).find(':selected'); // Get the selected option
    const supplierDetails = selectedOption.data('details'); // Retrieve the stored details

    if (supplierDetails) {
        // Update Advance Balance with the supplier's opening balance
        const openingBalance = parseFloat(supplierDetails.opening_balance || 0); // Default to 0 if not provided
        $('#advance-payment').val(openingBalance.toFixed(2)); // Display in the Advance Balance input

        // Optionally, update Payment Due or any other related fields
        const paymentDue = calculatePaymentDue(openingBalance); // Example function to compute payment due
        $('.payment-due').text(paymentDue.toFixed(2)); // Display payment due

        // Update supplier details
        $('#supplier-name').text(`${supplierDetails.first_name} ${supplierDetails.last_name}`); // Display supplier name
        $('#supplier-phone').text(supplierDetails.mobile_no);
    }
});

// Example function to calculate payment due (replace with your logic)
function calculatePaymentDue(openingBalance) {
    const totalPurchase = parseFloat($('#purchase-total').val() || 0); // Default to 0 if empty or invalid
    return totalPurchase - openingBalance;
}


// Example function to calculate payment due (replace with your logic)
function calculatePaymentDue(openingBalance) {
    // Ensure #purchase-total has a valid numeric value
    const totalPurchase = parseFloat($('#purchase-total').val() || 0); // Default to 0 if empty or invalid

    if (isNaN(totalPurchase) || isNaN(openingBalance)) {
        console.error('Invalid values for calculation. Check #purchase-total and openingBalance.');
        return 0; // Return 0 as a fallback
    }

    // Calculate payment due
    return totalPurchase - openingBalance;
}


    // Fetch locations using AJAX
    $.ajax({
        url: '/location-get-all',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.status === 200) {
                const locationSelect = $('#services');

                // Clear existing options before adding new ones
                locationSelect.html('<option selected disabled>Please Select Locations</option>');

                // Loop through the location data and create an option for each location
                data.message.forEach(function(location) {
                    const option = $('<option></option>')
                        .val(location.id) // Ensure this matches your database column
                        .text(location.name);
                    locationSelect.append(option);
                });

            } else {
                console.error('Failed to fetch location data:', data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching location data:', error);
        }
    });


    document.addEventListener('DOMContentLoaded', function () {
    const purchaseButton = document.getElementById('purchaseButton');

    // Function to reset form and validation errors
    function resetFormAndValidationPurchase() {
        // Reset the form fields
        const form = document.getElementById('purchaseForm');
        if (form) {
            form.reset(); // Resets form input fields
        }

        // Reset validation messages and states
        $('#purchaseForm').validate().resetForm(); // Resets validation states (if jQuery Validation is used)
        $('#purchaseForm').find('.is-invalidRed').removeClass('is-invalidRed');
        $('#purchaseForm').find('.is-validGreen').removeClass('is-validGreen');

        // Reset any custom fields, dynamic rows, or images
        $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png'); // Reset the image
        $('#purchase_product tbody').empty(); // Clear the product table (if it contains dynamic rows)

        // Optionally, reset select dropdowns if needed
        $('#supplier-id, #discount-type, #payment-method').val('').trigger('change');
    }

    if (purchaseButton) {
        purchaseButton.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default form submission

            // Create FormData for AJAX request
            const formData = new FormData();
            const locationId = document.getElementById('services')?.value || '';
            const purchaseDate = document.getElementById('purchase-date')?.value || '';
            const formattedDate = purchaseDate.split('-').reverse().join('-'); // Convert to YYYY-MM-DD

            // Purchase Details
            formData.append('supplier_id', document.getElementById('supplier-id')?.value || '');
            formData.append('reference_no', document.getElementById('reference-no')?.value || '');
            formData.append('purchase_date', formattedDate);
            formData.append('location_id', locationId);
            formData.append('discount_type', document.getElementById('discount-type')?.value || '');
            formData.append('discount_amount', document.getElementById('discount-amount')?.value || '0');
            formData.append('payment_method', document.getElementById('payment-method')?.value || '');
            formData.append('payment_note', document.getElementById('payment-note')?.value || '');

            // Product Details
            const productTableRows = document.querySelectorAll('#purchase_product tbody tr');
            productTableRows.forEach((row, index) => {
                const productId = row.querySelector('td:nth-child(1)')?.textContent.trim() || '';
                const quantity = row.querySelector('td:nth-child(3) input')?.value.trim() || '0';
                const price = row.querySelector('td:nth-child(4)')?.textContent.trim() || '0';
                const total = row.querySelector('td:nth-child(10)')?.textContent.trim() || '0';

                formData.append(`products[${index}][product_id]`, productId);
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][price]`, price);
                formData.append(`products[${index}][total]`, total);
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // AJAX Request
            $.ajax({
                url: 'purchases/store', // Endpoint to store product
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken, // CSRF token for Laravel
                },
                data: formData,
                contentType: false, // Don't set content-type, as FormData handles it
                processData: false, // Prevent jQuery from processing data
                dataType: 'json', // Expecting JSON response
                success: function (response) {
                    if (response.status === 400) {
                        // Show validation errors if any
                        $.each(response.errors, function (key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        // Success case
                        document.getElementsByClassName('successSound')[0].play(); // Play success sound
                        toastr.success(response.message, 'Product Added');
                        resetFormAndValidationPurchase(); // Reset the form and validation
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Something went wrong! Please try again.', 'Error');
                },
            });
        });
    } else {
        console.error('Purchase button not found!');
    }
});

    // Global variable to store combined product data
    let allProducts = [];

    // Fetch data from all APIs and combine it
    Promise.all([
            fetch('/product-get-all').then(response => response.json().then(data => data.message || [])),
            fetch('/import-opening-stock-get-all').then(response => response.json().then(data => data.message ||
            [])),
            fetch('/get-all-purchases').then(response => response.json().then(data => data.purchases || []))
         ])
                .then(([products, openingStocks, purchases]) => {
                    // Ensure all responses are arrays before processing
                    if (!Array.isArray(products)) {
                        console.error('Unexpected format for products:', products);
                        products = [];
                    }
                    if (!Array.isArray(openingStocks)) {
                        console.error('Unexpected format for openingStocks:', openingStocks);
                        openingStocks = [];
                    }
                    if (!Array.isArray(purchases)) {
                        console.error('Unexpected format for purchases:', purchases);
                        purchases = [];
                    }

                    // Merge all product data
                    allProducts = [
                        ...products.map(product => ({
                            id: product.id,
                            name: product.product_name,
                            sku: product.sku,
                            quantity: null,
                            price: product.retail_price,
                            source: 'products'
                        })),
                        ...openingStocks.map(stock => ({
                            id: stock.product?.id || null,
                            name: stock.product?.product_name || 'Unnamed Product',
                            sku: stock.product?.sku || null,
                            quantity: stock.quantity,
                            price: stock.unit_cost,
                            source: 'openingStock'
                        })),
                        ...purchases.flatMap(purchase =>
                            purchase.purchase_products.map(purchaseProduct => ({
                                id: purchaseProduct.product_id,
                                name: `Product ID ${purchaseProduct.product_id}`, // Placeholder if name is unavailable
                                sku: null, // Placeholder if SKU is unavailable
                                quantity: purchaseProduct.quantity,
                                price: purchaseProduct.price,
                                source: 'purchases'
                            }))
                        )
                    ];

                    console.log('Combined product data:', allProducts);
                    // Initialize search functionality after data is ready
                    initSearchFunctionality();
                })
                .catch(err => console.error('Error fetching product data:', err));

    // Function to initialize search functionality
            function initSearchFunctionality() {
                const searchInput = document.getElementById('productSearchInput');
                const searchResults = document.getElementById('productSearchResults');
                // const table = $('#purchase_product').DataTable();

                // Function to render search results
                function renderSearchResults(filteredProducts) {
                    searchResults.innerHTML = ''; // Clear existing results
                    if (filteredProducts.length > 0) {
                        filteredProducts.forEach(product => {

                            if (product.name && product.name !== `Product ID ${product.id}`) {
                            const resultItem = document.createElement('div');
                            resultItem.classList.add('dropdown-item');
                            resultItem.textContent =
                                `${product.name || 'Unnamed Product'} (${product.sku || 'No SKU'})`;
                            searchResults.appendChild(resultItem);

                            // Add click event to populate the input field
                            resultItem.addEventListener('click', () => {
                                searchInput.value = `${product.name} (${product.sku || 'No SKU'})`;
                                searchResults.style.display = 'none';
                                // Add the selected product to the data table a
                                addProductToTable(product);
                            });
                            }
                        });
                        searchResults.style.display = 'block'; // Show the dropdown
                    } else {
                        searchResults.style.display = 'none'; // Hide if no results
                    }
                }

                // Search functionality
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.name && product.name.toLowerCase().includes(searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );
                    renderSearchResults(filteredProducts);
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.input-group')) {
                        searchResults.style.display = 'none';
                    }
                });



         function addProductToTable(product) {
            // Calculate the current quantity
            let currentStock = 0;

            // Check for the product in the opening stock
            const openingStock = allProducts.find(p => p.id === product.id && p.source === 'openingStock');
            if (openingStock) {
                currentStock += Number(openingStock.quantity || 0);
            }

            // Add quantities from purchases
            const purchaseQuantities = allProducts
                .filter(p => p.id === product.id && p.source === 'purchases')
                .reduce((sum, p) => sum + Number(p.quantity || 0), 0);

            currentStock += purchaseQuantities;

            // Generate the new row
            const newRow = `
                <tr data-id="${product.id}">
                    <td>${product.id}</td>
                    <td>${product.name || '-'} <br>Current stock: ${currentStock || '0'}</td>
                    <td>
                        <input type="number" class="form-control purchase-quantity" value="1" min="1">
                    </td>
                    <td>
                        <input type="number" class="form-control product-price" value="${product.price || '0'}" min="0">
                    </td>
                    <td>
                        <input type="number" class="form-control discount-percent" value="0" min="0" max="100">
                    </td>
                    <td class="retail-price">${product.price || '0'}</td>
                    <td class="sub-total">0</td>
                    <td>
                        <input type="number" class="form-control product-tax" value="0" min="0">
                    </td>
                    <td class="net-cost">0</td>
                    <td class="line-total">0</td>
                    <td>
                        <input type="number" class="form-control profit-margin" value="${product.profit_margin || '0'}" min="0">
                    </td>
                    <td class="whole-sale-price">${product.whole_sale_price || '-'}</td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-product">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            // Add the new row to the DataTable
            const $newRow = $(newRow);
            $('#purchase_product').DataTable().row.add($newRow).draw();

            // Remove the product from allProducts array
            allProducts = allProducts.filter(p => p.id !== product.id);

            // Update footer after adding the product
            updateFooter();
            toastr.success('New product added to the table!', 'Success');

            // Add event listeners for dynamic updates
            $newRow.find('.purchase-quantity, .discount-percent, .product-tax, .product-price, .profit-margin').on('input', function() {
                updateRow($newRow);
                updateFooter();
            });

            // Function to update row values
            function updateRow($row) {
                const quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
                const price = parseFloat($row.find('.product-price').val()) || 0;
                const discountPercent = parseFloat($row.find('.discount-percent').val()) || 0;
                const tax = parseFloat($row.find('.product-tax').val()) || 0;
                const profitMargin = parseFloat($row.find('.profit-margin').val()) || 0;

                const subTotal = quantity * price;
                const discountAmount = subTotal * (discountPercent / 100);
                const netCost = subTotal - discountAmount + tax;
                const lineTotal = netCost;

                $row.find('.sub-total').text(subTotal.toFixed(2));
                $row.find('.net-cost').text(netCost.toFixed(2));
                $row.find('.line-total').text(lineTotal.toFixed(2));
                $row.find('.retail-price').text(price.toFixed(2));
                $row.find('.whole-sale-price').text((price * (1 - profitMargin / 100)).toFixed(2));
            }


       }
}


// Function to update footer (total items and net total)
function updateFooter() {
    let totalItems = 0;
    let netTotalAmount = 0;

    $('#purchase_product tbody tr').each(function() {
        totalItems += parseFloat($(this).find('.purchase-quantity').val()) || 0;
        netTotalAmount += parseFloat($(this).find('.line-total').text()) || 0;
    });

    $('#total-items').text(totalItems.toFixed(2));
    $('#net-total-amount').text(netTotalAmount.toFixed(2));
}


$(document).ready(function () {
        // Send AJAX request to fetch purchases data
        $.ajax({
            url: 'get-all-purchases', // API endpoint
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log(response);

            // Check if the response contains purchases data
            if (response && response.purchases) {
                let purchases = response.purchases;
                let tableBody = $('#purchase-table-body'); // Table body selector

                // Clear the table before populating
                tableBody.empty();

                // Loop through each purchase and populate the table
                purchases.forEach(function (purchase) {
                    let purchaseRow = `
                        <tr>
                            <td>
                                <div class="dropdown dropdown-action">
                                    <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <button type="button" class="btn btn-outline-info">
                                            Actions &nbsp;<i class="fas fa-sort-down"></i>
                                        </button>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;View payments</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Purchase Return</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Update Status</a>
                                        <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-envelope"></i>&nbsp;&nbsp;Item Received Notification</a>
                                    </div>
                                </div>
                            </td>
                            <td>${purchase.purchase_date}</td>
                            <td>${purchase.reference_no}</td>
                            <td>${purchase.location_id}</td> <!-- You can replace this with location name if needed -->
                            <td>${purchase.supplier_id}</td> <!-- You can replace this with supplier name if needed -->
                            <td>${purchase.purchasing_status}</td>
                            <td>${purchase.payment_status}</td>
                            <td>${purchase.final_total}</td>
                            <td>${purchase.total - purchase.final_total}</td> <!-- Assuming 'total' - 'final_total' gives the payment due -->
                            <td>${purchase.created_at}</td> <!-- Assuming created_at represents 'Added By' -->
                        </tr>
                    `;

                    // Append the row to the table body
                    tableBody.append(purchaseRow);
                });

                // Initialize or reinitialize the DataTable after adding rows
                $('#purchase-list').DataTable();
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching purchases:', error);
        }
    });
});


</script>
