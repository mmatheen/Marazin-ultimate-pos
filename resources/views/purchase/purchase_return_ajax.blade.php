<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    var purchaseReturnValidationOptions = {
        rules: {
            supplier_id: {
                required: true,
            },
            purchase_return_date: {
                required: true,
            },
            location: {
                required: true,
            },
            attach_document: {
                required: false,
                extension: "jpg,jpeg,png,gif,pdf,csv,zip,doc,docx",
                filesize: 5242880 // 5MB in bytes
            },
        },
        messages: {
            supplier_id: {
                required: "Supplier is required",
            },
            purchase_return_date: {
                required: "Purchase Return Date is required",
            },
            location: {
                required: "Location is required",
            },
            attach_document: {
                extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
                filesize: "Max file size is 5MB"
            },
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
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
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalidRed').removeClass('is-validGreen');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalidRed').addClass('is-validGreen');
        }
    };

    $('#purchaseReturn').validate(purchaseReturnValidationOptions);

    function resetFormAndValidation() {
        $('#purchaseReturn')[0].reset();
        $('#purchaseReturn').validate().resetForm();
        $('#purchaseReturn').find('.is-invalidRed, .is-validGreen').removeClass('is-invalidRed is-validGreen');
        $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png').hide();
        $('#purchase_return tbody').empty();
        $('.form-select').val(null).trigger('change');
    }

    $('.btn-reset[type="reset"]').on('click', function () {
        resetFormAndValidation();
    });

    $(".show-file").on("change", function () {
        const input = this;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            if (file.type === "application/pdf") {
                reader.onload = function (e) {
                    $("#pdfViewer").attr("src", e.target.result).show();
                    $("#selectedImage").hide();
                };
            } else if (file.type.startsWith("image/")) {
                reader.onload = function (e) {
                    $("#selectedImage").attr("src", e.target.result).show();
                    $("#pdfViewer").hide();
                };
            }

            reader.readAsDataURL(file);
        }
    });

    function fetchDropdownData(url, targetSelect, placeholder, selectedId) {
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.status === 200 && Array.isArray(data.message)) {
                    targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                    data.message.forEach(item => {
                        const option = $('<option></option>').val(item.id).text(item.name || item.first_name + ' ' + item.last_name);
                        if (item.id == selectedId) {
                            option.attr('selected', 'selected');
                        }
                        targetSelect.append(option);
                    });
                } else {
                    console.error(`Failed to fetch data: ${data.message}`);
                }
            },
            error: function (xhr, status, error) {
                console.error(`Error fetching data: ${error}`);
            }
        });
    }

    fetchDropdownData('/supplier-get-all', $('#supplier-id'), "Select Supplier");
    fetchDropdownData('/location-get-all', $('#location'), "Select Location", 2); // Default to location with ID 2

    // Fetch product data based on selected supplier
    $('#supplier-id').change(function () {
        const supplierId = $(this).val();
        if (supplierId) {
            fetchProductData(supplierId);
        }
    });

    function fetchProductData(supplierId) {
        $.ajax({
            url: `/products/stock?supplier_id=${supplierId}`,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.status === 200 && Array.isArray(data.data)) {
                    allProducts = data.data.map(stock => {
                        const product = stock.product;
                        const totalQuantity = stock.total_stock;

                        return {
                            id: product.id,
                            name: product.product_name,
                            sku: product.sku,
                            quantity: totalQuantity,
                            price: product.retail_price,
                            product_details: product,
                            batches: stock.batches
                        };
                    });

                    initAutocomplete();
                } else {
                    console.error('Unexpected format or status for stocks data:', data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching product data:', error);
            }
        });
    }

    // Initialize autocomplete functionality
    function initAutocomplete() {
        $("#productSearchInput").autocomplete({
            source: function (request, response) {
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
            select: function (event, ui) {
                $("#productSearchInput").val(ui.item.value);
                addProductToTable(ui.item.product);
                return false;
            }
        }).autocomplete("instance")._renderItem = function (ul, item) {
            return $("<li>")
                .append(`<div>${item.label}</div>`)
                .appendTo(ul);
        };
    }

    // Function to add product to table
    function addProductToTable(product) {
        const quantity = 1; // Initial quantity set to 1 (can be changed later)
        const subtotal = product.price * quantity;

        // Generate batch options
        const batchOptions = product.batches.map(batch => `
            <option value="${batch.id}">${batch.batch_no} - Exp: ${batch.expiry_date}</option>
        `).join('');

        // Generate the new row
        const newRow = `
            <tr data-id="${product.id}">
                <td>${product.id}</td>
                <td>${product.name || '-'} <br>Current stock: ${product.quantity || '0'}
                    <br>
                    <select class="form-control batch-select">
                        ${batchOptions}
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control purchase-quantity" value="${quantity}" min="1" max="${product.quantity}">
                </td>
                <td>${product.price || '0'}</td>
                <td class="sub-total">${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger btn-sm delete-product">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        // Add the new row to the DataTable
        const $newRow = $(newRow);
        $('#purchase_return').DataTable().row.add($newRow).draw();

        // Remove the product from allProducts array
        allProducts = allProducts.filter(p => p.id !== product.id);

        // Update footer after adding the product
        updateFooter();
        toastr.success('New product added to the table!', 'Success');

        // Add event listeners for dynamic updates
        $newRow.find('.purchase-quantity').on('input', function() {
            updateRow($newRow);
            updateFooter();
        });

        // Function to update row values
        function updateRow($row) {
            const quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
            const price = parseFloat($row.find('td:nth-child(4)').text()) || 0;
            const subTotal = quantity * price;

            $row.find('.sub-total').text(subTotal.toFixed(2));
        }
    }

    // Function to remove product from table
    function removeProductFromTable(button) {
        const row = $(button).closest('tr'); // Get the row containing the button
        const productId = row.data('id');   // Get the product ID from the row

        // Remove the row from the DataTable
        $('#purchase_return').DataTable().row(row).remove().draw();

        // Update the footer after removal
        updateFooter();

        toastr.success(`Product ID ${productId} removed from the table!`, 'Success');
    }

    // Event listener for remove button
    $('#purchase_return').on('click', '.delete-product', function () {
        removeProductFromTable(this);
    });

    // Update footer function
    function updateFooter() {
        let totalItems = 0;
        let netTotalAmount = 0;

        $('#purchase_return tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
            const price = parseFloat($(this).find('td:nth-child(4)').text()) || 0;
            const subtotal = quantity * price;

            $(this).find('.sub-total').text(subtotal.toFixed(2));

            totalItems += quantity;
            netTotalAmount += subtotal;
        });

        $('#total-items').text(totalItems.toFixed(2));
        $('#net-total-amount').text(netTotalAmount.toFixed(2));
    }

    // Event listener for quantity change
    $('#purchase_return').on('input', '.purchase-quantity', function() {
        const input = $(this);
        const productId = input.closest('tr').data('id');
        const maxQuantity = parseInt(input.attr('max')) || 0;

        // Check if the quantity exceeds the available stock
        let newQuantity = parseInt(input.val()) || 0;
        if (newQuantity > maxQuantity) {
            toastr.warning(`Cannot enter more than ${maxQuantity} for this product.`, 'Quantity Limit Exceeded');
            input.val(maxQuantity);  // Reset to the max quantity
        }

        updateFooter();
    });

    // Form submission
    $('#purchaseReturn').on('submit', function (e) {
        e.preventDefault();

        if (!$('#purchaseReturn').valid()) {
            document.getElementsByClassName('warningSound')[0].play(); // for sound
            toastr.options = { "closeButton": true, "positionClass": "toast-top-right" };
            toastr.error('Invalid inputs, Check & try again!!', 'Warning');
            return; // Return if form is not valid
        }

        const formData = new FormData(this);
        formData.append('supplier_id', $('#supplier-id').val());
        formData.append('location_id', $('#location').val());
        formData.append('reference_no', $('input[placeholder="Reference No"]').val());
        formData.append('return_date', $('input.datetimepicker').val());

        const fileInput = $('#attach_document')[0];
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'application/pdf'];
            if (validTypes.includes(file.type)) {
                formData.append('attach_document', file);
            }
        }

        const productRows = document.querySelectorAll('#purchase_return tbody tr');
        if (productRows.length === 0) {
            toastr.error("No products added.");
            return;
        }

        productRows.forEach((row, index) => {
            formData.append(`products[${index}][product_id]`, row.querySelector('td:nth-child(1)').textContent.trim());
            formData.append(`products[${index}][quantity]`, row.querySelector('td:nth-child(3) input').value);
            formData.append(`products[${index}][unit_price]`, row.querySelector('td:nth-child(4)').textContent.trim());
            formData.append(`products[${index}][subtotal]`, row.querySelector('td:nth-child(5)').textContent.trim());
            formData.append(`products[${index}][batch_id]`, row.querySelector('.batch-select').value);
        });

        for (const [key, value] of formData.entries()) {
            console.log(key, value); // Debug FormData
        }

        $.ajax({
            url: '/purchase-return/store',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                    });
                    const responseData = response.responseJSON || {}; // Extract JSON response
                    const message = responseData.error || 'An error occurred during submission.';
                    const errors = responseData.messages || {}; // Adjust based on backend response format

                    // Display the error message
                    toastr.error(message);

                } else {
                    document.getElementsByClassName('successSound')[0].play(); // for sound
                    toastr.success(response.message, 'Added');
                    resetFormAndValidation();
                }
            }
        });
    });

    $(document).ready(function () {
        // Initialize DataTable
        var table = $('#purchase_return_list').DataTable();

        // Fetch data with AJAX
        $.ajax({
            url: 'purchase-returns/get-All', // API endpoint
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log(response);

                if (response && response.purchases_Return) {
                    let purchasesReturn = response.purchases_Return;
                    let tableData = [];

                    // Prepare table rows data
                    purchasesReturn.forEach(function (purchase) {
                        let supplierName = purchase.supplier
                            ? purchase.supplier.first_name + ' ' + purchase.supplier.last_name
                            : 'Unknown Supplier';
                        let locationName = purchase.location
                            ? purchase.location.name
                            : 'Unknown Location';

                        // Calculate grand total and payment due
                        let grandTotal = 0;
                        if (purchase.purchase_return_products) {
                            purchase.purchase_return_products.forEach(function (product) {
                                grandTotal += parseFloat(product.subtotal);
                            });
                        }

                        let paymentDue = grandTotal - parseFloat(purchase.final_total || 0);

                        // Push data row
                        tableData.push([
                            purchase.return_date,
                            purchase.reference_no,
                            purchase.id,
                            locationName,
                            supplierName,
                            'Due', // Assuming 'Due' as a static value for Payment Status
                            grandTotal.toFixed(2), // Format to 2 decimal places
                            paymentDue.toFixed(2), // Format to 2 decimal places
                            `<div class="dropdown dropdown-action">
                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <button type="button" class="btn btn-outline-info">
                                    Actions &nbsp;<i class="fas fa-sort-down"></i>
                                </button>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item view-btn" href="#" data-id="${purchase.id}">
                                    <i class="fas fa-eye"></i>&nbsp;&nbsp;View
                                </a>
                                <a class="dropdown-item" href="edit-invoice.html">
                                    <i class="fas fa-print"></i>&nbsp;&nbsp;Print
                                </a>
                                <a class="dropdown-item edit-link" href="/purchase-returns/edit/${purchase.id}" data-id="${purchase.id}">
                                    <i class="far fa-edit me-2"></i>&nbsp;Edit
                                </a>
                                <a class="dropdown-item add-payment-btn" href="#" data-id="${purchase.id}" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment
                                </a>
                            </div>
                        </div>`
                        ]);
                    });

                    // Initialize or update the DataTable
                    table.clear().rows.add(tableData).draw();
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching purchases:', error);
            }
        });

        // View button click to show modal
        $('#purchase_return_list tbody').on('click', '.view-btn', function (event) {
            event.preventDefault(); // Prevent default link behavior

            var purchaseId = $(this).data('id'); // Get purchase ID directly from data attribute
            console.log("View button clicked. Purchase ID:", purchaseId);

            // Fetch purchase details using AJAX
            $.ajax({
                url: `purchase-returns/get-Details/${purchaseId}`,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    console.log(response);

                    if (response && response.purchase_return) {
                        let purchaseReturn = response.purchase_return;

                        let supplier = purchaseReturn.supplier
                            ? purchaseReturn.supplier.first_name + ' ' + purchaseReturn.supplier.last_name
                            : 'Unknown Supplier';

                        let location = purchaseReturn.location
                            ? purchaseReturn.location.name
                            : 'Unknown Location';

                        // Dynamically generate products table
                        let productsHtml = '';
                        let netTotal = 0;

                        if (purchaseReturn.purchase_return_products.length > 0) {
                            purchaseReturn.purchase_return_products.forEach((product, index) => {
                                let subtotal = parseFloat(product.subtotal);
                                netTotal += subtotal;

                                productsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${product.product.product_name}</td>
                                        <td>$ ${parseFloat(product.unit_price).toFixed(2)}</td>
                                        <td>${product.quantity} Pc(s)</td>
                                        <td>$ ${subtotal.toFixed(2)}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            productsHtml = `
                                <tr>
                                    <td colspan="5" class="text-center">No products found for this purchase return.</td>
                                </tr>`;
                        }

                        let returnTax = parseFloat(purchaseReturn.tax_amount || 0);
                        let returnTotal = netTotal + returnTax;

                        // Inject content into modal
                        let modalContent = `
                            <span class="close-btn" id="closeBtn">&times;</span>
                            <h2>Purchase Return Details</h2>
                            <h2>Reference No: ${purchaseReturn.reference_no}</h2>

                        <p><b>Return Date:</b> ${purchaseReturn.return_date}</p>
                        <p><b>Supplier:</b> ${supplier}</p>
                        <p><b>Business Location:</b> ${location}</p>

                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Unit Price</th>
                                    <th>Return Quantity</th>
                                    <th>Return Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${productsHtml}
                            </tbody>
                        </table>

                        <div class="footer">
                            <div>
                                <p><b>Net Total Amount:</b> $${netTotal.toFixed(2)}</p>
                                <p><b>Net Total Return Tax:</b> $${returnTax.toFixed(2)}</p>
                                <p><b>Return Total:</b> $${returnTotal.toFixed(2)}</p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="button print-btn">Print</button>
                                <button class="button close">Close</button>
                            </div>
                        </div>
                    `;

                    $('#myModal .modal-content').html(modalContent); // Inject content into modal
                    $('#myModal').css("display", "block");

                    var modal = document.getElementById("myModal");
                    var span = document.getElementById("closeBtn");
                    span.onclick = function () {
                        modal.style.display = "none";
                    }

                    // Modal close button
                    $('#myModal').on('click', '.close', function () {
                        $('#myModal').hide();
                    });

                    // Print button
                    $('#myModal').off('click').on('click', '.print-btn', function () {
                        window.print();
                    });

                } else {
                    alert("No details found for this purchase.");
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching purchase details:', error);
                alert("Error fetching purchase details.");
            }
        });
    });
})
    })

    // Add Payment button click to show modal
    $('#purchase_return_list tbody').on('click', '.add-payment-btn', function (event) {
        event.preventDefault(); // Prevent default link behavior

        var purchaseId = $(this).data('id'); // Get purchase ID directly from data attribute
        console.log("Add Payment button clicked. Purchase ID:", purchaseId);

        // Fetch purchase return details using AJAX
        $.ajax({
            url: `purchase-returns/get-Details/${purchaseId}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log(response);

                if (response && response.purchase_return) {
                    let purchaseReturn = response.purchase_return;

                    let supplier = purchaseReturn.supplier
                        ? purchaseReturn.supplier.first_name + ' ' + purchaseReturn.supplier.last_name
                        : 'Unknown Supplier';

                    let location = purchaseReturn.location
                        ? purchaseReturn.location.name
                        : 'Unknown Location';

                        let netTotal = 0;

                    if (purchaseReturn.purchase_return_products.length > 0) {
                        purchaseReturn.purchase_return_products.forEach((product, index) => {
                            let subtotal = parseFloat(product.subtotal);
                            netTotal += subtotal;
                        });
                    }


                    // Populate modal fields
                    $('#supplierDetails').text(supplier);
                    $('#referenceNo').text(purchaseReturn.reference_no);
                    $('#locationDetails').text(location);
                    $('#totalAmount').text(netTotal.toFixed(2));
                    $('#payAmount').text(netTotal.toFixed(2));

                    // Open the modal
                    $('#paymentModal').modal('show');
                } else {
                    alert("No details found for this purchase return.");
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching purchase return details:', error);
                alert("Error fetching purchase return details.");
            }
        });
    });

    // // Save payment button click event
    // $('#savePayment').on('click', function () {
    //     var formData = new FormData($('#paymentForm')[0]);

    //     $.ajax({
    //         url: 'purchase-returns/add-payment', // Change to your actual endpoint
    //         type: 'POST',
    //         data: formData,
    //         contentType: false,
    //         processData: false,
    //         success: function (response) {
    //             alert('Payment saved successfully!');
    //             $('#paymentModal').modal('hide');
    //             // Optionally, refresh the DataTable or perform other actions
    //         },
    //         error: function (xhr, status, error) {
    //             console.error('Error saving payment:', error);
    //             alert('Error saving payment.');
    //         }
    //     });
    // });




