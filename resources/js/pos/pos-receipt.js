/**
 * pos-receipt.js — Phase 16
 * Receipt preview modal and printing logic.
 * Extracted from pos_ajax.blade.php.
 *
 * Reads from window:
 *   window.navigateToPosCreate()  — pos_ajax (bridge added Phase 16)
 *
 * Exports to window:
 *   window.showReceiptPreview(saleId)
 */
'use strict';

// ---- showReceiptPreview ----
function showReceiptPreview(saleId) {

    const modalHtml = `
        <div class="modal fade" id="receiptPreviewModal" tabindex="-1" aria-labelledby="receiptPreviewLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptPreviewLabel">
                            <i class="fas fa-receipt me-2"></i>Receipt Preview
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="btn-group w-100" role="group" aria-label="Receipt Layout Selector">
                                    <input type="radio" class="btn-check" name="receiptLayout" id="layout_80mm" value="80mm" checked>
                                    <label class="btn btn-outline-primary" for="layout_80mm">
                                        <i class="fas fa-receipt me-1"></i>80mm Thermal
                                    </label>

                                    <input type="radio" class="btn-check" name="receiptLayout" id="layout_a4" value="a4">
                                    <label class="btn btn-outline-primary" for="layout_a4">
                                        <i class="fas fa-file-invoice me-1"></i>A4 Size
                                    </label>

                                    <input type="radio" class="btn-check" name="receiptLayout" id="layout_dot_matrix" value="dot_matrix">
                                    <label class="btn btn-outline-primary" for="layout_dot_matrix">
                                        <i class="fas fa-print me-1"></i>Dot Matrix (Half)
                                    </label>

                                    <input type="radio" class="btn-check" name="receiptLayout" id="layout_dot_matrix_full" value="dot_matrix_full">
                                    <label class="btn btn-outline-primary" for="layout_dot_matrix_full">
                                        <i class="fas fa-print me-1"></i>Dot Matrix (Full)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="text-center py-3" id="receiptLoadingSpinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading receipt preview...</p>
                                </div>
                                <div id="receiptPreviewContent" style="display: none; max-height: 70vh; overflow-y: auto; overflow-x: auto; background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center;">
                                    <div id="receiptInnerContainer" style="background: white; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.1); zoom: 1.5; margin: 0 auto;">
                                        <!-- Receipt HTML will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" id="printReceiptBtn">
                            <i class="fas fa-print me-1"></i>Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Initialize modal
    const modalElement = document.getElementById('receiptPreviewModal');
    const modal = new bootstrap.Modal(modalElement);

    // Load initial receipt (80mm default)
    loadReceiptPreview(saleId, '80mm');

    // Handle layout change
    const layoutRadios = document.querySelectorAll('input[name="receiptLayout"]');
    layoutRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadReceiptPreview(saleId, this.value);
            }
        });
    });

    // Handle print button
    document.getElementById('printReceiptBtn').addEventListener('click', function() {
        const selectedLayout = document.querySelector('input[name="receiptLayout"]:checked').value;
        printReceiptWithLayout(saleId, selectedLayout);
        modal.hide();
    });

    // Show modal
    modal.show();

    // Remove modal from DOM when closed
    modalElement.addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// ---- loadReceiptPreview ----
function loadReceiptPreview(saleId, layout) {
    const contentDiv = document.getElementById('receiptPreviewContent');
    const innerContainer = document.getElementById('receiptInnerContainer');
    const spinnerDiv = document.getElementById('receiptLoadingSpinner');

    // Show spinner, hide content
    spinnerDiv.style.display = 'block';
    contentDiv.style.display = 'none';

    fetch(`/sales/print-recent-transaction/${saleId}?layout=${layout}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.invoice_html) {
                // Insert receipt into the inner container
                if (innerContainer) {
                    innerContainer.innerHTML = data.invoice_html;
                } else {
                    contentDiv.innerHTML = data.invoice_html;
                }

                // Hide spinner, show content
                spinnerDiv.style.display = 'none';
                contentDiv.style.display = 'block';
            } else {
                throw new Error('No invoice HTML received');
            }
        })
        .catch(error => {
            console.error('Error loading receipt preview:', error);
            if (innerContainer) {
                innerContainer.innerHTML = '<div class="alert alert-danger m-3">Error loading receipt preview. Please try again.</div>';
            } else {
                contentDiv.innerHTML = '<div class="alert alert-danger m-3">Error loading receipt preview. Please try again.</div>';
            }
            spinnerDiv.style.display = 'none';
            contentDiv.style.display = 'block';
        });
}

// ---- printReceiptWithLayout ----
function printReceiptWithLayout(saleId, layout) {

    fetch(`/sales/print-recent-transaction/${saleId}?layout=${layout}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.invoice_html) {
                // Check if mobile device
                const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

                if (isMobile) {
                    // For mobile: use a Blob URL so window.open() gets a real URL
                    // (avoids pop-up blocking and onload reliability issues caused by
                    // calling window.open inside an async fetch .then())
                    const blob = new Blob([data.invoice_html], { type: 'text/html' });
                    const blobUrl = URL.createObjectURL(blob);
                    const printWindow = window.open(blobUrl, '_blank');

                    if (printWindow) {
                        printWindow.onload = function() {
                            setTimeout(() => {
                                printWindow.print();
                                URL.revokeObjectURL(blobUrl); // free memory after print dialog opens

                                printWindow.onafterprint = function() {
                                    setTimeout(() => {
                                        const searchInput = document.getElementById('productSearchInput');
                                        if (searchInput) {
                                            searchInput.focus();
                                            searchInput.select();
                                        }
                                    }, 300);
                                };
                            }, 500);
                        };
                    } else {
                        URL.revokeObjectURL(blobUrl);
                        toastr.error('Please allow pop-ups to print the receipt.');
                    }
                } else {
                    // For desktop: Use hidden iframe method
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'absolute';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = 'none';
                    iframe.style.left = '-9999px';
                    iframe.style.top = '-9999px';
                    iframe.style.visibility = 'hidden';
                    document.body.appendChild(iframe);

                    const iframeDoc = iframe.contentWindow.document;
                    iframeDoc.open();
                    iframeDoc.write(data.invoice_html);
                    iframeDoc.close();

                    iframe.onload = function() {
                        setTimeout(() => {
                            try {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();

                                iframe.contentWindow.onafterprint = function() {
                                    setTimeout(() => {
                                        const searchInput = document.getElementById('productSearchInput');
                                        if (searchInput) {
                                            searchInput.focus();
                                            searchInput.select();
                                        }
                                    }, 200);
                                };
                            } catch (e) {
                                console.error('Print error:', e);
                                toastr.error('Unable to print. Please try again.');
                            }

                            // Cleanup
                            setTimeout(() => {
                                if (iframe && document.body.contains(iframe)) {
                                    document.body.removeChild(iframe);
                                }

                                // Navigate if edit mode
                                if (window.location.pathname.includes('/edit/')) {
                                    window.navigateToPosCreate();
                                }
                            }, 1000);
                        }, 500);
                    };
                }
            }
        })
        .catch(error => {
            console.error('Error printing receipt:', error);
            toastr.error('Error printing receipt. Please try again.');
        });
}

// ---- Window exports ----
window.showReceiptPreview       = showReceiptPreview;
window.loadReceiptPreview       = loadReceiptPreview;
window.printReceiptWithLayout   = printReceiptWithLayout;
