<div>
    <div class="header">

        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="logo">
                <img src="{{ asset('assets/img/ARB Logo.png') }}" alt="Logo" width="100" height="50">
            </a>
            <a href="{{ route('dashboard') }}" class="logo logo-small">
                <img src="{{ asset('assets/img/ARB Logo.png') }}" alt="Logo" width="50" height="50">
            </a>
        </div>
        <div class="menu-toggle">
            <a href="javascript:void(0);" id="toggle_btn">
                <i class="fas fa-bars"></i>
            </a>
        </div>

        {{-- <div class="top-nav-search">
            <form>
                <input type="text" class="form-control" placeholder="Search here">
                <button class="btn" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div> --}}
        <a class="mobile_btn" id="mobile_btn">
            <i class="fas fa-bars"></i>
        </a>

        <ul class="nav user-menu">



            @can('pos page')
                <a href="{{ route('pos-create') }}" class="btn btn-primary me-3" role="button">
                    <img src="{{ asset('assets/img/payment-terminal.png') }}" alt=""
                        style="width:25px; height:25px; filter: brightness(0) invert(1);"> POS
                </a>
            @endcan


            {{-- Location dropdown code start --}}
            @php
                $locations = Auth::user()->locations->pluck('name')->toArray();
                $locationText = implode(', ', $locations);
            @endphp

            <!-- Tooltip Button -->
            <i class="fas fa-map-marker-alt text-primary mx-3 fa-2x" data-bs-toggle="tooltip" data-bs-html="true"
                title="{{ wordwrap(e($locationText), 50, '<br>') }}"> </i>

            {{-- Location dropdown code end --}}


            {{-- Notification dropdown --}}

            <li class="nav-item dropdown noti-dropdown me-2">
                <a href="#" class="dropdown-toggle nav-link header-nav-list" data-bs-toggle="dropdown">
                    <img src="{{ asset('assets/img/icons/header-icon-05.svg') }}" alt="">
                    <span class="badge badge-pill badge-danger notification-count" style="display:none;"></span>
                </a>
                <div class="dropdown-menu notifications">
                    <div class="topnav-dropdown-header">
                        <span class="notification-title">Notifications</span>
                        <a href="javascript:void(0)" class="clear-noti"> Clear All </a>
                    </div>
                    <div class="noti-content">
                        <ul class="notification-list">
                            <!-- Notifications will be dynamically inserted here -->
                        </ul>
                    </div>
                    <div class="topnav-dropdown-footer">
                        <a href="#">View all Notifications</a>
                    </div>
                </div>
            </li>

            <li class="nav-item zoom-screen me-2">
                <a href="#" class="nav-link header-nav-list win-maximize">
                    <img src="{{ asset('assets/img/icons/header-icon-04.svg') }}" alt="">
                </a>
            </li>

            <li class="nav-item dropdown has-arrow new-user-menus">
                <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                    <span class="user-img">
                        <img class="rounded-circle" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}"
                            width="31" alt="Soeng Souy">
                        <div class="user-text">
                            <h6>{{ Auth::user()->user_name }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->role_name }}</p>
                        </div>
                    </span>
                </a>
                <div class="dropdown-menu">
                    <div class="user-header">
                        <div class="avatar avatar-sm">
                            <img src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="User Image"
                                class="avatar-img rounded-circle">
                        </div>
                        <div class="user-text">
                            <h6>{{ Auth::user()->user_name }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->role_name }}</p>

                        </div>
                    </div>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">My Profile</a>
                    {{-- <a class="dropdown-item" href="{{ route('logout') }}">Logout</a> --}}
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <a class="dropdown-item" href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        </a>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>


