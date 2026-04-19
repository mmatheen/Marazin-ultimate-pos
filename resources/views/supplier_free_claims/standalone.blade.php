@extends('layout.layout')
@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">New Standalone Free Qty Claim</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier-claims.index') }}">Supplier Claims</a></li>
                            <li class="breadcrumb-item active">New Standalone Claim</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card card-table">
                <div class="card-body">

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-1"></i>
                        Use this when the supplier promises free items <strong>without a specific purchase bill</strong>.
                        No stock will be added yet — stock is added when you <strong>Receive</strong> against this claim.
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('supplier-claims.store-standalone') }}" method="POST" id="standaloneClaimForm" data-skip-global="true">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group local-forms">
                                    <label>Supplier <span class="login-danger">*</span></label>
                                    <select name="supplier_id" class="form-control form-select selectBox" required>
                                        <option value="">— Select Supplier —</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                                                {{ $s->first_name }} {{ $s->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group local-forms calendar-icon">
                                    <label>Claim Date <span class="login-danger">*</span></label>
                                    <input type="date" name="claim_date" class="form-control"
                                           value="{{ old('claim_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group local-forms">
                                    <label>Default Stock Location <span class="login-danger">*</span></label>
                                    <select name="location_id" class="form-control form-select selectBox" required>
                                        <option value="">— Select Location —</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Search + line items (same pattern as Add Purchase: search to add; duplicate product increments qty) --}}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="fw-semibold mb-2">Claimed Products</label>
                                    <div class="local-forms mb-3">
                                        <label>Search product <span class="login-danger">*</span></label>
                                        <input type="text"
                                               id="standaloneClaimProductSearch"
                                               class="form-control"
                                               placeholder="Type name or SKU, then select (same as Add Purchase)"
                                               autocomplete="off">
                                        <small class="text-muted d-block mt-1">
                                            Select a <strong>Default Stock Location</strong> first. Picking a product adds a line (or increases quantity if it is already in the list).
                                        </small>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-center mb-0" id="claimItemsTable">
                                            <thead>
                                                <tr>
                                                    <th>Product <span class="login-danger">*</span></th>
                                                    <th style="width:200px">Claimed Qty <span class="login-danger">*</span></th>
                                                    <th style="width:60px" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="claimItemsBody">
                                                {{-- Rows added via search (autocomplete); names reindexed before submit --}}
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small mb-0 mt-2" id="claimItemsEmptyHint">
                                        No products yet — use the search box above to add items.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="doctor-submit text-end">
                            <a href="{{ route('supplier-claims.index') }}" class="btn btn-cancel me-2">
                                Cancel
                            </a>
                            <button type="submit" id="standaloneSubmitBtn" class="btn btn-primary submit-form me-2">
                                Save Claim
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ui-autocomplete.standalone-claim-ac {
        max-height: 220px;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1055 !important;
    }
