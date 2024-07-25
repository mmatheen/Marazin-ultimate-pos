@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Customer Groups</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Customer Groups List</li>
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
                                    <button type="button" class="btn btn-outline-info " data-bs-toggle="modal"
                                        data-bs-target="#addModal">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Customer Group Name</th>
                                        <th>Calculation Percentage</th>
                                        <th>Selling Price Group</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Table rows will go here, populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       {{-- Add modal row --}}
<div class="row">
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add Category</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAndEditForm" method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Customer Group Name<span class="login-danger">*</span></label>
                                        <input id="customerGroupName" class="form-control" type="text" placeholder="Customer Group Name">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group local-forms days">
                                    <label>Price calculation type<span class="login-danger"></span></label>
                                    <select class="form-control form-select select" id="priceselection">
                                        <option selected disabled>Price calculation type</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Selling Price Group">Selling Price Group</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12" id="additional-content">
                                <!-- Content will be added dynamically here -->
                            </div>

                            <div class="row mb-3">
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- End of modal row --}}

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const priceSelection = document.getElementById('priceselection');
            const additionalContent = document.getElementById('additional-content');
    
            priceSelection.addEventListener('change', function () {
                additionalContent.innerHTML = ''; // Clear any existing content
    
                if (this.value === 'Percentage') {
                    additionalContent.innerHTML = `
                        <div class="form-group local-forms">
                            <label>Calculation Percentage (%)<span class="login-danger">*</span></label>
                            <input class="form-control" type="text" placeholder="Calculation Percentage (%)">
                        </div>
                    `;
                }
            });
    
            const customerGroupName = document.getElementById('customerGroupName');
            
            customerGroupName.addEventListener('input', function () {
                if (this.value) {
                    additionalContent.innerHTML = `
                        <div class="form-group local-forms">
                            <label>Group ID<span class="login-danger">*</span></label>
                            <input class="form-control" type="text" placeholder="Group ID">
                        </div>
                    `;
                } else {
                    additionalContent.innerHTML = ''; // Clear the Group ID field if input is empty
                }
            });
        });
    </script>
@endsection
