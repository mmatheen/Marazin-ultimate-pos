/**
 * POS Cash Register – open/close drawer, pay in/out, expense, balance.
 * Depends: window.PosConfig.routes.cashRegister, jQuery, toastr.
 */
(function () {
    'use strict';

    const routes = window.PosConfig?.routes?.cashRegister || {};
    const cashRegisterPerms = window.PosConfig?.permissions?.cashRegister || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function getCsrfHeaders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token || '',
        };
    }

    let currentRegister = null;
    let currentBalance = 0;

    function getCurrentRegister(locationId) {
        if (!cashRegisterPerms.canPollCurrent) {
            return Promise.resolve({ success: true, open: false, register: null, balance: 0 });
        }
        if (!locationId) return Promise.resolve({ open: false, register: null, balance: 0 });
        const url = routes.current + '?location_id=' + encodeURIComponent(locationId);
        return fetch(url, { headers: getCsrfHeaders() })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.open) {
                    currentRegister = data.register;
                    currentBalance = data.balance ?? 0;
                } else {
                    currentRegister = null;
                    currentBalance = 0;
                }
                return data;
            })
            .catch(err => {
                console.error('Cash register current:', err);
                currentRegister = null;
                currentBalance = 0;
                return { success: false, open: false, register: null, balance: 0 };
            });
    }

    function openRegister(locationId, openingAmount) {
        return fetch(routes.open, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: JSON.stringify({ location_id: locationId, opening_amount: openingAmount }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.register) {
                    currentRegister = data.register;
                    currentBalance = data.balance ?? 0;
                }
                return data;
            });
    }

    function payIn(registerId, amount, notes) {
        return fetch(routes.payIn, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: JSON.stringify({ register_id: registerId, amount, notes: notes || '' }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.balance != null) currentBalance = data.balance;
                return data;
            });
    }

    function payOut(registerId, amount, notes) {
        return fetch(routes.payOut, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: JSON.stringify({ register_id: registerId, amount, notes: notes || '' }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.balance != null) currentBalance = data.balance;
                return data;
            });
    }

    function closeRegister(registerId, closingAmount, notes) {
        return fetch(routes.close, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: JSON.stringify({ register_id: registerId, closing_amount: closingAmount, notes: notes || '' }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    currentRegister = null;
                    currentBalance = 0;
                }
                return data;
            });
    }

    function getCloseScreenData(registerId) {
        return fetch(routes.closeScreen + '?register_id=' + encodeURIComponent(registerId), { headers: getCsrfHeaders() })
            .then(r => r.json());
    }

    function refreshBalance(registerId) {
        if (!registerId) return Promise.resolve(currentBalance);
        return fetch(routes.balance + '?register_id=' + encodeURIComponent(registerId), { headers: getCsrfHeaders() })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.balance != null) currentBalance = data.balance;
                return data;
            });
    }

    function addExpenseFromPos(payload) {
        return fetch(routes.expense, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.balance != null) currentBalance = data.balance;
                return data;
            });
    }

    function updateBalanceDisplay() {
        if (!cashRegisterPerms.canPollCurrent) return;
        const el = document.getElementById('posCashDrawerBalance');
        if (!el) return;
        if (currentRegister) {
            el.textContent = 'Rs. ' + (typeof currentBalance === 'number' ? currentBalance.toFixed(2) : currentBalance);
            el.closest('.pos-cash-drawer-wrap')?.classList.remove('d-none');
        } else {
            el.closest('.pos-cash-drawer-wrap')?.classList.add('d-none');
        }
    }

    window.PosCashRegister = {
        getCurrentRegister,
        openRegister,
        payIn,
        payOut,
        closeRegister,
        getCloseScreenData,
        refreshBalance,
        addExpenseFromPos,
        get currentRegister() { return currentRegister; },
        get currentBalance() { return currentBalance; },
        setCurrentBalance(v) { currentBalance = v; updateBalanceDisplay(); },
        updateBalanceDisplay,
    };

    $(document).ready(function () {
        updateBalanceDisplay();

        function getSelectedLocationId() {
            const sel = document.getElementById('locationSelectDesktop') || document.getElementById('locationSelect');
            return sel ? parseInt(sel.value, 10) : null;
        }

        $(document).on('pos:location-changed pos:register-refresh', function () {
            const locId = getSelectedLocationId();
            if (!cashRegisterPerms.canPollCurrent || !locId || !window.PosCashRegister.getCurrentRegister) {
                return;
            }
            window.PosCashRegister.getCurrentRegister(locId).then(function (data) {
                window.PosCashRegister.updateBalanceDisplay();
                if (cashRegisterPerms.canOpen && data.open === false && data.success && document.getElementById('posOpenRegisterModal')) {
                    $('#posOpenRegisterModal').modal('show');
                }
            });
        });

        $('#posOpenRegisterBtn').on('click', function () {
            const locId = getSelectedLocationId();
            if (!locId) {
                if (typeof toastr !== 'undefined') toastr.warning('Select a location first.');
                return;
            }
            const amount = parseFloat($('#posOpeningAmount').val()) || 0;
            if (amount < 0) {
                if (typeof toastr !== 'undefined') toastr.warning('Enter opening amount.');
                return;
            }
            window.PosCashRegister.openRegister(locId, amount).then(function (res) {
                if (res.success) {
                    $('#posOpenRegisterModal').modal('hide');
                    $('#posOpeningAmount').val('');
                    window.PosCashRegister.updateBalanceDisplay();
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Register opened.');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Failed to open register.');
                }
            });
        });

        $('#posPayInConfirmBtn').on('click', function () {
            const reg = window.PosCashRegister.currentRegister;
            if (!reg) { if (typeof toastr !== 'undefined') toastr.warning('Open a register first.'); return; }
            const amount = parseFloat($('#posPayInAmount').val()) || 0;
            const notes = $('#posPayInNotes').val() || '';
            if (amount <= 0) { if (typeof toastr !== 'undefined') toastr.warning('Enter amount.'); return; }
            window.PosCashRegister.payIn(reg.id, amount, notes).then(function (res) {
                if (res.success) {
                    $('#posPayInModal').modal('hide');
                    $('#posPayInAmount').val(''); $('#posPayInNotes').val('');
                    window.PosCashRegister.updateBalanceDisplay();
                    if (typeof toastr !== 'undefined') toastr.success(res.message);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Pay in failed.');
                }
            });
        });

        $('#posPayOutConfirmBtn').on('click', function () {
            const reg = window.PosCashRegister.currentRegister;
            if (!reg) { if (typeof toastr !== 'undefined') toastr.warning('Open a register first.'); return; }
            const amount = parseFloat($('#posPayOutAmount').val()) || 0;
            const notes = $('#posPayOutNotes').val() || '';
            if (amount <= 0) { if (typeof toastr !== 'undefined') toastr.warning('Enter amount.'); return; }
            window.PosCashRegister.payOut(reg.id, amount, notes).then(function (res) {
                if (res.success) {
                    $('#posPayOutModal').modal('hide');
                    $('#posPayOutAmount').val(''); $('#posPayOutNotes').val('');
                    window.PosCashRegister.updateBalanceDisplay();
                    if (typeof toastr !== 'undefined') toastr.success(res.message);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Pay out failed.');
                }
            });
        });

        $('#posCloseRegisterBtn').on('click', function () {
            const reg = window.PosCashRegister.currentRegister;
            if (!reg) { if (typeof toastr !== 'undefined') toastr.warning('No open register.'); return; }
            window.PosCashRegister.getCloseScreenData(reg.id).then(function (res) {
                if (res.success && res.register) {
                    $('#posCloseExpectedBalance').text('Rs. ' + (res.register.expected_balance ?? 0).toFixed(2));
                    $('#posCloseRegisterModal').modal('show');
                }
            });
        });

        $('#posCloseRegisterConfirmBtn').on('click', function () {
            const reg = window.PosCashRegister.currentRegister;
            if (!reg) return;
            const closingAmount = parseFloat($('#posClosingAmount').val());
            const notes = $('#posCloseNotes').val() || '';
            if (isNaN(closingAmount) || closingAmount < 0) {
                if (typeof toastr !== 'undefined') toastr.warning('Enter counted cash amount.');
                return;
            }
            window.PosCashRegister.closeRegister(reg.id, closingAmount, notes).then(function (res) {
                if (res.success) {
                    $('#posCloseRegisterModal').modal('hide');
                    $('#posClosingAmount').val(''); $('#posCloseNotes').val('');
                    window.PosCashRegister.updateBalanceDisplay();
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Register closed.');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Close failed.');
                }
            });
        });

        $('#posAddExpenseBtn').on('click', function () {
            const reg = window.PosCashRegister.currentRegister;
            if (!reg) { if (typeof toastr !== 'undefined') toastr.warning('Open a register first.'); return; }
            const locId = document.getElementById('locationSelectDesktop')?.value || document.getElementById('locationSelect')?.value;
            if (!locId) { if (typeof toastr !== 'undefined') toastr.warning('Select location.'); return; }
            $('#posExpenseRegisterId').val(reg.id);
            $('#posExpenseLocationId').val(locId);
            $('#posExpenseAmount').val('');
            $('#posExpensePaidTo').val('');
            $('#posExpenseNote').val('');
            $('#posExpenseParentCategory').empty().append('<option value="">Select category</option>');
            $('#posExpenseSubCategory').empty().append('<option value="">Select subcategory</option>');
            const parentCatUrl = (window.PosConfig?.routes?.expenseParentCategories || '/expense-parent-catergory-get-all');
            fetch(parentCatUrl, { headers: getCsrfHeaders() }).then(r => r.json()).then(function (data) {
                const list = Array.isArray(data.message) ? data.message : (data.data || data || []);
                (Array.isArray(list) ? list : []).forEach(function (item) {
                    $('#posExpenseParentCategory').append('<option value="' + (item.id) + '">' + (item.expenseParentCatergoryName || item.name || item.id) + '</option>');
                });
            }).catch(() => {});
            $('#posExpenseModal').modal('show');
        });

        $('#posExpenseSubmitBtn').on('click', function () {
            const registerId = $('#posExpenseRegisterId').val();
            const locationId = $('#posExpenseLocationId').val();
            const amount = parseFloat($('#posExpenseAmount').val());
            const parentCat = $('#posExpenseParentCategory').val();
            const subCat = $('#posExpenseSubCategory').val();
            const paidTo = $('#posExpensePaidTo').val();
            const note = $('#posExpenseNote').val();
            if (!registerId || !locationId || !parentCat || !amount || amount <= 0) {
                if (typeof toastr !== 'undefined') toastr.warning('Fill required fields: category and amount.');
                return;
            }
            window.PosCashRegister.addExpenseFromPos({
                register_id: registerId,
                location_id: locationId,
                expense_parent_category_id: parentCat,
                expense_sub_category_id: subCat || null,
                amount,
                paid_to: paidTo || null,
                note: note || null,
            }).then(function (res) {
                if (res.success) {
                    $('#posExpenseModal').modal('hide');
                    window.PosCashRegister.updateBalanceDisplay();
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Expense recorded.');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Expense failed.');
                }
            });
        });

        $('#posExpenseParentCategory').on('change', function () {
            const parentId = $(this).val();
            const $sub = $('#posExpenseSubCategory');
            $sub.empty().append('<option value="">Select subcategory</option>');
            if (!parentId) return;
            const base = (window.PosConfig?.routes?.expenseSubCategories || '/expense-sub-categories/');
            fetch(base + parentId, { headers: getCsrfHeaders() })
                .then(r => r.json())
                .then(data => {
                    const list = data.data || data.sub_categories || data || [];
                    (Array.isArray(list) ? list : []).forEach(function (item) {
                        $sub.append('<option value="' + (item.id || item.value) + '">' + (item.subExpenseCategoryname || item.subExpenseCategoryName || item.name || item.id) + '</option>');
                    });
                })
                .catch(() => {});
        });
    });
})();
