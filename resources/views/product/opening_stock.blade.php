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
                                                        value="{{ $batch['quantity'] ?? '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="locations[{{ $loop->index }}][unit_cost]"
                                                        value="{{ $product->original_price }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control batch-no-input"
                                                        name="locations[{{ $loop->index }}][batch_no]"
                                                        value="{{ $batch['batch_no'] ?? '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control datetimepicker"
                                                        name="locations[{{ $loop->index }}][expiry_date]"
                                                        value="{{ $batch['expiry_date'] ?? '' }}">
                                                </td>
                                            </tr>
                                        @endforeach
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
<script type="text/javascript">
    $(document).ready(function() {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD'
        });

        $('#addRow').click(function() {
            var index = $('#locationRows tr').length;
            var locationId = $('#locationRows tr:last').data('location-id');
            var locationName = $('#locationRows tr:last td:first p').text();
            var newRow = `
                <tr data-location-id="${locationId}">
                    <td>
                        <input type="hidden" name="locations[` + index + `][id]" value="${locationId}">
                        <p>${locationName}</p>
                    </td>
                    <td>
                        <p>{{ $product->product_name }}</p>
                    </td>
                    <td>
                        <p>{{ $product->sku }}</p>
                    </td>
                    <td>
                        <input type="number" class="form-control"
                            name="locations[` + index + `][qty]"
                            value="">
                    </td>
                    <td>
                        <input type="text" class="form-control"
                            name="locations[` + index + `][unit_cost]"
                            value="{{ $product->original_price }}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control batch-no-input"
                            name="locations[` + index + `][batch_no]"
                            value="">
                    </td>
                    <td>
                        <input type="text" class="form-control datetimepicker"
                            name="locations[` + index + `][expiry_date]"
                            value="">
                    </td>
                </tr>
            `;
            $('#locationRows').append(newRow);
            $('.datetimepicker').datetimepicker({
                format: 'YYYY-MM-DD'
            });
        });

        const currentPath = window.location.pathname;
        const productId = $('#product_id').val();
        const isEditMode = currentPath.startsWith('/edit-opening-stock/');

        if (isEditMode) {
            $('#pageTitle').text('Edit Opening Stock for Product');
            $('#breadcrumbTitle').text('Edit Opening Stock');
            $('#submitOpeningStock').text('Update');
            fetchOpeningStockData(productId);
        }

        $('#submitOpeningStock').click(function(e) {
            e.preventDefault();
            handleFormSubmission(isEditMode, productId);
        });

        function fetchOpeningStockData(productId) {
            $.ajax({
                url: `/api/edit-opening-stock/${productId}`,
                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        let batches = response.openingStock.batches;

                        if (batches.length === 0) {
                            $('#locationRows').html(''); // Clear existing rows before appending
                            @foreach ($product->locations as $location)
                                var newRow = `
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
                                                value="">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="locations[{{ $loop->index }}][unit_cost]"
                                                value="{{ $product->original_price }}" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control batch-no-input"
                                                name="locations[{{ $loop->index }}][batch_no]"
                                                value="">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control datetimepicker"
                                                name="locations[{{ $loop->index }}][expiry_date]"
                                                value="">
                                        </td>
                                    </tr>
                                `;
                                $('#locationRows').append(newRow);
                            @endforeach
                            $('.datetimepicker').datetimepicker({
                                format: 'YYYY-MM-DD'
                            });
                        } else {
                            $('#locationRows').html(''); // Clear existing rows before appending
                            batches.forEach(function(batch, index) {
                                var newRow = `
                                    <tr data-location-id="${batch.location_id}">
                                        <td>
                                            <input type="hidden" name="locations[` + index + `][id]" value="${batch.location_id}">
                                            <p>${batch.location_name}</p>
                                        </td>
                                        <td>
                                            <p>{{ $product->product_name }}</p>
                                        </td>
                                        <td>
                                            <p>{{ $product->sku }}</p>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control"
                                                name="locations[` + index + `][qty]"
                                                value="${batch.quantity}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="locations[` + index + `][unit_cost]"
                                                value="{{ $product->original_price }}" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control batch-no-input"
                                                name="locations[` + index + `][batch_no]"
                                                value="${batch.batch_no}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control datetimepicker"
                                                name="locations[` + index + `][expiry_date]"
                                                value="${batch.expiry_date}">
                                        </td>
                                    </tr>
                                `;
                                $('#locationRows').append(newRow);
                                $('.datetimepicker').datetimepicker({
                                    format: 'YYYY-MM-DD'
                                });
                            });
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to fetch existing stock data.', 'Error');
                }
            });
        }

        function handleFormSubmission(isEditMode, productId) {
            let form = $('#openingStockForm')[0];
            let formData = new FormData(form);

            let locations = [];
            formData.forEach((value, key) => {
                if (key.includes('locations') && value) {
                    let parts = key.split('[');
                    let index = parts[1].split(']')[0];
                    if (!locations[index]) {
                        locations[index] = {};
                    }
                    let field = parts[2].split(']')[0];
                    locations[index][field] = value;
                }
            });

            // Filter out locations with empty qty
            locations = locations.filter(location => location.qty);

            if (!validateBatchNumbers(locations)) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error(
                    'Invalid Batch Number. It should start with "BATCH" followed by at least 3 digits.',
                    'Warning');
                return;
            }

            let url = isEditMode ? `/opening-stock/${productId}` : `/opening-stock/${productId}`;
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify({ locations }),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success(response.message, 'Success');
                        window.location.href = '/list-product';
                    } else {
                        toastr.error(response.message, 'Error');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            $(`#${key}_error`).text(val[0]);
                        });
                    } else {
                        toastr.error('Unexpected error occurred', 'Error');
                    }
                }
            });
        }

        function validateBatchNumbers(locations) {
            let isValid = true;
            locations.forEach(location => {
                if (location.batch_no && !/^BATCH[0-9]{3,}$/.test(location.batch_no)) {
                    isValid = false;
                }
            });
            return isValid;
        }
    });
</script>
@endsection