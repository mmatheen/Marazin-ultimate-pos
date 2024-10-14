<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token

        var addAndUpdateValidationOptions = {
            rules: {

                product_name: {
                    required: true
                , },

                unit_id: {
                    required: true
                , }
                , brand_id: {
                    required: true
                , }
                , main_category_id: {
                    required: true
                , }
                , sub_category_id: {
                    required: true
                , }
                , business_location_id: {
                    required: true
                , }

                , retail_price: {
                    required: true
                , }
                , whole_sale_price: {
                    required: true
                , }
                , special_price: {
                    required: true
                , }
                , original_price: {
                    required: true
                , },

            }
            , messages: {

                product_name: {
                    required: "Product Name is required"
                , }
                , unit_id: {
                    required: "Product Unit  is required"
                , }
                , brand_id: {
                    required: "Product Brand is required"
                , }
                , main_category_id: {
                    required: "Main Category  is required"
                , }
                , sub_category_id: {
                    required: "Sub Category  is required"
                , }
                , business_location_id: {
                    required: "Business Location  is required"
                , }

                , retail_price: {
                    required: "Retail Price is required"
                , }
                , whole_sale_price: {
                    required: "Whole Sale Price is required"
                , }
                , special_price: {
                    required: "Special Price is required"
                , }
                , original_price: {
                    required: "Cost Price is required"
                , },

            },
            errorElement: 'span',
             errorPlacement: function(error, element) {
                // error message show after selectbox
                if (element.is("select")) {
                    error.addClass('text-danger');
                    // Insert the error after the closest parent div for better placement with select
                    error.insertAfter(element.closest('div'));
                }
                // error message show after checkbox
                else if (element.is(":checkbox")) {
                    error.addClass('text-danger');
                    // For checkboxes, place the error after the checkbox's parent container
                    error.insertAfter(element.closest('div').find('label').last());
                }
                // error message show after inputbox
                else {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                }
            },

            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            }
            , unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to forms
        $('#addForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

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
            $('#edit_sub_category_id').empty().append('<option selected disabled>Sub Category</option>');

            $.ajax({
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id,
                type: 'get',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        // Populate the batch_no options
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append('<option value="' + subCategory.id + '">' + subCategory.subCategoryname + '</option>');
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

        // Submit the only product only
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
                headers: {'X-CSRF-TOKEN': csrfToken},
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
                    }
                }
            });
        });


        // Submit the Save And Another product only
        $('#SaveProductButtonAndAnother').click(function(e) {
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
                headers: {'X-CSRF-TOKEN': csrfToken},
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
                        window.location.href = '{{ route("list-product") }}';
                    }
                }
            });
        });


           // Submit the Save & Add Opening Stock only
           $('#openingStockAndProduct').click(function(e) {
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
                headers: {'X-CSRF-TOKEN': csrfToken},
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
                        window.location.href = '{{ route("list-product") }}';
                    }
                }
            });
        });


// Submit product data and open modal for opening stock
$('#openingStockAndProduct').click(function(e) {
    e.preventDefault();

    // Validate product details before opening modal
    let productData = $('#addForm').serialize();

    $.ajax({
        url: '/product-store',
        method: 'POST',
        data: productData,
        success: function(response) {
            if (response.success) {
                // Show the opening stock modal and pass product ID
                $('#addOpeningStockModal').modal('show');
            } else {
                displayValidationErrors(response.errors);
            }
        },
        error: function(xhr) {
            console.log(xhr.responseText);
        }
    });
});

// Submit the opening stock form when the modal is filled
$('#modalButton').click(function(e) {
    e.preventDefault();

    let stockData = {
        location_id: $('#edit_location_id').val(),
        stock_quantity: $('#edit_stock_quantity').val() // Assuming you have stock quantity field
    };

    $.ajax({
        url: '/import-opening-stock-store',
        method: 'POST',
        data: stockData,
        success: function(response) {
            if (response.success) {
                alert('Opening stock added successfully');
                $('#addOpeningStockModal').modal('hide');
            } else {
                // Handle validation errors
                displayValidationErrors(response.errors);
            }
        },
        error: function(xhr) {
            console.log(xhr.responseText);
        }
    });
});

});

// Function to display validation errors
function displayValidationErrors(errors) {
for (let field in errors) {
    $('#' + field + '_error').text(errors[field][0]);
}
}

</script>


