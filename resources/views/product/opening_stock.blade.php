@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Add Opening Stock for Product</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                            <li class="breadcrumb-item active">Add Opening Stock</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <form id="openingStockForm">
            <input type="hidden" name="product_id" id="product_id" value="{{ $product->id }}">
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
                                            <th scope="col">Lot No</th>
                                            <th scope="col">Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="locationRows">
                                        @foreach ($locations as $location)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="locations[{{ $loop->index }}][id]" value="{{ $location->id }}">
                                                    <input type="text" class="form-control" value="{{ $location->name }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="locations[{{ $loop->index }}][product_name]" value="{{ $product->product_name }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="locations[{{ $loop->index }}][sku]" value="{{ $product->sku }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" name="locations[{{ $loop->index }}][quantity]" placeholder="Enter Quantity" required>
                                                    <small class="text-danger" id="quantity_error_{{ $loop->index }}"></small>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="locations[{{ $loop->index }}][unit_cost]" value="{{ $product->retail_price }}" readonly>
                                                    <small class="text-danger" id="unit_cost_error_{{ $loop->index }}"></small>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="locations[{{ $loop->index }}][batch_id]" placeholder="Enter Batch No" required>
                                                    <small class="text-danger" id="batch_id_error_{{ $loop->index }}"></small>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control datetimepicker" name="locations[{{ $loop->index }}][expiry_date]" autocomplete="off" placeholder="YYYY.MM.DD" required>
                                                    <small class="text-danger" id="expiry_date_error_{{ $loop->index }}"></small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="submitOpeningStock" class="btn btn-primary">Save</button>
                                {{-- <a href="{{ route('product.index') }}" class="btn btn-secondary">Cancel</a> --}}
                            </div>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
@include('product.product_ajax')
@include('stock.import_opening_stock_ajax')
@endsection
