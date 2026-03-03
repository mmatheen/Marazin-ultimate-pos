'use strict';

// ============================================================
// Phase 11: pos-salesrep-display.js
// Sales rep UI, customer filtering, button visibility,
// access checks — all the display/logic layer.
//
// Storage helpers (getSalesRepSelection etc.) live in pos-salesrep.js (Phase 6).
//
// Shared state accessed via window.*:
//   window.isSalesRep            — set here, read by pos_ajax
//   window.isEditing             — set by pos_ajax, read here (read-only)
//   window.currentEditingSaleId  — set by pos_ajax, read here (read-only)
//   window.selectedLocationId    — set by pos_ajax / here
//   window.cachedLocations       — set by pos-location.js
//   window.locationCacheExpiry   — set by pos-location.js
//   window.PosConfig.auth.userId — from pos-config.blade.php
//
// Module-local state (only used inside Phase 11 functions):
//   salesRepCustomersFiltered, salesRepCustomersLoaded,
//   isCurrentlyFiltering, lastCustomerFilterCall,
//   filteringInProgress, lastSuccessfulFilter, filterRequestId
// ============================================================

// --- Module-local state ---
let salesRepCustomersFiltered = false;
let salesRepCustomersLoaded   = false;
let isCurrentlyFiltering      = false;
let lastCustomerFilterCall    = 0;
let filteringInProgress       = false;
let lastSuccessfulFilter      = null;
let filterRequestId           = 0;

// ---- restoreSalesRepDisplayFromStorage ----
function restoreSalesRepDisplayFromStorage() {
    if (!window.isSalesRep) {
        return;
    }

    const storedSelection = window.getSalesRepSelection();
    const currentUserId = window.PosConfig ? window.PosConfig.auth.userId : null;

    if (storedSelection && storedSelection.userId && storedSelection.userId !== currentUserId) {
        window.clearSalesRepSelection();
        salesRepCustomersFiltered = false;
        salesRepCustomersLoaded = false;
        window.hasStoredSalesRepSelection = false;
        return;
    }

    if (storedSelection && storedSelection.vehicle && storedSelection.route) {
        window.hasStoredSalesRepSelection = true;

        setTimeout(() => {
            updateSalesRepDisplay(storedSelection);

            if (storedSelection.vehicle && storedSelection.vehicle.id) {
                checkAndToggleSalesRepButtons(storedSelection.vehicle.id);
            }

            setTimeout(() => {
                filterCustomersByRoute(storedSelection);
            }, 300);
        }, 100);

        window.storeSalesRepSelection(storedSelection);
    } else {
        window.hasStoredSalesRepSelection = false;
    }
}

// ---- protectSalesRepCustomerFiltering ----
function protectSalesRepCustomerFiltering() {
    // DISABLED: Mutation observer removed to prevent unwanted customer re-selection
    return;

    /* eslint-disable no-unreachable */
    let debounceTimer = null;
    let lastFilterTime = 0;
    const FILTER_COOLDOWN = 10000;

    const observer = new MutationObserver(function(mutations) {
        if (salesRepCustomersLoaded) return;
        if (window.salesRepCustomerResetInProgress || window.preventAutoSelection) return;
        if (window.lastCustomerResetTime && (Date.now() - window.lastCustomerResetTime) < 5000) return;

        const now = Date.now();
        if (now - lastFilterTime < FILTER_COOLDOWN) return;
        if (filteringInProgress || isCurrentlyFiltering) return;

        if (debounceTimer) clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && window.isSalesRep && !filteringInProgress) {
                    const customerSelect = $('#customer-id');
                    const options = customerSelect.find('option');
                    if (options.length <= 1) return;

                    const hasWalkIn = options.filter(function() {
                        return $(this).text().toLowerCase().includes('walk-in');
                    }).length > 0;

                    const selection = window.getSalesRepSelection();
                    let hasWrongRouteCustomers = false;

                    if (selection && selection.route && selection.route.name) {
                        const selectedRouteName = selection.route.name.toLowerCase();
                        options.each(function() {
                            const optionText = $(this).text().toLowerCase();
                            if (optionText !== 'please select' &&
                                !optionText.includes('walk-in') &&
                                !optionText.includes(selectedRouteName) &&
                                (optionText.includes('kalmunai') || optionText.includes('retailer'))) {
                                hasWrongRouteCustomers = true;
                                return false;
                            }
                        });
                    }

                    if ((salesRepCustomersFiltered && (hasWalkIn || hasWrongRouteCustomers)) ||
                        (!salesRepCustomersFiltered && selection && (hasWalkIn || hasWrongRouteCustomers))) {
                        lastFilterTime = Date.now();
                        if (selection) filterCustomersByRoute(selection);
                    }
                }
            });
        }, 500);
    });

    const customerDropdown = document.getElementById('customer-id');
    if (customerDropdown) {
        observer.observe(customerDropdown, { childList: true, subtree: true });
    }
    /* eslint-enable no-unreachable */
}

