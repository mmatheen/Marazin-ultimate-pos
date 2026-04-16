<div>
    <style>
        .profile-hover-wrap {
            position: relative;
        }

        .profile-hover-preview {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 180px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: all 0.16s ease;
            pointer-events: none;
            z-index: 1090;
        }

        .profile-hover-wrap:hover .profile-hover-preview,
        .profile-hover-wrap:focus-within .profile-hover-preview {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-hover-preview img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }

        .profile-hover-preview .name {
            margin-top: 8px;
            text-align: center;
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }

        @media (hover: none) {
            .profile-hover-preview {
                display: none;
            }
        }
    </style>

    <div class="header">

        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="logo">
                <img src="{{ $activeSetting?->logo_url }}" alt="Logo" width="100" height="50">
            </a>
            <a href="{{ route('dashboard') }}" class="logo logo-small">
                <img src="{{ $activeSetting?->logo_url }}" alt="Logo" width="50" height="50">
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



            @can('access pos')
                <a href="{{ route('pos-create') }}" class="btn btn-primary me-3" role="button">
                    <img src="{{ asset('assets/img/payment-terminal.png') }}" alt=""
                        style="width:25px; height:25px; filter: brightness(0) invert(1);"> POS
                </a>
            @endcan


            {{-- Location dropdown code start --}}
            @php
                $locations = Auth::user()->locations->pluck('name')->toArray();
                $locationText = implode(', ', $locations);

                $defaultProfileImage = asset('assets/img/profiles/default-avatar.svg');
                $rawProfileImage = trim((string) (Auth::user()->profile_image ?? ''));
                $profileImage = $defaultProfileImage;

                if ($rawProfileImage !== '') {
                    if (preg_match('/^https?:\/\//i', $rawProfileImage)) {
                        $profileImage = $rawProfileImage;
                    } else {
                        if (str_starts_with($rawProfileImage, '/')) {
                            $publicRelativePath = ltrim($rawProfileImage, '/');
                        } elseif (str_starts_with($rawProfileImage, 'assets/')) {
                            $publicRelativePath = $rawProfileImage;
                        } else {
                            $publicRelativePath = 'assets/img/profiles/' . $rawProfileImage;
                        }

                        if (file_exists(public_path($publicRelativePath))) {
                            $profileImage = asset($publicRelativePath);
                        }
                    }
                }
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
                    <span class="user-img profile-hover-wrap">
                        <img class="rounded-circle" src="{{ $profileImage }}"
                            width="31" alt="{{ Auth::user()->user_name }}"
                            onerror="this.src='{{ asset('assets/img/profiles/default-avatar.svg') }}'">
                        <div class="user-text">
                            <h6>{{ Auth::user()->user_name }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->getRoleName() ?? 'No Role' }}</p>
                        </div>
                        <div class="profile-hover-preview">
                            <img src="{{ $profileImage }}" alt="{{ Auth::user()->user_name }}"
                                onerror="this.src='{{ asset('assets/img/profiles/default-avatar.svg') }}'">
                            <div class="name">{{ Auth::user()->full_name ?? Auth::user()->user_name }}</div>
                        </div>
                    </span>
                </a>
                <div class="dropdown-menu">
                    <div class="user-header">
                        <div class="avatar avatar-sm">
                            <img src="{{ $profileImage }}" alt="{{ Auth::user()->user_name }}"
                                class="avatar-img rounded-circle"
                                onerror="this.src='{{ asset('assets/img/profiles/default-avatar.svg') }}'">
                        </div>
                        <div class="user-text">
                            <h6>{{ Auth::user()->user_name }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->getRoleName() ?? 'No Role' }}</p>

                        </div>
                    </div>
                    {{-- <a class="dropdown-item" href="{{ route('profile.edit') }}">My Profile</a> --}}
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


{{-- Notifications code start --}}
<script>
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
{{-- Notifications code end --}}