</style>
<script>
    $(function () {
        const $tbody = $('#claimItemsBody');
        const $location = $('select[name="location_id"]');
        const $search = $('#standaloneClaimProductSearch');
        const $emptyHint = $('#claimItemsEmptyHint');

        function sellableStockDisplay(stockRow) {
            const p = stockRow && stockRow.product;
            if (!p) return 0;
            if (p.stock_alert === 0 || p.stock_alert === '0') {
                return 'Unlimited';
            }
            const paid = parseFloat(stockRow.total_stock) || 0;
            const free = parseFloat(stockRow.total_free_stock) || 0;
            return paid + free;
        }

        function toggleEmptyHint() {
            const has = $tbody.find('tr.claim-item-row').length > 0;
            $emptyHint.toggle(!has);
        }

        function reindexClaimRows() {
            $tbody.find('tr.claim-item-row').each(function (idx) {
                $(this).find('.claim-product-id').attr('name', 'items[' + idx + '][product_id]');
                $(this).find('.claimed-qty-input').attr('name', 'items[' + idx + '][claimed_qty]');
            });
        }

        function findRowByProductId(productId) {
            return $tbody.find('tr.claim-item-row[data-product-id="' + productId + '"]').first();
        }

        function addOrUpdateLine(product) {
            if (!product || !product.id) {
                return;
            }
            const allowDecimal = product.allow_decimal === true || product.allow_decimal === 'true';
            // min + step must be compatible: min=0.0001 with step=0.01 makes whole numbers like 5 invalid in HTML5.
            // Server still enforces min:0.0001; step any avoids bogus client-side blocking.
            const step = 'any';
            const min = '0.0001';
            const $existing = findRowByProductId(product.id);

            if ($existing.length) {
                const $qty = $existing.find('.claimed-qty-input');
                let cur = parseFloat($qty.val());
                if (isNaN(cur)) cur = 0;
                const next = allowDecimal ? (cur + 1) : (parseInt(cur, 10) + 1);
                $qty.val(next).trigger('input');
                if (typeof toastr !== 'undefined') {
                    toastr.success('Quantity updated for ' + (product.name || 'product'), 'Already in list');
                }
                return;
            }

            const label = $('<div>').text(product.name || '').html()
                + ' <small class="text-muted">(' + (product.sku || 'N/A') + ')</small>';

            const $row = $('<tr class="claim-item-row">').attr('data-product-id', product.id);
            $row.append(
                $('<td>').append(
                    $('<div>').html(label),
                    $('<input>', { type: 'hidden', class: 'claim-product-id', name: 'items[0][product_id]', value: product.id })
                ),
                $('<td>').append(
                    $('<input>', {
                        type: 'number',
                        name: 'items[0][claimed_qty]',
                        class: 'form-control claimed-qty-input',
                        min: min,
                        step: step,
                        inputmode: 'decimal',
                        placeholder: 'e.g. 10',
                        value: 1,
                        required: true,
                        'data-allow-decimal': allowDecimal ? '1' : '0'
                    })
                ),
                $('<td>', { class: 'text-center' }).append(
                    $('<button>', { type: 'button', class: 'btn btn-sm btn-danger remove-row' }).html('<i class="fas fa-trash"></i>')
                )
            );

            $tbody.prepend($row);
            reindexClaimRows();
            toggleEmptyHint();
        }

        function initStandaloneAutocomplete() {
            if ($search.data('ui-autocomplete')) {
                $search.autocomplete('destroy');
            }

            $search.off('keydown.standaloneAc').on('keydown.standaloneAc', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const widget = $(this).autocomplete('widget');
                const focused = widget.find('.ui-state-focus');
                let itemToAdd = null;
                if (focused.length > 0) {
                    const inst = $(this).autocomplete('instance');
                    if (inst && inst.menu.active) {
                        itemToAdd = inst.menu.active.data('ui-autocomplete-item');
                    }
                }
                if (itemToAdd && itemToAdd.product) {
                    addOrUpdateLine(itemToAdd.product);
                    $(this).val('');
                    $(this).autocomplete('close');
                }
                event.stopImmediatePropagation();
            });

            $search.autocomplete({
                minLength: 1,
                appendTo: 'body',
                source: function (request, response) {
                    const locationId = $location.val();
                    if (!locationId) {
                        response([{ label: 'Select a Default Stock Location first', value: '', product: null }]);
                        return;
                    }
                    $.ajax({
                        url: '/products/stocks/autocomplete',
                        data: {
                            search: request.term.trim(),
                            location_id: locationId,
                            per_page: 50,
                            page: 1,
                            context: 'purchase'
                        },
                        dataType: 'json',
                        success: function (data) {
                            if (data.status === 200 && Array.isArray(data.data)) {
                                const items = data.data.map(function (item) {
                                    const stockDisplay = sellableStockDisplay(item);
                                    return {
                                        label: item.product.product_name + ' (' + (item.product.sku || 'N/A') + ') [Stock: ' + stockDisplay + ']',
                                        value: item.product.product_name,
                                        product: {
                                            id: item.product.id,
                                            name: item.product.product_name,
                                            sku: item.product.sku || 'N/A',
                                            allow_decimal: item.product.unit ? item.product.unit.allow_decimal : false
                                        }
                                    };
                                });
                                response(items.length ? items.slice(0, 15) : [{
                                    label: 'No products found',
                                    value: '',
                                    product: null
                                }]);
                            } else {
                                response([{ label: 'No products found', value: '', product: null }]);
                            }
                        },
                        error: function () {
                            response([{ label: 'Error fetching products', value: '', product: null }]);
                        }
                    });
                },
                select: function (event, ui) {
                    if (!ui.item.product) {
                        return false;
                    }
                    addOrUpdateLine(ui.item.product);
                    $search.val('');
                    return false;
                },
                open: function () {
                    const w = $search.autocomplete('widget');
                    w.addClass('standalone-claim-ac');
                    setTimeout(function () {
                        w.scrollTop(0);
                        const inst = $search.autocomplete('instance');
                        if (!inst || !inst.menu) return;
                        const first = inst.menu.element.find('li:first-child');
                        if (first.length && first.text().indexOf('No products') === -1 && first.text().indexOf('Select a Default') === -1) {
                            inst.menu.element.find('.ui-state-focus').removeClass('ui-state-focus');
                            first.addClass('ui-state-focus');
                            inst.menu.active = first;
                        }
                    }, 50);
                }
            });

            const ac = $search.data('ui-autocomplete');
            if (ac) {
                ac._renderItem = function (ul, item) {
                    if (!item.product) {
                        return $('<li>').append($('<div>').css('color', '#c00').text(item.label)).appendTo(ul);
                    }
                    return $('<li>')
                        .append($('<div>').text(item.label))
                        .data('ui-autocomplete-item', item)
                        .appendTo(ul);
                };
            }
        }

        initStandaloneAutocomplete();
        $location.on('change', function () {
            initStandaloneAutocomplete();
            $search.val('');
        });

        $tbody.on('click', '.remove-row', function () {
            $(this).closest('tr.claim-item-row').remove();
            reindexClaimRows();
            toggleEmptyHint();
        });

        $('#standaloneClaimForm').on('submit', function (e) {
            reindexClaimRows();
            if ($tbody.find('tr.claim-item-row').length === 0) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.error('Add at least one product using the search box.', 'Claim');
                }
                return false;
            }
            const btn = $('#standaloneSubmitBtn');
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        });
    });
</script>
@endsection
