@extends('layout.layout')
@section('content')
<div class="container my-5">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Add Sale Return</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Sell</a></li>
                            <li class="breadcrumb-item active">Add Sale Return</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header Section -->
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title">Sales Return</h4>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Parent Purchase</strong></p>
            <p id="displayInvoiceNo"><strong>Invoice No.:</strong> PR0001</p>
            <p id="displayDate"><strong>Date:</strong> 01/16/2025</p>
          </div>
          <div class="col-md-6 text-md-end">
            <p><strong>Supplier:</strong> Supplier ABC</p>
            <p><strong>Business Location:</strong> Main Warehouse</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase Return Form -->
    <div class="card">
      <div class="card-body">
        <form id="salesReturnForm">
          <!-- Invoice Details -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="invoiceNo" class="form-label">Invoice No.:</label>
                <input type="text" class="form-control" id="invoiceNo" placeholder="Enter Invoice Number">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="date" class="form-label">Date:</label>
                <input type="datetime-local" class="form-control" id="date">
              </div>
            </div>
          </div>

          <!-- Customer and Location Select -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="customer_id" class="form-label">Customer:</label>
                <select id="customer_id" class="form-select">
                  <!-- Populate with customers -->
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="location_id" class="form-label">Location:</label>
                <select id="location_id" class="form-select">
                  <!-- Populate with locations -->
                </select>
              </div>
            </div>
          </div>

          <!-- Product Table -->
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Product Name</th>
                  <th>Unit Price</th>
                  <th>Purchase Quantity</th>
                  <th>Return Quantity</th>
                  <th>Return Subtotal</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="productsTableBody">
                <!-- Dynamic Product Rows -->
              </tbody>
            </table>
          </div>

          <!-- Discount Section -->
          <div class="row mt-4">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="discountType" class="form-label">Discount Type:</label>
                <select id="discountType" class="form-select">
                  <option value="percentage">Percentage</option>
                  <option value="flat">Flat</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="discountAmount" class="form-label">Discount Amount:</label>
                <input type="number" class="form-control" id="discountAmount" placeholder="Enter discount">
              </div>
            </div>
          </div>

          <!-- Summary Section -->
          <div class="row mt-4">
            <div class="col-md-6">
              <p><strong>Total Return Discount:</strong> $0.00</p>
              <p><strong>Total Return Tax:</strong> $0.00</p>
              <p><strong>Return Total:</strong> $0.00</p>
            </div>
            <div class="col-md-6 text-md-end">
              <button type="submit" class="btn btn-primary btn-lg">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove this product?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteButton">Remove</button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery, jQuery UI, Bootstrap JS, and Toastr -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script>
$(document).ready(function() {
    let productToRemove;

    // Autocomplete for Invoice No
    $("#invoiceNo").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "/api/search/sales",
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            // When an item is selected, fetch the sales products
            fetchSaleProducts(ui.item.value);
        }
    });

    function fetchSaleProducts(invoiceNo) {
        $.ajax({
            url: `/api/sales/${invoiceNo}`,
            method: 'GET',
            success: function(data) {
                const productsTableBody = $("#productsTableBody");
                productsTableBody.empty();

                data.products.forEach((product, index) => {
                    const row = `
                      <tr data-index="${index}">
                        <td>${index + 1}</td>
                        <td>${product.product.product_name}<br><small class="text-muted">${product.product.sku}</small></td>
                        <td>$${product.product.retail_price}</td>
                        <td>${product.quantity} Pc(s)</td>
                        <td>
                          <input type="number" class="form-control return-quantity" name="products[${index}][quantity]" placeholder="Enter quantity" max="${product.quantity}">
                        </td>
                        <td>$0.00</td>
                        <td><button type="button" class="btn btn-danger remove-product">Remove</button></td>
                      </tr>
                    `;
                    productsTableBody.append(row);
                });

                // Update the display of invoice number and date
                $("#displayInvoiceNo").html(`<strong>Invoice No.:</strong> ${invoiceNo}`);
                $("#displayDate").html(`<strong>Date:</strong> ${new Date(data.products[0].created_at).toLocaleDateString()}`);

                // Populate customer and location fields
                $("#customer_id").val(data.customer_id);
                $("#location_id").val(data.location_id);

                // Add validation for return quantity
                $(".return-quantity").on('input', function() {
                    const max = $(this).attr('max');
                    if (parseInt($(this).val()) > parseInt(max)) {
                        $(this).val(max);
                        toastr.error('Return quantity cannot be greater than the sale quantity.');
                    }
                });

                // Handle remove product button click
                $(".remove-product").on('click', function() {
                    productToRemove = $(this).closest('tr');
                    $('#confirmDeleteModal').modal('show');
                });
            },
            error: function(error) {
                console.error('Error fetching sales data:', error);
            }
        });
    }

     // Fetch locations using AJAX
     $.ajax({
        url: '/location-get-all',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Location Data:', data); // Log location data
            if (data.status === 200) {
                const locationSelect = $('#location_id');
                locationSelect.html('<option selected disabled>Please Select Locations</option>');

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

    // Confirm delete product
    $("#confirmDeleteButton").on('click', function() {
        productToRemove.remove();
        $('#confirmDeleteModal').modal('hide');
        toastr.success('Product removed successfully.');

        // Check if all products are removed and reset the invoice
        if ($("#productsTableBody tr").length === 0) {
            resetInvoice();
        }
    });

    function resetInvoice() {
        $("#displayInvoiceNo").html('<strong>Invoice No.:</strong> PR0001');
        $("#displayDate").html('<strong>Date:</strong> 01/16/2025');
        $("#customer_id").val('');
        $("#location_id").val('');
        $("#invoiceNo").val('');
        toastr.info('All products removed. Invoice reset.');
    }
});
</script>
@endsection
