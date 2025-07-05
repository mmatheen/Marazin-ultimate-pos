@extends('layout.layout')
@section('content')
    <div class="container-fluid my-5">
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
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body text-center">
                <h4 class="card-title mb-4 fw-bold">Select Billing Option</h4>
                <div class="d-flex justify-content-center gap-3">
                    <input type="radio" name="billingOption" id="withBill" value="withBill" class="d-none" checked>
                    <label for="withBill" class="billing-btn">With Bill</label>

                    <input type="radio" name="billingOption" id="withoutBill" value="withoutBill" class="d-none">
                    <label for="withoutBill" class="billing-btn">Without Bill</label>
                </div>
            </div>
        </div>

        <style>
            .billing-btn {
                padding: 12px 24px;
                font-size: 16px;
                font-weight: 600;
                border-radius: 30px;
                cursor: pointer;
                transition: all 0.3s ease-in-out;
                background: #f8f9fa;
                border: 2px solid #ddd;
                color: #333;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
                display: inline-block;
                min-width: 140px;
            }

            .billing-btn:hover {
                background: #e9ecef;
                border-color: #bbb;
            }

            input[type="radio"]:checked+.billing-btn {
                background: #007bff;
                border-color: #007bff;
                color: white;
                box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            }
        </style>


        <!-- Common Details Section -->

        <!-- Sale Return Form -->
        <div class="card">
            <div class="card-body">
                <form id="salesReturnForm">

                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title">Common Details</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Date:</label>
                                        <input type="date" class="form-control" id="date" name="return_date"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="locationId" class="form-label">Location:</label>
                                        <select id="locationId" name="location_id" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <!-- Populate with locations -->
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="isDefective" name="is_defective"
                                            value="1">
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
                                        <input type="text" class="form-control" id="invoiceNo" name="invoiceNo"
                                            placeholder="Enter Invoice Number">
                                        <input type="hidden" class="form-control" id="sale-id" name="sale_id">
                                        <input type="hidden" class="form-control" id="customer-id" name="customer_id">
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

                    <!-- Customer Selection Section for Without Bill -->
                    <div class="card mb-4" id="customerSelectionSection" style="display: none;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customerId" class="form-label">Customer:</label>
                                        <select id="customerId" name="customer_id" class="form-select">
                                            <option value="">Select Customer</option>
                                            <!-- Populate with customers -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary mt-4" id="addCustomerButton">
                                        Add New Customer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Autocomplete Product Section -->
                    <div class="row mb-3" id="productSearchSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productSearch" class="form-label">Product Search:</label>
                                <input type="text" class="form-control" id="productSearch"
                                    placeholder="Search for a product">
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
                                <input type="number" class="form-control" id="discountAmount"
                                    placeholder="Enter discount">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="returnTotal" class="form-label">Return Total:</label>
                                <input type="number" class="form-control" id="returnTotal" name="return_total"
                                    placeholder="Enter total return amount" required>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes:</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter return reason"
                                    required></textarea>
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
        #productsTable th,
        #productsTable td {
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

        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .quantity-error {
            display: none;
            color: red;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleSearchSections() {
                const withBill = document.getElementById('withBill').checked;
                const invoiceDetailsSection = document.getElementById('invoiceDetailsSection');
                const productSearchSection = document.getElementById('productSearchSection');
                const productsTableBody = document.getElementById('productsTableBody');
                const stockColumn = document.getElementById('stockColumn');
                const customerSelectionSection = document.getElementById('customerSelectionSection');

                if (withBill) {
                    invoiceDetailsSection.style.display = 'block';
                    productSearchSection.style.display = 'none';
                    customerSelectionSection.style.display = 'none';
                    stockColumn.textContent = 'Sales Quantity';
                    setFieldState(true);

                    // Only use hidden input for customer_id
                    document.getElementById('customer-id').setAttribute('name', 'customer_id');
                    document.getElementById('customerId').removeAttribute('name');
                } else {
                    invoiceDetailsSection.style.display = 'none';
                    productSearchSection.style.display = 'block';
                    customerSelectionSection.style.display = 'block';
                    stockColumn.textContent = 'Current Total Stock';
                    setFieldState(false);

                    // Only use select input for customer_id
                    document.getElementById('customer-id').removeAttribute('name');
                    document.getElementById('customerId').setAttribute('name', 'customer_id');
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set the default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;

            // Initialize the date picker
            $('#date').on('focus', function() {
                $(this).attr('type', 'date');
            });

            // Show date picker on click anywhere on the input
            $('#date').on('click', function() {
                $(this).attr('type', 'date').focus();
            });
        });
    </script>

    @include('contact.customer.add_customer_modal')
    @include('contact.customer.customer_ajax')
    @include('saleReturn.sale_return_ajax')
@endsection
