'use strict';

// ============================================================
// Phase 15: pos-sales-list.js
// Recent Transactions modal — DataTable init, fetchSalesData,
// loadTableData, updateTabBadges, navigateToEdit, printReceipt
// Extracted from pos_ajax.blade.php (Phase 15)
// ============================================================

// --- Module-local state ---
let sales = [];
let lastSalesDataFetch = 0;
const SALES_CACHE_DURATION = 30000; // 30 seconds
let isLoadingTableData = false; // Prevent concurrent loading
let loadTableDataTimeout = null; // For debouncing
let recentTransactionsInitialized = false;

// Check if sales data should be refreshed
function shouldRefreshSalesData() {
    return (Date.now() - lastSalesDataFetch) > SALES_CACHE_DURATION;
}

// Debounced version of loadTableData
function debouncedLoadTableData(status) {
    clearTimeout(loadTableDataTimeout);
    loadTableDataTimeout = setTimeout(() => {
        loadTableData(status);
    }, 100); // 100ms debounce
}

// Function to fetch sales data from the server using AJAX
function fetchSalesData() {
    // Prevent multiple concurrent fetches
    if (window.fetchingSalesData) {
        return;
    }

    window.fetchingSalesData = true;

    $.ajax({
        url: '/sales',
        type: 'GET',
        dataType: 'json',
        data: {
            recent_transactions: 'true', // Add parameter to get all statuses for Recent Transactions
            order_by: 'created_at', // Request sorting by creation date
            order_direction: 'desc', // Latest first
            limit: 200 // Server returns lean rows (no line items); 200 is fine for modal badges
        },
        success: function(data) {
            window.fetchingSalesData = false;
            lastSalesDataFetch = Date.now();

            if (Array.isArray(data)) {
                sales = data;
            } else if (data.sales && Array.isArray(data.sales)) {
                sales = data.sales;
            } else if (data.data && Array.isArray(data.data)) {
                sales = data.data;
            } else {
                console.error('Unexpected data format:', data);
                sales = [];
            }

            // Load the default tab data (e.g., 'final')
            loadTableData('final');
            updateTabBadges();
        },
        error: function(xhr, status, error) {
            window.fetchingSalesData = false;
            console.error('Error fetching sales data:', error);
            console.error('Response:', xhr.responseText);

            // Show user-friendly error message
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to load recent transactions. Please try again.');
            }
        }
    });
}

function loadTableData(status) {
    // Prevent concurrent loading
    if (isLoadingTableData) {
        return;
    }

    isLoadingTableData = true;

    const table = $('#transactionTable').DataTable();
    table.clear(); // Clear existing data

    // Filter by status - remove excessive logging per sale
    const filteredSales = sales.filter(sale => sale.status === status);

    if (filteredSales.length === 0) {
        table.row.add([
            '',
            '<div class="text-center text-muted">No records found</div>',
            '',
            '',
            '',
            '',
            ''
        ]);
    } else {
        // Sort by date and time descending (latest first), fallback to ID if no date
        const sortedSales = filteredSales.sort((a, b) => {
            // First try to sort by created_at date
            if (a.created_at && b.created_at) {
                const dateA = new Date(a.created_at);
                const dateB = new Date(b.created_at);
                return dateB.getTime() - dateA.getTime(); // Latest first
            }

            // Fallback to sale_date if created_at is not available
            if (a.sale_date && b.sale_date) {
                const dateA = new Date(a.sale_date);
                const dateB = new Date(b.sale_date);
                return dateB.getTime() - dateA.getTime(); // Latest first
            }

            // Final fallback to ID (latest ID first)
            return (b.id || 0) - (a.id || 0);
        });

        // Read userPermissions from window (exposed by pos_ajax)
        const userPermissions = window.userPermissions || {};

        // Add each row in sorted order
        sortedSales.forEach((sale, index) => {
            let customerName = 'Walk-In Customer';

            if (sale.customer) {
                customerName = [
                    sale.customer.prefix,
                    sale.customer.first_name,
                    sale.customer.last_name
                ].filter(Boolean).join(' ');
            } else if (sale.customer_id && String(sale.customer_id) !== '1') {
                // Relation missing (e.g. old API) — avoid labelling credit customers as walk-in
                customerName = 'Customer #' + sale.customer_id;
            }

            // Format the final total
            const finalTotal = parseFloat(sale.final_total || 0).toFixed(2);

            // Create action buttons based on status and permissions
            let actionButtons = `<button class='btn btn-outline-success btn-sm me-1' onclick="printReceipt(${sale.id})" title="Print">
                                    <i class="fas fa-print"></i>
                                </button>`;

            // Add edit button based on user permissions and status
            if (userPermissions.canEditSale && status !== 'quotation') {
                actionButtons += `<button class='btn btn-outline-primary btn-sm' onclick="navigateToEdit(${sale.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>`;
            }

            const fmt = window.formatAmountWithSeparators || (v => v);
            const locationName = sale.location && sale.location.name
                ? String(sale.location.name).trim()
                : (sale.location_id ? `#${sale.location_id}` : '—');

            table.row.add([
                index + 1,
                sale.invoice_no || 'N/A',
                customerName,
                locationName,
                sale.sales_date || 'N/A',
                `Rs. ${fmt(finalTotal)}`,
                actionButtons
            ]);
        });
    }

    table.draw(); // Draw all rows at once for performance

    // Reset loading flag
    isLoadingTableData = false;

    // Update tab badge counts
    updateTabBadges();
}