// ---- checkSalesRepStatus ----
function checkSalesRepStatus(callback) {

    fetch('/sales-rep/my-assignments', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        return response.json().then(data => ({ status: response.status, data }));
    })
    .then(({ status, data }) => {

        if (status === 200 && data.status === true && data.data && data.data.length > 0) {
            window.isSalesRep = true;
            restoreSalesRepDisplayFromStorage();
            handleSalesRepUser(data.data);
            if (typeof callback === 'function') callback(true);
        } else if (status === 200 && data.status === false) {
            window.isSalesRep = false;
            hideSalesRepDisplay();
            if (typeof callback === 'function') callback(false);
        } else if (status === 200 && data.status === true && (!data.data || data.data.length === 0)) {
            window.isSalesRep = true;
            hideSalesRepDisplay();
            if (typeof callback === 'function') callback(true);
        } else {
            window.isSalesRep = false;
            hideSalesRepDisplay();
            if (typeof callback === 'function') callback(false);
        }
    })
    .catch(error => {
        window.isSalesRep = false;
        hideSalesRepDisplay();
        if (typeof callback === 'function') callback(false);
    });
}

// ---- handleSalesRepUser ----
function handleSalesRepUser(assignments) {
    window.salesRepAssignments = assignments;

    const storedSelection = window.getSalesRepSelection();
    const currentUserId = window.PosConfig ? window.PosConfig.auth.userId : null;

    if (storedSelection && storedSelection.userId && storedSelection.userId !== currentUserId) {
        window.clearSalesRepSelection();
        salesRepCustomersFiltered = false;
        salesRepCustomersLoaded = false;
        $('#customer-id').empty().append('<option value="">Please Select</option>');
    }

    const salesRepDisplay = document.getElementById('salesRepDisplay');
    if (salesRepDisplay) {
        salesRepDisplay.classList.remove('d-none');
    }

    if (!window.hasSalesRepSelection()) {
        if (typeof showSalesRepModal === 'function') {
            showSalesRepModal();
        } else {
            console.error('showSalesRepModal function not found');
        }
    } else {
        const selection = window.getSalesRepSelection();

        if (window.hasStoredSalesRepSelection) {
        }

        let validAssignment = null;
        if (selection && selection.vehicle && selection.route && selection.vehicle.id && selection.route.id) {
            validAssignment = assignments.find(a =>
                a.sub_location && a.route &&
                a.sub_location.id === selection.vehicle.id &&
                a.route.id === selection.route.id
            );
            if (!validAssignment) {
                validAssignment = assignments.find(a =>
                    a.sub_location && a.sub_location.id === selection.vehicle.id
                );
            }
        }

        if (validAssignment) {
            const updatedSelection = {
                ...selection,
                canSell: validAssignment.can_sell || selection.canSell || true
            };
            window.storeSalesRepSelection(updatedSelection);

            if (!window.hasStoredSalesRepSelection) {
                updateSalesRepDisplay(updatedSelection);
            }

            if (!window.isEditing) {
                setTimeout(() => {
                    restrictLocationAccess(updatedSelection);
                }, 800);
            } else {
            }

            if (!salesRepCustomersLoaded) {
                setTimeout(() => filterCustomersByRoute(updatedSelection), 1200);
            }
        } else {
            if (selection && selection.vehicle && selection.route) {
                if (typeof selection.canSell === 'undefined') selection.canSell = true;

                const vehicleExists = assignments.some(a =>
                    a.sub_location && a.sub_location.id === selection.vehicle.id
                );

                if (vehicleExists) {
                    try {
                        if (!window.hasStoredSalesRepSelection) {
                            updateSalesRepDisplay(selection);
                        }

                        if (!window.isEditing) {
                            setTimeout(() => {
                                restrictLocationAccess(selection);
                            }, 800);
                        } else {
                        }

                        if (!salesRepCustomersLoaded) {
                            setTimeout(() => filterCustomersByRoute(selection), 1200);
                        }
                    } catch (error) {
                        console.error('Error applying selection:', error);
                        window.clearSalesRepSelection();
                        if (typeof showSalesRepModal === 'function') showSalesRepModal();
                    }
                } else {
                    window.clearSalesRepSelection();
                    if (typeof showSalesRepModal === 'function') showSalesRepModal();
                }
            } else {
                window.clearSalesRepSelection();
                if (typeof showSalesRepModal === 'function') showSalesRepModal();
            }
        }
    }

    setupSalesRepEventListeners();
}

