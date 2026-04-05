<!-- Modal to show sale details -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1" aria-labelledby="saleDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalTitle"></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Customer:</h5>
                            <p id="customerDetails"></p>
                        </div>
                        <div class="col-md-4">
                            <h5>Location:</h5>
                            <p id="locationDetails"></p>
                        </div>
                        <div class="col-md-4">
                            <h5>Sales Details:</h5>
                            <p id="salesDetails"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-3">
                            <h5>Sales Notes:</h5>
                            <p id="salesNotes" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-4">
                            <h5>Products:</h5>
                            <table class="table table-bordered" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mt-4">
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
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="col-md-6 mt-4">
                            <h5>Amount Details:</h5>
                            <table class="table" id="amountDetailsTable">
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Activities:</h5>
                            <table class="table table-bordered" id="activitiesTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>By</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4">No records found.</td>
                                    </tr>
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
                <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentId" name="payment_id">
                    <input type="hidden" id="saleId" name="reference_id">
                    <input type="hidden" id="payment_type" name="payment_type">
                    <input type="hidden" id="customer_id" name="customer_id">
                    <input type="hidden" id="reference_no" name="reference_no">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Customer</h5>
                                    <p id="paymentCustomerDetail" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Reference No</h5>
                                    <p id="referenceNo" style="margin: 0;"></p>
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Location</h5>
                                    <p id="paymentLocationDetails" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total Amount</h5>
                                    <p id="totalAmount" style="margin: 0;"></p>
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total Paid Amount</h5>
                                    <p id="totalPaidAmount" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
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
                                <input class="form-control datetimepicker" type="text" name="payment_date" id="paidOn" placeholder="DD-MM-YYYY">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="payAmount" class="form-label">Amount</label>
                                <input type="text" class="form-control" id="payAmount" name="amount" oninput="validateAmount()">
                                <div id="amountError" class="text-danger" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div id="cardFields" class="row mb-3" style="display:none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" name="card_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Card Holder Name</label>
                                <input type="text" class="form-control" name="card_holder_name">
                            </div>
                        </div>
                    </div>

                    <div id="chequeFields" class="row mb-3" style="display:none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" class="form-control" name="cheque_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bank Branch</label>
                                <input type="text" class="form-control" name="cheque_bank_branch">
                            </div>
                        </div>
                    </div>

                    <div id="bankTransferFields" class="row mb-3" style="display:none;">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Bank Account Number</label>
                                <input type="text" class="form-control" name="bank_account_number">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="paymentNotes" class="form-label">Payment Note</label>
                        <textarea class="form-control" id="paymentNotes" name="payment_note"></textarea>
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

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPaymentModalLabel">View Payments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer:</strong>
                        <p id="viewCustomerDetail"></p>
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
                    {{-- "Add payment" is handled by sales table action buttons; avoid inline JS dependencies here --}}
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
                <button class="btn btn-dark" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

