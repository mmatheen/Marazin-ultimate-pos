<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-box me-2"></i>Product-wise Profit & Loss Report
            <span class="badge bg-secondary ms-2">{{ count($products) }} products</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="productTable">
                <thead class="table-dark">
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand</th>
                        <th class="text-end">Paid Qty</th>
                        <th class="text-end">Free Qty</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Total Cost</th>
                        <th class="text-end">Profit/Loss</th>
                        <th class="text-end">Margin %</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product['product_name'] }}</strong>
                            @if($product['gross_profit'] < 0)
                                <span class="badge bg-danger ms-1">Loss</span>
                            @elseif($product['gross_profit'] > 0)
                                <span class="badge bg-success ms-1">Profit</span>
                            @else
                                <span class="badge bg-secondary ms-1">Break Even</span>
                            @endif
                        </td>
                        <td><code>{{ $product['sku'] }}</code></td>
                        <td>{{ $product['brand_name'] }}</td>
                        <td class="text-end"><strong>{{ number_format($product['paid_quantity']) }}</strong></td>
                        <td class="text-end"><span class="text-success">{{ number_format($product['free_quantity']) }}</span></td>
                        <td class="text-end"><span class="text-primary fw-bold">{{ number_format($product['total_quantity']) }}</span></td>
                        <td class="text-end">Rs. {{ number_format($product['total_sales'], 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($product['total_cost'], 2) }}</td>
                        <td class="text-end">
                            <span class="{{ $product['gross_profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($product['gross_profit'], 2) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="{{ $product['profit_margin'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($product['profit_margin'], 2) }}%
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="viewProductDetails({{ $product['product_id'] }})"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info"
                                        onclick="viewFifoBreakdown({{ $product['product_id'] }})"
                                        title="FIFO Breakdown">
                                    <i class="fas fa-layer-group"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No products found for the selected criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($products) > 0)
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="3">TOTALS</th>
                        <th class="text-end">{{ number_format(collect($products)->sum('paid_quantity')) }}</th>
                        <th class="text-end">{{ number_format(collect($products)->sum('free_quantity')) }}</th>
                        <th class="text-end">{{ number_format(collect($products)->sum('total_quantity')) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($products)->sum('total_sales'), 2) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($products)->sum('total_cost'), 2) }}</th>
                        <th class="text-end">
                            @php
                                $totalProfit = collect($products)->sum('gross_profit');
                            @endphp
                            <span class="{{ $totalProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($totalProfit, 2) }}
                            </span>
                        </th>
                        <th class="text-end">
                            @php
                                $totalSales = collect($products)->sum('total_sales');
                                $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                            @endphp
                            <span class="{{ $avgMargin >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($avgMargin, 2) }}%
                            </span>
                        </th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Summary Statistics -->
        @if(count($products) > 0)
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Profitable Products</h6>
                        <h3 class="text-success mb-0">{{ collect($products)->where('profit_loss', '>', 0)->count() }}</h3>
                        <small class="text-muted">out of {{ count($products) }} products</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Products at Loss</h6>
                        <h3 class="text-danger mb-0">{{ collect($products)->where('profit_loss', '<', 0)->count() }}</h3>
                        <small class="text-muted">need attention</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Best Margin</h6>
                        <h3 class="text-info mb-0">{{ number_format(collect($products)->max('profit_margin'), 2) }}%</h3>
                        <small class="text-muted">highest margin</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Worst Margin</h6>
                        <h3 class="text-warning mb-0">{{ number_format(collect($products)->min('profit_margin'), 2) }}%</h3>
                        <small class="text-muted">needs improvement</small>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    if (typeof $('#productTable').DataTable !== 'undefined') {
        $('#productTable').DataTable({
            pageLength: 25,
            order: [[6, 'desc']], // Sort by profit/loss desc
            columnDefs: [
                { orderable: false, targets: [9] } // Actions column
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    title: 'Product Wise Profit Loss Report'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    title: 'Product Wise Profit Loss Report'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-dark',
                    title: 'Product Wise Profit Loss Report'
                }
            ]
        });
    }
});
</script>
