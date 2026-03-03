'use strict';

/**
 * ============================================================
 * POS Helpers Module (Phase 18)
 * ============================================================
 * Utility functions used across the POS page:
 *   - preventDoubleClick / enableButton / safeAjaxCall
 *   - showPriceHistoryModal
 *   - Datetime picker init, autocomplete off, Select2 focus fix
 * ============================================================
 */

/* ── Button Protection ──────────────────────────────────────── */
function preventDoubleClick(button, callback) {
    if (button.dataset.isProcessing === 'true') return;
    button.dataset.isProcessing = 'true';
    button.disabled = true;

    try {
        callback();
    } catch (error) {
        enableButton(button);
    }
}

function enableButton(button) {
    button.disabled = false;
    button.dataset.isProcessing = 'false';

    var $button = $(button);
    if ($button.attr('id') === 'cashButton') {
        $button.html('<i class="fa fa-money"></i> Cash');
    } else if ($button.attr('id') === 'cardButton') {
        $button.html('<i class="fa fa-credit-card"></i> Card');
    } else if ($button.attr('id') === 'creditButton') {
        $button.html('<i class="fa fa-credit-card"></i> Credit');
    } else {
        var originalText = $button.data('original-text') ||
            $button.text().replace(/Processing\.\.\./g, '').replace(/fa-spinner fa-spin/g, 'fa-money');
        $button.html(originalText);
    }
}

window.enableButton       = enableButton;
window.preventDoubleClick = preventDoubleClick;

/* ── Safe AJAX Call (button-protected) ──────────────────────── */
function safeAjaxCall(button, options) {
    preventDoubleClick(button, function () {
        $.ajax(options)
            .done(function (response) {
                if (options.done) options.done(response);
            })
            .fail(function (xhr, status, error) {
                var errorMessage = 'An error occurred during payment processing.';
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    } else if (errorResponse.error) {
                        errorMessage = errorResponse.error;
                    }
                } catch (e) {
                    if (xhr.responseText && xhr.responseText.includes('SQLSTATE')) {
                        if (xhr.responseText.includes('Invalid datetime format')) {
                            errorMessage = 'Invalid date format. Please check the payment date.';
                        } else {
                            errorMessage = 'Database error occurred. Please try again.';
                        }
                    }
                }
                toastr.error(errorMessage);
                if (options.fail) options.fail(xhr, status, error);
            })
            .always(function () {
                enableButton(button);
                if (options.always) options.always();
            });
    });
}
window.safeAjaxCall = safeAjaxCall;

/* ── Price History Modal ────────────────────────────────────── */
window.showPriceHistoryModal = function (productName, priceHistoryJson, customerName) {
    try {
        var priceHistory = JSON.parse(priceHistoryJson);

        var modalHtml =
            '<div class="modal fade" id="priceHistoryModal" tabindex="-1">' +
            '  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">' +
            '    <div class="modal-content">' +
            '      <div class="modal-header bg-primary text-white py-2">' +
            '        <h6 class="modal-title mb-0">\uD83D\uDCCA ' + productName + ' - Price History</h6>' +
            '        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
            '      </div>' +
            '      <div class="modal-body p-3">' +
            '        <div class="text-center mb-3 pb-2 border-bottom">' +
            '          <div class="row">' +
            '            <div class="col-6">' +
            '              <div class="text-success">' +
            '                <strong style="font-size:1.1em;">Last: Rs. ' + formatAmountWithSeparators(priceHistory.last_price.toFixed(2)) + '</strong>' +
            '                <div style="font-size:0.85em;color:#666;">' + priceHistory.last_purchase_date + '</div>' +
            '              </div>' +
            '            </div>' +
            '            <div class="col-6">' +
            '              <div class="text-primary">' +
            '                <strong style="font-size:1.1em;">Avg: Rs. ' + formatAmountWithSeparators(priceHistory.average_price.toFixed(2)) + '</strong>' +
            '                <div style="font-size:0.85em;color:#666;">' + priceHistory.previous_prices.length + ' purchases</div>' +
            '              </div>' +
            '            </div>' +
            '          </div>' +
            '        </div>' +
            '        <div class="table-responsive">' +
            '          <table class="table table-sm table-hover" style="margin-bottom:0;">' +
            '            <thead style="background-color:#f8f9fa;">' +
            '              <tr style="font-size:0.9em;">' +
            '                <th style="padding:8px;">Date</th>' +
            '                <th style="padding:8px;text-align:center;">Price</th>' +
            '                <th style="padding:8px;text-align:center;">Qty</th>' +
            '                <th style="padding:8px;">Invoice</th>' +
            '              </tr>' +
            '            </thead>' +
            '            <tbody>' +
            priceHistory.previous_prices.map(function (price) {
                return '<tr style="font-size:0.9em;">' +
                    '<td style="padding:8px;">' + price.sale_date + '</td>' +
                    '<td style="padding:8px;text-align:center;"><strong class="text-success">Rs. ' + formatAmountWithSeparators(price.unit_price.toFixed(2)) + '</strong></td>' +
                    '<td style="padding:8px;text-align:center;">' + formatAmountWithSeparators(price.quantity) + '</td>' +
                    '<td style="padding:8px;"><span class="badge bg-light text-dark" style="font-size:0.8em;">' + price.invoice_no + '</span></td>' +
                    '</tr>';
            }).join('') +
            '            </tbody>' +
            '          </table>' +
            '        </div>' +
            '      </div>' +
            '      <div class="modal-footer py-2">' +
            '        <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">\u2713 OK</button>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        var existingModal = document.getElementById('priceHistoryModal');
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        var modal = new bootstrap.Modal(document.getElementById('priceHistoryModal'));
        modal.show();

        document.getElementById('priceHistoryModal').addEventListener('hidden.bs.modal', function () {
            this.remove();
        });
    } catch (error) {
        toastr.error('Error displaying price history');
    }
};

/* ── Small jQuery Inits ─────────────────────────────────────── */
$(function () {
    $('.datetime').datetimepicker({ format: 'hh:mm:ss a' });
});

/* Autocomplete off */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input').forEach(function (input) {
        input.setAttribute('autocomplete', 'off');
    });
});

/* Select2 focus fix */
$(document).ready(function () {
    $('.selectBox').select2();

    $('.selectBox').on('select2:open', function () {
        setTimeout(function () {
            var allDropdowns = document.querySelectorAll('.select2-container--open');
            var lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];
            if (lastOpenedDropdown) {
                var searchInput = lastOpenedDropdown.querySelector('.select2-search__field');
                if (searchInput) { searchInput.focus(); searchInput.select(); }
            }
        }, 10);
    });
});
