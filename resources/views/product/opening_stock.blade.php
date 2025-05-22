@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title" id="pageTitle">
                            {{ isset($editing) && $editing ? 'Edit Opening Stock for Product' : 'Add Opening Stock for Product' }}
                        </h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                            <li class="breadcrumb-item active" id="breadcrumbTitle">
                                {{ isset($editing) && $editing ? 'Edit Opening Stock' : 'Add Opening Stock' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <form id="openingStockForm">
            <input type="hidden" name="product_id" id="product_id" value="{{ $product->id }}">
            <input type="hidden" id="product_name" value="{{ $product->product_name }}">
            <input type="hidden" id="product_sku" value="{{ $product->sku }}">
            <input type="hidden" id="product_original_price" value="{{ $product->original_price }}">

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th scope="col">Location Name</th>
                                            <th scope="col">Product Name</th>
                                            <th scope="col">SKU</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Unit Cost</th>
                                            <th scope="col">Batch No</th>
                                            <th scope="col">Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="locationRows">
                                        <!-- Rows will be appended dynamically here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal-footer">
                                <button type="button" id="addRow" class="btn btn-secondary">Add New Row</button>
                                <button type="submit" id="submitOpeningStock" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- IMEI Entry Modal -->
<div class="modal fade" id="imeiModal" tabindex="-1" aria-labelledby="imeiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imeiModalLabel">Enter IMEI Numbers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <p>Total required: <strong><span id="totalImeiCount"></span></strong></p>

                <!-- Textarea for Paste -->
                <textarea id="imeiInput" rows="6" class="form-control mb-2" placeholder="Paste or type one IMEI per line..."></textarea>
                <button type="button" class="btn btn-sm btn-info float-end text-white" id="autoFillImeis">Auto Fill Rows</button>

                <!-- Table for IMEIs -->
                <table class="table table-bordered mt-3" id="imeiTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>IMEI Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <!-- Buttons -->
                <button type="button" class="btn btn-sm btn-success mb-3" id="addImeiRow">+ Add Row</button>
                {{-- <div id="imeiError" class="text-danger mt-2 d-none">Please enter valid IMEIs.</div> --}}
                <span id="totalImeiCount"></span>
                <span id="imeiCountDisplay" class="ms-2 text-info"></span>
                <div id="imeiError" class="text-danger mt-2 d-none"></div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Skip</button>
                <button type="button" class="btn btn-primary" id="saveImeiButton">Save IMEIs</button>
            </div>
        </div>
    </div>
</div>
@include('product.product_ajax')

@endsection