// ---- setupSalesRepEventListeners ----
function setupSalesRepEventListeners() {
    window.addEventListener('salesRepSelectionConfirmed', function(event) {
        const selection = event.detail;
        salesRepCustomersFiltered = false;
        salesRepCustomersLoaded = false;
        updateSalesRepDisplay(selection);
        restrictLocationAccess(selection);
        setTimeout(() => filterCustomersByRoute(selection), 500);
    });

    const changeBtnElement = document.getElementById('changeSalesRepSelection');
    if (changeBtnElement) {
        changeBtnElement.addEventListener('click', function() {
            if (typeof showSalesRepModal === 'function') {
                showSalesRepModal();
            } else {
                console.error('showSalesRepModal function not available');
            }
        });
    } else {
        setTimeout(() => {
            const delayedBtn = document.getElementById('changeSalesRepSelection');
            if (delayedBtn) {
                delayedBtn.addEventListener('click', function() {
                    if (typeof showSalesRepModal === 'function') showSalesRepModal();
                });
            }
        }, 1000);
    }

    const changeBtnMenuElement = document.getElementById('changeSalesRepSelectionMenu');
    if (changeBtnMenuElement) {
        changeBtnMenuElement.addEventListener('click', function() {
            const mobileMenuModal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
            if (mobileMenuModal) mobileMenuModal.hide();
            setTimeout(() => {
                if (typeof showSalesRepModal === 'function') {
                    showSalesRepModal();
                } else {
                    console.error('showSalesRepModal function not available');
                }
            }, 300);
        });
    } else {
        setTimeout(() => {
            const delayedBtnMenu = document.getElementById('changeSalesRepSelectionMenu');
            if (delayedBtnMenu) {
                delayedBtnMenu.addEventListener('click', function() {
                    const mobileMenuModal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
                    if (mobileMenuModal) mobileMenuModal.hide();
                    setTimeout(() => {
                        if (typeof showSalesRepModal === 'function') showSalesRepModal();
                    }, 300);
                });
            }
        }, 1000);
    }

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && window.isSalesRep) {
            const selection = window.getSalesRepSelection();
            if (selection && selection.vehicle && selection.route) {
                setTimeout(() => updateSalesRepDisplay(selection), 200);
            }
        }
    });

    window.addEventListener('beforeunload', function() {
        const selection = window.getSalesRepSelection();
        if (selection && window.isSalesRep) {
            window.storeSalesRepSelection(selection);
        }
    });
}

// ---- updateMobileSalesRepDisplay ----
function updateMobileSalesRepDisplay(selection) {
    if (!selection || !selection.vehicle || !selection.route) return;

    const salesRepDisplayMenu     = document.getElementById('salesRepDisplayMenu');
    const selectedVehicleDisplayMenu = document.getElementById('selectedVehicleDisplayMenu');
    const selectedRouteDisplayMenu   = document.getElementById('selectedRouteDisplayMenu');
    const salesAccessBadgeMenu    = document.getElementById('salesAccessBadgeMenu');

    if (!salesRepDisplayMenu || !selectedVehicleDisplayMenu || !selectedRouteDisplayMenu || !salesAccessBadgeMenu) {
        console.error('❌ Mobile menu elements not found');
        return;
    }

    const vehicleText = `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})`;
    const routeText   = selection.route.name;

    selectedVehicleDisplayMenu.textContent = vehicleText;
    selectedRouteDisplayMenu.textContent   = routeText;

    selectedVehicleDisplayMenu.setAttribute('style', 'display: inline-block !important; visibility: visible !important; opacity: 1 !important;');
    selectedRouteDisplayMenu.setAttribute('style', 'display: inline-block !important; visibility: visible !important; opacity: 1 !important;');
    salesAccessBadgeMenu.setAttribute('style', 'display: inline-block !important; visibility: visible !important; opacity: 1 !important;');

    if (selection.canSell) {
        salesAccessBadgeMenu.className = 'badge bg-success';
        salesAccessBadgeMenu.textContent = 'Sales Allowed';
    } else {
        salesAccessBadgeMenu.className = 'badge bg-warning text-dark';
        salesAccessBadgeMenu.textContent = 'View Only';
    }

    salesRepDisplayMenu.setAttribute('style', 'display: block !important; visibility: visible !important;');
}

