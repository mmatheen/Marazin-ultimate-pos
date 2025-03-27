@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Customer</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Warranties</a></li>
                            <li class="breadcrumb-item active">List Customers</li>
                        </ul>
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

                                @can('create customer')
                                <button type="button" class="btn btn-outline-info " id="addCustomerButton">
                                    New <i class="fas fa-plus px-2"> </i>
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="customer" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Prefix</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Mobile No</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Opening Balance</th>
                                    <th>Total Sale Due</th>
                                    <th>Total Return Due</th>
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

    @include('contact.customer.add_customer_modal')

    {{-- Delete modal --}}
    <div id="deleteModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3 id="deleteName"></h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="deleting_id">
                            <div class="row">
                                <div class="col-6">
                                    <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn" style="width: 100%;">Delete</button>
                                </div>
                                <div class="col-6">
                                    <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('contact.customer.customer_ajax')
@endsection
