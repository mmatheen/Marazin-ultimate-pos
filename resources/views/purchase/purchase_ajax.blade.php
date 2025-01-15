<script type="text/javascript">
    $(document).ready(function() {
        // CSRF Token setup
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        fetchProducts();
        fetchLocations();

        // Function to print modal
        function printModal() {
            window.print();
        }

        const validationMessages = {
            supplier_id: {
                required: "Supplier is required"
            },
            purchase_date: {
                required: "Purchase Date is required"
            },
            purchase_status: {
                required: "Purchase Status is required"
            },
            services: {
                required: "Business Location is required"
            },
            duration: {
                required: "Duration is required",
                number: "Please enter a valid number"
            },
            duration_type: {
                required: "Period is required"
            },
            image: {
                extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
                filesize: "Max file size is 5MB"
            }
        };

        // Validation setup
        var purchaseValidationOptions = {
            rules: {
                supplier_id: {
                    required: true
                },
                purchase_date: {
                    required: true
                },
                purchase_status: {
                    required: true
                },
                services: {
                    required: true
                },
                duration: {
                    required: true,
                    number: true
                },
                duration_type: {
                    required: true
                },
                image: {
                    required: false,
                    extension: "jpg|jpeg|png|gif|pdf|csv|zip|doc|docx",
                    filesize: 5242880 // 5MB
                }
            },
            messages: validationMessages,

            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.is("select")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest('.input-group')); // Place error after select container
                } else if (element.is(":checkbox") || element.is(":radio")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.addClass('text-danger small');
                    error.insertAfter(element); // Default placement for text and other inputs
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

        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#purchaseForm')[0].reset(); // Reset the form
            $('#purchaseForm').validate().resetForm(); // Reset validation states
            $('#purchaseForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#purchaseForm').find('.is-validGreen').removeClass('is-validGreen');
            $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png'); // Reset the image
            $('#purchase_product tbody').empty(); // Clear the product table
            $('#supplier-id, #discount-type, #payment-method').val('').trigger('change');
        }

        // Fetch locations data from the server
        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const locationSelect = $('#services');
                    locationSelect.html('<option selected disabled>Please Select Locations</option>');

                    if (data.status === 200) {
                        data.message.forEach(function(location) {
                            const option = $('<option></option>').val(location.id).text(location.name);
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
        }

        let allProducts = []; // Store all product data

        // Fetch product data from the server
        function fetchProducts() {
            fetch('/products/stocks/')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200 && Array.isArray(data.data)) {
                        allProducts = data.data.map(stock => ({
                            id: stock.product.id,
                            name: stock.product.product_name,
                            sku: stock.product.sku || "N/A",
                            quantity: stock.total_stock || 0,
                            price: stock.product.retail_price || 0,
                            wholesale_price: stock.batches?.[0]?.wholesale_price || 0,
                            special_price: stock.batches?.[0]?.special_price || 0,
                            max_retail_price: stock.batches?.[0]?.max_retail_price || 0,
                        }));
                        initAutocomplete(allProducts); // Initialize autocomplete
                    } else {
                        console.error("Failed to fetch product data:", data);
                    }
                })
                .catch(error => console.error("Error fetching products:", error));
        }

        // Initialize autocomplete
        function initAutocomplete(products) {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = products.filter(
                        product =>
                        product.name.toLowerCase().includes(searchTerm) ||
                        product.sku.toLowerCase().includes(searchTerm)
                    );

                    response(
                        filteredProducts.map(product => ({
                            label: `${product.name} (${product.sku})`,
                            value: product.name,
                            product: product,
                        }))
                    );
                },
                select: function(event, ui) {
                    addProductToTable(ui.item.product); // Add product to the table
                    $("#productSearchInput").val(""); // Clear search input
                    return false;
                },
            });
        }

        // Add product row to the table
        function addProductToTable(product) {
            const price = parseFloat(product.price) || 0;
            const wholesalePrice = parseFloat(product.wholesale_price) || 0;
            const specialPrice = parseFloat(product.special_price) || 0;
            const maxRetailPrice = parseFloat(product.max_retail_price) || 0;

            const newRow = `
                <tr data-id="${product.id}">
                    <td>${product.id}</td>
                    <td>${product.name} <br><small>Stock: ${product.quantity}</small></td>
                    <td><input type="number" class="form-control purchase-quantity" value="1" min="1"></td>
                    <td><input type="number" class="form-control product-price" value="${price.toFixed(2)}" min="0"></td>
                    <td><input type="number" class="form-control discount-percent" value="0" min="0" max="100"></td>
                    <td>${price.toFixed(2)}</td>
                    <td>${price.toFixed(2)}</td>
                    <td><input type="number" class="form-control" value="${wholesalePrice.toFixed(2)}" ></td>
                    <td><input type="number" class="form-control" value="${specialPrice.toFixed(2)}" ></td>
                    <td><input type="number" class="form-control" value="${maxRetailPrice.toFixed(2)}" ></td>
                    <td class="sub-total">0</td>
                    <td><input type="number" class="form-control profit-margin" value="0" min="0"></td>
                    <td><input type="text" class="form-control batch-id"></td>
                    <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;

            const $newRow = $(newRow);
            const table = $("#purchase_product").DataTable();

            table.row.add($newRow).draw(); // Add new row to DataTable
            updateRow($newRow); // Update calculations for the new row

            // Set up event listeners for quantity, discount, and price changes
            $newRow.find(".purchase-quantity, .discount-percent, .product-price").on("input", function() {
                updateRow($newRow); // Update calculations when values change
                updateFooter(); // Update footer
            });

            // Set up event listener for deleting a product row
            $newRow.find(".delete-product").on("click", function() {
                table.row($newRow).remove().draw(); // Remove row from table
                updateFooter(); // Update footer
            });
        }

        // Update row calculations
        function updateRow($row) {
            const quantity = parseFloat($row.find(".purchase-quantity").val()) || 0;
            const price = parseFloat($row.find(".product-price").val()) || 0;
            const discountPercent = parseFloat($row.find(".discount-percent").val()) || 0;

            const discountedPrice = price - (price * discountPercent) / 100;
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

            $('#purchase-total').text(`Purchase Total: $ ${finalTotal.toFixed(2)}`);
            $('#discount-display').text(`(-) $ ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) $ ${taxAmount.toFixed(2)}`);
        }
        // Update footer when discount or tax values change
        $('#discount-type, #discount-amount, #tax-type').on('change input', updateFooter);

        // Handle form submission
        $('#purchaseButton').on('click', function(event) {
            event.preventDefault(); // Prevent default form submission

            if (!$('#purchaseForm').valid()) {
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            const formData = new FormData($('#purchaseForm')[0]);
            formData.append('payment_method', document.getElementById('payment-method').value);

            const locationId = document.getElementById('services')?.value || '';
            let purchaseDate = document.getElementById('purchase-date')?.value || '';

            if (purchaseDate) {
                const dateParts = purchaseDate.split('-');
                if (dateParts.length === 3) {
                    purchaseDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                }
            }

            formData.append('location_id', locationId);
            formData.append('purchase_date', purchaseDate);

            const fileInput = $('#attach_document')[0];
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'application/pdf'];
                if (validTypes.includes(file.type)) {
                    formData.append('attached_document', file);
                }
            }

            const productTableRows = document.querySelectorAll('#purchase_product tbody tr');
            productTableRows.forEach((row, index) => {
                const productId = row.querySelector('.product-id').textContent.trim() || '';
                const quantity = row.querySelector('.purchase-quantity').value.trim() || '0';
                const price = row.querySelector('.product-price').value.trim() || '0';
                const total = row.querySelector('.line-total').textContent.trim() || '0';
                const batchId = row.querySelector('.batch-id').value.trim() || '';

                formData.append(`products[${index}][product_id]`, productId);
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][price]`, price);
                formData.append(`products[${index}][total]`, total);
                formData.append(`products[${index}][location_id]`, locationId);
                formData.append(`products[${index}][batch_id]`, batchId);
            });

            // Log form data for debugging
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            $.ajax({
                url: 'purchases/store',
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
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Product Added');
                        resetFormAndValidation();
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Something went wrong! Please try again.', 'Error');
                },
            });
        });

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

        // Event listener for dynamic table changes
        $('#purchase_product').on('input', '.purchase-quantity, .discount-percent, .product-tax', function() {
            const row = $(this).closest('tr');
            updateRow(row);
            updateFooter();
        });

        // Event listener for deleting a row
        $('#purchase_product').on('click', '.delete-product', function() {
            $(this).closest('tr').remove();
            updateFooter();
            toastr.info('Product removed from the table.', 'Info');
        });

        // Fetch suppliers using AJAX
        $('#supplier-id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const supplierDetails = selectedOption.data('details');

            if (supplierDetails) {
                const openingBalance = parseFloat(supplierDetails.opening_balance || 0);
                $('#advance-payment').val(openingBalance.toFixed(2));
                const paymentDue = calculatePaymentDue(openingBalance);
                $('.payment-due').text(paymentDue.toFixed(2));
                $('#supplier-name').text(`${supplierDetails.first_name} ${supplierDetails.last_name}`);
                $('#supplier-phone').text(supplierDetails.mobile_no);
            }
        });

        // Example function to calculate payment due
        function calculatePaymentDue(openingBalance) {
            const totalPurchase = parseFloat($('#purchase-total').val() || 0);
            return totalPurchase - openingBalance;
        }



        // Send AJAX request to fetch purchases data
        $.ajax({
            url: 'get-all-purchases', // API endpoint
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log(response);

                // Check if the response contains purchases data
                if (response && response.purchases) {
                    let purchases = response.purchases;

                    let tableBody = $('#purchase-table-body'); // Table body selector

                    // Clear the table before populating
                    tableBody.empty();

                    // Loop through each purchase and populate the table
                    purchases.forEach(function(purchase) {
                        let purchaseRow = `
                <tr data-id="${purchase.id}">
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
                  <td>${purchase.supplier.full_name}</td> <!-- You can replace this with supplier name if needed -->
                  <td>${purchase.purchasing_status}</td>
                  <td>${purchase.payment_status}</td>
                  <td>${purchase.final_total}</td>
                  <td>${purchase.total - purchase.final_total}</td> <!-- Assuming 'total' - 'final_total' gives the payment due -->
                </tr>
              `;

                        // Append the row to the table body
                        tableBody.append(purchaseRow);
                    });

                    // Initialize or reinitialize the DataTable after adding rows
                    $('#purchase-list').DataTable();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching purchases:', error);
            }
        });

        // Row Click Event
        $('#purchase-list').on('click', 'tr', function(e) {
            if (!$(e.target).closest('button').length) {
                var purchaseId = $(this).data('id'); // Extract product ID from data-id attribute
                $('#viewPurchaseProductModal').modal('show');

                // Send AJAX request to fetch purchase details
                $.ajax({
                    url: '/get-all-purchases-product/' + purchaseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.purchase) {
                            const purchase = response.purchase;
                            const supplier = purchase.supplier;
                            const location = purchase.location;
                            const purchaseProducts = purchase.purchase_products;
                            const purchasePayment = purchase.purchase_payment;

                            // Set modal title
                            $('#modalTitle').text(
                                `Purchase Details (#${purchase.reference_no})`);

                            // Populate Supplier Details
                            $('#supplierDetails').html(`
                  ${supplier.prefix} ${supplier.first_name} ${supplier.last_name}<br>
                  Mobile: ${supplier.mobile_no}<br>
                  Email: ${supplier.email}
                `);

                            // Populate Location Details
                            $('#locationDetails').html(`
                  ${location.name},<br>
                  ${location.address},<br>
                  ${location.city}, ${location.district}, ${location.province}<br>
                  Email: ${location.email}<br>
                  Mobile: ${location.mobile}
                `);

                            // Populate Purchase Details
                            $('#purchaseDetails').html(`
                  Reference No: ${purchase.reference_no}<br>
                  Date: ${new Date(purchase.purchase_date).toLocaleDateString()}<br>
                  Purchase Status: ${purchase.purchasing_status}<br>
                  Payment Status: ${purchase.payment_status}
                `);

                            // Populate Products
                            const productsTableBody = $('#productsTable tbody');
                            productsTableBody.empty(); // Clear any existing rows
                            purchaseProducts.forEach((product, index) => {
                                productsTableBody.append(`
                    <tr>
                      <td>${index + 1}</td>
                      <td>${product.product.product_name}</td>
                      <td>${product.product.sku}</td>
                      <td>${product.quantity}</td>
                      <td>${product.price}</td>
                      <td>${product.total}</td>
                    </tr>
                  `);
                            });

                            // Populate Payment Info
                            const paymentInfoTableBody = $('#paymentInfoTable tbody');
                            paymentInfoTableBody.empty(); // Clear any existing rows
                            paymentInfoTableBody.append(`
                  <tr>
                    <td>${new Date(purchasePayment.payment_date).toLocaleDateString()}</td>
                    <td>${purchasePayment.id}</td>
                    <td>${purchasePayment.amount}</td>
                    <td>${purchasePayment.payment_method}</td>
                    <td>${purchasePayment.payment_note || 'N/A'}</td>
                  </tr>
                `);

                            // Populate Amount Details
                            const amountDetailsTableBody = $('#amountDetailsTable tbody');
                            amountDetailsTableBody.empty(); // Clear any existing rows
                            amountDetailsTableBody.append(`
                  <tr>
                    <td>Net Total Amount:</td>
                    <td>${purchase.total}</td>
                  </tr>
                  <tr>
                    <td>Discount:</td>
                    <td>${purchase.discount_amount}</td>
                  </tr>
                  <tr>
                    <td>Purchase Total:</td>
                    <td>${purchase.final_total}</td>
                  </tr>
                `);

                            // Open the modal
                            $('#viewPurchaseProductModal').modal('show');
                        } else {
                            alert('Failed to load purchase details.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching purchase details:', error);
                        alert('Error fetching purchase details.');
                    }
                });
            }
        });
    });
</script>