// ---- updateDesktopSalesRepDisplay ----
function updateDesktopSalesRepDisplay(selection) {
    const salesRepDisplay        = document.getElementById('salesRepDisplay');
    const selectedVehicleDisplay = document.getElementById('selectedVehicleDisplay');
    const selectedRouteDisplay   = document.getElementById('selectedRouteDisplay');
    const salesAccessBadge       = document.getElementById('salesAccessBadge');
    const salesAccessText        = document.getElementById('salesAccessText');

    if (!salesRepDisplay || !selectedVehicleDisplay || !selectedRouteDisplay) {
        console.error('Desktop display elements not found');
        return;
    }

    selectedVehicleDisplay.textContent = selection.vehicle && selection.vehicle.name
        ? `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})`
        : 'Unknown Vehicle';
    selectedRouteDisplay.textContent = selection.route && selection.route.name
        ? selection.route.name
        : 'Unknown Route';

    if (salesAccessBadge && salesAccessText) {
        if (selection.canSell) {
            salesAccessBadge.className = 'badge bg-success text-white p-2';
            salesAccessText.textContent = 'Sales Allowed';
        } else {
            salesAccessBadge.className = 'badge bg-warning text-dark p-2';
            salesAccessText.textContent = 'View Only';
        }
    }

    salesRepDisplay.style.display = 'flex';
    salesRepDisplay.classList.add('d-flex', 'sales-rep-visible');
    salesRepDisplay.classList.remove('d-none');
}

// ---- updateSalesRepDisplay ----
function updateSalesRepDisplay(selection) {
    if (!selection || !selection.vehicle || !selection.route) return;

    updateDesktopSalesRepDisplay(selection);
    updateMobileSalesRepDisplay(selection);

    try {
        localStorage.setItem('salesRepSelection', JSON.stringify(selection));
    } catch (e) {
        console.warn('Failed to store selection:', e);
    }

    if (!salesRepCustomersLoaded) {
        setTimeout(() => filterCustomersByRoute(selection), 500);
    }
}

// ---- restrictLocationAccess ----
function restrictLocationAccess(selection) {
    if (window.isEditing) {
        return;
    }

    const autoSelectVehicle = (retryCount = 0, maxRetries = 20) => {

        const locationSelect        = document.getElementById('locationSelect');
        const locationSelectDesktop = document.getElementById('locationSelectDesktop');

        if (!locationSelect) {
            console.error('❌ Location select element not found!');
            return;
        }
        if (!selection || !selection.vehicle || !selection.vehicle.id) {
            console.error('❌ Invalid selection data:', selection);
            return;
        }

        const allOptions = $(locationSelect).find('option');
        allOptions.each(function(index) {
        });

        const optionExists = $(locationSelect).find(`option[value="${selection.vehicle.id}"]`).length > 0;

        if (optionExists) {
            locationSelect.value = selection.vehicle.id;
            window.selectedLocationId = selection.vehicle.id;

            $(locationSelect).trigger('change');
            checkAndToggleSalesRepButtons(selection.vehicle.id);
        } else if (retryCount < maxRetries) {
            setTimeout(() => autoSelectVehicle(retryCount + 1, maxRetries), 200 + (retryCount * 100));
        } else {
            console.error('❌ [FAILED] Failed to auto-select sublocation after', maxRetries, 'attempts.');
            console.error('🔴 Looking for sublocation ID:', selection.vehicle.id, 'Name:', selection.vehicle.name);
            console.error('🔴 Final check - Available options:', $(locationSelect).find('option').map(function() {
                return $(this).val() + ': ' + $(this).text();
            }).get());
        }

        if (locationSelectDesktop && selection.vehicle && selection.vehicle.id) {
            const desktopOptionExists = $(locationSelectDesktop).find(`option[value="${selection.vehicle.id}"]`).length > 0;
            if (desktopOptionExists) {
                locationSelectDesktop.value = selection.vehicle.id;
                $(locationSelectDesktop).trigger('change');
            }
        }
    };

    setTimeout(autoSelectVehicle, 500);
}

