@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Sell</a></li>
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
                                        <label>Bussiness Location <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Awesome Shop</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>walk in Customer</option>
                                            <option>Harry</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status<span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disableds>All</option>
                                            <option>Paid</option>
                                            <option>Due</option>
                                            <option>Partial</option>
                                            <option>Overdue</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range<span class="login-danger"></span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY">
                                    </div>
                                </div>

                            </div>

                            <div class="row">

                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Advance</option>
                                            <option>Cash</option>
                                            <option>Card</option>
                                            <option>Cheque</option>
                                        </select>
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
                                                    <a href="#"><button type="button" class="btn btn-outline-info">
                                                            <i class="fas fa-plus px-2"> </i>Add
                                                        </button></a>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="datatable table table-stripped" style="width:100%" id="posTable">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" name="" value=""
                                                                id="allchecked"
                                                                onclick="toggleLoginFields(id,'.checked')" /></th>
                                                        <th>Action</th>
                                                        <th>Date</th>
                                                        <th>Invoice No</th>
                                                        <th>Customer Name</th>
                                                        <th>Contact Number</th>
                                                        <th>Location</th>
                                                        <th>Payment Status</th>
                                                        <th>Payment Method</th>
                                                        <th>Total Amount</th>
                                                        <th>Total Paid</th>
                                                        <th>Sell Due</th>
                                                        <th>Total Items</th>
                                                        <th>Added By</th>
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


                </div>
            </div>
        </div>

    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        function toggleLoginFields(propertyId, actionClass) {
            var checkBox = document.getElementById(propertyId);
            var loginFields = document.querySelectorAll(actionClass);
            loginFields.forEach(function(field) {
                field.checked = checkBox.checked;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
                    fetch('/sales')
                        .then(response => response.json())
                        .then(data => {
                                const posSales = data.sales.filter(sale => sale.sale_type === "POS");
                                const tbody = document.querySelector('#posTable tbody');
                                posSales.forEach(sale => {
                                        const row = document.createElement('tr');
                                        row.innerHTML = `
                        <td><input type="checkbox" class="checked" /></td>
                         <td>
                        <div class="dropdown dropdown-action">
                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item view-sale-return" href="#" data-id="${sale.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                <a class="dropdown-item edit-link" href="/sale-return/edit/${sale.id}" data-id="${sale.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                <a class="dropdown-item add-payment-btn" href="" data-id="${sale.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                <a class="dropdown-item view-payment-btn" href="" data-id="${sale.id}" data-bs-toggle="modal" data-bs-target="#viewPaymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;View Payment</a>
                            </div>
                        </div>
                    </td>
                        <td>${sale.sales_date}</td>
                        <td>${sale.invoice_no}</td>
                        <td>${sale.customer.first_name} ${sale.customer.last_name}</td>
                        <td>${sale.customer.mobile_no}</td>
                        <td>${sale.location.name}</td>
                         <td>
                        ${(() => {
                            let paymentStatusBadge = '';
                            if (sale.payment_status === 'Due') {
                                paymentStatusBadge = '<span class="badge bg-danger">Due</span>';
                            } else if (sale.payment_status === 'Partial') {
                                paymentStatusBadge = '<span class="badge bg-warning">Partial</span>';
                            } else if (sale.payment_status === 'Paid') {
                                paymentStatusBadge = '<span class="badge bg-success">Paid</span>';
                            } else {
                                paymentStatusBadge = ` < span class = "badge bg-secondary" > $ {
                                            sale.payment_status
                                        } < /span>`;
                                }
                                return paymentStatusBadge;
                            })()
                    } <
                    /td> <
                    td > $ {
                        sale.payments.length > 0 ? sale.payments[0].payment_method : ''
                    } < /td> <
                    td > $ {
                        sale.final_total
                    } < /td> <
                    td > $ {
                        sale.total_paid
                    } < /td> <
                    td > $ {
                        sale.total_due
                    } < /td> <
                    td > $ {
                        sale.products.length
                    } < /td> <
                    td > $ {
                        sale.created_at
                    } < /td>
                `;
                            tbody.appendChild(row);
                        });

                        // Destroy existing DataTable instance if it exists
                        if ($.fn.DataTable.isDataTable('#posTable')) {
                            $('#posTable').DataTable().destroy();
                        }

                        // Initialize DataTable
                        $('#posTable').DataTable({
                            "searching": true, // Enable default search box
                            "paging": true, // Enable pagination
                            "info": true // Enable information display
                        });
                    })
                    .catch(error => console.error('Error fetching sales:', error));
            });
    </script>

    @include('sell.pos_ajax')
@endsection
