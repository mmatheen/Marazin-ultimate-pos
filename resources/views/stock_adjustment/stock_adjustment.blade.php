@extends('layout.layout')
@section('content')
 <style>
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
                    visibility: visible;}}
    </style>
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Stock Adjustments</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Stock Adjustment</li>
                                <li class="breadcrumb-item active">Stock Adjustment List</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button to Add Stock Adjustment -->

                                    @can('create stock-adjustment')
                                        <a href="{{ route('add-stock-adjustment') }}">
                                            <button type="button" class="btn btn-outline-info">
                                                <i class="fas fa-plus px-2"></i>Add
                                            </button>
                                        </a>
                                   @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="stockAdjustmentTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Location</th>
                                        <th>Adjustment Type</th>
                                        <th>Total Amount</th>
                                        <th>Total Amount Recovered</th>
                                        <th>Reason</th>
                                        <th>Added By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="stockAdjustmentModal" tabindex="-1" aria-labelledby="stockAdjustmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
           <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockAdjustmentModalLabel">Stock Adjustment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
               <!-- Modal Body -->
               <div class="modal-body " style="max-height: 70vh; overflow-y: auto;">
                   <div class="container-fluid">
                       <div class="row">
                         
                           <div class="col-md-4">
                               <h5>Location:</h5>
                               <p class="modal-location"></p>
                           </div>
                           <div class="col-md-4">
                               <h5>Adjustment Details:</h5>
                               <p class="modal-date"></p>
                               
                               <p class="modal-type"></p>
                               <p class="modal-reason"></p>
                               <p class="modal-user"></p>
                           </div>
                       </div>
                       <div class="row">
                           <div class="col-md-12 mt-5">
                               <h5>Products:</h5>
                               <table class="table table-bordered modal-products">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Batch ID</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Product rows will be inserted here -->
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                {{-- <button type="button" cLlass="btn btn-secondary" onclick="printModal()">Print</button> --}}

               </div>
             
           </div>
       </div>
   </div>
{{-- 
    <div class="modal fade" id="stockAdjustmentModal" tabindex="-1" aria-labelledby="stockAdjustmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockAdjustmentModalLabel">Stock Adjustment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="mb-3">
                        <p class="modal-date"></p>
                        <p class="modal-location"></p>
                        <p class="modal-type"></p>
                        <p class="modal-reason"></p>
                        <p class="modal-user"></p>
                    </div>
                    <table class="table table-bordered modal-products">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Batch ID</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Product rows will be inserted here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> --}}

    @include('stock_adjustment.stock_adjustment_ajax');
@endsection