// ---- filterCustomersByRoute ----
function filterCustomersByRoute(selection) {
    if (filteringInProgress || isCurrentlyFiltering) {
        return;
    }

    if (window.customerDataLoading) {
        setTimeout(() => filterCustomersByRoute(selection), 500);
        return;
    }

    if (window.preventAutoSelection || window.salesRepCustomerResetInProgress) {
        return;
    }

    if (window.lastCustomerResetTime && (Date.now() - window.lastCustomerResetTime) < 5000) {
        return;
    }

    if (salesRepCustomersLoaded) {
        return;
    }

    if (!selection || !selection.route) return;

    const routeKey = `route_${selection.route.id}_${JSON.stringify(selection.route.cities?.map(c => c.id).sort())}`;
    if (lastSuccessfulFilter === routeKey) {
        const timeSinceLastFilter = Date.now() - lastCustomerFilterCall;
        if (timeSinceLastFilter < 5000) {
            return;
        }
    }

    filteringInProgress = true;
    isCurrentlyFiltering = true;
    filterRequestId++;
    const currentRequestId = filterRequestId;

    if (!selection.route.cities || selection.route.cities.length === 0) {
        fallbackRouteFiltering(selection);
        filteringInProgress = false;
        isCurrentlyFiltering = false;
        return;
    }

    const routeCityIds = selection.route.cities.map(city => city.id);

    const customerSelect = $('#customer-id');
    if (!customerSelect.length) {
        filteringInProgress = false;
        setTimeout(() => filterCustomersByRoute(selection), 200);
        return;
    }

    const existingOptions = customerSelect.find('option');
    if (existingOptions.length === 0) {
        filteringInProgress = false;
        setTimeout(() => filterCustomersByRoute(selection), 200);
        return;
    }

    if (!window.originalCustomerOptions) {
        window.originalCustomerOptions = customerSelect.html();
    }

    fetch('/customers/filter-by-cities', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ city_ids: routeCityIds })
    })
    .then(response => response.json())
    .then(data => {
        if (currentRequestId !== filterRequestId) {
            return;
        }
        if (data.status && data.customers) {
            populateFilteredCustomers(data.customers, selection.route.name);
            salesRepCustomersFiltered = true;
            salesRepCustomersLoaded   = true;
            lastSuccessfulFilter = routeKey;
        } else {
            console.error(`❌ [Request #${currentRequestId}] Failed to filter customers:`, data.message || 'Unknown error');
            fallbackRouteFiltering(selection);
        }
    })
    .catch(error => {
        console.error(`❌ [Request #${currentRequestId}] Error filtering customers:`, error);
        fallbackRouteFiltering(selection);
    })
    .finally(() => {
        filteringInProgress  = false;
        isCurrentlyFiltering = false;
        lastCustomerFilterCall = Date.now();
    });
}

// ---- fallbackRouteFiltering ----
function fallbackRouteFiltering(selection) {
    const customerSelect = $('#customer-id');
    const routeName = selection.route.name.toLowerCase();
    const filteredOptions = [];

    if (window.originalCustomerOptions) {
        const tempDiv = $('<div>').html(window.originalCustomerOptions);
        tempDiv.find('option').each(function() {
            const optionText  = $(this).text().toLowerCase();
            const optionValue = $(this).val();

            if (optionValue === '' || optionText.includes('please select')) {
                filteredOptions.push($(this)[0].outerHTML);
            } else if (optionText.includes(routeName) ||
                (routeName.includes('sainthasmaruthu') && optionText.includes('sainthasmaruthu')) ||
                (routeName.includes('kalmunai') && optionText.includes('kalmunai'))) {
                filteredOptions.push($(this)[0].outerHTML);
            }
        });

        if (filteredOptions.length > 1) {
            customerSelect.html(filteredOptions.join(''));
        } else {
        }
    }

    salesRepCustomersFiltered = true;
    salesRepCustomersLoaded   = true;
}

// ---- populateFilteredCustomers ----
function populateFilteredCustomers(customers, routeName) {
    routeName = routeName || '';
    const customerSelect = $('#customer-id');

    if (!customers || !Array.isArray(customers)) {
        console.error('populateFilteredCustomers: customers parameter is not a valid array:', customers);
        restoreOriginalCustomers();
        return;
    }

    customerSelect.empty();
    customerSelect.append('<option value="">Please Select</option>');

    if (!window.isSalesRep) {
        const walkInOption = $('<option value="1" data-customer-type="retailer">Walk-in Customer (Walk-in Customer)</option>');
        walkInOption.data('due', 0);
        walkInOption.data('credit_limit', 0);
        customerSelect.append(walkInOption);
    }

    customers.sort((a, b) => {
        const nameA = [a.prefix, a.first_name, a.last_name].filter(Boolean).join(' ').toLowerCase();
        const nameB = [b.prefix, b.first_name, b.last_name].filter(Boolean).join(' ').toLowerCase();
        return nameA.localeCompare(nameB);
    });

    const customersWithCity    = customers.filter(c => c.city_name && c.city_name !== 'No City');
    const customersWithoutCity = customers.filter(c => !c.city_name || c.city_name === 'No City');

    customersWithCity.forEach(customer => {
        const customerName  = [customer.prefix, customer.first_name, customer.last_name].filter(Boolean).join(' ');
        const customerType  = customer.customer_type
            ? ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}`
            : '';
        const cityInfo      = ` [${customer.city_name}]`;
        const displayText   = `${customerName}${customerType}${cityInfo} (${customer.mobile || 'No mobile'})`;
        const option = $(`<option value="${customer.id}" data-customer-type="${customer.customer_type || 'retailer'}">${displayText}</option>`);
        option.data('due', customer.current_due || customer.current_balance || 0);
        option.data('credit_limit', customer.credit_limit || 0);
        customerSelect.append(option);
    });

    if (customersWithoutCity.length > 0 && customersWithCity.length > 0) {
        customerSelect.append('<option disabled>── Customers without city ──</option>');
    }

    customersWithoutCity.forEach(customer => {
        const customerName  = [customer.prefix, customer.first_name, customer.last_name].filter(Boolean).join(' ');
        const customerType  = customer.customer_type
            ? ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}`
            : '';
        const cityInfo      = ' [No City]';
        const displayText   = `${customerName}${customerType}${cityInfo} (${customer.mobile || 'No mobile'})`;
        const option = $(`<option value="${customer.id}" data-customer-type="${customer.customer_type || 'retailer'}">${displayText}</option>`);
        option.data('due', customer.current_due || customer.current_balance || 0);
        option.data('credit_limit', customer.credit_limit || 0);
        customerSelect.append(option);
    });

    customerSelect.trigger('change');
}

