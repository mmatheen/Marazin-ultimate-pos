<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-map-marker-alt me-2"></i>Location-wise Profit & Loss Report
            <span class="badge bg-secondary ms-2">{{ count($locations) }} locations</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="locationTable">
                <thead class="table-dark">
                    <tr>
                        <th>Location</th>
                        <th class="text-end">Products</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Total Cost</th>
                        <th class="text-end">Profit/Loss</th>
                        <th class="text-end">Margin %</th>
                        <th class="text-end">Revenue Share</th>
                        <th class="text-end">Avg Transaction</th>
                        <th class="text-center">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-store me-2 text-primary"></i>
                                <div>
                                    <strong>{{ $location['location_name'] }}</strong>
                                    @if($location['profit_loss'] < 0)
                                        <br><span class="badge bg-danger">Loss Making</span>
                                    @elseif($location['profit_loss'] > 0)
                                        <br><span class="badge bg-success">Profitable</span>
                                    @else
                                        <br><span class="badge bg-secondary">Break Even</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-end">{{ number_format($location['product_count']) }}</td>
                        <td class="text-end">{{ number_format($location['total_quantity']) }}</td>
                        <td class="text-end">Rs. {{ number_format($location['total_sales'], 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($location['total_cost'], 2) }}</td>
                        <td class="text-end">
                            <span class="{{ $location['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($location['profit_loss'], 2) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="{{ $location['profit_margin'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($location['profit_margin'], 2) }}%
                            </span>
                        </td>
                        <td class="text-end">
                            @php
                                $totalSales = collect($locations)->sum('total_sales');
                                $revenueShare = $totalSales > 0 ? ($location['total_sales'] / $totalSales) * 100 : 0;
                            @endphp
                            {{ number_format($revenueShare, 2) }}%
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: {{ $revenueShare }}%"></div>
                            </div>
                        </td>
                        <td class="text-end">Rs. {{ number_format($location['avg_transaction'], 2) }}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center flex-column">
                                @php
                                    $performanceScore = 0;
                                    if ($location['profit_margin'] > 20 && $revenueShare > 10) $performanceScore = 5;
                                    elseif ($location['profit_margin'] > 15 && $revenueShare > 5) $performanceScore = 4;
                                    elseif ($location['profit_margin'] > 10) $performanceScore = 3;
                                    elseif ($location['profit_margin'] > 0) $performanceScore = 2;
                                    else $performanceScore = 1;
                                @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $performanceScore ? 'text-warning' : 'text-muted' }}" style="font-size: 0.8em;"></i>
                                @endfor
                                <small class="text-muted">({{ $performanceScore }}/5)</small>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <p>No locations found for the selected criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($locations) > 0)
                <tfoot class="table-secondary">
                    <tr>
                        <th>TOTALS</th>
                        <th class="text-end">{{ number_format(collect($locations)->sum('product_count')) }}</th>
                        <th class="text-end">{{ number_format(collect($locations)->sum('total_quantity')) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($locations)->sum('total_sales'), 2) }}</th>
                        <th class="text-end">Rs. {{ number_format(collect($locations)->sum('total_cost'), 2) }}</th>
                        <th class="text-end">
                            @php
                                $totalProfit = collect($locations)->sum('profit_loss');
                            @endphp
                            <span class="{{ $totalProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                Rs. {{ number_format($totalProfit, 2) }}
                            </span>
                        </th>
                        <th class="text-end">
                            @php
                                $totalSales = collect($locations)->sum('total_sales');
                                $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                            @endphp
                            <span class="{{ $avgMargin >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                {{ number_format($avgMargin, 2) }}%
                            </span>
                        </th>
                        <th class="text-end">100.00%</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
        <!-- Location Performance Dashboard -->
        @if(count($locations) > 0)
        <div class="row mt-4">
            <div class="col-md-8">
                <!-- Location Comparison Chart -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bubble me-2"></i>Location Performance Matrix
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="locationChart" height="300"></canvas>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Bubble size represents total sales volume. X-axis: Profit Margin, Y-axis: Revenue Share
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <!-- Top Performers -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Top Performer</h6>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $topLocation = collect($locations)->sortByDesc('profit_loss')->first();
                        @endphp
                        <i class="fas fa-trophy text-warning fa-2x mb-2"></i>
                        <h6 class="text-success">{{ $topLocation['location_name'] }}</h6>
                        <p class="mb-1">Rs. {{ number_format($topLocation['profit_loss'], 2) }}</p>
                        <small class="text-muted">{{ number_format($topLocation['profit_margin'], 2) }}% margin</small>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Highest Revenue</h6>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $highestRevenue = collect($locations)->sortByDesc('total_sales')->first();
                        @endphp
                        <i class="fas fa-chart-line text-primary fa-2x mb-2"></i>
                        <h6 class="text-primary">{{ $highestRevenue['location_name'] }}</h6>
                        <p class="mb-1">Rs. {{ number_format($highestRevenue['total_sales'], 2) }}</p>
                        <small class="text-muted">{{ number_format($highestRevenue['total_quantity']) }} units sold</small>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Needs Focus</h6>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $needsFocus = collect($locations)->sortBy('profit_loss')->first();
                        @endphp
                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                        <h6 class="text-danger">{{ $needsFocus['location_name'] }}</h6>
                        <p class="mb-1">Rs. {{ number_format($needsFocus['profit_loss'], 2) }}</p>
                        <small class="text-muted">{{ number_format($needsFocus['profit_margin'], 2) }}% margin</small>
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
    if (typeof $('#locationTable').DataTable !== 'undefined') {
        $('#locationTable').DataTable({
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
                    title: 'Location Wise Profit Loss Report'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    title: 'Location Wise Profit Loss Report'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-dark',
                    title: 'Location Wise Profit Loss Report'
                }
            ]
        });
    }
    
    // Location Performance Chart
    @if(count($locations) > 0)
    var ctx = document.getElementById('locationChart').getContext('2d');
    var locationChart = new Chart(ctx, {
        type: 'bubble',
        data: {
            datasets: [{
                label: 'Location Performance',
                data: [
                    @foreach($locations as $location)
                    @php
                        $totalSales = collect($locations)->sum('total_sales');
                        $revenueShare = $totalSales > 0 ? ($location['total_sales'] / $totalSales) * 100 : 0;
                    @endphp
                    {
                        x: {{ $location['profit_margin'] }},
                        y: {{ $revenueShare }},
                        r: {{ $location['total_sales'] / 10000 }}, // Scale down for bubble size
                        label: '{{ $location['location_name'] }}'
                    },
                    @endforeach
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Profit Margin (%)'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Revenue Share (%)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw.label + ': ' + 
                                   'Margin: ' + context.parsed.x.toFixed(2) + '%, ' +
                                   'Revenue Share: ' + context.parsed.y.toFixed(2) + '%';
                        }
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
    @endif
});
</script>