// Function to update tab badge counts
function updateTabBadges() {
    const statusCounts = {
        final: 0,
        quotation: 0,
        draft: 0,
        jobticket: 0,
        suspend: 0
    };

    // Count sales by status
    sales.forEach(sale => {
        if (statusCounts.hasOwnProperty(sale.status)) {
            statusCounts[sale.status]++;
        }
    });

    // Update badge counts on tabs
    Object.keys(statusCounts).forEach(status => {
        const tabLink = $(`#transactionTabs a[href="#${status}"]`);

        if (tabLink.length > 0) {
            // Remove existing badge
            tabLink.find('.badge').remove();

            // Add new badge if count > 0
            if (statusCounts[status] > 0) {
                const tabText = tabLink.text().trim();
                const badge = ` <span class="badge bg-warning text-dark rounded-pill ms-1 fw-bold">${statusCounts[status]}</span>`;
                tabLink.html(tabText + badge);
            }
        }
    });

}

// Function to navigate to the edit page
function navigateToEdit(saleId) {
    window.location.href = "/sales/edit/" + saleId;
}

// Function to print the receipt for the sale
function printReceipt(saleId) {
    document.querySelectorAll('.modal.show').forEach(modal => {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }
    });

    // Start fetch immediately (parallel with modal close). Old code waited 300ms before
    // requesting — that stacked on top of server render time and felt very slow.
    const runPrint = (invoiceHtml) => {
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

        const focusProductSearch = () => {
            setTimeout(() => {
                const searchInput = document.getElementById('productSearchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }, 300);
        };

        if (isMobile) {
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                toastr.error('Please allow pop-ups to print the receipt.');
                return;
            }
            printWindow.document.open();
            printWindow.document.write(invoiceHtml);
            printWindow.document.close();
            printWindow.onload = function() {
                // One frame is enough for layout; 500ms was unnecessary delay
                requestAnimationFrame(() => {
                    printWindow.print();
                    printWindow.onafterprint = function() {
                        focusProductSearch();
                    };
                });
            };
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:absolute;width:0;height:0;border:none;left:-9999px;top:-9999px;visibility:hidden;';
        document.body.appendChild(iframe);
        const iframeDoc = iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(invoiceHtml);
        iframeDoc.close();

        iframe.onload = function() {
            requestAnimationFrame(() => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Print error:', e);
                    toastr.error('Unable to print. Please try again.');
                }

                const cleanup = () => {
                    if (iframe && document.body.contains(iframe)) {
                        document.body.removeChild(iframe);
                    }
                    focusProductSearch();
                };

                if (iframe.contentWindow.matchMedia) {
                    const mediaQueryList = iframe.contentWindow.matchMedia('print');
                    mediaQueryList.addListener(function(mql) {
                        if (!mql.matches) {
                            setTimeout(cleanup, 500);
                        }
                    });
                }
                setTimeout(cleanup, 3000);
            });
        };
    };

    fetch(`/sales/print-recent-transaction/${saleId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.invoice_html) {
                runPrint(data.invoice_html);
            } else {
                toastr.error('Failed to load receipt. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error fetching the receipt:', error);
            toastr.error('Error loading receipt. Please try again.');
        });
}

// --- Initialization (DataTable + event bindings) ---
document.addEventListener('DOMContentLoaded', function() {
    if (recentTransactionsInitialized) {
        return;
    }
    recentTransactionsInitialized = true;

    // Initialize DataTable with proper configuration
    if ($.fn.DataTable.isDataTable('#transactionTable')) {
        $('#transactionTable').DataTable().destroy();
    }

    $('#transactionTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [], // Disable initial ordering since we handle it manually
        columnDefs: [
            { orderable: true, targets: [0, 1, 2, 3, 4, 5] },
            { orderable: false, targets: [6] }
        ]
    });

    // Unbind existing tab event listeners first, then setup new ones
    $('#transactionTabs a[data-bs-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        let status = '';

        // Extract status from href
        switch(target) {
            case '#final':
                status = 'final';
                break;
            case '#quotation':
                status = 'quotation';
                break;
            case '#draft':
                status = 'draft';
                break;
            case '#jobticket':
                status = 'jobticket';
                break;
            case '#suspend':
                status = 'suspend';
                break;
            default:
                status = 'final';
        }

        debouncedLoadTableData(status);
    });

    // ⚡ PERFORMANCE FIX: Don't fetch sales data on page load - only when modal is opened
    // fetchSalesData(); // REMOVED - was causing 9 second delay on page load!

    // Unbind existing modal event listeners first, then setup new one
    $('#recentTransactionsModal').off('shown.bs.modal').on('shown.bs.modal', function () {
        // Always fetch fresh data when modal is opened
        fetchSalesData();
    });
});

// --- Window exports ---
window.fetchSalesData          = fetchSalesData;
window.loadTableData           = loadTableData;
window.updateTabBadges         = updateTabBadges;
window.debouncedLoadTableData  = debouncedLoadTableData;
window.shouldRefreshSalesData  = shouldRefreshSalesData;
window.navigateToEdit          = navigateToEdit;
window.printReceipt            = printReceipt;