// ---- validateCustomerRouteMatch ----
function validateCustomerRouteMatch() {
    const selection = window.getSalesRepSelection();
    if (!selection || !selection.route || !window.isSalesRep) return;

    if (filteringInProgress) {
        return;
    }

    const customerSelect = $('#customer-id');
    const options        = customerSelect.find('option');
    const routeName      = selection.route.name.toLowerCase();
    let correctCustomers     = 0;
    let wrongRouteCustomers  = 0;

    options.each(function() {
        const optionText  = $(this).text().toLowerCase();
        const optionValue = $(this).val();
        if (!optionValue || optionText.includes('please select')) return;

        if (optionText.includes(routeName) ||
            (routeName.includes('sainthasmaruthu') && optionText.includes('sainthasmaruthu')) ||
            (routeName.includes('kalmunai') && optionText.includes('kalmunai'))) {
            correctCustomers++;
        } else if (!optionText.includes('walk-in')) {
            wrongRouteCustomers++;
        }
    });

    if (wrongRouteCustomers > 0 && !salesRepCustomersFiltered) {
        setTimeout(() => filterCustomersByRoute(selection), 500);
    }
}

// ---- restoreOriginalCustomers ----
function restoreOriginalCustomers() {
    if (window.originalCustomerOptions) {
        const customerSelect = $('#customer-id');
        customerSelect.html(window.originalCustomerOptions);
        customerSelect.trigger('change');
        setTimeout(() => { customerSelect.val('1').trigger('change'); }, 100);
    }
}

// ---- clearSalesRepFilters ----
function clearSalesRepFilters() {
    restoreOriginalCustomers();

    const salesRepDisplay = document.getElementById('salesRepDisplay');
    if (salesRepDisplay) {
        salesRepDisplay.style.display = 'none';
        salesRepDisplay.classList.remove('d-flex');
    }

    window.clearSalesRepSelection();
}

// ---- hideSalesRepDisplay ----
function hideSalesRepDisplay() {
    const salesRepDisplay = document.getElementById('salesRepDisplay');
    if (salesRepDisplay) {
        salesRepDisplay.style.display = 'none';
        salesRepDisplay.classList.remove('d-flex', 'sales-rep-visible');
        salesRepDisplay.classList.add('d-none');
    }

    const salesRepDisplayMenu = document.getElementById('salesRepDisplayMenu');
    if (salesRepDisplayMenu) salesRepDisplayMenu.style.display = 'none';

    const changeSalesRepBtn = document.getElementById('changeSalesRepSelection');
    if (changeSalesRepBtn) changeSalesRepBtn.style.display = 'none';

    const changeSalesRepBtnMenu = document.getElementById('changeSalesRepSelectionMenu');
    if (changeSalesRepBtnMenu) changeSalesRepBtnMenu.style.display = 'none';

}

// ---- checkSalesAccess ----
function checkSalesAccess() {
    if (!window.isSalesRep) return true;

    const selection = window.getSalesRepSelection();
    if (!selection) {
        toastr.error('Please select your vehicle and route before making a sale.', 'Selection Required');
        return false;
    }

    if (!selection.canSell) {
        toastr.error('You only have view access for this vehicle/route. Sales are not permitted.', 'Access Denied');
        return false;
    }

    const selectedLoc      = document.getElementById('locationSelect')?.value;
    const assignedVehicleId = selection.vehicle.id;
    const parentLocationId  = selection.vehicle.parent_id || selection.vehicle.parent?.id;

    if (selectedLoc != assignedVehicleId && selectedLoc != parentLocationId) {
        toastr.error('You can only sell from your assigned vehicle location or its parent location.', 'Location Mismatch');
        return false;
    }

    return true;
}

