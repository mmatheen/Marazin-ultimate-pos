<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tags me-2"></i>Brand-wise Profit & Loss Report
            <span class="badge bg-secondary ms-2">{{ count($brands) }} brands</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="brandTable">
                <thead class="table-dark">
                    <tr>
                        <th>Brand Name</th>
                        <th class="text-end">Products</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Total Cost</th>
                        <th class="text-end">Profit/Loss</th>
                        <th class="text-end">Margin %</th>
                        <th class="text-end">Avg Selling Price</th>
                        <th class="text-end">Sales per Product</th>
                        <th class="text-center">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                    <tr>
                        <td>
                            <strong>{{ $brand['brand_name'] }}</strong>
                            @if($brand['profit_loss'] < 0)
                                <span class="badge bg-danger ms-1">Loss</span>
                            @elseif($brand['profit_loss'] > 0)
                                <span class="badge bg-success ms-1">Profit</span>
                            @else
                                <span class="badge bg-secondary ms-1">Break Even</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($brand['product_count']) }}</td>
                        <td class="text-end">{{ number_format($brand['total_quantity']) }}</td>
                        <td class="text-end">Rs. {{ number_format($brand['total_sales'], 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($brand['total_cost'], 2) }}</td>
                        <td class="text-end">
                            <span class="{{ $brand['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($brand['profit_loss'], 2) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="{{ $brand['profit_margin'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($brand['profit_margin'], 2) }}%
                            </span>
                        </td>
                        <td class="text-end">Rs. {{ number_format($brand['avg_selling_price'], 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($brand['sales_per_product'], 2) }}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                @php
                                    $performanceScore = 0;
                                    if ($brand['profit_margin'] > 20) $performanceScore = 5;
                                    elseif ($brand['profit_margin'] > 10) $performanceScore = 4;
                                    elseif ($brand['profit_margin'] > 5) $performanceScore = 3;
                                    elseif ($brand['profit_margin'] > 0) $performanceScore = 2;
                                    else $performanceScore = 1;
                                @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $performanceScore ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                <small class="ms-2 text-muted">({{ $performanceScore }}/5)</small>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No brands found for the selected criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($brands) > 0)
                <tfoot class="table-secondary">
                    <tr>
                        <th>TOTALS</th>
                        <th class="text-end">{{ number_format(collect($brands)->sum('product_count')) }}</th>
                        <th class="text-end">{{ number_format(collect($brands)->sum('total_quantity')) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($brands)->sum('total_sales'), 2) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($brands)->sum('total_cost'), 2) }}</th>
                        <th class="text-end">
                            @php
                                $totalProfit = collect($brands)->sum('profit_loss');
                            @endphp
                            <span class="{{ $totalProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($totalProfit, 2) }}
                            </span>
                        </th>
                        <th class="text-end">
                            @php
                                $totalSales = collect($brands)->sum('total_sales');
                                $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                            @endphp
                            <span class="{{ $avgMargin >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($avgMargin, 2) }}%
                            </span>
                        </th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
        <!-- Brand Performance Dashboard -->
        @if(count($brands) > 0)
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Top Performing Brand</h6>
                        @php
                            $topBrand = collect($brands)->sortByDesc('profit_loss')->first();
                        @endphp
                        <h6 class="text-success mb-1">{{ $topBrand['brand_name'] }}</h6>
                        <p class="mb-0">Rs. {{ number_format($topBrand['profit_loss'], 2) }}</p>
                        <small class="text-muted">{{ number_format($topBrand['profit_margin'], 2) }}% margin</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Needs Attention</h6>
                        @php
                            $worstBrand = collect($brands)->sortBy('profit_loss')->first();
                        @endphp
                        <h6 class="text-danger mb-1">{{ $worstBrand['brand_name'] }}</h6>
                        <p class="mb-0">Rs. {{ number_format($worstBrand['profit_loss'], 2) }}</p>
                        <small class="text-muted">{{ number_format($worstBrand['profit_margin'], 2) }}% margin</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Best Margin</h6>
                        @php
                            $bestMarginBrand = collect($brands)->sortByDesc('profit_margin')->first();
                        @endphp
                        <h6 class="text-info mb-1">{{ $bestMarginBrand['brand_name'] }}</h6>
                        <p class="mb-0">{{ number_format($bestMarginBrand['profit_margin'], 2) }}%</p>
                        <small class="text-muted">Rs. {{ number_format($bestMarginBrand['total_sales'], 2) }} sales</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Highest Volume</h6>
                        @php
                            $highestVolumeBrand = collect($brands)->sortByDesc('total_quantity')->first();
                        @endphp
                        <h6 class="text-primary mb-1">{{ $highestVolumeBrand['brand_name'] }}</h6>
                        <p class="mb-0">{{ number_format($highestVolumeBrand['total_quantity']) }} units</p>
                        <small class="text-muted">Rs. {{ number_format($highestVolumeBrand['total_sales'], 2) }} sales</small>
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
    if (typeof $('#brandTable').DataTable !== 'undefined') {
        $('#brandTable').DataTable({
            pageLength: 25,
            order: [[5, 'desc']], // Sort by profit/loss desc
            columnDefs: [
                { orderable: false, targets: [9] } // Performance column
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    title: 'Brand Wise Profit Loss Report'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    title: 'Brand Wise Profit Loss Report'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-dark',
                    title: 'Brand Wise Profit Loss Report'
                }
            ]
        });
    }
});
</script>