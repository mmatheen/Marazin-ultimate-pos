
<script>
    document.addEventListener('DOMContentLoaded', function () {
     const productSearchInput = document.getElementById('productSearchInput');
     const productTableBody = document.getElementById('productTableBody');
     const totalAmount = document.getElementById('totalAmount');
     let productsData = [];
     let productIndex = 0;

     // Fetch products data from the server
     fetchProductsData();

     function fetchProductsData() {
         $.ajax({
             url: '/products/stocks',
             method: 'GET',
             success: function(response) {
                 if (response.status === 200) {
                     productsData = response.data;
                     setupAutocomplete();
                 }
             },
             error: function(error) {
                 console.error('Error fetching products data:', error);
                 toastr.error('Failed to fetch products data. Please try again.');
             }
         });
     }

     function setupAutocomplete() {
         const productNames = productsData.map(data => data.product.product_name);

         $('#productSearchInput').autocomplete({
             source: productNames,
             select: function(event, ui) {
                 const selectedProduct = productsData.find(data => data.product.product_name === ui.item.value);
                 addProductToTable(selectedProduct);
                 $(this).val('');
                 return false;
             }
         });
     }

     function addProductToTable(productData) {
         const product = productData.product;
         const batches = productData.batches.flatMap(batch => batch.location_batches.map(locationBatch => ({
             batch_id: batch.id,
             batch_price: parseFloat(batch.retail_price),
             batch_quantity: locationBatch.quantity
         })));

         const batchOptions = batches.map(batch => `
             <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}">
                 Batch ${batch.batch_id} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price}
             </option>
         `).join('');

         const newRow = `
             <tr class="add-row">
                 <td>
                     ${product.product_name}
                     <input type="hidden" name="products[${productIndex}][product_id]" value="${product.id}">
                 </td>
                 <td>
                     <select class="form-control batch-select" name="products[${productIndex}][batch_id]" required>
                         ${batchOptions}
                     </select>
                     <div class="error-message batch-error"></div>
                 </td>
                 <td>
                     <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="1" required>
                     <div class="error-message quantity-error text-danger"></div>
                 </td>
                 <td>
                     <input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${batches[0].batch_price}" readonly>
                 </td>
                 <td>
                     <input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${batches[0].batch_price}" readonly>
                 </td>
                 <td class="add-remove text-end">
                     <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                 </td>
             </tr>
         `;

         productTableBody.insertAdjacentHTML('beforeend', newRow);
         updateTotal();
         productIndex++;
     }

     $(document).on("change", ".batch-select", function() {
         const row = $(this).closest("tr");
         const selectedBatch = row.find(".batch-select option:selected");
         const unitPrice = parseFloat(selectedBatch.data("price"));
         row.find(".unit-price").val(unitPrice.toFixed(2));
         const quantity = parseFloat(row.find(".quantity-input").val());
         const subtotal = quantity * unitPrice;
         row.find(".sub_total").val(subtotal.toFixed(2));
         updateTotal();
     });

     $(document).on("change", ".quantity-input", function() {
         const row = $(this).closest("tr");
         const quantity = parseFloat(row.find(".quantity-input").val());
         const selectedBatch = row.find(".batch-select option:selected");
         const unitPrice = parseFloat(selectedBatch.data("price"));
         const availableQuantity = parseFloat(selectedBatch.data("quantity"));

         if (quantity > availableQuantity) {
             row.find(".quantity-error").text("The quantity exceeds the available batch quantity.");
             row.find(".quantity-input").val(availableQuantity);
         } else {
             row.find(".quantity-error").text("");
         }

         const subtotal = quantity * unitPrice;
         row.find(".unit-price").val(unitPrice.toFixed(2));
         row.find(".sub_total").val(subtotal.toFixed(2));
         updateTotal();
     });

     $(document).on("click", ".remove-btn", function() {
         $(this).closest(".add-row").remove();
         updateTotal();
         return false;
     });

     function updateTotal() {
         let total = 0;
         $(".add-row").each(function() {
             const subtotal = parseFloat($(this).find('input[name^="products"][name$="[sub_total]"]').val());
             total += subtotal;
         });
         $('#totalAmount').text(total.toFixed(2));
     }

     fetchDropdownData('/location-get-all', $('#location_id'), "Select Location");

     function fetchDropdownData(url, targetSelect, placeholder, selectedId = null) {
         $.ajax({
             url: url,
             method: 'GET',
             dataType: 'json',
             success: function(data) {
                 if (data.status === 200 && Array.isArray(data.message)) {
                     targetSelect.html(`<option value="" selected disabled>${placeholder}</option>`);
                     data.message.forEach(item => {
                         const option = $('<option></option>').val(item.id).text(item.name || item.first_name + ' ' + item.last_name);
                         if (item.id == selectedId) {
                             option.attr('selected', 'selected');
                         }
                         targetSelect.append(option);
                     });
                 } else {
                     console.error(`Failed to fetch data: ${data.message}`);
                     toastr.error('Failed to fetch dropdown data. Please try again.');
                 }
             },
             error: function(xhr, status, error) {
                 console.error(`Error fetching data: ${error}`);
                 toastr.error('Failed to fetch dropdown data. Please try again.');
             }
         });
     }

     // jQuery Validation
     $('#stockAdjustmentForm').validate({
         rules: {
             location_id: { required: true },
             date: { required: true },
             adjustmentType: { required: true },
             reason: { required: true },
         },
         messages: {
             location_id: { required: "Please select a business location." },
             date: { required: "Please select a date." },
             adjustmentType: { required: "Please select an adjustment type." },
             reason: { required: "Please provide a reason for the adjustment." },
         },
         errorPlacement: function(error, element) {
             error.appendTo(element.closest('.form-group').find('.error-message'));
         },
         submitHandler: function(form) {
             submitForm(form);
         }
     });



     function submitForm(form) {
        const submitButton = $(form).find('button[type="submit"]');
        submitButton.prop('disabled', true); // Disable the button

         const formData = $(form).serialize();

    $.ajax({
        url: '/api/stock-adjustment/store',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
        data: formData,
        success: function(data) {
            if (data.message) {
                toastr.success(data.message, 'Adjust');
                // Redirect or reload the page
              location.reload();
            } else {
                if (data.errors) {
                    for (const [key, value] of Object.entries(data.errors)) {
                        toastr.error(value.join(', '));
                    }
                } else {
                    toastr.error(data.message || 'Failed to create stock adjustment.');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            toastr.error('An error occurred. Please try again.');
        },
        complete: function() {
            submitButton.prop('disabled', false); // Re-enable the button
        }
    });
}

 fetchData();

            function fetchData() {
                $.ajax({
                    url: '/stock-adjustments', // API endpoint
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            populateDataTable(response.stockAdjustment);
                        } else {
                            console.error('Error fetching stock adjustments:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching stock adjustments:', error);
                    }
                });
            }

            function populateDataTable(data) {
                var table = $('#stockAdjustmentTable').DataTable({
                    destroy: true, // Destroy existing table to reinitialize
                    data: data,
                    columns: [
                        { data: 'date' },
                        { data: 'reference_no' },
                        { data: 'location.name' }, // Access nested location name
                        { data: 'adjustment_type' },
                        {
                            data: 'adjustment_products',
                            render: function(data, type, row) {
                                // Calculate total amount from adjustment_products
                                let totalAmount = data.reduce((sum, product) => sum + parseFloat(product.subtotal), 0);
                                return totalAmount.toFixed(2);
                            }
                        },
                        { data: 'total_amount_recovered' },
                        { data: 'reason' },
                        { data: 'added_by' }, // Assuming 'added_by' is part of the response
                        {
                            data: 'id',
                            render: function(data, type, row) {
                                // Add action buttons (Edit, Delete, etc.)
                                return `
                                    <a href="/edit-stock-adjustment/${data}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button onclick="deleteStockAdjustment(${data})" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                            }
                        }
                    ],
                    columnDefs: [
                        { targets: [8], orderable: false } // Disable sorting for the action column
                    ]
                });
            }

            // Example function for deleting a stock adjustment
            function deleteStockAdjustment(id) {
                if (confirm('Are you sure you want to delete this stock adjustment?')) {
                    $.ajax({
                        url: `/delete-stock-adjustment/${id}`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.status === 200) {
                                alert('Stock adjustment deleted successfully.');
                                fetchData(); // Refresh the table
                            } else {
                                alert('Failed to delete stock adjustment.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error deleting stock adjustment:', error);
                        }
                    });
                }
            }


            $(document).ready(function() {


                fetchData();

function fetchData() {
    $.ajax({
        url: '/stock-adjustments', // API endpoint
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 200) {
                populateDataTable(response.stockAdjustment);
            } else {
                console.error('Error fetching stock adjustments:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching stock adjustments:', error);
        }
    });
}

function populateDataTable(data) {
    var table = $('#stockAdjustmentTable').DataTable({
        destroy: true, // Destroy existing table to reinitialize
        data: data,
        columns: [
            { data: 'date' },
            { data: 'reference_no' },
            { data: 'location.name' }, // Access nested location name
            { data: 'adjustment_type' },
            {
                data: 'adjustment_products',
                render: function(data, type, row) {
                    // Calculate total amount from adjustment_products
                    let totalAmount = data.reduce((sum, product) => sum + parseFloat(product.subtotal), 0);
                    return totalAmount.toFixed(2);
                }
            },
            { data: 'total_amount_recovered' },
            { data: 'reason' },
            { data: 'added_by' }, // Assuming 'added_by' is part of the response
            {
                data: 'id',
                render: function(data, type, row) {
                    // Add action buttons (Edit, Delete, etc.)
                    return `
                        <a href="/edit-stock-adjustment/${data}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>

                        <button onclick="deleteStockAdjustment(${data})" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        columnDefs: [
            { targets: [8], orderable: false } // Disable sorting for the action column
        ]
    });
}









// Example function for deleting a stock adjustment
function deleteStockAdjustment(id) {
    if (confirm('Are you sure you want to delete this stock adjustment?')) {
        $.ajax({
            url: `/delete-stock-adjustment/${id}`,
            type: 'DELETE',
            success: function(response) {
                if (response.status === 200) {
                    alert('Stock adjustment deleted successfully.');
                    fetchData(); // Refresh the table
                } else {
                    alert('Failed to delete stock adjustment.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deleting stock adjustment:', error);
            }
        });
    }
}

});
 });

 </script>
