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
                                        @foreach ($product->locations as $location)
                                            @php
                                                $batch = isset($openingStock['batches']) ? collect($openingStock['batches'])->firstWhere('location_id', $location->id) : null;
                                            @endphp

                                            <tr data-location-id="{{ $location->id }}">
                                                <td>
                                                    <input type="hidden" name="locations[{{ $loop->index }}][id]" value="{{ $location->id }}">
                                                    <p>{{ $location->name }}</p>
                                                </td>
                                                <td>
                                                    <p>{{ $product->product_name }}</p>
                                                </td>
                                                <td>
                                                    <p>{{ $product->sku }}</p>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        name="locations[{{ $loop->index }}][qty]"
                                                        value="{{ $batch['quantity'] ?? '' }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="locations[{{ $loop->index }}][unit_cost]"
                                                        value="{{ $product->original_price }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control batch-no-input"
                                                        name="locations[{{ $loop->index }}][batch_no]"
                                                        value="{{ $batch['batch_no'] ?? '' }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control datetimepicker"
                                                        name="locations[{{ $loop->index }}][expiry_date]"
                                                        value="{{ $batch['expiry_date'] ?? '' }}" required>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal-footer">
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