// $(document).ready(function() {
//         // Fetch product details and populate the form when the page is loaded
//         const purchaseId = window.location.pathname.split('/').pop();

//         if (!purchaseId) return; // Stop if no product ID is found

//         // Fetch product details and populate the form
//         $.ajax({
//             url: `/purchase-returns/edit/${purchaseId}`,
//             type: 'GET',
//             success: function(response) {
//                 if (response.status === 200) {
//                     const purchaseReturn = response.purchase_return;

//             $('#reference_no').val(purchaseReturn.reference_no);

//             //           // Populate form fields with the correct data
//             // $('#supplier-id').val(purchaseReturn.supplier_id).trigger('change');
//             // $('#location').val(purchaseReturn.location_id).trigger('change');
//             // $('input[name="purchase_return_date"]').val(purchaseReturn.return_date);
//             // $('#attach_document').val(purchaseReturn.attach_document);

//             // // Show image or PDF if available
//             // if (purchaseReturn.attach_document) {
//             //     const fileExtension = purchaseReturn.attach_document.split('.').pop().toLowerCase();
//             //     const fileUrl = `/uploads/${purchaseReturn.attach_document}`; // Adjust path as needed

//             //     if (fileExtension === 'pdf') {
//             //         $('#pdfViewer').attr('src', fileUrl).show();
//             //         $('#selectedImage').hide();
//             //     } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
//             //         $('#selectedImage').attr('src', fileUrl).show();
//             //         $('#pdfViewer').hide();
//             //     } else {
//             //         $('#selectedImage, #pdfViewer').hide();
//             //     }
//             // } else {
//             //     $('#selectedImage, #pdfViewer').hide();
//             // }

//             // // Populate products table
//             // let productRows = '';
//             // if (purchaseReturn.purchase_return_products && Array.isArray(purchaseReturn.purchase_return_products)) {
//             //     purchaseReturn.purchase_return_products.forEach(product => {
//             //         productRows += `
//             //             <tr data-id="${product.id}">
//             //                 <td>${product.product.id}</td>
//             //                 <td>${product.product.product_name}</td>
//             //                 <td><input type="number" class="form-control purchase-quantity" name="quantity[${product.id}]" value="${product.quantity}"></td>
//             //                 <td>${product.unit_price}</td>
//             //                 <td>${product.subtotal}</td>
//             //                 <td><button class="btn btn-danger btn-sm delete-product" data-id="${product.id}">Remove</button></td>
//             //             </tr>
//             //         `;
//             //     });
//             // }
//             // $('#purchase_return tbody').html(productRows);

//             // // Update totals
//             // $('#total-items').text(purchaseReturn.total_items || 0);
//             // $('#net-total-amount').text(purchaseReturn.net_total_amount || 0);



//                 } else {
//                     // alert('Error: ' + response.message);
//                 }
//             },
//             error: function() {
//                 // alert('An error occurred while fetching product details.');
//             }
//         });



//     });










  </script>

