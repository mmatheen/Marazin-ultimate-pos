<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Live Vehicle Tracking</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap 5 -->
    <link href="{{ asset('vendor/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/dist/css/leaflet.css') }}" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/all-6.5.0.min.css') }}" />

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .tracking-container {
            height: calc(100vh - 20px);
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #map {
            width: 100%;
            height: 100%;
            border-radius: 12px;
        }

        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
        }

        .car-icon {
            width: 28px;
            height: 28px;
        }

        .car-icon i {
            color: #e53935;
            font-size: 28px;
        }

        .test-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card tracking-container">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>🚛 Live Vehicle Tracking</span>
                        <span class="badge bg-info">
                            {{ ucfirst(Auth::user()->role_name ?? 'User') }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Only show in local environment -->
    @if (app()->isLocal())
        <div class="test-panel">
            <button id="startTestMove" class="btn btn-success btn-sm">
                <i class="fas fa-play"></i> Start Test Movement
            </button>
            <span id="testStatus" class="text-muted ms-2">Ready</span>
        </div>
    @endif

    <!-- Leaflet JS -->
    <script src="{{ asset('vendor/leaflet/dist/js/leaflet.js') }}"></script>

    <script>
        // Normalize role name from server
        const rawUserRole = "{{ Auth::user()->role_name ?? 'Guest' }}".trim();
        const userRole = rawUserRole.toLowerCase();

        // Role checks (case-insensitive)
        const isAdmin = ['super admin', 'admin', 'manager'].includes(userRole);
        const isSalesRep = ['sales rep', 'salesrep', 'sales representative'].includes(userRole);

        const map = L.map('map').setView([7.8731, 80.7718], 11); // Sri Lanka
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const vehicleMarkers = {};

        // Custom car icon with rotation
        function getCarIcon(rotation = 0) {
            return L.divIcon({
                className: 'car-icon',
                html: `<i class="fas fa-truck" style="transform: rotate(${rotation}deg);"></i>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });
        }

        function updateVehicles() {
            let url;
            if (isAdmin) {
                url = '/sales-rep/vehicle/live'; // All vehicles
            } else if (isSalesRep) {
                url = '/sales-rep/vehicle/my-live'; // Only their vehicle
            } else {
                console.warn('Access denied: Unauthorized role:', rawUserRole);
                return;
            }

            fetch(url, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 200) return;

                    if (isSalesRep) {
                        // Clear previous markers for single vehicle view
                        Object.values(vehicleMarkers).forEach(marker => map.removeLayer(marker));
                        Object.keys(vehicleMarkers).forEach(k => delete vehicleMarkers[k]);

                        const vehicle = data.data;
                        if (!vehicle) return;

                        const latLng = [vehicle.latitude, vehicle.longitude];
                        const rotation = vehicle.heading || 0;

                        // Update existing marker or create new one
                        if (vehicleMarkers[vehicle.vehicle_id]) {
                            vehicleMarkers[vehicle.vehicle_id].setLatLng(latLng);
                            const iconEl = vehicleMarkers[vehicle.vehicle_id].getElement()?.querySelector('.fa-truck');
                            if (iconEl) iconEl.style.transform = `rotate(${rotation}deg)`;
                        } else {
                            vehicleMarkers[vehicle.vehicle_id] = L.marker(latLng, {
                                    icon: getCarIcon(rotation)
                                })
                                .addTo(map)
                                .bindPopup(`
                            <b>🚛 ${vehicle.vehicle_number}</b><br>
                            Speed: ${vehicle.speed || '0'} km/h<br>
                            Updated: ${new Date(vehicle.updated_at).toLocaleTimeString()}
                        `);
                        }

                        // Auto-center map on vehicle
                        map.setView(latLng, 14);
                    } else {
                        // Admin/Manager: Show all vehicles
                        if (!Array.isArray(data.data)) return;
                        data.data.forEach(vehicle => {
                            if (!vehicle.latitude || !vehicle.longitude) return;
                            const key = vehicle.vehicle_id;
                            const latLng = [vehicle.latitude, vehicle.longitude];
                            const rotation = vehicle.heading || 0;

                            if (vehicleMarkers[key]) {
                                vehicleMarkers[key].setLatLng(latLng);
                                const iconEl = vehicleMarkers[key].getElement()?.querySelector('.fa-truck');
                                if (iconEl) iconEl.style.transform = `rotate(${rotation}deg)`;
                            } else {
                                vehicleMarkers[key] = L.marker(latLng, {
                                        icon: getCarIcon(rotation)
                                    })
                                    .addTo(map)
                                    .bindPopup(`
                                <b>🚛 ${vehicle.vehicle_number}</b><br>
                                Driver: ${vehicle.driver_name}<br>
                                Route: ${vehicle.route}<br>
                                Speed: ${vehicle.speed || '0'} km/h<br>
                                Updated: ${new Date(vehicle.updated_at).toLocaleTimeString()}
                            `);
                            }
                        });
                    }
                })
                .catch(err => console.error('Fetch error:', err));
        }

        // Reduce polling interval to 5 seconds for smoother updates
        setInterval(updateVehicles, 5000);
        updateVehicles();
        // Poll every 3 seconds during test, 30s otherwise
        const pollInterval = document.getElementById('startTestMove') ? 3000 : 30000;
        setInterval(updateVehicles, pollInterval);
        updateVehicles(); // Initial load

        // 📍 Send current GPS location
        function sendVehicleLocation() {
            if (!navigator.geolocation) {
                console.warn("Geolocation not supported by this browser.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const {
                        latitude,
                        longitude,
                        accuracy,
                        speed,
                        heading
                    } = position.coords;

                    const payload = {
                        latitude: parseFloat(latitude.toFixed(6)),
                        longitude: parseFloat(longitude.toFixed(6)),
                        accuracy: accuracy ? parseFloat(accuracy.toFixed(2)) : null,
                        speed: speed ? parseFloat((speed * 3.6).toFixed(2)) : null,
                        heading: heading ? Math.round(heading) : null
                    };

                    fetch('/sales-rep/vehicle/location', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.ok ? res.json() : res.text().then(t => {
                            throw new Error(t)
                        }))
                        .then(data => {
                            if (data.status === 200) {
                                console.log("✅ Location sent:", data.data);
                            }
                        })
                        .catch(err => console.error("📡 Failed to send location:", err.message));
                },
                (err) => console.warn("📍 GPS Error:", err.message), {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 30000
                }
            );
        }

        // Send every 30 seconds
        setInterval(sendVehicleLocation, 30000);
        sendVehicleLocation();
    </script>

    @if (app()->isLocal())
        <script>
            const button = document.getElementById('startTestMove');
            const status = document.getElementById('testStatus');
            let interval = null;
            let step = 0;

            button.addEventListener('click', () => {
                if (interval) {
                    clearInterval(interval);
                    interval = null;
                    button.innerHTML = '<i class="fas fa-play"></i> Start Test Movement';
                    status.textContent = 'Stopped.';
                    return;
                }

                step = 0;
                button.innerHTML = '<i class="fas fa-stop"></i> Stop Movement';
                status.textContent = 'Initializing...';

                interval = setInterval(() => {
                    step++;

                    fetch('/sales-rep/test/move-vehicle', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                step: step,
                                vehicle_id: 4
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.completed) {
                                status.textContent = '✅ Arrived in Kalmunai!';
                                clearInterval(interval);
                                interval = null;
                                button.innerHTML = '<i class="fas fa-play"></i> Start Test Movement';
                            } else {
                                status.textContent = `📍 Moving... (${data.step}/${data.total_steps})`;
                            }
                        })
                        .catch(err => {
                            console.error('Simulate error:', err);
                            status.textContent = '❌ Error';
                        });
                }, 1500); // 1.5 seconds per waypoint → ~60 sec total
            });
        </script>
    @endif
</body>

</html>
