@extends('layout.layout')
@section('title', 'Profit & Loss Report')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

<style>
    /* Summary Cards Row Layout */
    #summaryCards .card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    #summaryCards .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    #summaryCards .dash-widget-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border: 2px solid;
    }
    
    #summaryCards h4 {
        font-weight: 700;
        font-size: 1.5rem;
    }
    
    #summaryCards h6 {
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    /* Ensure horizontal layout */
    #summaryCards .row {
        margin: 0;
    }
    
    #summaryCards .col-lg-3 {
        padding: 0 8px;
        margin-bottom: 15px;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        #summaryCards .col-lg-3 {
            margin-bottom: 10px;
        }
        
        #summaryCards .d-flex {
            flex-direction: column;
            text-align: center;
        }
        
        #summaryCards .dash-widget-icon {
            margin-bottom: 10px;
            margin-right: 0 !important;
        }
    }
</style>
@endpush

@section('content')

<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title">Profit & Loss Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#">Reports</a></li>
                        <li class="breadcrumb-item active">Profit & Loss</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <div class="card-body">
                    <form id="reportFilters">
                        <div class="row">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ $startDate ?? date('Y-m-01') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ $endDate ?? date('Y-m-t') }}" required>
                            </div>

                            <!-- Location Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <select class="form-control select2" id="location_ids" name="location_ids[]" multiple>
                                    <option value="">All Locations</option>
                                    @foreach($locations ?? [] as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Report Type -->
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-control" id="report_type" name="report_type">
                                    <option value="overall">Overall Summary</option>
                                    <option value="product">Product-wise</option>
                                    <option value="batch">Batch-wise</option>
                                    <option value="brand">Brand-wise</option>
                                    <option value="location">Location-wise</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date Presets -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange('today')">Today</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange('yesterday')">Yesterday</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange('this_week')">This Week</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange('this_month')">This Month</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange('last_month')">Last Month</button>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" onclick="generateReport()">
                                    <i class="fas fa-chart-line"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summaryCards" style="display: none;">
        <div class="col-12">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card board1 fill h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="dash-widget-icon text-primary border-primary me-3">
                                    <i class="fe fe-money"></i>
                                </span>
                                <div>
                                    <h6 class="card-title mb-1">Total Sales</h6>
                                    <h4 class="text-primary mb-0" id="totalSales">Rs.0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card board1 fill h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="dash-widget-icon text-warning border-warning me-3">
                                    <i class="fe fe-money"></i>
                                </span>
                                <div>
                                    <h6 class="card-title mb-1">Total Cost</h6>
                                    <h4 class="text-warning mb-0" id="totalCost">Rs.0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card board1 fill h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="dash-widget-icon text-success border-success me-3">
                                    <i class="fe fe-money"></i>
                                </span>
                                <div>
                                    <h6 class="card-title mb-1">Gross Profit</h6>
                                    <h4 class="text-success mb-0" id="grossProfit">Rs.0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card board1 fill h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="dash-widget-icon text-info border-info me-3">
                                    <i class="fe fe-percent"></i>
                                </span>
                                <div>
                                    <h6 class="card-title mb-1">Profit Margin</h6>
                                    <h4 class="text-info mb-0" id="profitMargin">0.00%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card" id="reportTableCard" style="display: none;">
                <div class="card-header">
                    <h4 class="card-title mb-0" id="reportTitle">Report Results</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="profitLossTable">
                            <thead>
                                <tr id="tableHeaders">
                                    <!-- Headers will be populated dynamically -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script>
let profitLossDataTable = null;

// Date range presets
function setDateRange(period) {
    const today = new Date();
    let startDate, endDate;
    
    switch(period) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = yesterday.toISOString().split('T')[0];
            break;
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            startDate = startOfWeek.toISOString().split('T')[0];
            endDate = endOfWeek.toISOString().split('T')[0];
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            startDate = lastMonth.toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
}

// Generate report
function generateReport() {
    const formData = new FormData(document.getElementById('reportFilters'));
    
    // Add debugging
    console.log('Form data:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Show loading
    showLoading();
    
    // Make AJAX request
    fetch('{{ route("profit-loss.data") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        console.log('Data type:', typeof data);
        console.log('Data keys:', Object.keys(data));
        console.log('Data.data:', data.data);
        console.log('Data.summary:', data.summary);
        
        if (data.error) {
            throw new Error(data.error);
        }
        hideLoading();
        updateSummaryCards(data.summary || {});
        updateReportTable(data);
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Error generating report: ' + error.message);
    });
}

// Update summary cards
function updateSummaryCards(summary) {
    const elements = {
        totalSales: document.getElementById('totalSales'),
        totalCost: document.getElementById('totalCost'),
        grossProfit: document.getElementById('grossProfit'),
        profitMargin: document.getElementById('profitMargin'),
        summaryCards: document.getElementById('summaryCards')
    };
    
    // Check if all elements exist
    for (const [key, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Element ${key} not found`);
            return;
        }
    }
    
    elements.totalSales.textContent = `Rs.${formatNumber(summary.total_sales || 0)}`;
    elements.totalCost.textContent = `Rs.${formatNumber(summary.total_cost || 0)}`;
    elements.grossProfit.textContent = `Rs.${formatNumber(summary.gross_profit || 0)}`;
    elements.profitMargin.textContent = `${(summary.profit_margin || 0).toFixed(2)}%`;
    
    elements.summaryCards.style.display = 'block';
}

// Update report table
function updateReportTable(data) {
    const reportType = document.getElementById('report_type').value;
    
    // Show the table card first so elements are accessible
    const reportTableCard = document.getElementById('reportTableCard');
    if (reportTableCard) {
        reportTableCard.style.display = 'block';
    }
    
    // Destroy existing DataTable
    if (profitLossDataTable) {
        profitLossDataTable.destroy();
    }
    
    // Clear table content
    $('#profitLossTable').empty();
    
    // Check if we have data
    if (!data.data || data.data.length === 0) {
        const tableElement = document.getElementById('profitLossTable');
        if (tableElement) {
            tableElement.innerHTML = `
                <thead>
                    <tr>
                        <th>No Data Available</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>No records found for the selected criteria.</td>
                    </tr>
                </tbody>
            `;
        }
        return;
    }
    
    // Set table headers based on report type
    let headers = [];
    let columns = [];
    
    switch(reportType) {
        case 'product':
            headers = ['Product Name', 'SKU', 'Qty Sold', 'Total Sales', 'Total Cost', 'Gross Profit', 'Profit Margin'];
            columns = [
                {data: 'product_name', defaultContent: 'N/A'},
                {data: 'sku', defaultContent: 'N/A'},
                {data: 'quantity_sold', render: (data, type, row) => formatQuantity(data, row.allow_decimal), defaultContent: '0'},
                {data: 'total_sales', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'total_cost', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'gross_profit', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'profit_margin', render: data => `${(data || 0).toFixed(2)}%`, defaultContent: '0.00%'}
            ];
            break;
        case 'batch':
            headers = ['Batch Number', 'Product Name', 'Expiry Date', 'Qty Sold', 'Total Sales', 'Total Cost', 'Gross Profit', 'Profit Margin'];
            columns = [
                {data: 'batch_number', defaultContent: 'N/A'},
                {data: 'product_name', defaultContent: 'N/A'},
                {data: 'expiry_date', defaultContent: 'N/A'},
                {data: 'quantity_sold', render: (data, type, row) => formatQuantity(data, row.allow_decimal), defaultContent: '0'},
                {data: 'total_sales', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'total_cost', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'gross_profit', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'profit_margin', render: data => `${(data || 0).toFixed(2)}%`, defaultContent: '0.00%'}
            ];
            break;
        case 'brand':
            headers = ['Brand Name', 'Qty Sold', 'Total Sales', 'Total Cost', 'Gross Profit', 'Profit Margin'];
            columns = [
                {data: 'brand_name', defaultContent: 'N/A'},
                {data: 'quantity_sold', render: data => formatQuantity(data), defaultContent: '0.00'},
                {data: 'total_sales', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'total_cost', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'gross_profit', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'profit_margin', render: data => `${(data || 0).toFixed(2)}%`, defaultContent: '0.00%'}
            ];
            break;
        case 'location':
            headers = ['Location', 'Qty Sold', 'Total Sales', 'Total Cost', 'Gross Profit', 'Profit Margin'];
            columns = [
                {data: 'location_name', defaultContent: 'N/A'},
                {data: 'quantity_sold', render: data => formatQuantity(data), defaultContent: '0.00'},
                {data: 'total_sales', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'total_cost', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'gross_profit', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'},
                {data: 'profit_margin', render: data => `${(data || 0).toFixed(2)}%`, defaultContent: '0.00%'}
            ];
            break;
        default:
            headers = ['Description', 'Amount'];
            columns = [
                {data: 'description', defaultContent: 'N/A'},
                {data: 'amount', render: data => `Rs.${formatNumber(data || 0)}`, defaultContent: 'Rs.0.00'}
            ];
    }
    
    // Rebuild the table structure
    const tableElement = document.getElementById('profitLossTable');
    if (!tableElement) {
        console.error('profitLossTable element not found');
        return;
    }
    
    tableElement.innerHTML = `
        <thead>
            <tr id="tableHeaders">
                ${headers.map(header => `<th>${header}</th>`).join('')}
            </tr>
        </thead>
        <tbody>
            <!-- Data will be populated by DataTables -->
        </tbody>
    `;
    
    // Update report title
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) {
        reportTitle.textContent = getReportTitle(reportType);
    }
    
    // Debug: Log the data structure
    console.log('Table data:', data.data);
    console.log('First row:', data.data[0]);
    
    // Initialize DataTable with export buttons
    try {
        profitLossDataTable = $('#profitLossTable').DataTable({
            data: data.data,
            columns: columns,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    title: `Profit Loss Report - ${getReportTitle(reportType)}`,
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    title: `Profit Loss Report - ${getReportTitle(reportType)}`,
                    className: 'btn btn-danger btn-sm'
                },
                {
                    extend: 'print',
                    title: `Profit Loss Report - ${getReportTitle(reportType)}`,
                    className: 'btn btn-info btn-sm'
                }
            ],
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                {
                    targets: '_all',
                    defaultContent: 'N/A'
                }
            ]
        });
        
        console.log('DataTable initialized successfully');
        
    } catch (error) {
        console.error('DataTable initialization error:', error);
        alert('Error initializing table: ' + error.message);
        return;
    }
}

// Get report title
function getReportTitle(reportType) {
    const titles = {
        'overall': 'Overall Summary',
        'product': 'Product-wise Report',
        'batch': 'Batch-wise Report',
        'brand': 'Brand-wise Report',
        'location': 'Location-wise Report'
    };
    return titles[reportType] || 'Report';
}

// Utility functions
function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function formatQuantity(qty, allowDecimal) {
    if (allowDecimal === undefined) {
        // For aggregated data (brand/location), always show decimal
        return parseFloat(qty || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    if (allowDecimal) {
        // Show with 2 decimal places
        return parseFloat(qty || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        // Show as integer
        return parseInt(qty || 0).toLocaleString('en-IN');
    }
}

function showLoading() {
    // Add loading spinner or indicator
    document.querySelector('.btn[onclick="generateReport()"]').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
}

function hideLoading() {
    document.querySelector('.btn[onclick="generateReport()"]').innerHTML = '<i class="fas fa-chart-line"></i> Generate Report';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select2 for multi-select
    if (typeof $.fn.select2 !== 'undefined') {
        $('#location_ids').select2({
            placeholder: 'Select locations',
            allowClear: true
        });
    }
});
</script>
@endpush



