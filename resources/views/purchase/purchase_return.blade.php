@extends('layout.layout')
@section('content')
@php
    $canUseFreeQty = (bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && auth()->user()?->can('use free quantity');
@endphp
    <div class="content container-fluid">
        <style>
            .login-fields1,
            .login-fields2,
            .login-fields3,
            .hidden,
            .hiddenway_two_action {
                display: none;
            }

            .modal-xl {
                max-width: 90%;
            }

            .modal-body {
                height: 75vh;
                overflow-y: auto;
            }

            @media print {
                .modal-dialog {
                    max-width: 100%;
                    margin: 0;
                    padding: 0;
                }

                .modal-content {
                    border: none;
                }

                .modal-body {
                    height: auto;
                    overflow: visible;
                }

                body * {
                    visibility: hidden;
                }

                .modal-content,
                .modal-content * {
                    visibility: visible;
                }
            }
        </style>
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Purchases Return</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Purchases</li>
                                <li class="breadcrumb-item active">List Purchases Return</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <p>
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample"
                    aria-expanded="false" aria-controls="collapseExample">
                    Filters
                </button>
            </p>
            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Business Location <span class="login-danger"></span></label>
                                        <select class="form-control select">
                                            <option>All</option>
                                            <option>Awesomeshop</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Date Range<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="01/01/2024 - 12/31/2024">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->
                                @can('create purchase-return')
                                <a href="{{ route('add-purchase-return') }}"><button type="button"
                                    class="btn btn-outline-info">
                                    <i class="fas fa-plus px-2"> </i>Add
                                </button></a>
                                @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="purchase_return_list">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Parent Purchase</th>
                                        <th>Location</th>
                                        <th>Supplier</th>
                                        <th>Payment Status</th>
                                        <th>Grand Total</th>
                                        <th>Payment Due</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="viewPurchaseReturnProductModal" tabindex="-1"
        aria-labelledby="viewPurchaseReturnProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalTitle"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Supplier:</h5>
                                <p id="supplierDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Location:</h5>
                                <p id="locationDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Purchase Details:</h5>
                                <p id="purchaseDetails"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mt-5">
                                <h5>Products:</h5>
                                <table class="table table-bordered" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                            @if($canUseFreeQty)<th>Free Qty</th>@endif
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Product rows will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <h5>Payment Info:</h5>
                                <table class="table table-bordered" id="paymentInfoTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference No</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Payment Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Payment info will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mt-3">
                                <h5>Amount Details:</h5>
                                <table class="table" id="amountDetailsTable">
                                    <tbody>
                                        <!-- Amount details will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-secondary" onclick="printModal()">Print</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Add payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="purchaseReturnId" name="reference_id">
                        <input type="hidden" id="payment_type" name="payment_type">
                        <input type="hidden" id="supplier_id" name="supplier_id">
                        <input type="hidden" id="reference_no" name="reference_no">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Supplier
                                        </h5>
                                        <p id="paymentSupplierDetail" style="margin: 0;"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Reference
                                            No</h5>
                                        <p id="referenceNo" style="margin: 0;"></p>
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Location
                                        </h5>
                                        <p id="paymentLocationDetails" style="margin: 0;"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total
                                            Amount</h5>
                                        <p id="totalAmount" style="margin: 0;"></p>
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total Paid
                                            Amount</h5>
                                        <p id="totalPaidAmount" style="margin: 0;"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Additional form elements -->
                        <div class="row">
                            <div class="col-md-12">
                                <label for="advanceBalance" class="form-label">Advance Balance : Rs. 0.00</label>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="paymentMethod" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod" name="payment_method"
                                        onchange="togglePaymentFields()">
                                        <option value="cash" selected>Cash</option>
                                        <option value="card">Credit Card</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="paidOn" class="form-label">Paid On</label>
                                    <input class="form-control datetimepicker" type="text" name="payment_date"
                                        id="paidOn" placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="payAmount" class="form-label">Amount</label>
                                    <input type="text" class="form-control" id="payAmount" name="amount">
                                    <div id="amountError" class="text-danger" style="display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditional Payment Fields -->
                        <div id="creditCardFields" class="row mb-3 d-none">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cardNumber" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="cardNumber" name="card_number">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cardHolderName" class="form-label">Card Holder Name</label>
                                    <input type="text" class="form-control" id="cardHolderName"
                                        name="card_holder_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cardType" class="form-label">Card Type</label>
                                    <select class="form-select" id="cardType" name="card_type">
                                        <option value="visa">Visa</option>
                                        <option value="mastercard">MasterCard</option>
                                        <option value="amex">American Express</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expiryMonth" class="form-label">Expiry Month</label>
                                    <input type="text" class="form-control" id="expiryMonth"
                                        name="card_expiry_month">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expiryYear" class="form-label">Expiry Year</label>
                                    <input type="text" class="form-control" id="expiryYear" name="card_expiry_year">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="securityCode" class="form-label">Security Code</label>
                                    <input type="text" class="form-control" id="securityCode"
                                        name="card_security_code">
                                </div>
                            </div>
                        </div>

                        <div id="chequeFields" class="row mb-3 d-none">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="chequeNumber" class="form-label">Cheque Number</label>
                                    <input type="text" class="form-control" id="chequeNumber" name="cheque_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bankBranch" class="form-label">Bank Branch</label>
                                    <input type="text" class="form-control" id="bankBranch"
                                        name="cheque_bank_branch">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cheque_received_date" class="form-label">Check Recieved Date</label>
                                    <input type="text" class="form-control datetimepicker" id="cheque_received_date"
                                        name="cheque_received_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                                    <input type="text" class="form-control datetimepicker" id="cheque_valid_date"
                                        name="cheque_valid_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cheque_given_by" class="form-label">Check Given by</label>
                                    <input type="text" class="form-control" id="cheque_given_by"
                                        name="cheque_given_by">
                                </div>
                            </div>
                        </div>

                        <div id="bankTransferFields" class="row mb-3 d-none">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="bankAccountNumber" class="form-label">Bank Account Number</label>
                                    <input type="text" class="form-control" id="bankAccountNumber"
                                        name="bank_account_number">
                                </div>
                            </div>
                        </div>

                        <!-- Remaining Form Elements -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentAccount" class="form-label">Payment Account</label>
                                    <select class="form-select" id="paymentAccount" name="payment_account">
                                        <option selected>None</option>
                                        <option value="1">Account 1</option>
                                        <option value="2">Account 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="attachDocument" class="form-label">Attach Document</label>
                                    <input type="file" class="form-control" id="attachDocument"
                                        name="attach_document" accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNote" class="form-label">Payment Note</label>
                            <textarea class="form-control" id="paymentNote" name="payment_note"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="savePayment">Save</button>
                </div>
            </div>
        </div>
    </div>



    @include('purchase.purchase_return_ajax')
    <script>
        function togglePaymentFields() {
            const paymentMethod = document.getElementById('paymentMethod').value;
            const creditCardFields = document.getElementById('creditCardFields');
            const chequeFields = document.getElementById('chequeFields');
            const bankTransferFields = document.getElementById('bankTransferFields');

            creditCardFields.classList.add('d-none');
            chequeFields.classList.add('d-none');
            bankTransferFields.classList.add('d-none');

            // Clear all fields when changing payment method
            $('#cardNumber, #cardHolderName, #expiryMonth, #expiryYear, #securityCode, #chequeNumber, #bankBranch, #cheque_received_date, #cheque_valid_date, #cheque_given_by, #bankAccountNumber')
                .val('');
            $('.error').remove(); // Remove any existing error messages

            if (paymentMethod === 'card') {
                creditCardFields.classList.remove('d-none');
            } else if (paymentMethod === 'cheque') {
                chequeFields.classList.remove('d-none');
            } else if (paymentMethod === 'bank_transfer') {
                bankTransferFields.classList.remove('d-none');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const cardNumberInput = document.getElementById("cardNumber");
            const expiryMonthInput = document.getElementById("expiryMonth");
            const expiryYearInput = document.getElementById("expiryYear");
            const securityCodeInput = document.getElementById("securityCode");
            const chequeNumberInput = document.getElementById("chequeNumber");

            // Format card number (max 16 digits, spaced every 4)
            cardNumberInput.addEventListener("input", function(e) {
                let value = this.value.replace(/\D/g, "").substring(0, 16);
                value = value.replace(/(\d{4})/g, "$1 ").trim();
                this.value = value;
            });

            // Validate expiry month (only 1-12 allowed)
            expiryMonthInput.addEventListener("input", function() {
                let value = this.value.replace(/\D/g, "").substring(0, 2);
                let month = parseInt(value);
                if (month < 1 || month > 12) {
                    alert("Invalid month! Please enter a value between 1 and 12.");
                    this.value = "";
                } else {
                    this.value = value;
                }
            });

            // Validate security code (3 digits only)
            securityCodeInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, "").substring(0, 3);
            });

            // Validate cheque number (max 12 digits)
            chequeNumberInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, "").substring(0, 12);
            });
        });

    </script>

      <!-- View Payment Modal -->
      <div class="modal fade" id="viewPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
      aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="viewPaymentModalLabel">View Payments ( Reference No: <span
                          id="viewReferenceNo">PO2018/0002</span> )</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                  <div class="row mb-3">
                      <div class="col-md-6">
                          <strong>Supplier:</strong>
                          <p id="viewSupplierDetail"></p>
                      </div>
                      <div class="col-md-6">
                          <strong>Business:</strong>
                          <p id="viewBusinessDetail"></p>
                      </div>
                  </div>
                  <div class="row mb-3">
                      <div class="col-md-6">
                          <strong>Reference No:</strong> <span id="viewReferenceNo"></span><br>
                          <strong>Date:</strong> <span id="viewDate"></span><br>
                      </div>
                      <div class="col-md-6">
                          <strong>Purchase Status:</strong> <span id="viewPurchaseStatus"></span><br>
                          <strong>Payment Status:</strong> <span id="viewPaymentStatus"></span>
                      </div>
                  </div>
                  <div class="d-flex justify-content-between mb-3">
                    <button id="addPayment" class="btn btn-outline-primary">
                        <i class="fas fa-plus-circle"></i> Add payment
                    </button>
                </div>
                  <table class="table table-bordered">
                      <thead>
                          <tr>
                              <th>Date</th>
                              <th>Reference No</th>
                              <th>Amount</th>
                              <th>Payment Method</th>
                              <th>Payment Note</th>
                              <th>Payment Account</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr>
                              <td colspan="7" class="text-center">No records found</td>
                          </tr>
                      </tbody>
                  </table>
              </div>
              <div class="modal-footer">
                  <button class="btn btn-primary"> <i class="fas fa-print"></i> Print</button>
                  <button class="btn btn-dark" data-bs-dismiss="modal">Close</button>
              </div>
          </div>
      </div>
  </div>

@endsection
