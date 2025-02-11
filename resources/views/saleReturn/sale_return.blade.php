@extends('layout.layout')
@section('content')
<style>
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: #f2f2f2;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: left;
    list-style: none;
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}
</style>
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href={{ route('list-sale') }}>Sell</a></li>
                                <li class="breadcrumb-item active">All Sales</li>
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
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Business Location <span class="login-danger">*</span></label>
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
                </div>
            </div>
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
                                                <a href={{ route('add-sale') }}><button type="button" class="btn btn-outline-info">
                                                        <i class="fas fa-plus px-2"> </i>Add
                                                    </button></a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="salesReturnTable">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Invoice No.</th>
                                                    <th>Parent Sale</th>
                                                    <th>Customer Name</th>
                                                    <th>Location</th>
                                                    <th>Payment Status</th>
                                                    <th>Total Amount</th>
                                                    <th>Payment Due</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td>Total:</td>
                                                    <td>0</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
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

    <!-- Modal to show sale details -->
    <div class="modal fade" id="saleDetailsModal" tabindex="-1" aria-labelledby="saleDetailsModalLabel"
        aria-hidden="true">
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
    </div>

    @include("saleReturn.sale_return_ajax")
    <script>
        $(document).ready(function() {
            $.ajax({
                url: '/sale-returns',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        var salesReturns = response.data;
                        var totalAmount = response.totalAmount;
                        var totalDue = response.totalDue;

                        $('#salesReturnTable tbody').empty();
                        salesReturns.forEach(function(salesReturn) {
                            var parentSaleInvoice = salesReturn.sale ? salesReturn.sale.invoice_no : 'N/A';
                            var customerName = salesReturn.sale && salesReturn.sale.customer ? salesReturn.sale.customer.first_name + ' ' + salesReturn.sale.customer.last_name : 'N/A';
                            var locationName = salesReturn.sale ? salesReturn.sale.location.name : 'N/A';

                            $('#salesReturnTable tbody').append(`
                                <tr>
                                    <td>${new Date(salesReturn.return_date).toLocaleDateString()}</td>
                                    <td>${salesReturn.invoice_number}</td>
                                    <td>${parentSaleInvoice}</td>
                                    <td>${customerName}</td>
                                    <td>${locationName}</td>
                                    <td>${salesReturn.payment_status}</td>
                                    <td>${salesReturn.return_total}</td>
                                    <td>${salesReturn.total_due}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">View</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                                <a class="dropdown-item" href="#">Delete</a>
                                                <a class="dropdown-item" href="#">Print</a>
                                                <a class="dropdown-item" href="#">Add Payment</a>
                                                <a class="dropdown-item" href="#">View Payments</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });

                        $('#salesReturnTable tfoot tr').find('td:eq(1)').text(totalAmount);
                        $('#salesReturnTable tfoot tr').find('td:eq(2)').text(totalDue);
                    }
                },
                error: function(error) {
                    console.log('Error fetching sales returns:', error);
                }
            });
        });
    </script>
@endsection
