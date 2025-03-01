<script>
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); // For CSRF token
        fetchSalesData();
        // Initialize DataTable
        const table = $('#salesTable').DataTable();

        var saleValidationOptions = {
            rules: {
                customer_id: {
                    required: true
                },
                location_id: {
                    required: true
                },
                sales_date: {
                    required: true
                },
                status: {
                    required: true
                },

            },
            messages: {
                customer_id: {
                    required: "Customer is required"
                },
                location_id: {
                    required: "Location is required"
                },
                sales_date: {
                    required: "Sales Date is required"
                },
                status: {
                    required: "Status is required"
                },

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
        $('#addSalesForm').validate(saleValidationOptions);

        // Show Image Preview
        $(".show-file").on("change", function() {
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

        function fetchSalesData() {
            $.ajax({
                url: '/sales',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    table.clear().draw();
                    if (response.sales && Array.isArray(response.sales)) {
                        var counter = 1;
                        response.sales.forEach(function(item) {
                            if (item.sale_type === 'Normal') { // Add this condition
                                let row = $('<tr>');
                                row.append('<td>' +
                                    '<div class="btn-group">' +
                                    '<button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    '<i class="feather-menu"></i> Actions' +
                                    '</button>' +
                                    '<ul class="dropdown-menu">' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="view-details dropdown-item"><i class="feather-eye text-info"></i> View</button></li>' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="edit_btn dropdown-item"><i class="feather-edit text-info"></i> Edit</button></li>' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="delete_btn dropdown-item"><i class="feather-trash-2 text-danger"></i> Delete</button></li>' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="add-payment dropdown-item"><i class="feather-dollar-sign text-success"></i> Add Payment</button></li>' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="view-payments dropdown-item"><i class="feather-list text-primary"></i> View Payments</button></li>' +
                                    '<li><button type="button" value="' + item.id +
                                    '" class="sell-return dropdown-item"><i class="feather-rotate-ccw text-warning"></i> Sell Return</button></li>' +
                                    '</ul>' +
                                    '</div>' +
                                    '</td>');
                                row.append('<td>' + item.sales_date + '</td>');
                                row.append('<td>' + item.invoice_no + '</td>');
                                row.append('<td>' + item.customer.first_name + ' ' + item
                                    .customer.last_name + '</td>');
                                row.append('<td>' + item.customer.mobile_no + '</td>');
                                row.append('<td>' + item.location.name + '</td>');
                                row.append('<td>' + item.payment_status + '</td>');
                                row.append('<td>' + (item.payments && item.payments[0] ?
                                        item.payments[0].payment_method : 'N/A') +
                                    '</td>');
                                row.append('<td>' + item.final_total + '</td>');
                                row.append('<td>' + item.total_paid + '</td>');
                                row.append('<td>' + item.total_due + '</td>');
                                row.append('<td>' + item.status + '</td>');
                                row.append('<td>' + item.products.length + '</td>');
                                row.append('<td>' + 'Added By User' +
                                '</td>'); // Replace with actual user data if available

                                table.row.add(row).draw(false);
                                counter++;
                            }
                        });
                    } else {
                        console.error('Sales data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales data:', error);
                }
            });
        }


// Show the modal when the button is clicked
$('#bulkPaymentBtn').click(function() {
    $('#bulkPaymentModal').modal('show');
});

// Fetch customers and populate the dropdown
$.ajax({
    url: '/customer-get-all',
    type: 'GET',
    dataType: 'json',
    success: function(response) {
        var customerSelect = $('#customerSelect');
        customerSelect.empty();
        customerSelect.append('<option value="" selected disabled>Select Customer</option>');
        if (response.message && response.message.length > 0) {
            response.message.forEach(function(customer) {
                customerSelect.append(
                    '<option value="' + customer.id +
                    '" data-opening-balance="' + customer.opening_balance + '">' +
                    customer.first_name + ' ' + customer.last_name + '</option>'
                );
            });
        } else {
            console.error("No customers found or response.message is undefined.");
        }
    },
    error: function(xhr, status, error) {
        console.error("AJAX error: ", status, error);
    }
});

let originalOpeningBalance = 0; // Store the actual customer opening balance
$('#customerSelect').change(function() {
    var customerId = $(this).val();
    originalOpeningBalance = parseFloat($(this).find(':selected').data('opening-balance')) || 0;

    $('#openingBalance').text(originalOpeningBalance.toFixed(2)); // Display initial balance

    $.ajax({
        url: '/sales',
        type: 'GET',
        dataType: 'json',
        data: { customer_id: customerId }, // Ensure customer_id is sent
        success: function(response) {
            var salesTable = $('#salesList').DataTable();
            salesTable.clear(); // Clear the table before adding new data
            var totalSalesAmount = 0, totalPaidAmount = 0, totalDueAmount = 0;

            if (response.sales && response.sales.length > 0) {
                response.sales.forEach(function(sale) {
                    if (sale.customer_id == customerId) { // Filter by customer ID
                        var finalTotal = parseFloat(sale.final_total) || 0;
                        var totalPaid = parseFloat(sale.total_paid) || 0;
                        var totalDue = parseFloat(sale.total_due) || 0;

                        if (totalDue > 0) {
                            totalSalesAmount += finalTotal;
                            totalPaidAmount += totalPaid;
                            totalDueAmount += totalDue;

                            salesTable.row.add([
                                sale.id + " (" + sale.invoice_no + ")",
                                finalTotal.toFixed(2),
                                totalPaid.toFixed(2),
                                totalDue.toFixed(2),
                                '<input type="number" class="form-control reference-amount" data-reference-id="' + sale.id + '">'
                            ]).draw();
                        }
                    }
                });
            } else {
                salesTable.row.add([
                    { className: 'text-center', colspan: 5, text: 'No records found' }
                ]).draw();
            }

            $('#totalSalesAmount').text(totalSalesAmount.toFixed(2));
            $('#totalPaidAmount').text(totalPaidAmount.toFixed(2));
            $('#totalDueAmount').text(totalDueAmount.toFixed(2));
        },
        error: function(xhr, status, error) {
            console.error("AJAX error: ", status, error);
        }
    });
});

$('#globalPaymentAmount').on('input', function() {
    var globalAmount = parseFloat($(this).val()) || 0;
    var customerOpeningBalance = originalOpeningBalance; // Always use original balance
    var totalDueAmount = parseFloat($('#totalDueAmount').text()) || 0;
    var remainingAmount = globalAmount;

    // Validate global amount
    if (globalAmount > (customerOpeningBalance + totalDueAmount)) {
        $(this).addClass('is-invalid').after('<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>');
        return;
    } else {
        $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
    }

    // Deduct from opening balance first
    let newOpeningBalance = customerOpeningBalance;
    if (newOpeningBalance > 0) {
        if (remainingAmount <= newOpeningBalance) {
            newOpeningBalance -= remainingAmount;
            remainingAmount = 0;
        } else {
            remainingAmount -= newOpeningBalance;
            newOpeningBalance = 0;
        }
    }
    $('#openingBalance').text(newOpeningBalance.toFixed(2));

    // Apply the remaining amount to the sales dynamically
    $('.reference-amount').each(function() {
        var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
        if (remainingAmount > 0) {
            var paymentAmount = Math.min(remainingAmount, referenceDue);
            $(this).val(paymentAmount);
            remainingAmount -= paymentAmount;
        } else {
            $(this).val(0);
        }
    });
});

// Validate individual payment amounts
$(document).on('input', '.reference-amount', function() {
    var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
    var paymentAmount = parseFloat($(this).val());
    if (paymentAmount > referenceDue) {
        $(this).addClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
        $(this).after('<span class="invalid-feedback d-block">Amount exceeds total due.</span>');
    } else {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    }
});

// Function to update the opening balance
function updateOpeningBalance() {
    var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
    var customerOpeningBalance = parseFloat($('#customerSelect').find(':selected').data('opening-balance')) || 0;
    var totalPayment = 0;

    // Calculate the total payment from individual amounts
    $('.reference-amount').each(function() {
        totalPayment += parseFloat($(this).val()) || 0;
    });

    var remainingAmount = globalAmount - totalPayment;

    // Adjust the opening balance based on the remaining amount
    if (remainingAmount >= 0) {
        $('#openingBalance').text((customerOpeningBalance - remainingAmount).toFixed(2));
    } else {
        $('#openingBalance').text("0.00");
    }
}

// Handle global payment amount input
$('#globalPaymentAmount').change(function() {
    updateOpeningBalance();
});

// Initialize DataTable
$(document).ready(function() {
    $('#salesTable').DataTable();
});

// Handle payment submission
$('#submitBulkPayment').click(function() {
    var customerId = $('#customerSelect').val();
    var paymentMethod = $('#paymentMethod').val();
    var paymentDate = $('#paidOn').val();
    var globalPaymentAmount = $('#globalPaymentAmount').val();
    var salePayments = [];


    $('.reference-amount').each(function() {
        var referenceId = $(this).data('reference-id');
        var paymentAmount = parseFloat($(this).val());
        if (paymentAmount > 0) {
            salePayments.push({
                reference_id: referenceId,
                amount: paymentAmount
            });
        }
    });

    var paymentData = {
        entity_type: 'customer',
        entity_id: customerId,
        payment_method: paymentMethod,
        payment_date: paymentDate,
        global_amount: globalPaymentAmount,
        payments: salePayments
    };

    $.ajax({
        url: '/api/submit-bulk-payment',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(paymentData),
        success: function(response) {
            toastr.success(response.message, 'Payment Submitted');
            $('#bulkPaymentModal').modal('hide');
            $('#bulkPaymentForm')[0].reset(); // Reset the form
            fetchSalesData()
        },
        error: function(xhr, status, error) {
            console.error("AJAX error: ", status, error);
            alert('Failed to submit payment.');
        }
    });
});



        // Event handler for view details button
        $('#salesTable tbody').on('click', 'button.view-details', function() {
            var saleId = $(this).val();
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;
                        const location = saleDetails.location;
                        const products = saleDetails.products;

                        // Populate modal fields
                        $('#modalTitle').text('Sale Details - Invoice No: ' + saleDetails
                            .invoice_no);
                        $('#customerDetails').text(customer.first_name + ' ' + customer
                            .last_name);
                        $('#locationDetails').text(location.name);
                        $('#salesDetails').text('Date: ' + saleDetails.sales_date +
                            ', Status: ' + saleDetails.status);

                        // Populate products table
                        const productsTableBody = $('#productsTable tbody');
                        productsTableBody.empty();
                        if (products && Array.isArray(products)) {
                            products.forEach((product, index) => {
                                const productRow = $('<tr>');
                                productRow.append('<td>' + (index + 1) + '</td>');
                                productRow.append('<td>' + product.product
                                    .product_name + '</td>');
                                productRow.append('<td>' + product.product.sku +
                                    '</td>');
                                productRow.append('<td>' + product.quantity +
                                    '</td>');
                                productRow.append('<td>' + product.price + '</td>');
                                productRow.append('<td>' + (product.quantity *
                                    product.price).toFixed(2) + '</td>');
                                productsTableBody.append(productRow);
                            });
                        }

                        // Populate payment info table
                        const paymentInfoTableBody = $('#paymentInfoTable tbody');
                        paymentInfoTableBody.empty();
                        if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                            saleDetails.payments.forEach((payment) => {
                                const paymentRow = $('<tr>');
                                paymentRow.append('<td>' + payment.payment_date +
                                    '</td>');
                                paymentRow.append('<td>' + payment.reference_no +
                                    '</td>');
                                paymentRow.append('<td>' + payment.amount +
                                    '</td>');
                                paymentRow.append('<td>' + payment.payment_method +
                                    '</td>');
                                paymentRow.append('<td>' + payment.notes + '</td>');
                                paymentInfoTableBody.append(paymentRow);
                            });
                        }

                        // Populate amount details table
                        const amountDetailsTableBody = $('#amountDetailsTable tbody');
                        amountDetailsTableBody.empty();
                        amountDetailsTableBody.append('<tr><td>Total Amount</td><td>' +
                            saleDetails.final_total + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Paid Amount</td><td>' +
                            saleDetails.total_paid + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Due Amount</td><td>' +
                            saleDetails.total_due + '</td></tr>');

                        $('#saleDetailsModal').modal('show');
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        // Event handler for add payment button
        $('#salesTable tbody').on('click', 'button.add-payment', function() {
            var saleId = $(this).val();
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;
                        const location = saleDetails.location;

                        // Populate payment modal fields
                        $('#paymentModalLabel').text('Add Payment - Invoice No: ' +
                            saleDetails.invoice_no);
                        $('#paymentCustomerDetail').text(customer.first_name + ' ' +
                            customer.last_name);
                        $('#paymentLocationDetails').text(location.name);
                        $('#totalAmount').text(saleDetails.final_total);
                        $('#totalPaidAmount').text(saleDetails.total_paid);

                        $('#saleId').val(saleDetails.id);
                        $('#payment_type').val('sale');
                        $('#customer_id').val(customer.id);
                        $('#reference_no').val(saleDetails.invoice_no);
                        // Set default date to today
                        $('#paidOn').val(new Date().toISOString().split('T')[0]);

                        // Set the amount field to the total due amount
                        $('#payAmount').val(saleDetails.total_due);

                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(saleDetails.total_due);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });

                        $('#paymentModal').modal('show');
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        // Event handler for view payments button
        $('#salesTable tbody').on('click', 'button.view-payments', function(event) {
            event.preventDefault();
            var saleId = $(this).val();
            $('#viewPaymentModal').data('sale-id', saleId);
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;

                        // Populate view payments modal fields
                        $('#viewPaymentModalLabel').text('View Payments ( Reference No: ' +
                            saleDetails.invoice_no + ' )');
                        $('#viewCustomerDetail').text(customer.first_name + ' ' + customer
                            .last_name);
                        $('#viewBusinessDetail').text(saleDetails.location.name);
                        $('#viewReferenceNo').text(saleDetails.invoice_no);
                        $('#viewDate').text(saleDetails.sales_date);
                        $('#viewPurchaseStatus').text(saleDetails.status);
                        $('#viewPaymentStatus').text(saleDetails.payment_status);

                        const paymentsTableBody = $('#viewPaymentModal table tbody');
                        paymentsTableBody.empty();
                        if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                            saleDetails.payments.forEach((payment) => {
                                const paymentRow = $('<tr>');
                                paymentRow.append('<td>' + payment.payment_date +
                                    '</td>');
                                paymentRow.append('<td>' + payment.reference_no +
                                    '</td>');
                                paymentRow.append('<td>' + payment.amount +
                                    '</td>');
                                paymentRow.append('<td>' + payment.payment_method +
                                    '</td>');
                                paymentRow.append('<td>' + payment.notes + '</td>');
                                paymentRow.append('<td>' + 'Account Name' +
                                    '</td>'); // Replace with actual account name
                                paymentRow.append(
                                    '<td><button type="button" value="' +
                                    payment.id +
                                    '" class="btn btn-outline-warning btn-sm edit-payment"><i class="feather-edit text-warning me-1"></i>Edit</button></td>'
                                );
                                paymentRow.append(
                                    '<td><button type="button" value="' +
                                    payment.id +
                                    '" class="btn btn-outline-danger btn-sm delete-payment"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>'
                                );
                                paymentsTableBody.append(paymentRow);
                            });
                        } else {
                            paymentsTableBody.append(
                                '<tr><td colspan="7" class="text-center">No records found</td></tr>'
                            );
                        }

                        $('#viewPaymentModal').modal('show');
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        // Event handler for delete payment button
        $(document).on('click', 'button.delete-payment', function() {
            var paymentId = $(this).val();
            if (confirm('Are you sure you want to delete this payment?')) {
                $.ajax({
                    url: '/payments/' + paymentId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        toastr.success('Payment deleted successfully.', 'Deleted');
                        $('#viewPaymentModal').modal('hide');
                        fetchPurchases();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }
        });

        // Event handler for edit payment button
        $(document).on('click', 'button.edit-payment', function() {
            var paymentId = $(this).val();
            $.ajax({
                url: '/payments/' + paymentId,
                type: 'GET',
                success: function(response) {
                    if (response.data) {
                        const payment = response.data;
                        const customer = payment.customer || {};
                        const location = payment.location || {};

                        // Populate edit payment modal fields
                        $('#paymentModalLabel').text('Edit Payment - Reference No: ' + (
                            payment.reference_no || 'N/A'));
                        $('#paymentCustomerDetail').text((customer.first_name || 'N/A') +
                            ' ' + (customer.last_name || ''));
                        $('#paymentLocationDetails').text(location.name || 'N/A');
                        $('#totalAmount').text(payment.final_total || 'N/A');
                        $('#totalPaidAmount').text(payment.total_paid || 'N/A');

                        $('#saleId').val(payment.reference_id);
                        $('#payment_type').val(payment.payment_type);
                        $('#customer_id').val(payment.customer_id);
                        $('#reference_no').val(payment.reference_no);
                        $('#paidOn').val(payment.payment_date);
                        $('#payAmount').val(payment.amount);
                        $('#paymentNotes').val(payment.notes);

                        // Ensure the Edit Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(payment.total_due || 0);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });

                        $('#paymentModal').modal('show');
                    } else {
                        console.error('Payment data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching payment details:', error);
                }
            });
        });

        // Function to print the modal content
        window.printModal = function() {
            var printContents = document.getElementById('saleDetailsModal').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            // location.reload();  // Reload the page to restore the original content and bindings
        };

        $('#savePayment').click(function() {
            var formData = new FormData($('#paymentForm')[0]);

            $.ajax({
                url: '/api/payments',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#paymentModal').modal('hide');
                    document.getElementsByClassName('successSound')[0].play();
                    toastr.success(response.message, 'Payment Added');
                    fetchSalesData();
                },
                error: function(xhr, status, error) {
                    console.error('Error adding payment:', error);
                }
            });
        });




        $('#addSalesForm').on('submit', function(event) {
            event.preventDefault();

            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Processing...');

            const formData = new FormData(this);
            formData.set('sales_date', convertDateFormat($('#sales_date').val()));

            $('#addSaleProduct tbody tr').each(function(index) {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat($(this).find('.price-input').val()) || 0;
                const discount = parseFloat($(this).find('.discount-percent').val()) || 0;
                const tax = parseFloat($(this).find('.product-tax').val()) || 0;
                const priceType = $(this).find('.price-type').val() || 'retail';
                const subtotal = (quantity * unitPrice) - discount + tax;

                formData.append(`products[${index}][product_id]`, $(this).data('id'));
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][unit_price]`, unitPrice);
                formData.append(`products[${index}][discount]`, discount);
                formData.append(`products[${index}][tax]`, tax);
                formData.append(`products[${index}][subtotal]`, subtotal);
                formData.append(`products[${index}][batch_id]`, $(this).find('.batch-dropdown')
                    .val());
                formData.append(`products[${index}][price_type]`, priceType);
            });

            if (!$('#addSalesForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                submitButton.prop('disabled', false).text('Save');
                return;
            }

            const saleId = $('#sale_id').val();
            const url = saleId ? `/sales/update/${saleId}` : '/sales/store';

            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    submitButton.prop('disabled', false).text('Save');
                    if (response.message) {
                        toastr.success(response.message, 'Success');
                        resetFormAndValidation();
                        window.location.href = '/list-sale';
                        if (response.invoice_html) {
                            $('#invoiceContainer').html(response.invoice_html);
                        }
                    } else {
                        toastr.error('Failed to add sale.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    submitButton.prop('disabled', false).text('Save');
                    console.error('Error adding sale:', error);
                    toastr.error('Something went wrong while adding the sale.', 'Error');
                }
            });
        });


        // Function to convert date format from DD-MM-YYYY to YYYY-MM-DD
        function convertDateFormat(dateStr) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`;
        }


            // Fetch locations using AJAX
        $.ajax({
            url: '/location-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Location Data:', data); // Log location data
                if (data.status === 200) {
                    const locationSelect = $('#location');
                    locationSelect.html(
                        '<option selected disabled>Please Select Locations</option>');

                    data.message.forEach(function(location) {
                        const option = $('<option></option>').val(location.id).text(location.name);
                        // Check if the location ID matches the user's location ID and set it as selected
                        if (location.id === data.user_id) {
                            option.attr('selected', 'selected');
                        }
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
             // Fetch customers using AJAX
// Fetch customers using AJAX
$.ajax({
    url: '/customer-get-all',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        console.log('Customer Data:', data); // Log customer data
        if (data.status === 200) {
            const customerSelect = $('#customer-id');
            customerSelect.html('<option selected disabled>Customer</option>');

            data.message.forEach(function(customer) {
                const option = $('<option></option>')
                    .val(customer.id)
                    .text(
                        `${customer.first_name} ${customer.last_name} (ID: ${customer.id})`
                    )
                    .data('details', customer);
                // Check if the customer is the "Walking Customer" and set it as selected
                if (customer.first_name === "Walking" && customer.last_name === "Customer") {
                    option.attr('selected', 'selected');
                }
                customerSelect.append(option);
            });

            // Trigger change event to display details of the default selected customer (Walking Customer)
            customerSelect.trigger('change');
        } else {
            console.error('Failed to fetch customer data:', data.message);
        }
    },
    error: function(xhr, status, error) {
        console.error('Error fetching customer data:', error);
    }
});

// Handle customer selection
$('#customer-id').on('change', function() {
    const selectedOption = $(this).find(':selected');
    const customerDetails = selectedOption.data('details');

    if (customerDetails) {
        $('#customer-name').text(`${customerDetails.first_name} ${customerDetails.last_name}`);
        $('#customer-phone').text(customerDetails.mobile_no);
    }
});

        // Global variable to store combined product data
        let allProducts = [];

        // Fetch data from the single API and combine it
        fetch('/products/stocks')
            .then(response => response.json())
            .then(data => {
                console.log('Stock Data:', data); // Log stock data
                if (data.status === 200 && Array.isArray(data.data)) {
                    allProducts = data.data.map(stock => {
                        const product = stock.product;
                        const totalQuantity = stock.total_stock;

                        // Ensure product object and necessary properties are defined
                        if (product && typeof product.id !== 'undefined' && typeof product
                            .product_name !== 'undefined') {
                            return {
                                id: product.id,
                                name: product.product_name,
                                sku: product.sku,
                                quantity: totalQuantity,
                                price: product.retail_price,
                                product_details: product,
                                batches: stock.batches
                            };
                        } else {
                            console.error('Invalid product data:', product);
                            return null;
                        }
                    }).filter(product => product !== null); // Filter out invalid products

                    initAutocomplete();
                } else {
                    console.error('Unexpected format or status for stocks data:', data);
                }
            })
            .catch(err => console.error('Error fetching product data:', err));

        // Function to initialize autocomplete functionality
        function initAutocomplete() {
            if (typeof $.ui === 'undefined' || typeof $.ui.autocomplete === 'undefined') {
                console.error('jQuery UI Autocomplete is not loaded.');
                return;
            }

            const autocompleteInstance = $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.name && product.name.toLowerCase().includes(searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );
                    response(filteredProducts.map(product => ({
                        label: `${product.name} (${product.sku || 'No SKU'})`,
                        value: product.name,
                        product: product
                    })));
                },
                select: function(event, ui) {
                    $("#productSearchInput").val(ui.item.value);
                    ui.item.product.quantity =
                        1; // Ensure quantity is set to 1 when adding a new product
                    addProductToTable(ui.item.product);
                    return false;
                }
            }).autocomplete("instance");

            if (autocompleteInstance) {
                console.log('Autocomplete instance initialized successfully.');
                autocompleteInstance._renderItem = function(ul, item) {
                    return $("<li>")
                        .append(`<div>${item.label}</div>`)
                        .appendTo(ul);
                };
            } else {
                console.error('Failed to initialize autocomplete instance.');
            }
        }

        // Function to get batches
        function getBatches(product, selectedBatchId, isEditing) {
            if (!Array.isArray(product.batches)) {
                return [];
            }

            if (isEditing) {
                return product.batches.map(batch => ({
                    batch_id: batch.id,
                    batch_price: parseFloat(batch.retail_price) || 0,
                    batch_quantity: batch.qty || 0,
                    batch_quantity_plus_sold: batch.qty + (batch.id === selectedBatchId ? product
                        .quantity : 0) // Adjust batch quantity if editing
                }));
            } else {
                return product.batches.flatMap(batch =>
                    Array.isArray(batch.location_batches) ? batch.location_batches.map(locationBatch => ({
                        batch_id: batch.id,
                        batch_price: parseFloat(batch.retail_price) || 0,
                        batch_quantity: locationBatch.quantity || 0
                    })) : []
                );
            }
        }

        // Function to add product to table
        function addProductToTable(product, selectedBatchId = null, isEditing = false) {
            // Validate product data
            if (!product || typeof product.id === 'undefined' || typeof product.name === 'undefined') {
                console.error("Invalid product data:", product);
                return;
            }

            // Set default quantity if it's not provided
            if (typeof product.quantity === 'undefined') {
                product.quantity = 1;
            }

            const batches = getBatches(product, selectedBatchId, isEditing);

            const totalQuantity = batches.reduce((total, batch) => total + (isEditing ? batch
                .batch_quantity_plus_sold : batch.batch_quantity), 0); // Calculate total quantity correctly
            const finalPrice = typeof product.price !== 'undefined' ? parseFloat(product.price) : 0;

            // Generate batch options
            const batchOptions = batches.map(batch => `
        <option value="${batch.batch_id}"
                data-price="${batch.batch_price}"
                data-quantity="${batch.batch_quantity}"
                ${isEditing ? `data-quantity-plus-sold="${batch.batch_quantity_plus_sold}"` : ''}
                ${selectedBatchId === batch.batch_id ? 'selected' : ''}>
            Batch ${batch.batch_id} - Qty: ${isEditing ? batch.batch_quantity_plus_sold : batch.batch_quantity} - Price: ${batch.batch_price}
        </option>
    `).join('');

            const newRow = `
        <tr data-id="${product.id}">
            <td>${product.name || '-'} <br><span style="font-size:12px;">Current stock: ${totalQuantity}</span>
                <select class="form-select batch-dropdown" aria-label="Select Batch">
                    <option value="all" data-price="${finalPrice}" data-quantity="${totalQuantity}">
                        All Batches - Total Qty: ${totalQuantity} - Price: ${finalPrice}
                    </option>
                    ${batchOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" value="${product.quantity}" min="1">
            </td>
            <td>
                <input type="number" class="form-control price-input" value="${finalPrice.toFixed(2)}" min="0">
            </td>
            <td>
                <input type="number" class="form-control discount-percent" value="${product.discount || 0}" min="0" max="100">
            </td>
            <td class="retail-price">${finalPrice.toFixed(2)}</td>
            <td class="subtotal">${(finalPrice * product.quantity).toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm remove-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

            const $newRow = $(newRow);
            $('#addSaleProduct').DataTable().row.add($newRow).draw();
            allProducts = allProducts.filter(p => p.id !== product.id);

            // Update footer and set up event listeners
            updateFooter();
            toastr.success('Product added to the table!', 'Success');

            // Event listeners for row updates
            const quantityInput = $newRow.find('.quantity-input');
            const priceInput = $newRow.find('.price-input');
            const batchDropdown = $newRow.find('.batch-dropdown');

            $newRow.find('.remove-btn').on('click', function(event) {
                event.preventDefault(); // Prevent form submission
                var row = $(this).closest('tr');
                $('#confirmRemoveModal').data('row', row).modal('show');
            });

            $newRow.find('.quantity-minus').on('click', () => {
                if (quantityInput.val() > 1) {
                    quantityInput.val(quantityInput.val() - 1);
                    updateTotals();
                }
            });

            $newRow.find('.quantity-plus').on('click', () => {
                let newQuantity = parseInt(quantityInput.val(), 10) + 1;
                const selectedOption = batchDropdown.find(':selected');
                const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
                    selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
                );

                if (newQuantity > maxQuantity) {
                    document.getElementsByClassName('errorSound')[0].play();
                    toastr.error(`You cannot add more than ${maxQuantity} units of this product.`,
                        'Error');
                } else {
                    quantityInput.val(newQuantity);
                    updateTotals();
                }
            });

            quantityInput.on('input', () => {
                const quantityValue = parseInt(quantityInput.val(), 10);
                const selectedOption = batchDropdown.find(':selected');
                const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
                    selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
                );

                if (quantityValue > maxQuantity) {
                    quantityInput.val(maxQuantity);
                    document.getElementsByClassName('errorSound')[0].play();
                    toastr.error(`You cannot add more than ${maxQuantity} units of this product.`,
                        'Error');
                }
                updateTotals();
            });

            priceInput.on('input', () => {
                updateTotals();
            });

            batchDropdown.on('change', () => {
                const selectedOption = batchDropdown.find(':selected');
                const batchPrice = parseFloat(selectedOption.data('price')) || 0;
                const batchQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
                    selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
                );

                if (quantityInput.val() > batchQuantity) {
                    quantityInput.val(batchQuantity);
                    toastr.error(`You cannot add more than ${batchQuantity} units from this batch.`,
                        'Error');
                }
                priceInput.val(batchPrice.toFixed(2));
                const subtotal = parseFloat(quantityInput.val()) * batchPrice;
                $newRow.find('.subtotal').text(subtotal.toFixed(2));
                quantityInput.attr('max', batchQuantity);
                updateTotals();
            });
        }

        // Function to update row totals
        function updateRow($row) {
            const batchElement = $row.find('.batch-dropdown option:selected');
            const quantity = parseFloat($row.find('.quantity-input').val()) || 0;
            const price = parseFloat(batchElement.data('price')) || 0;
            const discountPercent = parseFloat($row.find('.discount-percent').val()) || 0;
            const batchQuantity = parseFloat(batchElement.data('quantity')) || 0;

            if (quantity > batchQuantity) {
                alert('Requested quantity exceeds available batch quantity.');
                $row.find('.quantity-input').val(batchQuantity);
                quantity = batchQuantity;
            }

            const subTotal = quantity * price;
            const discountAmount = subTotal * (discountPercent / 100);
            const netCost = subTotal - discountAmount;
            const lineTotal = netCost;

            $row.find('.subtotal').text(subTotal.toFixed(2));
            $row.find('.net-cost').text(netCost.toFixed(2));
            $row.find('.line-total').text(lineTotal.toFixed(2));
            $row.find('.retail-price').text(price.toFixed(2));

            // Update batch quantity if the quantity is updated
            batchElement.data('quantity', batchQuantity - quantity);
        }

        function updateTotals() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                totalItems += parseFloat($(this).find('.quantity-input').val()) || 0;
                netTotalAmount += parseFloat($(this).find('.subtotal').text()) || 0;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));

            const discountType = $('#discount_type').val();
            const discountAmount = parseFloat($('#discount_amount').val()) || 0;
            let discountNetTotalAmount = netTotalAmount;

            if (discountType === 'percentage') {
                discountNetTotalAmount -= (netTotalAmount * (discountAmount / 100));
            } else if (discountType === 'fixed') {
                discountNetTotalAmount -= discountAmount;
            }

            $('#discount-net-total-amount').text(discountNetTotalAmount.toFixed(2));

            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const paymentDue = discountNetTotalAmount - paidAmount;
            $('.payment-due').text(`Rs. ${paymentDue.toFixed(2)}`);
        }

          // Function to update calculations
          function updateCalculations() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                const discountPercent = parseFloat($(this).find('.discount-percent').val()) || 0;

                const subTotal = quantity * price;
                const discountAmount = subTotal * (discountPercent / 100);
                const netCost = subTotal - discountAmount;
                const lineTotal = netCost;

                $(this).find('.sub-total').text(subTotal.toFixed(2));
                $(this).find('.net-cost').text(netCost.toFixed(2));
                $(this).find('.line-total').text(lineTotal.toFixed(2));
                $(this).find('.retail-price').text(price.toFixed(2));

                totalItems += quantity;
                netTotalAmount += lineTotal;
            });

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

            if (taxType === 'vat10' || taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#purchase-total').text(`Purchase Total: Rs. ${finalTotal.toFixed(2)}`);
            $('#discount-display').text(`(-) Rs. ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs. ${taxAmount.toFixed(2)}`);
            updatePaymentDue(finalTotal, discountAmount);
        }

        // Function to update payment due amount
        function updatePaymentDue(finalTotal, discountAmount) {
            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const discountNetTotalAmount = finalTotal - discountAmount;
            const paymentDue = discountNetTotalAmount - paidAmount;
            $('.payment-due').text(`Rs. ${paymentDue.toFixed(2)}`);
        }

        // Event listener for remove button click
        $(document).on('click', '.remove-btn', function(event) {
            event.preventDefault(); // Prevent form submission
            var row = $(this).closest('tr');
            $('#confirmRemoveModal').data('row', row).modal('show');
        });

        // Event listener for confirmation modal
        $('#confirmRemoveButton').on('click', function() {
            var row = $('#confirmRemoveModal').data('row');
            removeProduct(row);
            $('#confirmRemoveModal').modal('hide');
        });

        // Function to handle the removal of the product from the DataTable
        function removeProduct(row) {
            var table = $('#addSaleProduct').DataTable();
            var productId = row.data('id');
            var product = allProducts.find(p => p.id === productId);

            // Re-add the removed product back to the allProducts array
            allProducts.push(product);

            table.row(row).remove().draw();

            toastr.success('Product removed successfully!', 'Success');
            updateCalculations();
            updateFooter();
        }

        // Trigger calculations on events
        $(document).on('change keyup',
            '.quantity-input, .discount-percent, .price-input, #discount-amount, #discount-type, #tax-type',
            function() {
                updateCalculations();
            });


        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#addSalesForm')[0].reset(); // Reset the form
            $('.error-message').html(''); // Clear error messages
            $('#addSaleProduct').DataTable().clear().draw(); // Clear the data table
            $('#addSalesForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addSalesForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // // Use event delegation for the action buttons and stop event propagation
        $(document).on('click', '.edit_btn', function(event) {
            var id = $(this).val();
            window.location.href = `/sales/edit/${id}`;
        });

        // Function to fetch sale data for editing
        function fetchSaleData(saleId) {
            $.ajax({
                url: `/sales/edit/${saleId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        populateForm(response.sales);
                        $('#editSaleModal').modal('show');
                    } else {
                        toastr.error('Failed to fetch sale data.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 404) {
                        toastr.error('Sale not found.', 'Error');
                    } else {
                        toastr.error('Something went wrong while fetching the sale data.', 'Error');
                    }
                    console.error('Error fetching sale data:', error);
                }
            });
        }

        // Extract the sale ID from the URL and fetch data if editing
        $(document).ready(function() {
            const pathSegments = window.location.pathname.split('/');
            const saleId = pathSegments[pathSegments.length - 1];

            if (saleId && saleId !== 'add-sale' && saleId !== 'list-sale') {
                fetchSaleData(saleId);
            }
        });
        // Populate form with sale data
        function populateForm(sale) {
            $('#sale_id').val(sale.id);
            $('#location').val(sale.location_id).change();
            $('#customer-id').val(sale.customer_id).change();
            $('#sales_date').val(convertDateFormat(sale.sales_date));
            $('#status').val(sale.status).change();
            $('#invoice_no').val(sale.invoice_no);

            // Clear existing products in the table
            const productTable = $('#addSaleProduct').DataTable();
            productTable.clear().draw();

            // Populate products table
            if (sale.products && Array.isArray(sale.products)) {
                sale.products.forEach(product => {
                    const productData = {
                        id: product.product_id,
                        name: product.product.product_name,
                        sku: product.product.sku,
                        quantity: product.quantity,
                        price: product.price,
                        discount: product.discount,
                        price_type: product.price_type,
                        batch_quantity_plus_sold: product.batch_quantity_plus_sold,
                        batches: product.product.batches || []
                    };
                    addProductToTable(productData, product.batch_id,
                        true); // Pass true to indicate editing
                });
            }

            updateCalculations();
        }





    });
</script>
