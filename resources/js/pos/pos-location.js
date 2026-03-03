/**
 * POS LOCATION MODULE — Phase 7
 * Location fetching, caching, and dropdown rendering.
 *
 * Extracted from resources/views/sell/pos_ajax.blade.php
 * Functions: fetchAllLocations, populateLocationDropdown
 *
 * State lives on window:
 *   window.cachedLocations      — cached location array (null when stale)
 *   window.locationCacheExpiry  — expiry timestamp (ms)
 *   window.LOCATION_CACHE_DURATION — 5 min TTL constant
 *
 * Depends on: jQuery ($)
 * Must load BEFORE pos_ajax.blade.php.
 *
 * Public API:
 *   window.Pos.Location.fetchAllLocations,
 *   window.Pos.Location.populateLocationDropdown,
 *   window.LOCATION_CACHE_DURATION
 */

// POS namespace for location helpers
window.Pos = window.Pos || {};
window.Pos.Location = window.Pos.Location || {};

const LOCATION_CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

/**
 * Fetch all locations from server (or from cache if still valid).
 * Populates both #locationSelect and #locationSelectDesktop dropdowns.
 * Executes optional callback after population.
 */
function fetchAllLocations(forceRefresh = false, callback = null) {
    // Check cache first
    if (
        !forceRefresh &&
        window.cachedLocations &&
        window.locationCacheExpiry &&
        Date.now() < window.locationCacheExpiry
    ) {
        populateLocationDropdown(window.cachedLocations);

        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    $.ajax({
        url: '/location-get-all',
        method: 'GET',
        success: function(response) {
            if (response.status && Array.isArray(response.data)) {
                // Cache the locations
                window.cachedLocations = response.data;
                window.locationCacheExpiry = Date.now() + LOCATION_CACHE_DURATION;

                populateLocationDropdown(response.data);

                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error('Error fetching locations:', response.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error fetching locations:', textStatus, errorThrown);
        }
    });
}

/**
 * Populate #locationSelect and #locationSelectDesktop with the given location array.
 * Parent locations are listed first, then sub-locations with parent→child display names.
 */
function populateLocationDropdown(locations) {
    const locationSelect         = $('#locationSelect');
    const locationSelectDesktop  = $('#locationSelectDesktop');

    locationSelect.empty();
    locationSelectDesktop.empty();

    locationSelect.append('<option value="" disabled selected>Select Location</option>');
    locationSelectDesktop.append('<option value="" disabled selected>Select Location</option>');

    const parentLocations = locations.filter(loc => !loc.parent_id);
    const subLocations    = locations.filter(loc =>  loc.parent_id);

    // Parent locations first
    parentLocations.forEach(location => {
        let displayName = location.name;
        const childCount = subLocations.filter(sub => sub.parent_id === location.id).length;
        if (childCount > 0) {
            displayName += ` (Main Location - ${childCount} vehicles)`;
        }
        locationSelect.append(
            $('<option>').val(location.id).text(displayName)
        );
        locationSelectDesktop.append(
            $('<option>').val(location.id).text(displayName)
        );
    });

    // Sub-locations with parent reference and vehicle details
    subLocations.forEach(location => {
        let displayName = location.name;
        if (location.parent && location.parent.name) {
            displayName = `${location.parent.name} \u2192 ${location.name}`;
        }
        if (location.vehicle_number) displayName += ` (${location.vehicle_number})`;
        if (location.vehicle_type)   displayName += ` - ${location.vehicle_type}`;

        locationSelect.append(
            $('<option>').val(location.id).text(displayName)
        );
        locationSelectDesktop.append(
            $('<option>').val(location.id).text(displayName)
        );
    });

}

// ---- Expose via namespace + TTL constant ----
window.Pos.Location.fetchAllLocations        = fetchAllLocations;
window.Pos.Location.populateLocationDropdown = populateLocationDropdown;

// Initialise window state so pos_ajax closure vars are in sync from the start
if (typeof window.cachedLocations   === 'undefined') window.cachedLocations   = null;
if (typeof window.locationCacheExpiry === 'undefined') window.locationCacheExpiry = null;
