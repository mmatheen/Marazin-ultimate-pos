<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-layer-group me-2"></i>Batch-wise Profit & Loss Report
            <span class="badge bg-secondary ms-2">{{ count($batches) }} batches</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="batchTable">
                <thead class="table-dark">
                    <tr>
                        <th>Batch No</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Paid Qty</th>
                        <th class="text-end">Free Qty</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Cost</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Profit/Loss</th>
                        <th class="text-end">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr>
                        <td>
                            <code>{{ $batch['batch_number'] }}</code>
                            @if($batch['gross_profit'] < 0)
                                <span class="badge bg-danger ms-1">Loss</span>
                            @elseif($batch['gross_profit'] > 0)
                                <span class="badge bg-success ms-1">Profit</span>
                            @else
                                <span class="badge bg-secondary ms-1">Break Even</span>
                            @endif
                        </td>
                        <td><strong>{{ $batch['product_name'] }}</strong></td>
                        <td><code>{{ $batch['sku'] }}</code></td>
                        <td>{{ $batch['brand_name'] }}</td>
                        <td class="text-end">Rs. {{ number_format($batch['purchase_price'], 2) }}</td>
                        <td class="text-end"><strong>{{ number_format($batch['paid_quantity']) }}</strong></td>
                        <td class="text-end"><span class="text-success">{{ number_format($batch['free_quantity']) }}</span></td>
                        <td class="text-end"><span class="text-primary fw-bold">{{ number_format($batch['total_quantity']) }}</span></td>
                        <td class="text-end">Rs. {{ number_format($batch['total_cost'], 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($batch['total_sales'], 2) }}</td>
                        <td class="text-end">
                            <span class="{{ $batch['gross_profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($batch['gross_profit'], 2) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="{{ $batch['profit_margin'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($batch['profit_margin'], 2) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No batches found for the selected criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($batches) > 0)
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="5">TOTALS</th>
                        <th class="text-end">{{ number_format(collect($batches)->sum('paid_quantity')) }}</th>
                        <th class="text-end">{{ number_format(collect($batches)->sum('free_quantity')) }}</th>
                        <th class="text-end">{{ number_format(collect($batches)->sum('total_quantity')) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($batches)->sum('total_cost'), 2) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($batches)->sum('total_sales'), 2) }}</th>
                        <th class="text-end">
                            @php
                                $totalProfit = collect($batches)->sum('gross_profit');
                            @endphp
                            <span class="{{ $totalProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($totalProfit, 2) }}
                            </span>
                        </th>
                        <th class="text-end">
                            @php
                                $totalSales = collect($batches)->sum('total_sales');
                                $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                            @endphp
                            <span class="{{ $avgMargin >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($avgMargin, 2) }}%
                            </span>
                        </th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Batch Performance Analysis -->
        @if(count($batches) > 0)
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Profitable Batches</h6>
                        <h3 class="text-success mb-0">{{ collect($batches)->where('profit_loss', '>', 0)->count() }}</h3>
                        <small class="text-muted">out of {{ count($batches) }} batches</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Loss-making Batches</h6>
                        <h3 class="text-danger mb-0">{{ collect($batches)->where('profit_loss', '<', 0)->count() }}</h3>
                        <small class="text-muted">need review</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Best Performing Batch</h6>
                        @php
                            $bestBatch = collect($batches)->sortByDesc('profit_loss')->first();
                        @endphp
                        <h3 class="text-info mb-0">Rs. {{ number_format($bestBatch['profit_loss'] ?? 0, 2) }}</h3>
                        <small class="text-muted">{{ $bestBatch['batch_no'] ?? 'N/A' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Worst Performing Batch</h6>
                        @php
                            $worstBatch = collect($batches)->sortBy('profit_loss')->first();
                        @endphp
                        <h3 class="text-warning mb-0">Rs. {{ number_format($worstBatch['profit_loss'] ?? 0, 2) }}</h3>
                        <small class="text-muted">{{ $worstBatch['batch_no'] ?? 'N/A' }}</small>
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
    if (typeof $('#batchTable').DataTable !== 'undefined') {
        $('#batchTable').DataTable({
            pageLength: 25,
            order: [[9, 'desc']], // Sort by profit/loss desc
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    title: 'Batch Wise Profit Loss Report'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    title: 'Batch Wise Profit Loss Report'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-dark',
                    title: 'Batch Wise Profit Loss Report'
                }
            ]
        });
    }
});
</script>
