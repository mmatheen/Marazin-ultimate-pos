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

            <li class="nav-item dropdown noti-dropdown me-4">

                {{-- dynamically change the location and only show owner --}}
                @if(Auth::check() && is_null(Auth::user()->role_name))
                <select class="form-control form-select" id="location_dropdown">
                    {{-- dynamicly location name getting --}}
                </select>
                @endif
            </li>
            <li class="nav-item dropdown noti-dropdown me-3 mt-4">
                {{-- dynamically change the location --}}
                <p id="location_text"><b>Location:</b> <span id="location_name">Loading...</span></p>
            </li> 

            @can('pos page')
                <a href="{{ route('pos-create') }}" class="btn btn-primary me-3" role="button">
                    <img src="{{ asset('assets/img/payment-terminal.png') }}" alt="" style="width:25px; height:25px; filter: brightness(0) invert(1);"> POS
                </a>
            @endcan
                
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
                        <img class="rounded-circle" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" width="31" alt="Soeng Souy">
                        <div class="user-text">
                            <h6>{{ Auth::user()->user_name }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->role_name }}</p>
                        </div>
                    </span>
                </a>
                <div class="dropdown-menu">
                    <div class="user-header">
                        <div class="avatar avatar-sm">
                            <img src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="User Image" class="avatar-img rounded-circle">
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
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
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

        // Fetch the user details from the server
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
                $('#location').text('Error retrieving details');
            }
        });

        const locationSelect = $('#location_dropdown');
        const locationNameDisplay = $('#location_name');

        // Fetch user and location details
        $.ajax({
            url: 'user-location-get-all', // Replace with your endpoint URL
            type: 'GET',
             success: function(response) {
                if (response.status === 200) {
                    // Clear any existing options
                    locationSelect.empty();
                    locationSelect.append('<option value="">Select Location</option>');

                     // Use a Set to keep track of unique location IDs
                     const uniqueLocations = new Set();

                      // Loop through each message object
                        response.message.forEach(item => {
                            if (item.location && !uniqueLocations.has(item.location.id)) {
                                uniqueLocations.add(item.location.id); // Mark this ID as seen
                                locationSelect.append(
                                    `<option value="${item.location.id}">${item.location.name}</option>`
                                );
                            }
                        });

                    // Restore the selected location from localStorage if available
                    const savedLocationId = localStorage.getItem('selectedLocationId');
                    const savedLocationName = localStorage.getItem('selectedLocationName');

                    if (savedLocationId && savedLocationName) {
                        locationSelect.val(savedLocationId); // Set the dropdown value
                        locationNameDisplay.text(savedLocationName); // Display the saved location name
                    }
                }
            },
             error: function(error) {
                console.log("Error:", error);
            }
        });

        // Update location text and save selection to localStorage on change
        locationSelect.on('change', function() {
            const selectedText = $(this).find("option:selected").text(); // Get selected text
            const selectedValue = $(this).val(); // Get selected value

            // Update the location name display
            locationNameDisplay.text(selectedText);

            // Save to localStorage
            if (selectedValue) {
                localStorage.setItem('selectedLocationId', selectedValue);
                localStorage.setItem('selectedLocationName', selectedText);

                // Redirect to the dashboard if a valid option is selected
                window.location.href = '{{ route("brand") }}';
            } else {
                // Clear localStorage if no valid selection
                localStorage.removeItem('selectedLocationId');
                localStorage.removeItem('selectedLocationName');
            }
        });

        //uptate the location in session using select box
        $(document).on('change', '#location_dropdown', function() {
            $.ajax({
                url: '/update-location',
                 type: 'GET',
                 data: {
                    id: $(this).val()
                },
                 success: function(response) {},
                 error: function() {
                    $('#location').text('Error retrieving details');

                }
            });
        })
    });
    document.addEventListener('DOMContentLoaded', function () {
    fetchNotifications();

    function fetchNotifications() {
        fetch('/notifications')
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    updateNotifications(data.data);
                    updateNotificationCount(data.count);
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    function updateNotifications(notifications) {
        const notificationList = document.querySelector('.notification-list');
        notificationList.innerHTML = ''; // Clear existing notifications

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
    }

    function updateNotificationCount(count) {
        const notificationCountBadge = document.querySelector('.notification-count');
        notificationCountBadge.textContent = count;
        notificationCountBadge.style.display = count > 0 ? 'inline' : 'none';
    }

    // Event listener to mark notifications as seen when dropdown is opened
    document.querySelector('.dropdown-toggle').addEventListener('click', function () {
        updateNotificationCount(0); // Reset the count to zero
        markNotificationsAsSeen();
    });

    function markNotificationsAsSeen() {
        fetch('/notifications/seen', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                console.log('Notifications marked as seen.');
            }
        })
        .catch(error => console.error('Error marking notifications as seen:', error));
    }
});

</script>
{{-- it will get the location details code end --}}
