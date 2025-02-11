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

        <!-- Billing Option Selection -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Select Billing Option</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="billingOption" id="withBill" value="withBill" checked>
                            <label class="form-check-label" for="withBill">
                                With Bill
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="billingOption" id="withoutBill" value="withoutBill">
                            <label class="form-check-label" for="withoutBill">
                                Without Bill
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Common Details Section -->

        <!-- Purchase Return Form -->
        <div class="card">
            <div class="card-body">
                <form id="salesReturnForm">

                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title">Common Details</h4>
                            <div class="row">
                                 <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date:</label>
                                            <input type="date" class="form-control" id="date" name="return_date" required>
                                        </div>
                                    </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="locationId" class="form-label">Location:</label>
                                        <select id="locationId" name="location_id" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <!-- Populate with locations -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="isDefective" name="is_defective" value="1">
                                        <label class="form-check-label" for="isDefective">Is Defective</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details Section -->
                    <div class="card mb-4" id="invoiceDetailsSection">
                        <div class="card-body">
                            <h4 class="card-title">Sales Return</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Parent Sales</strong></p>
                                    <div class="mb-3">
                                        <label for="invoiceNo" class="form-label">Invoice No.:</label>
                                        <input type="text" class="form-control" id="invoiceNo" name="invoiceNo" placeholder="Enter Invoice Number">
                                        <input type="hidden" class="form-control" id="sale-id" name="sale_id">
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p id="displayInvoiceNo"><strong>Invoice No.:</strong> PR0001</p>
                                    <p id="displayDate"><strong>Date:</strong> 01/16/2025</p>
                                    <p id="displayCustomer"><strong>Customer:</strong></p>
                                    <p id="displayLocation"><strong>Business Location:</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Autocomplete Product Section -->
                    <div class="row mb-3" id="productSearchSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productSearch" class="form-label">Product Search:</label>
                                <input type="text" class="form-control" id="productSearch" placeholder="Search for a product">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="productsTable" class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Retail Price</th>
                                    <th id="stockColumn">Sales Quantity</th>
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

                    <!-- Discount and Return Total -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discountType" class="form-label">Discount Type:</label>
                                <select id="discountType" class="form-select">
                                    <option value="">Select Discount</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="flat">Flat</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="discountAmount" class="form-label">Discount Amount:</label>
                                <input type="number" class="form-control" id="discountAmount" placeholder="Enter discount">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="returnTotal" class="form-label">Return Total:</label>
                                <input type="number" class="form-control" id="returnTotal" name="return_total" placeholder="Enter total return amount" required>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes:</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter return reason" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <p><strong>Total Return Discount:</strong> <span id="totalReturnDiscount">$0.00</span></p>
                            <p><strong>Return Total:</strong> <span id="returnTotalDisplay">$0.00</span></p>
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
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
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

    <style>
        /* General table styling */
        #productsTable th, #productsTable td {
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        #productsTable th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .return-quantity {
            width: 120px;
            height: 40px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .return-quantity:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .quantity-error {
            font-size: 12px;
            color: red;
            margin-top: 5px;
            display: none;
            text-align: left;
        }

        .btn-danger {
            padding: 6px 10px;
            font-size: 14px;
            border-radius: 4px;
            background-color: #dc3545;
            border: none;
            color: white;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .form-check {
            padding-top: 10px;
        }
    </style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Function to toggle between invoice and product search sections
        function toggleSearchSections() {
            const withBill = document.getElementById('withBill').checked;
            const invoiceDetailsSection = document.getElementById('invoiceDetailsSection');
            const productSearchSection = document.getElementById('productSearchSection');
            const productsTableBody = document.getElementById('productsTableBody');
            const stockColumn = document.getElementById('stockColumn');

            if (withBill) {
                invoiceDetailsSection.style.display = 'block';
                productSearchSection.style.display = 'none';
                stockColumn.textContent = 'Sales Quantity';
                setFieldState(true);
            } else {
                invoiceDetailsSection.style.display = 'none';
                productSearchSection.style.display = 'block';
                stockColumn.textContent = 'Current Total Stock';
                setFieldState(false);
            }

            // Clear the product table body
            productsTableBody.innerHTML = '';
        }

        // Function to set the disabled state of input fields
        function setFieldState(enabled) {
            document.getElementById('invoiceNo').disabled = !enabled;
            document.getElementById('sale-id').disabled = !enabled;
            document.getElementById('productSearch').disabled = enabled;
        }

        // Add event listeners to the radio buttons
        document.getElementById('withBill').addEventListener('change', toggleSearchSections);
        document.getElementById('withoutBill').addEventListener('change', toggleSearchSections);

        // Initial toggle based on the default selected option
        toggleSearchSections();
    });
</script>

@include("saleReturn.sale_return_ajax")
@endsection