// ---- validatePaymentMethodCompatibility ----
function validatePaymentMethodCompatibility(paymentMethod, saleData) {
    if (!window.isEditing || !window.currentEditingSaleId) return true;

    const originalPaymentStatus = saleData?.payment_status || 'pending';
    const originalTotalPaid     = parseFloat(saleData?.total_paid || 0);
    const originalFinalTotal    = parseFloat(saleData?.final_total || 0);
    const originalDue           = originalFinalTotal - originalTotalPaid;

    if (originalDue > 0 && originalPaymentStatus !== 'paid') {
        if (paymentMethod === 'cash' || paymentMethod === 'card') {
            const confirmChange = confirm(
                `⚠️ PAYMENT METHOD CHANGE WARNING\n\n` +
                `Original Sale: Credit Sale (Due: Rs ${originalDue.toFixed(2)})\n` +
                `New Payment: ${paymentMethod.toUpperCase()} Payment\n\n` +
                `This will change the sale from CREDIT to CASH payment.\n` +
                `Are you sure you want to proceed?\n\n` +
                `This action will:\n` +
                `• Remove the credit from customer ledger\n` +
                `• Mark sale as fully paid\n` +
                `• Update payment records`
            );

            if (!confirmChange) {
                return false;
            }

        }
    }

    return true;
}

// ---- checkAndToggleSalesRepButtons ----
function checkAndToggleSalesRepButtons(locationId) {
    if (window.cachedLocations && window.locationCacheExpiry && Date.now() < window.locationCacheExpiry) {
        const selectedLocation = window.cachedLocations.find(loc => loc.id == locationId);
        if (selectedLocation) {
            const isParentLocation = !selectedLocation.parent_id;
            if (isParentLocation) {
                hideSalesRepButtonsExceptSaleOrder();
            } else {
                showAllSalesRepButtons();
            }
        }
    } else {
        $.ajax({
            url: '/location-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status && Array.isArray(response.data)) {
                    // Update cache on both window and local vars owned by pos-location.js
                    window.cachedLocations    = response.data;
                    window.locationCacheExpiry = Date.now() + (window.LOCATION_CACHE_DURATION || 300000);

                    const selectedLocation = response.data.find(loc => loc.id == locationId);
                    if (selectedLocation) {
                        const isParentLocation = !selectedLocation.parent_id;
                        if (isParentLocation) {
                            hideSalesRepButtonsExceptSaleOrder();
                        } else {
                            showAllSalesRepButtons();
                        }
                    }
                }
            },
            error: function(xhr) {
                console.error('Error fetching location details:', xhr);
            }
        });
    }
}

// ---- hideSalesRepButtonsExceptSaleOrder ----
function hideSalesRepButtonsExceptSaleOrder() {

    $('#draftButton').addClass('sales-rep-hide-payment');
    $('#quotationButton').addClass('sales-rep-hide-payment');
    $('#suspendButton, button[data-bs-target="#suspendModal"]').addClass('sales-rep-hide-payment');
    $('#creditSaleButton').addClass('sales-rep-hide-payment');
    $('#cardButton').addClass('sales-rep-hide-payment');
    $('#chequeButton').addClass('sales-rep-hide-payment');
    $('#cashButton').addClass('sales-rep-hide-payment');
    $('button[data-bs-target="#paymentModal"]').addClass('sales-rep-hide-payment');

    $('#mobileCashBtn').addClass('sales-rep-hide-payment');
    $('#mobileCardBtn').addClass('sales-rep-hide-payment');
    $('#mobileChequeBtn').addClass('sales-rep-hide-payment');
    $('#mobileCreditBtn').addClass('sales-rep-hide-payment');
    $('#mobileMultiplePayBtn').addClass('sales-rep-hide-payment');

    $('.mobile-payment-btn[data-payment="cash"]').addClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="card"]').addClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="cheque"]').addClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="credit"]').addClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="multiple"]').addClass('sales-rep-hide-payment');

    $('#mobileDraftBtnCol').addClass('sales-rep-hide-payment');
    $('#mobileQuotationBtnCol').addClass('sales-rep-hide-payment');
    $('#mobileJobTicketBtnCol').addClass('sales-rep-hide-payment');
    $('#mobileSuspendBtnCol').addClass('sales-rep-hide-payment');

    $('.mobile-action-btn[data-action="draft"]').parent().addClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="quotation"]').parent().addClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="job-ticket"]').parent().addClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="suspend"]').parent().addClass('sales-rep-hide-payment');

    $('#saleOrderButton').removeClass('sales-rep-hide-payment').addClass('sales-rep-show-sale-order');
    $('#mobileSaleOrderBtnCol').removeClass('sales-rep-hide-payment').addClass('sales-rep-show-sale-order');
    $('.mobile-action-btn[data-action="sale-order"]').parent().removeClass('sales-rep-hide-payment').addClass('sales-rep-show-sale-order');

}

