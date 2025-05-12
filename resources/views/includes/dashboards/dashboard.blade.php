@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Welcome {{ Auth::user()->user_name }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                            <li class="breadcrumb-item active">Admin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row d-flex justify-content-end">

            <div class="col-md-3">
                <div class="form-group">
                    @if(Auth::check() && Auth::user()->locations->count() > 0)
                    <select class="form-control form-select" id="location_dropdown">
                        @foreach(Auth::user()->locations as $location)
                            <option value="{{ $location->id }}" 
                                @if(session('selectedLocation', Auth::user()->locations->first()->id) == $location->id) selected @endif>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif
                  
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <div id="reportrange"
                        style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                    </div>
                </div>
            </div>

        </div>


        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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
                <div class="card bg-comman w-100">
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

        <div class="row">
            <div class="col-md-12 col-lg-6">
                <div class="card card-chart">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h5 class="card-title">Sales Overview</h5>
                            </div>
                            <div class="col-6">
                                <ul class="chart-list-out">
                                    <li class="star-menus"><a href="javascript:;"><i class="fas fa-ellipsis-v"></i></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-6">
                <div class="card card-chart">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h5 class="card-title">Purchase Overview</h5>
                            </div>
                            <div class="col-6">
                                <ul class="chart-list-out">
                                    <li class="star-menus"><a href="javascript:;"><i class="fas fa-ellipsis-v"></i></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="purchaseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/date-fns/2.21.3/date-fns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@1.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
   
   <script>
    $(document).ready(function() {
        let salesChart;
        let purchaseChart;
    
        function formatCurrency(amount) {
            return 'Rs. ' + parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    
        function fetchDashboardData(start, end) {
            $.ajax({
                url: "/dashboard-data",
                type: "GET",
                dataType: "json",
                data: {
                    startDate: start.startOf('day').format('YYYY-MM-DD HH:mm:ss'),
                    endDate: end.endOf('day').format('YYYY-MM-DD HH:mm:ss')
                },
                success: function(response) {
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
                }
            });
        }
    
        function updateCharts(data) {
            // Destroy existing charts if they exist
            if (salesChart) {
                salesChart.destroy();
            }
            if (purchaseChart) {
                purchaseChart.destroy();
            }
    
            salesChart = new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: data.salesDates,
                    datasets: [{
                        label: 'Sales Amount (Rs.)',
                        data: data.salesAmounts,
                        borderColor: 'blue',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
    
            purchaseChart = new Chart(document.getElementById('purchaseChart'), {
                type: 'line',
                data: {
                    labels: data.purchaseDates,
                    datasets: [{
                        label: 'Purchase Amount (Rs.)',
                        data: data.purchaseAmounts,
                        borderColor: 'green',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        }
    
        // Date range picker 
        $(function() {
            var today = moment();
            
            function cb(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                fetchDashboardData(start, end);
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
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);
    
            // Initialize with Today selected
            cb(today, today);
        });
    });
    </script>

@endsection