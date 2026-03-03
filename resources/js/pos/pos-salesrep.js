/**
 * POS SALES REP STORAGE MODULE — Phase 6
 * Authoritative versions of the sales rep selection storage helpers.
 * These window.* definitions load after sales-rep-modal.blade.php and
 * will override the simpler versions defined there at page-head time.
 *
 * Functions extracted / consolidated from:
 *   - resources/views/components/sales-rep-modal.blade.php (weaker versions)
 *   - resources/views/sell/pos_ajax.blade.php (duplicate local versions)
 *
 * Functions NOT yet extracted (remain in pos_ajax — entangled with closure state):
 *   restoreSalesRepDisplayFromStorage, checkSalesRepStatus, handleSalesRepUser,
 *   setupSalesRepEventListeners, protectSalesRepCustomerFiltering,
 *   updateSalesRepDisplay, hideSalesRepDisplay, checkAndToggleSalesRepButtons,
 *   hideSalesRepButtonsExceptSaleOrder, showAllSalesRepButtons,
 *   restrictLocationAccess, filterCustomersByRoute
 *
 * Depends on: window.PosConfig.auth.userId (pos-config.blade.php)
 */

/**
 * Retrieve the current sales rep selection from session/local storage.
 * Checks sessionStorage first for speed, falls back to localStorage for
 * cross-session persistence and re-hydrates sessionStorage when found.
 */
function getSalesRepSelection() {
    try {
        let storedData = sessionStorage.getItem('salesRepSelection');
        let parsedData = storedData ? JSON.parse(storedData) : null;

        if (!parsedData) {
            storedData = localStorage.getItem('salesRepSelection');
            parsedData = storedData ? JSON.parse(storedData) : null;

            if (parsedData) {
                sessionStorage.setItem('salesRepSelection', JSON.stringify(parsedData));
            }
        }

        return parsedData;
    } catch (e) {
        console.warn('Error parsing sales rep selection from storage:', e);
        return null;
    }
}

/**
 * Returns true only when a selection exists AND has both vehicle.id and route.id.
 * More robust than the modal's simple sessionStorage-key check.
 */
function hasSalesRepSelection() {
    const selection = getSalesRepSelection();
    return !!(
        selection &&
        selection.vehicle &&
        selection.route &&
        selection.vehicle.id &&
        selection.route.id
    );
}

/**
 * Persist a sales rep vehicle/route selection.
 * Stamps the current user ID and a timestamp onto the stored object
 * so stale selections from other users are detectable on reload.
 */
function storeSalesRepSelection(selection) {
    try {
        const selectionWithUser = {
            ...selection,
            userId: window.PosConfig ? window.PosConfig.auth.userId : null,
            timestamp: Date.now()
        };
        const selectionJson = JSON.stringify(selectionWithUser);
        sessionStorage.setItem('salesRepSelection', selectionJson);
        localStorage.setItem('salesRepSelection', selectionJson);
    } catch (e) {
        console.error('Failed to store sales rep selection:', e);
    }
}

/**
 * Remove sales rep selection from both session and local storage.
 */
function clearSalesRepSelection() {
    sessionStorage.removeItem('salesRepSelection');
    localStorage.removeItem('salesRepSelection');
}

// ---- Expose all as globals ----
// These override the weaker versions set by sales-rep-modal.blade.php at page-head time.
window.getSalesRepSelection  = getSalesRepSelection;
window.hasSalesRepSelection  = hasSalesRepSelection;
window.storeSalesRepSelection = storeSalesRepSelection;
window.clearSalesRepSelection = clearSalesRepSelection;
