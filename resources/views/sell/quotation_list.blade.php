@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Quotation List</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('list-sale') }}">Sell</a></li>
                                <li class="breadcrumb-item active">Quotation List</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>
            <div>
                {{-- <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Bussiness Location <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="location">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="customer">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status<span class="login-danger">*</span></label>
                                        <select class="form-control select" name="payment_status">
                                            <option value="" disabled selected>All</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Due">Due</option>
                                            <option value="Partial">Partial</option>
                                            <option value="Overdue">Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range<span class="login-danger"></span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY"
                                            name="date_range">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>User<span class="login-danger">*</span></label>
                                        <select class="form-control select" name="user">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="shipping_status">
                                            <option value="" disabled selected>All</option>
                                            <option value="Order">Order</option>
                                            <option value="Packed">Packed</option>
                                            <option value="Shipped">Shipped</option>
                                            <option value="Delivered">Delivered</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="payment_method">
                                            <option value="" disabled selected>All</option>
                                            <option value="Advance">Advance</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Sources <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="sources">
                                            <option value="" disabled selected>All</option>
                                            <option value="Woocommerce">Woocommerce</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tab-content">
                                <div class="tab-pane show active" id="solid-justified-tab1">
                                    <div class="card-body">
                                        <div class="page-header">
                                            <div class="row align-items-center">
                                                <div class="col-auto text-end float-end ms-auto download-grp">
                                                    <!-- Button trigger modal -->
                                                    {{-- <a href="#"><button type="button" class="btn btn-outline-info">
                                                            <i class="fas fa-plus px-2"> </i>Add
                                                        </button></a> --}}
                                                </div>
                                            </div>
                                        </div>



                                        <div class="table-responsive">
                                            {{-- <div class="mb-3">
                                                <button id="bulkPaymentBtn" class="btn btn-primary">
                                                    Add Bulk Payment
                                                </button>
                                            </div> --}}
                                            <table id="draftSalesTable" class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Action</th>
                                                        <th>Date</th>
                                                        <th>Invoice No.</th>
                                                        <th>Customer Name</th>
                                                        <th>Contact Number</th>
                                                        <th>Location</th>
                                                        <th>Payment Status</th>
                                                        <th>Payment Method</th>
                                                        <th>Total Amount</th>
                                                        <th>Total Items</th>
                                                        <th>Added By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Rows will be dynamically added here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal to show sale details -->
    <div class="modal fade" id="saleDetailsModal" tabindex="-1" aria-labelledby="saleDetailsModalLabel" aria-hidden="true">
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
                            <div class="col-md-12  mt-4">
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
                                    <tbody>
                                        <!-- Product rows will be inserted here dynamically -->
                                    </tbody>
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
                                    <tbody>
                                        <!-- Payment info will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mt-4">
                                <h5>Amount Details:</h5>
                                <table class="table" id="amountDetailsTable">
                                    <tbody>
                                        <!-- Amount details will be inserted here dynamically -->
                                    </tbody>
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

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#draftSalesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '/sales?status=quotation',
                    type: 'GET',
                    dataSrc: function(json) {
                        // Return quotation sales directly
                        if (json.sales && Array.isArray(json.sales)) {
                            return json.sales;
                        }
                        return [];
                    }
                },
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="feather-menu"></i> Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="button" value="${row.id}" class="view-details dropdown-item"><i class="feather-eye text-info"></i> View</button></li>
                                <li><button type="button" value="${row.id}" class="edit_btn dropdown-item"><i class="feather-edit text-info"></i> Edit</button></li>
                                <li><button type="button" value="${row.id}" class="delete_btn dropdown-item"><i class="feather-trash-2 text-danger"></i> Delete</button></li>
                                <li><button type="button" value="${row.id}" class="convert-proforma dropdown-item"><i class="feather-file-text text-primary"></i> Convert to Proforma Invoice</button></li>
                            </ul>
                        </div>
                    `;
                        }
                    },
                    {
                        data: 'sales_date'
                    },
                    {
                        data: 'invoice_no'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return row.customer ? (row.customer.first_name + ' ' + row.customer
                                .last_name) : '';
                        }
                    },
                    {
                        data: 'customer.mobile_no',
                        defaultContent: ''
                    },
                    {
                        data: 'location.name',
                        defaultContent: ''
                    },
                    {
                        data: 'payment_status',
                        render: function(data) {
                            if (data === 'Due') {
                                return '<span class="badge bg-danger">Due</span>';
                            } else if (data === 'Partial') {
                                return '<span class="badge bg-warning">Partial</span>';
                            } else if (data === 'Paid') {
                                return '<span class="badge bg-success">Paid</span>';
                            } else {
                                return '<span class="badge bg-secondary">' + data + '</span>';
                            }
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return row.payments && row.payments[0] ? row.payments[0]
                                .payment_method : 'N/A';
                        }
                    },
                    {
                        data: 'final_total'
                    },

                    {
                        data: 'products',
                        render: function(data) {
                            return data ? data.length : 0;
                        }
                    },
                    {
                        data: 'user.user_name',
                        defaultContent: ''
                    }
                ]
            });

            // You can add event listeners for action buttons here, e.g.:
            $('#draftSalesTable tbody').on('click', '.view-details', function() {
                var data = table.row($(this).parents('tr')).data();
                // Show modal or handle view logic
            });
        });
    </script>
@endsection