// ---- showAllSalesRepButtons ----
function showAllSalesRepButtons() {
    $('#draftButton').removeClass('sales-rep-hide-payment');
    $('#quotationButton').removeClass('sales-rep-hide-payment');
    $('#suspendButton, button[data-bs-target="#suspendModal"]').removeClass('sales-rep-hide-payment');
    $('#creditSaleButton').removeClass('sales-rep-hide-payment');
    $('#cardButton').removeClass('sales-rep-hide-payment');
    $('#chequeButton').removeClass('sales-rep-hide-payment');
    $('#cashButton').removeClass('sales-rep-hide-payment');
    $('button[data-bs-target="#paymentModal"]').removeClass('sales-rep-hide-payment');
    $('#saleOrderButton').removeClass('sales-rep-hide-payment');

    $('#mobileCashBtn').removeClass('sales-rep-hide-payment');
    $('#mobileCardBtn').removeClass('sales-rep-hide-payment');
    $('#mobileChequeBtn').removeClass('sales-rep-hide-payment');
    $('#mobileCreditBtn').removeClass('sales-rep-hide-payment');
    $('#mobileMultiplePayBtn').removeClass('sales-rep-hide-payment');

    $('.mobile-payment-btn[data-payment="cash"]').removeClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="card"]').removeClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="cheque"]').removeClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="credit"]').removeClass('sales-rep-hide-payment');
    $('.mobile-payment-btn[data-payment="multiple"]').removeClass('sales-rep-hide-payment');

    $('#mobileDraftBtnCol').removeClass('sales-rep-hide-payment');
    $('#mobileSaleOrderBtnCol').removeClass('sales-rep-hide-payment');
    $('#mobileQuotationBtnCol').removeClass('sales-rep-hide-payment');
    $('#mobileJobTicketBtnCol').removeClass('sales-rep-hide-payment');
    $('#mobileSuspendBtnCol').removeClass('sales-rep-hide-payment');

    $('.mobile-action-btn[data-action="draft"]').parent().removeClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="quotation"]').parent().removeClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="job-ticket"]').parent().removeClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="suspend"]').parent().removeClass('sales-rep-hide-payment');
    $('.mobile-action-btn[data-action="sale-order"]').parent().removeClass('sales-rep-hide-payment');

}

// --- Window exports ---
window.restoreSalesRepDisplayFromStorage  = restoreSalesRepDisplayFromStorage;
window.protectSalesRepCustomerFiltering   = protectSalesRepCustomerFiltering;
window.checkSalesRepStatus                = checkSalesRepStatus;
window.handleSalesRepUser                 = handleSalesRepUser;
window.setupSalesRepEventListeners        = setupSalesRepEventListeners;
window.updateMobileSalesRepDisplay        = updateMobileSalesRepDisplay;
window.updateDesktopSalesRepDisplay       = updateDesktopSalesRepDisplay;
window.updateSalesRepDisplay              = updateSalesRepDisplay;
window.restrictLocationAccess             = restrictLocationAccess;
window.filterCustomersByRoute             = filterCustomersByRoute;
window.fallbackRouteFiltering             = fallbackRouteFiltering;
window.populateFilteredCustomers          = populateFilteredCustomers;
window.validateCustomerRouteMatch         = validateCustomerRouteMatch;
window.restoreOriginalCustomers           = restoreOriginalCustomers;
window.clearSalesRepFilters               = clearSalesRepFilters;
window.hideSalesRepDisplay                = hideSalesRepDisplay;
window.checkSalesAccess                   = checkSalesAccess;
window.validatePaymentMethodCompatibility = validatePaymentMethodCompatibility;
window.checkAndToggleSalesRepButtons      = checkAndToggleSalesRepButtons;
window.hideSalesRepButtonsExceptSaleOrder = hideSalesRepButtonsExceptSaleOrder;
window.showAllSalesRepButtons             = showAllSalesRepButtons;
