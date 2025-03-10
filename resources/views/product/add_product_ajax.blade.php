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
                , location_id: {
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
                , location_id: {
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

            }
            , errorElement: 'span',
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
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id
                , type: 'get'
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 200) {
                        // Populate the batch_no options
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append('<option value="' + subCategory.id + '">' + subCategory.subCategoryname + '</option>');
                        });

                    } else {
                        console.log('Error: ', response.message);
                    }
                }
                , error: function(xhr, status, error) {
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
                url: 'product-store'
                , type: 'POST'
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
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });


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

                        if ((brands && brands.length > 0) || (mainCategories && mainCategories.length > 0) || (subCategories && subCategories.length > 0) || (units && units.length > 0) || (locations && locations.length > 0)) {

                            // If there are brands or subcategories, add the default options and populate with data
                            brandSelect.append('<option selected disabled>Product Brand</option>');
                            mainCategorySelect.append('<option selected disabled>Main Category Brand</option>');
                            subCategorySelect.append('<option selected disabled>Sub Category Brand</option>');
                            unitSelect.append('<option selected disabled>Unit</option>');
                            // locationSelect.append('<option selected disabled>Location</option>');

                            brands.forEach(brand => {
                                brandSelect.append(`<option value="${brand.id}">${brand.name}</option>`);
                            });

                            mainCategories.forEach(mainCategory => {
                                mainCategorySelect.append(`<option value="${mainCategory.id}">${mainCategory.mainCategoryName}</option>`);
                            });

                            subCategories.forEach(subCategory => {
                                subCategorySelect.append(`<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`);
                            });

                            units.forEach(unit => {
                                unitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);
                            });

                            locations.forEach(location => {
                                locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                            });
                        } else {
                            // If no records are found, show appropriate message
                            brandSelect.append('<option selected disabled>No brands available</option>');
                            mainCategorySelect.append('<option selected disabled>No main categories available</option>');
                            subCategorySelect.append('<option selected disabled>No main categories available</option>');
                            unitSelect.append('<option selected disabled>No unit available</option>');
                            locationSelect.append('<option selected disabled>No location available</option>');
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
                url: 'product-store'
                , type: 'POST'
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
                url: 'product-store'
                , type: 'POST'
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
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                           // Show the opening stock modal and pass product ID
                           $('#addOpeningStockModal').modal('show');

                    }
                }
            });
        });

    });





</script>