{{-- it will get the location details code start --}}
<script>
    $(document).ready(function() {
        // Initialize elements
        const locationSelect = $('#location_dropdown');
        const locationNameDisplay = $('#location_name');

        // Function to update location display
        function updateLocationDisplay(locationId, locationName) {
            locationNameDisplay.text(locationName);
            localStorage.setItem('selectedLocationId', locationId);
            localStorage.setItem('selectedLocationName', locationName);
        }

        // Get initial location details
        $.ajax({
            url: '/get-all-details-using-guard',
            type: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    $('#location_name').text(response.message.location.name);
                } else {
                    $('#location_name').text('No Location Found');
                }
            },
            error: function() {
                $('#location_name').text('Error retrieving details');
            }
        });

        // Populate location dropdown
        $.ajax({
            url: '/user-location-get-all',
            type: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    // Clear existing options
                    locationSelect.empty();
                    locationSelect.append('<option value="">Select Location</option>');

                    // Track unique locations
                    const uniqueLocations = new Set();

                    response.message.forEach(user => {
                        if (user.locations) {
                            user.locations.forEach(location => {
                                if (!uniqueLocations.has(location.id)) {
                                    uniqueLocations.add(location.id);
                                    const selected = sessionStorage.getItem(
                                            'selectedLocationId') == location.id ?
                                        'selected' : '';
                                    locationSelect.append(
                                        `<option value="${location.id}" ${selected}>${location.name}</option>`
                                    );
                                }
                            });
                        }
                    });

                    // Restore from localStorage if available
                    const savedLocationId = localStorage.getItem('selectedLocationId');
                    const savedLocationName = localStorage.getItem('selectedLocationName');

                    if (savedLocationId && savedLocationName) {
                        locationSelect.val(savedLocationId);
                        updateLocationDisplay(savedLocationId, savedLocationName);
                    }
                }
            },
            error: function(error) {
                console.log("Error:", error);
            }
        });

        // Handle location change
        locationSelect.on('change', function() {
            const locationId = $(this).val();
            const locationName = $(this).find('option:selected').text();

            if (locationId) {
                $.ajax({
                    url: '/update-location',
                    type: 'GET',
                    data: {
                        id: locationId
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            updateLocationDisplay(locationId, locationName);
                            window.location
                        .reload(); // Refresh to update session-based content
                        }
                    },
                    error: function() {
                        console.error('Error updating location');
                    }
                });
            } else {
                localStorage.removeItem('selectedLocationId');
                localStorage.removeItem('selectedLocationName');
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        fetchNotifications();

        function fetchNotifications() {
            fetch('/notifications')
                .then(async response => {
                    const text = await response.text();
                    try {
                        // Check if text is not empty and is valid JSON before parsing
                        if (text && text.trim().length > 0 && text.trim().startsWith('{')) {
                            const data = JSON.parse(text);
                            if (data.status === 200) {
                                updateNotifications(data.data);
                                updateNotificationCount(data.count);
                            } else {
                                updateNotifications([]);
                                updateNotificationCount(0);
                            }
                        } else {
                            // If response is empty or not valid JSON, show no notifications
                            updateNotifications([]);
                            updateNotificationCount(0);
                            console.error('Response is not valid JSON or is empty:', text);
                        }
                    } catch (e) {
                        updateNotifications([]);
                        updateNotificationCount(0);
                        console.error('Invalid JSON response:', text);
                    }
                })
                .catch(error => {
                    updateNotifications([]);
                    updateNotificationCount(0);
                    console.error('Error fetching notifications:', error);
                });
        }

        function updateNotifications(notifications) {
            const notificationList = document.querySelector('.notification-list');
            notificationList.innerHTML = ''; // Clear existing notifications

            if (Array.isArray(notifications)) {
                notifications.forEach(notification => {
                    const notificationItem = document.createElement('li');
                    notificationItem.classList.add('notification-message');

                    const notificationContent = `
                    <a href="#">
                        <div class="media d-flex">
                            <span class="avatar avatar-sm flex-shrink-0">
                                <img class="avatar-img rounded-circle" alt="Product Image" src="${notification.product_image ? '/assets/images/' + notification.product_image : '/assets/images/No Product Image Available.png'}">
                            </span>
                            <div class="media-body flex-grow-1">
                                <p class="noti-details">
                                    <span class="noti-title">${notification.product_name}</span> stock is below alert quantity. Current stock: <span class="noti-title">${notification.total_stock}</span>
                                </p>
                                <p class="noti-time">
                                    <span class="notification-time">${new Date(notification.created_at).toLocaleString()}</span>
                                </p>
                            </div>
                        </div>
                    </a>
                `;

                    notificationItem.innerHTML = notificationContent;
                    notificationList.appendChild(notificationItem);
                });
            } else {
                // Optionally handle the case where notifications is not an array
                notificationList.innerHTML = '<li class="notification-message">No notifications found.</li>';
            }
        }

        function updateNotificationCount(count) {
            const notificationCountBadge = document.querySelector('.notification-count');
            notificationCountBadge.textContent = count;
            notificationCountBadge.style.display = count > 0 ? 'inline' : 'none';
        }

        // Event listener to mark notifications as seen when dropdown is opened
        document.querySelector('.dropdown-toggle').addEventListener('click', function() {
            updateNotificationCount(0); // Reset the count to zero
            markNotificationsAsSeen();
        });

        function markNotificationsAsSeen() {
            fetch('/notifications/seen', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Optional: handle response
                    console.log('Notifications marked as seen:', data);
                })
                .catch(error => console.error('Error marking notifications as seen:', error));
        }

    });
</script>
{{-- it will get the location details code end --}}
