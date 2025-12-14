@extends('layout.layout')
{{-- Title --}}
@section('title', 'Dashboard')
{{-- Dashboard Content --}}
@section('content')
    <div class="content container-fluid">
            @php
                $hour = date('H');

                if ($hour < 12) {
                    $greeting = "Good Morning";
                } elseif ($hour < 17) {
                    $greeting = "Good Afternoon";
                } else {
                    $greeting = "Good Evening";
                }
            @endphp

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                       <h3 class="page-title">{{ $greeting }}, {{ Auth::user()->user_name }}</h3>

                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">Admin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row d-flex justify-content-end">

            <div class="col-md-3">
                <div class="form-group">
                    {{-- @if (Auth::check() && Auth::user()->locations->count() > 0) --}}
                    <select class="form-control selectBox" id="location_dropdown">
                      <option value="">All Location</option>
                    </select>


                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <div id="reportrange" class="form-control"
                        style="background: #fff; cursor: pointer; display: flex; align-items: center; white-space: nowrap;">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span style="flex: 1;"></span> <i class="fa fa-caret-down"></i>
                    </div>
                </div>
            </div>

        </div>


        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-green">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Sales</h6>
                                <h3 id="totalSales">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-cash-register" style="color: #4caf50;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-blue">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Purchases</h6>
                                <h3 id="totalPurchases">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-shopping-cart" style="color: #2196f3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-red">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Sales Returns</h6>
                                <h3 id="totalSalesReturn">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-undo-alt" style="color: #f44336;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-orange">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Purchase Returns</h6>
                                <h3 id="totalPurchaseReturn">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-undo" style="color: #ff9800;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-green">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Sales Due</h6>
                                <h3 id="totalSalesDue">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-money-bill-alt" style="color: #4caf50;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-blue">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Purchases Due</h6>
                                <h3 id="totalPurchasesDue">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-money-bill" style="color: #2196f3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-red">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Sales Return Due</h6>
                                <h3 id="totalSalesReturnDue">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-file-invoice-dollar" style="color: #f44336;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100 border-top-orange">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Purchase Return Due</h6>
                                <h3 id="totalPurchaseReturnDue">Rs. 0.00</h3>
                            </div>
                            <div class="db-icon">
                                <i class="fas fa-file-invoice" style="color: #ff9800;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combined Sales & Purchase Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-chart">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h5 class="card-title">Sales & Purchase Overview</h5>
                            </div>
                            <div class="col-6">
                                <ul class="chart-list-out">
                                    <li><span class="badge" style="background-color: #4caf50;">■</span> Sales</li>
                                    <li><span class="badge" style="background-color: #2196f3;">■</span> Purchase</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="combinedChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling Products, Low Stock, and Recent Sales -->
        <div class="row">
            <!-- Top Selling Products -->
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-fire" style="color: #ff9800;"></i> Top Selling Products</h5>
                            <span class="badge bg-primary" id="topProductsFilter">Today</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="topProductsList" style="max-height: 350px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle" style="color: #f44336;"></i> Low Stock Products</h5>
                            <a href="#" class="text-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="lowStockList" style="max-height: 350px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-danger" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-shopping-bag" style="color: #4caf50;"></i> Recent Sales</h5>
                            <span class="badge bg-success" id="recentSalesCount">0</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="recentSalesList" style="max-height: 350px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Not Related to Any Sales -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-box" style="color: #ff9800;"></i> Products with Stock but No Sales
                                <small class="text-muted" style="font-size: 11px; font-weight: normal;">(Products in inventory that haven't been sold yet)</small>
                            </h5>
                            <span class="badge bg-warning" id="noSalesProductsCount">0</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="noSalesProductsList" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-secondary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .card-chart {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border: none;
        }

        .card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border: none;
            margin-bottom: 15px;
            overflow: hidden;
        }

        /* Beautiful colored top borders for cards */
        .border-top-green {
            border-top: 2px solid #4caf50 !important;
        }

        .border-top-blue {
            border-top: 2px solid #2196f3 !important;
        }

        .border-top-red {
            border-top: 2px solid #f44336 !important;
        }

        .border-top-orange {
            border-top: 2px solid #ff9800 !important;
        }

        .bg-comman {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .db-icon i {
            font-size: 2rem;
            opacity: 0.8;
        }

        .product-item, .sale-item {
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 8px !important;
        }

        .product-item:hover, .sale-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .product-item h6, .sale-item h6 {
            font-size: 13px;
            margin-bottom: 4px;
        }

        .product-item small, .sale-item small {
            font-size: 11px;
        }

        .chart-list-out {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 15px;
        }

        .chart-list-out li {
            font-size: 12px;
            color: #666;
        }

        .badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 12px 15px;
        }

        .card-body {
            padding: 12px 15px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .card-title i {
            font-size: 14px;
        }

        .db-info h6 {
            color: #666;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .db-info h3 {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .border-bottom {
            border-bottom: 1px solid #f0f0f0 !important;
        }

        .text-muted {
            color: #999 !important;
        }

        #combinedChart {
            min-height: 250px;
        }

        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
        }

        /* Reduce max-height for better visibility */
        #topProductsList,
        #lowStockList,
        #recentSalesList {
            max-height: 350px !important;
        }

        /* Scrollbar styling */
        #topProductsList::-webkit-scrollbar,
        #lowStockList::-webkit-scrollbar,
        #recentSalesList::-webkit-scrollbar {
            width: 5px;
        }

        #topProductsList::-webkit-scrollbar-track,
        #lowStockList::-webkit-scrollbar-track,
        #recentSalesList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #topProductsList::-webkit-scrollbar-thumb,
        #lowStockList::-webkit-scrollbar-thumb,
        #recentSalesList::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        #topProductsList::-webkit-scrollbar-thumb:hover,
        #lowStockList::-webkit-scrollbar-thumb:hover,
        #recentSalesList::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Compact spacing for rows */
        .row {
            margin-bottom: 10px;
        }

        .page-header {
            margin-bottom: 15px;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/date-fns/2.21.3/date-fns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@1.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    <script>
        $(document).ready(function() {

            // Fetch locations and populate dropdown
            function fetchLocation() {
                $.ajax({
                    url: "/location-get-all", // Adjust URL as needed
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response.status && response.data) {
                            let options = '<option value="">All Location</option>';
                            response.data.forEach(function(location) {
                                options +=
                                    `<option value="${location.id}">${location.name}</option>`;
                            });
                            $('#location_dropdown').html(options);

                            // Keep "All Location" selected by default (empty value)
                            $('#location_dropdown').val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching locations:', error);
                    }
                });
            }

            // Call fetchLocation on page load
            fetchLocation();

            // Handle location dropdown change
            $('#location_dropdown').on('change', function() {
                const selectedLocationId = $(this).val();
                console.log('Selected location ID:', selectedLocationId);

                // Get current date range and refresh dashboard data
                const dateRange = $('#reportrange').data('daterangepicker');
                if (dateRange) {
                    fetchDashboardData(dateRange.startDate, dateRange.endDate, selectedLocationId);
                }
            });

            let combinedChart;
            let dashboardDataCache = null;
            let lastFetchTime = null;
            const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes in milliseconds

            function formatCurrency(amount) {
                return 'Rs. ' + parseFloat(amount).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function fetchDashboardData(start, end, locationId = null) {
                // Check if we have valid cached data
                const now = Date.now();
                const cacheKey = `${start.format('YYYY-MM-DD')}_${end.format('YYYY-MM-DD')}_${locationId}`;

                if (dashboardDataCache &&
                    dashboardDataCache.key === cacheKey &&
                    lastFetchTime &&
                    (now - lastFetchTime) < CACHE_DURATION) {
                    console.log('Using cached dashboard data');
                    updateDashboardUI(dashboardDataCache.data);
                    return;
                }

                // Show loading indicators
                showLoadingState();

                $.ajax({
                    url: "/dashboard-data",
                    type: "GET",
                    dataType: "json",
                    data: {
                        startDate: start.startOf('day').format('YYYY-MM-DD HH:mm:ss'),
                        endDate: end.endOf('day').format('YYYY-MM-DD HH:mm:ss'),
                        location_id: locationId
                    },
                    success: function(response) {
                        // Cache the response
                        dashboardDataCache = {
                            key: cacheKey,
                            data: response
                        };
                        lastFetchTime = now;

                        updateDashboardUI(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching dashboard data:', error);
                        hideLoadingState();
                    }
                });
            }

            function showLoadingState() {
                // Optional: Add loading indicators
            }

            function hideLoadingState() {
                // Optional: Remove loading indicators
            }

            function updateDashboardUI(response) {
                $("#totalSales").text(formatCurrency(response.totalSales));
                $("#totalPurchases").text(formatCurrency(response.totalPurchases));
                $("#totalSalesReturn").text(formatCurrency(response.totalSalesReturn));
                $("#totalPurchaseReturn").text(formatCurrency(response.totalPurchaseReturn));
                $("#totalSalesDue").text(formatCurrency(response.totalSalesDue));
                $("#totalPurchasesDue").text(formatCurrency(response.totalPurchasesDue));
                $("#totalSalesReturnDue").text(formatCurrency(response.totalSalesReturnDue));
                $("#totalPurchaseReturnDue").text(formatCurrency(response.totalPurchaseReturnDue));
                $("#stockTransfer").text(response.stockTransfer);
                $("#totalProducts").text(response.totalProducts);

                updateCharts(response);
                updateTopProducts(response.topProducts);
                updateLowStock(response.lowStockProducts);
                updateRecentSales(response.recentSales);
                updateNoSalesProducts(response.noSalesProducts);
            }

            function updateCharts(data) {
                // Destroy existing chart if it exists
                if (combinedChart) {
                    combinedChart.destroy();
                }

                // Merge dates from sales and purchases
                const allDates = [...new Set([...data.salesDates, ...data.purchaseDates])].sort();

                // Create data mappings
                const salesMap = {};
                const purchaseMap = {};

                data.salesDates.forEach((date, index) => {
                    salesMap[date] = parseFloat(data.salesAmounts[index] || 0);
                });

                data.purchaseDates.forEach((date, index) => {
                    purchaseMap[date] = parseFloat(data.purchaseAmounts[index] || 0);
                });

                // Prepare combined data
                const salesData = allDates.map(date => salesMap[date] || 0);
                const purchaseData = allDates.map(date => purchaseMap[date] || 0);

                combinedChart = new Chart(document.getElementById('combinedChart'), {
                    type: 'line',
                    data: {
                        labels: allDates,
                        datasets: [
                            {
                                label: 'Sales',
                                data: salesData,
                                borderColor: '#4caf50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Purchase',
                                data: purchaseData,
                                borderColor: '#2196f3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day',
                                    displayFormats: {
                                        day: 'MMM dd'
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rs. ' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 13
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function updateTopProducts(products) {
                if (!products || products.length === 0) {
                    $('#topProductsList').html('<p class="text-muted text-center py-4">No data available</p>');
                    return;
                }

                let html = '';
                products.forEach((product, index) => {
                    const percentage = product.sales_percentage || 0;
                    const color = index === 0 ? '#4caf50' : index === 1 ? '#2196f3' : '#ff9800';

                    html += `
                        <div class="product-item mb-3 p-2 border-bottom">
                            <div class="d-flex align-items-center mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${product.product_name || 'N/A'}</h6>
                                    <small class="text-muted">${formatCurrency(product.total_sales || 0)} • ${product.quantity_sold || 0} Sales</small>
                                </div>
                                <span class="badge" style="background-color: ${color};">↑ ${percentage.toFixed(0)}%</span>
                            </div>
                        </div>
                    `;
                });

                $('#topProductsList').html(html);
            }

            function updateLowStock(products) {
                if (!products || products.length === 0) {
                    $('#lowStockList').html('<p class="text-muted text-center py-4">No low stock products</p>');
                    return;
                }

                let html = '';
                products.forEach((product) => {
                    const stockLevel = parseInt(product.current_stock || 0);
                    const alertLevel = parseInt(product.alert_quantity || 10);
                    const statusColor = stockLevel === 0 ? '#f44336' : stockLevel < alertLevel / 2 ? '#ff9800' : '#ff5722';

                    html += `
                        <div class="product-item mb-3 p-2 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">${product.product_name || 'N/A'}</h6>
                                    <small class="text-muted">ID: ${product.sku || product.id || 'N/A'}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge" style="background-color: ${statusColor}; font-size: 14px;">${stockLevel}</span>
                                    <br><small class="text-muted">Instock</small>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#lowStockList').html(html);
            }

            function updateRecentSales(sales) {
                if (!sales || sales.length === 0) {
                    $('#recentSalesList').html('<p class="text-muted text-center py-4">No recent sales</p>');
                    $('#recentSalesCount').text('0');
                    return;
                }

                $('#recentSalesCount').text(sales.length);

                let html = '';
                sales.forEach((sale) => {
                    const statusColors = {
                        'final': '#4caf50',
                        'completed': '#4caf50',
                        'processing': '#ff9800',
                        'cancelled': '#f44336'
                    };
                    const statusBadges = {
                        'final': 'Completed',
                        'completed': 'Completed',
                        'processing': 'Processing',
                        'cancelled': 'Cancelled'
                    };
                    const status = sale.status || 'final';
                    const statusColor = statusColors[status] || '#9e9e9e';
                    const statusLabel = statusBadges[status] || status;

                    // Format date
                    const saleDate = new Date(sale.created_at || sale.sales_date);
                    const dateStr = saleDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

                    html += `
                        <div class="sale-item mb-3 p-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">${sale.product_name || 'Multiple Products'}</h6>
                                    <small class="text-muted">${sale.category || 'Electronics'} • ${formatCurrency(sale.final_total || 0)}</small>
                                </div>
                                <span class="badge" style="background-color: ${statusColor};">${statusLabel}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">${dateStr}</small>
                                <small class="text-muted">${timeStr}</small>
                            </div>
                        </div>
                    `;
                });

                $('#recentSalesList').html(html);
            }

            function updateNoSalesProducts(products) {
                if (!products || products.length === 0) {
                    $('#noSalesProductsList').html('<p class="text-muted text-center py-4"><i class="fas fa-check-circle text-success"></i><br>Great! All products with stock have been sold at least once!</p>');
                    $('#noSalesProductsCount').text('0');
                    return;
                }

                $('#noSalesProductsCount').text(products.length);

                let html = '<div class="table-responsive"><table class="table table-hover table-sm mb-0">';
                html += '<thead><tr>';
                html += '<th style="font-size: 12px; font-weight: 600;">Product Name</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">SKU</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Category</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Current Stock</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Retail Price</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Stock Value</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Purchases</th>';
                html += '<th style="font-size: 12px; font-weight: 600;">Added Date</th>';
                html += '</tr></thead><tbody>';

                products.forEach((product) => {
                    const stock = parseInt(product.current_stock || 0);
                    const stockBadge = stock > 0
                        ? `<span class="badge bg-success">${stock}</span>`
                        : `<span class="badge bg-danger">0</span>`;

                    const addedDate = new Date(product.created_at);
                    const dateStr = addedDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });

                    const stockValue = parseFloat(product.stock_value || 0);
                    const purchaseCount = parseInt(product.purchase_count || 0);

                    html += `
                        <tr>
                            <td style="font-size: 12px;">
                                <strong>${product.product_name || 'N/A'}</strong>
                                ${product.brand ? `<br><small class="text-muted">${product.brand}</small>` : ''}
                            </td>
                            <td style="font-size: 12px;">${product.sku || 'N/A'}</td>
                            <td style="font-size: 12px;">${product.category || 'N/A'}</td>
                            <td style="font-size: 12px;">${stockBadge}</td>
                            <td style="font-size: 12px;">${formatCurrency(product.retail_price || 0)}</td>
                            <td style="font-size: 12px;"><strong>${formatCurrency(stockValue)}</strong></td>
                            <td style="font-size: 12px;"><span class="badge bg-info">${purchaseCount}</span></td>
                            <td style="font-size: 12px;">${dateStr}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                $('#noSalesProductsList').html(html);
            }

            // Date range picker
            $(function() {
                var today = moment();

                function cb(start, end) {
                    $('#reportrange span').html(start.format('MMM D, YYYY') + ' - ' + end.format(
                        'MMM D, YYYY'));

                    // Get selected location ID
                    const selectedLocationId = $('#location_dropdown').val();
                    fetchDashboardData(start, end, selectedLocationId);
                }

                $('#reportrange').daterangepicker({
                    startDate: today,
                    endDate: today,
                    ranges: {
                        'Today': [today, today],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment()
                            .subtract(1, 'month').endOf('month')
                        ]
                    }
                }, cb);

                // Initialize with Today selected
                cb(today, today);
            });
        });
    </script>
@endsection