<script>
    $(document).ready(function() {

        $('#openingStockAndProduct').on('click', function(e) {
            e.preventDefault();

            // Clear any previous error messages
            $('.text-danger').text('');

            // Perform form validation
            var isValid = true;

            var productName = $('#edit_product_name').val();
            var sku = $('#edit_sku').val();
            var originalPrice = $('#edit_original_price').val();
            var retailPrice = $('#edit_retail_price').val();

            // Validate product name
            if (productName === '') {
                $('#product_name_error').text('Product name is required');
                isValid = false;
            }



            // Validate original price
            if (originalPrice === '') {
                $('#original_price_error').text('Cost price is required');
                isValid = false;
            }

            // Validate retail price
            if (retailPrice === '') {
                $('#retail_price_error').text('Retail price is required');
                isValid = false;
            }

            // Check if the form is valid
            if (isValid) {

                $('#addForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#addForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true
                    , "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let url = 'product-store';
            let type = 'post';

            $.ajax({
                url: url
                , type: type
                , headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
                , data: formData
                , contentType: false
                , processData: false
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });

                    } else {
                        // Clear validation error messages
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });

                // Open the modal if validation passes
                $('#addOpeningStockModal').modal('show');
            } else {
                // Scroll to the top if there are errors
                $('html, body').animate({
                    scrollTop: $(".text-danger:visible").first().offset().top
                }, 500);
            }
        });
    });

</script>


{{--
<script>
    $(document).ready(function() {
    // Event listener for "Save" button to add product only
    $('#saveOnlyProductButton').on('click', function(e) {
        e.preventDefault(); // Prevent form submission

        // Collect product data
        var productData = {
            product_name: $('#edit_product_name').val(),
            business_location_id: $('#edit_business_location_id').val(),
            stock_alert: $('#edit_stock_alert').is(':checked') ? 1 : 0,
            alert_quantity: $('#edit_alert_quantity').val(),
            original_price: $('#edit_original_price').val(),
            retail_price: $('#edit_retail_price').val(),
            whole_sale_price: $('#edit_whole_sale_price').val(),
            special_price: $('#edit_special_price').val()
        };

        // Perform AJAX request to save the product
        $.ajax({
            url: '/save-product', // Define your route for saving product
            type: 'POST',
            data: productData,
            success: function(response) {
                if(response.success) {
                    // Handle success, e.g., show a notification, reset the form
                    alert('Product saved successfully');
                } else {
                    // Handle validation errors or other errors
                    alert('Error saving product');
                }
            },
            error: function(err) {
                console.log(err);
            }
        });
    });

    // Event listener for "Save & Add Opening Stock" button
    $('#openingStockAndProduct').on('click', function(e) {
        e.preventDefault(); // Prevent form submission

        // Collect product data (same as above)
        var productData = {
            product_name: $('#edit_product_name').val(),
            business_location_id: $('#edit_business_location_id').val(),
            stock_alert: $('#edit_stock_alert').is(':checked') ? 1 : 0,
            alert_quantity: $('#edit_alert_quantity').val(),
            original_price: $('#edit_original_price').val(),
            retail_price: $('#edit_retail_price').val(),
            whole_sale_price: $('#edit_whole_sale_price').val(),
            special_price: $('#edit_special_price').val()
        };

        // Perform AJAX request to save the product
        $.ajax({
            url: '/save-product', // Define your route for saving product
            type: 'POST',
            data: productData,
            success: function(response) {
                if(response.success) {
                    // After saving product, open the modal to add opening stock
                    $('#addOpeningStockModal').modal('show');
                    $('#modalTitle').text('Add Opening Stock for Product');

                    // Save product ID in the modal for later use
                    $('#edit_id').val(response.product_id);
                } else {
                    // Handle validation errors or other errors
                    alert('Error saving product');
                }
            },
            error: function(err) {
                console.log(err);
            }
        });
    });

    // Event listener for saving opening stock in the modal
    $('#modalButton').on('click', function(e) {
        e.preventDefault(); // Prevent form submission

        // Collect opening stock data
        var openingStockData = {
            product_id: $('#edit_id').val(),
            location_id: $('#edit_location_id').val(),
            // Add other stock data fields as needed
        };

        // Perform AJAX request to save the opening stock
        $.ajax({
            url: '/save-opening-stock', // Define your route for saving opening stock
            type: 'POST',
            data: openingStockData,
            success: function(response) {
                if(response.success) {
                    // Handle success, e.g., close the modal and show a notification
                    $('#addOpeningStockModal').modal('hide');
                    alert('Opening stock saved successfully');
                } else {
                    // Handle validation errors or other errors
                    alert('Error saving opening stock');
                }
            },
            error: function(err) {
                console.log(err);
            }
        });
    });
});

</script> --}}




