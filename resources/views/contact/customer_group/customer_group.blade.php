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
                            <li class="breadcrumb-item"><a href="students.html">Customer Groups</a></li>
                            <li class="breadcrumb-item active">Customer Groups</li>
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
                                <button type="button" class="btn btn-outline-info " id="addCustomerGroupButton">
                                    New <i class="fas fa-plus px-2"> </i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="customerGroup" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer Name</th>
                                    <th>Price Calculation Type</th>
                                    <th>Selling Price Group</th>
                                    <th>Calculation Percentage (%)</th>
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
</div>
{{-- Add/Edit modal row --}}
<div class="row">
    <div id="addAndEditCustomerGroupModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>
                    <form id="addAndUpdateForm">

                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label for="customerGroupName">Customer Group Name<span class="login-danger">*</span></label>
                                    <input id="edit_customerGroupName" name="customerGroupName" class="form-control" type="text" placeholder="Customer Group Name">
                                    <span class="text-danger" id="customerGroupName_error"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group local-forms">
                                <label for="priceCalculationType">Price calculation type<span class="login-danger">*</span></label>
                                <select class="form-control" id="edit_priceCalculationType" name="priceCalculationType">
                                    <option selected disabled>Price calculation type</option>
                                    <option value="Percentage">Percentage</option>
                                    <option value="Selling Price Group">Selling Price Group</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12" style="display: none;">
                            <div class="form-group local-forms">
                                <label for="selling_price_group_id">Selling Price Group<span class="login-danger"></span></label>
                                <select class="form-control" id="edit_selling_price_group_id" name="selling_price_group_id">
                                    <option selected disabled>Selling Price Group</option>
                                    @foreach ($SellingPriceGroups as $SellingPriceGroup)
                                    <option value="{{ $SellingPriceGroup->id }}">{{ $SellingPriceGroup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label for="calculationPercentage">Calculation Percentage (%)<span class="login-danger"></span></label>
                                    <input class="form-control" id="edit_calculationPercentage" name="calculationPercentage" type="number" placeholder="Calculation Percentage (%)">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to elements
        let calculationType = document.getElementById('edit_priceCalculationType');
        let calculationPercentage = document.getElementById('edit_calculationPercentage');
        let sellingPriceGroup = document.getElementById('edit_selling_price_group_id');

        // Event listener for changes in price calculation type dropdown
        calculationType.addEventListener('change', function() {
            let selectedValue = this.value;

            // Show or hide fields based on selected price calculation type
            if (selectedValue === 'Percentage') {
                calculationPercentage.parentElement.parentElement.style.display = 'block';
                sellingPriceGroup.parentElement.parentElement.style.display = 'none';
                // Automatically set calculationPercentage to 0
                sellingPriceGroup.value = 0; // when edit it will set this value

            } else if (selectedValue === 'Selling Price Group') {
                calculationPercentage.parentElement.parentElement.style.display = 'none';
                sellingPriceGroup.parentElement.parentElement.style.display = 'block';

                   // Automatically set calculationPercentage to 0
                   calculationPercentage.value = 0; // when edit it will set this value

            } else {
                calculationPercentage.parentElement.parentElement.style.display = 'none';
            }
        });
    });
</script>


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

@include('contact.customer_group.customer_group_ajax')
@endsection
