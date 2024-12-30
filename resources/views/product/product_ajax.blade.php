<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="productDetails">
                <!-- Modal content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token

        var addAndUpdateValidationOptions = {
            rules: {

                product_name: {
                    required: true,
                },

                unit_id: {
                    required: true,
                },
                brand_id: {
                    required: true,
                },
                main_category_id: {
                    required: true,
                },
                sub_category_id: {
                    required: true,
                },
                location_id: {
                    required: true,
                }

                ,
                retail_price: {
                    required: true,
                },
                whole_sale_price: {
                    required: true,
                },
                special_price: {
                    required: true,
                },
                original_price: {
                    required: true,
                },

            },
            messages: {

                product_name: {
                    required: "Product Name is required",
                },
                unit_id: {
                    required: "Product Unit  is required",
                },
                brand_id: {
                    required: "Product Brand is required",
                },
                main_category_id: {
                    required: "Main Category  is required",
                },
                sub_category_id: {
                    required: "Sub Category  is required",
                },
                location_id: {
                    required: "Business Location  is required",
                },
                retail_price: {
                    required: "Retail Price is required",
                },
                whole_sale_price: {
                    required: "Whole Sale Price is required",
                },
                special_price: {
                    required: "Special Price is required",
                },
                original_price: {
                    required: "Cost Price is required",
                },

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
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to forms
        $('#addForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end


        $(".show-picture").on("change", function() {
    const input = this;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        if (file.type === "application/pdf") {
            reader.onload = function(e) {
                $("#pdfViewer").attr("src", e.target.result);
                $("#pdfViewer").show();
                $("#selectedImage").hide();
            };
        } else if (file.type.startsWith("image/")) {
            reader.onload = function(e) {
                $("#selectedImage").attr("src", e.target.result);
                $("#selectedImage").show();
                $("#pdfViewer").hide();
            };
        }

        reader.readAsDataURL(file);
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
                        document.getElementsByClassName('successSound')[0]
                            .play(); // for sound
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
        // Fetch main category, sub category, location, unit, brand details to select box code start



        // function populateBrandDropdown(brands) {
        //     const brandSelect = $('#edit_brand_id');
        //     brandSelect.empty(); // Clear existing options

        //     if (brands.length > 0) {
        //         // If there are brands, add the default option and populate with brand options
        //         brandSelect.append('<option selected disabled>Product Brand</option>');
        //         brands.forEach(brand => {
        //             brandSelect.append(`<option value="${brand.id}">${brand.name}</option>`);
        //         });
        //     } else {
        //         // If no brands are found, add a single disabled option indicating no records
        //         brandSelect.append('<option selected disabled>No brands available</option>');
        //     }
        // }


 });

    $(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content'); // for CSRF token

    $('#summernote').summernote({
        placeholder: 'Enter your description...',
        tabsize: 2,
        height: 40
    });

    // Submit the Save & Add Opening Stock only
    $('#openingStockAndProduct').click(function(e) {
        e.preventDefault(); // Prevent default form submission

        // Gather the form data
        let form = $('#addForm')[0];
        let formData = new FormData(form);

        // Add Summernote content to form data
        formData.append('description', $('#summernote').val());

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

                    // Redirect to the opening stock page
                    window.location.href = `/opening-stock/${response.product_id}`;
                }
            },
            error: function(xhr) {
                toastr.error('Failed to add product. Please try again.', 'Error');
            }
        });
    });

    // Function to reset form and validation
    function resetFormAndValidation() {
        $('#addForm')[0].reset();
        $('#addForm').validate().resetForm();
        $('#summernote').summernote('reset');
    }
    $(document).ready(function() {
    $('#submitOpeningStock').click(function(e) {
        e.preventDefault();

        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let form = $('#openingStockForm')[0];
        let formData = new FormData(form);

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

});




    });


    let categoryMap = {};
    let brandMap = {};
    let locationMap = {};

    // Fetch categories, brands, and locations on page load
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

   function showFetchData() {
    // Fetch data from the single API and combine it
    $.ajax({
        url: '/all-stock-details',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 200 && Array.isArray(response.stocks)) {
            const stocks = response.stocks;
            let table = $('#productTable').DataTable();
            table.clear().draw();

            // Loop through all products and combine stock data if available
            stocks.forEach(stock => {
                const product = stock.products;
                const totalQuantity = stock.locations.reduce((sum, location) => sum + parseInt(location.total_quantity, 10), 0);

                const row = $('<tr>');
                row.append('<td><input type="checkbox" class="checked" /></td>');
                row.append('<td><img src="/assets/images/' + product.product_image + '" alt="' + product.product_name + '" width="50" height="70" /></td>');
                row.append(`
                    <td>
                        <div class="dropdown dropdown-action">
                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewProductModal" data-id="${product.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                <a class="dropdown-item edit-product" href="/edit-product/${product.id}" data-id="${product.id}">
                                    <i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit
                                </a>
                                <a class="dropdown-item delete_btn" data-id="${product.id}"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                <a class="dropdown-item" href="#"><i class="fas fa-database"></i>&nbsp;&nbsp;Add or edit opening stock</a>
                                <a class="dropdown-item" href="#"><i class="fas fa-history"></i>&nbsp;&nbsp;Product stock history</a>
                                <a class="dropdown-item" href="#"><i class="far fa-copy"></i>&nbsp;&nbsp;Duplicate Product</a>
                            </div>
                        </div>
                    </td>`);

                const locationName = stock.locations.length > 0 ? stock.locations[0].location_name : 'N/A';

                // Add product details
                row.append('<td>' + product.product_name + '</td>');
                row.append('<td>' + locationName + '</td>');
                row.append('<td>Rs ' + product.retail_price.toFixed(2) + '</td>');
                row.append('<td>' + totalQuantity + '</td>'); // Use combined quantity
                row.append('<td>' + product.product_type + '</td>');
                row.append('<td>' + (product.main_category_id || 'N/A') + '</td>');
                row.append('<td>' + (product.brand_id || 'N/A') + '</td>');
                row.append('<td>' + product.sku + '</td>');

                table.row.add(row).draw(false);
            });
        } else {
            console.error('Invalid product or stock data:', response);
        }
    }).fail(function(xhr, status, error) {
        console.error('Error fetching product or stock data:', error);
    });
   }


    // $(document).on('click', '.edit-product', function(event) {
    //     event.preventDefault();
    //     const productId = $(this).data('id');
    //     window.location.href = `/edit-product/${productId}`; // Navigate to the edit page
    // });
    $(document).ready(function() {
        // Fetch product details and populate the form when the page is loaded
        const productId = window.location.pathname.split('/').pop();

        if (!productId) return; // Stop if no product ID is found

        // Fetch product details and populate the form
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

                    console.log(response.message);

                    // Populate form fields with product data
                    populateProductDetails(product, mainCategories, subCategories, brands, units,
                        locations);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while fetching product details.');
            }
        });

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

            // Set checkboxes
            $('#Enable_Product_description').prop('checked', product.is_imei_or_serial_no === 1);
            $('#Not_for_selling').prop('checked', product.is_for_selling === "1");


            if (product.product_image) {
                const imagePath = `/assets/images/${product.product_image}`;
                $('#selectedImage').attr('src', imagePath).sho            }

            const locationIds = product.locations.map(location => location.id);
            $('#edit_location_id').val(locationIds).trigger('change');


            populateDropdowns(mainCategories, subCategories, brands, units, locations, product);


            $('#edit_main_category_id1').val(product.main_category_id).trigger('change');
            setTimeout(() => {

                populateSubCategories(product.main_category_id, subCategories);


                $('#edit_sub_category_id1').val(product.sub_category_id).trigger('change');
            }, 300);
        }

        function populateDropdowns(mainCategories, subCategories, brands, units, locations, product) {
            // Clear existing options
            const mainCategorySelect = $('#edit_main_category_id1').empty();
            const subCategorySelect = $('#edit_sub_category_id1').empty();
            const brandSelect = $('#edit_brand_id1').empty();
            const unitSelect = $('#edit_unit_id1').empty();

            // Add default options
            mainCategorySelect.append('<option selected disabled>Main Category</option>');
            subCategorySelect.append('<option selected disabled>Sub Category</option>');
            brandSelect.append('<option selected disabled>Product Brand</option>');
            unitSelect.append('<option selected disabled>Unit</option>');

            // Populate main categories
            mainCategories.forEach(function(category) {
                mainCategorySelect.append(new Option(category.mainCategoryName, category.id));
            });

            // Populate brands
            brands.forEach(function(brand) {
                brandSelect.append(new Option(brand.name, brand.id));
            });
            brandSelect.val(product.brand_id).trigger('change');

            // Populate units
            units.forEach(function(unit) {
                unitSelect.append(new Option(unit.name, unit.id));
            });
            unitSelect.val(product.unit_id).trigger('change');

            // Handle main category change dynamically
            $('#edit_main_category_id1').change(function() {
                const selectedMainCategoryId = $(this).val();
                populateSubCategories(selectedMainCategoryId, subCategories);
            });
        }

        function populateSubCategories(selectedMainCategoryId, subCategories) {
            const subCategorySelect = $('#edit_sub_category_id1').empty();
            subCategorySelect.append('<option selected disabled>Sub Category</option>');

            // Filter and add subcategories for the selected main category
            subCategories
                .filter(subCategory => subCategory.main_category_id == selectedMainCategoryId)
                .forEach(subCategory => {
                    subCategorySelect.append(new Option(subCategory.subCategoryname, subCategory.id));
                });

            // Trigger change to reflect updated subcategories
            subCategorySelect.trigger('change');
        }

    });



    $('#onlyUpdateProductButton').click(function(e) {
        e.preventDefault(); // Prevent default form submission

        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let productId = $('#product_id').val(); // Hidden input for product ID
        let form = $('#UpdateForm')[0];
        let formData = new FormData(form);

        // Log FormData
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // Validate form before submitting
        if (!$('#UpdateForm').valid()) {
            document.getElementsByClassName('warningSound')[0]?.play(); // Play warning sound if available
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
                        $('#' + key + '_error').html(err_value || ''); // Populate errors
                    });
                } else if (response.status === 200) {
                    document.getElementsByClassName('successSound')[0]
                        ?.play(); // Play success sound
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

    function resetFormAndValidation() {
        $('#UpdateForm')[0].reset(); // Reset the form
        $('.error').html(''); // Clear error messages
    }



    $(document).on('click', '.delete_btn', function() {
        var id = $(this).data('id');
        $('#deleteModal').modal('show');
        $('#deleting_id').val(id);
        $('#deleteName').text('Delete Product');
    });

    $(document).on('click', '.confirm_delete_btn', function() {
        var id = $('#deleting_id').val();
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        $.ajax({
            url: '/delete-product/' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.status == 404) {
                    toastr.options = {
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    };
                    toastr.error(response.message, 'Error');
                } else {
                    $('#deleteModal').modal('hide');
                    showFetchData();
                    document.getElementsByClassName('successSound')[0].play();
                    toastr.options = {
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    };
                    toastr.success(response.message, 'Deleted');
                }
            }
        });
    });




    // Fetch and show product details in the modal
    $('#viewProductModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var productId = button.data('id'); // Extract product ID from data-id attribute

        // Fetch product details by ID
        $.ajax({
            url: '/product-get-details/' + productId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 200) {
                    var product = response.message;
                    var details = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td rowspan="8" class="text-center align-middle">
                                        <img src='/assets/images/${product.product_image}' width='150' height='200' class="rounded img-fluid" />
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

    // Load data when the page is ready
    $(document).ready(function() {
        fetchCategoriesAndBrands();
        showFetchData();
    });
</script>
