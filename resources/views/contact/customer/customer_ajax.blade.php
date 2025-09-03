<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        fetchCustomerData();
        fetchCities();

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {

                first_name: {
                    required: true,

                },

                mobile_no: {
                    required: true,

                },

                city_id: {
                    required: true,
                },

                credit_limit: {
                    required: true,
                    number: true,
                },



            },
            messages: {



                first_name: {
                    required: "First Name is required",
                },

                mobile_no: {
                    required: "Mobile No  is required",
                },
                city_id: {
                    required: "City is required",
                },
                credit_limit: {
                    required: "Credit Limit is required",
                    number: "Credit Limit must be a number",
                },


            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                error.insertAfter(element);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to both forms
        $('#addAndUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#addAndUpdateForm').validate().resetForm();
            $('#addAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditCustomerModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Show Add Warranty Modal
        $('#addCustomerButton').click(function() {
            $('#modalTitle').text('New Customer');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditCustomerModal').modal('show');
        });

        function showFetchData() {
            $.ajax({
                url: '/customer-get-all',
                type: 'GET',
                dataType: 'json',
                xhrFields: {
                    withCredentials: true // 🔥 Required
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    var table = $('#customer').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.prefix + '</td>');
                        row.append('<td>' + item.first_name + '</td>');
                        row.append('<td>' + item.last_name + '</td>');
                        row.append('<td>' + item.mobile_no + '</td>');
                        row.append('<td>' + item.email + '</td>');
                        row.append('<td>' + item.city_name + '</td>');
                        row.append('<td>' + item.address + '</td>');
                        row.append('<td>' + item.opening_balance + '</td>');
                        row.append('<td>' + item.credit_limit + '</td>');
                        row.append('<td>' + item.total_sale_due + '</td>');
                        row.append('<td>' + item.total_return_due + '</td>');


                        row.append('<td>' +
                            '@can('edit customer')<button type="button" value="' +
                            item.id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can('delete customer')<button type="button" value="' +
                            item.id +
                            '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                            '</td>');
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        //Fetch cities
        function fetchCities() {
            $.ajax({
                url: '/api/cities',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var citySelect = $('#edit_city_id');
                    citySelect.empty();
                    citySelect.append('<option value="">Select City</option>');

                    if (response.status && response.data) {
                        response.data.forEach(function(city) {
                            citySelect.append('<option value="' + city.id + '">' + city
                                .name + '</option>');
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching cities:', error);
                }
            });
        }


        // Show Edit Modal
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Customer');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'customer-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200 && response.customer) {
                        $('#edit_prefix').val(response.customer.prefix || '').trigger(
                            'change');
                        $('#edit_first_name').val(response.customer.first_name || '');
                        $('#edit_last_name').val(response.customer.last_name || '');
                        $('#edit_mobile_no').val(response.customer.mobile_no || '');
                        $('#edit_email').val(response.customer.email || '');
                        $('#edit_address').val(response.customer.address || '');
                        $('#edit_opening_balance').val(response.customer.opening_balance ||
                            '');
                        $('#edit_credit_limit').val(response.customer.credit_limit || '');
                        $('#edit_city_id').val(response.customer.city_id || '').trigger(
                            'change');
                        $('#addAndEditCustomerModal').modal('show');
                    } else {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Failed to load customer data', 'Error');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#addAndUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'customer-update/' + id : 'customer-store';
            let type = id ? 'post' : 'post';

            $.ajax({
                url: url,
                type: type,
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
                        $('#addAndEditCustomerModal').modal('hide');
                        // Clear validation error messages
                        showFetchData();
                        fetchCustomerData();
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });


        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete customer');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'customer-delete/' + id,
                type: 'delete',
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
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });


        function fetchCustomerData() {
            return $.ajax({
            url: '/customer-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const customerSelect = $('#customer-id');
                customerSelect.empty();

                if (data && data.status === 200 && Array.isArray(data.message)) {
                const sortedCustomers = data.message.sort((a, b) => {
                    if (a.first_name === 'Walk-in') return -1;
                    if (b.first_name === 'Walk-in') return 1;
                    return 0;
                });

                sortedCustomers.forEach(customer => {
                    const option = $('<option></option>');
                    option.val(customer.id);
                    if (customer.first_name === 'Walk-in') {
                    option.text(`${customer.first_name || ''} ${customer.last_name || ''}`);
                    } else {
                    option.text(
                        `${customer.first_name || ''} ${customer.last_name || ''} (${customer.mobile_no || ''})`
                    );
                    }
                    option.data('due', customer.current_due || 0); // Default due to 0
                    customerSelect.append(option);
                });

                // Always select Walking Customer by default
                const walkingCustomer = sortedCustomers.find(customer => customer.first_name === 'Walk-in');
                if (walkingCustomer) {
                    customerSelect.val(walkingCustomer.id);
                    updateDueAmount(walkingCustomer.current_due || 0);
                }
                } else {
                console.error('Failed to fetch customer data:', data ? data.message : 'No data received');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching customer data:', error);
            }
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



        // 🔐 Make only selected functions globally available
        window.customerFunctions = {
            fetchCustomerData: fetchCustomerData,
            // other functions NOT exposed unless added here
        };


    });
</script>
