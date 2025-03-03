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
@include('product.product_ajax')

@endsection