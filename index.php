<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracking - IoT Monitoring</title>
    
    <!-- Tailwind CSS dari CDN lokal -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS & JS dari CDN yang lebih reliable -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <style>
        #map { 
            height: 100%; 
            min-height: 500px;
        }
        .leaflet-container {
            font-size: 14px;
        }
        .location-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .bg-blur {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php
    // URL API
    $api_url = "https://daffaiotdev.alwaysdata.net/apigps/api/get/monitoring";
    
    // Inisialisasi
    $locations = array();
    $error = null;
    
    // Coba file_get_contents dulu
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\n",
            'timeout' => 10
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ));
    
    $response = @file_get_contents($api_url, false, $context);
    
    // Fallback ke cURL
    if ($response === FALSE && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($response !== FALSE && !empty($response)) {
        $data = json_decode($response, true);
        
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                if (!empty($item['latitude']) && !empty($item['longitude'])) {
                    $locations[] = array(
                        'id' => isset($item['id']) ? $item['id'] : '',
                        'lat' => floatval($item['latitude']),
                        'lng' => floatval($item['longitude']),
                        'voltage' => isset($item['voltage']) ? floatval($item['voltage']) : 0,
                        'time' => isset($item['created_at']) ? $item['created_at'] : '',
                        'accuracy' => isset($item['accurate']) ? $item['accurate'] : null
                    );
                }
            }
            // Balik urutan - terbaru di atas
            $locations = array_reverse($locations);
        }
    } else {
        $error = "Tidak dapat mengambil data dari API";
    }
    
    // Batasi untuk ditampilkan di list (500 data maks)
    $display_locations = array_slice($locations, 0, 500);
    ?>
    
    <div class="flex flex-col lg:flex-row h-screen">
        <!-- Sidebar untuk mobile (toggle) -->
        <div class="lg:hidden bg-blue-600 text-white p-4 flex justify-between items-center">
            <h1 class="text-xl font-bold"><i class="fas fa-satellite mr-2"></i>GPS Tracking</h1>
            <button id="toggleSidebar" class="text-white">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="w-full lg:w-96 bg-white border-r border-gray-200 flex flex-col lg:flex">
            <!-- Header -->
            <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                <h1 class="text-2xl font-bold mb-1"><i class="fas fa-satellite mr-2"></i>GPS Tracking</h1>
                <p class="text-blue-100 text-sm">Real-time IoT Device Monitoring</p>
            </div>
            
            <!-- Stats -->
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                <i class="fas fa-map-marker-alt text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Points</p>
                                <p class="text-xl font-bold text-gray-800"><?php echo count($locations); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <i class="fas fa-bolt text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Avg Voltage</p>
                                <p class="text-xl font-bold text-gray-800">
                                    <?php 
                                    if (!empty($locations)) {
                                        $totalVoltage = 0;
                                        foreach ($locations as $loc) {
                                            $totalVoltage += $loc['voltage'];
                                        }
                                        echo number_format($totalVoltage / count($locations), 2) . 'V';
                                    } else {
                                        echo '0V';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info & Controls -->
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <span><?php echo date('H:i:s'); ?></span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="window.location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm flex items-center">
                            <i class="fas fa-redo-alt mr-1"></i> Refresh
                        </button>
                        <button onclick="downloadData()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm flex items-center">
                            <i class="fas fa-download mr-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filter -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex space-x-2">
                    <input type="text" id="searchInput" placeholder="Search ID..." class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm">
                    <select id="voltageFilter" class="border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="all">All Voltage</option>
                        <option value="low">Low (< 11.5V)</option>
                        <option value="normal">Normal (11.5-12V)</option>
                        <option value="high">High (> 12V)</option>
                    </select>
                </div>
            </div>
            
            <!-- Location List -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-4 sticky top-0 bg-white border-b border-gray-200 z-10">
                    <h3 class="font-semibold text-gray-700 flex items-center">
                        <i class="fas fa-history mr-2"></i> Location History
                        <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                            <?php echo count($display_locations); ?> shown
                        </span>
                    </h3>
                </div>
                
                <?php if (empty($display_locations)): ?>
                    <div class="text-center py-10 text-gray-500">
                        <i class="fas fa-map-marker-slash text-4xl mb-3"></i>
                        <p class="mb-2">No GPS data available</p>
                        <?php if ($error): ?>
                            <p class="text-sm text-red-500"><?php echo $error; ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($display_locations as $index => $loc): 
                            $time = !empty($loc['time']) ? date('H:i:s', strtotime($loc['time'])) : 'N/A';
                            $date = !empty($loc['time']) ? date('d/m/Y', strtotime($loc['time'])) : '';
                            
                            // Voltage styling
                            $voltageClass = 'text-green-600';
                            $voltageIcon = 'fa-battery-full';
                            $voltageStatus = 'Normal';
                            
                            if ($loc['voltage'] < 11.5) {
                                $voltageClass = 'text-red-600';
                                $voltageIcon = 'fa-battery-quarter';
                                $voltageStatus = 'Low';
                            } elseif ($loc['voltage'] > 12.0) {
                                $voltageClass = 'text-blue-600';
                                $voltageIcon = 'fa-battery-full';
                                $voltageStatus = 'High';
                            }
                        ?>
                        <div class="location-item p-4 hover:bg-blue-50 cursor-pointer transition-colors border-l-4 border-blue-500"
                             onclick="focusLocation(<?php echo $index; ?>)"
                             data-index="<?php echo $index; ?>"
                             data-voltage="<?php echo $loc['voltage']; ?>"
                             data-id="<?php echo $loc['id']; ?>">
                            <div class="flex justify-between items-start">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-700 rounded-full text-sm font-bold mr-3 mt-1">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">Point #<?php echo $loc['id']; ?></p>
                                        <p class="text-xs text-gray-500 mb-2">
                                            <i class="far fa-clock mr-1"></i><?php echo $date; ?> <?php echo $time; ?>
                                        </p>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-location-dot mr-2 text-blue-500"></i>
                                            <span class="font-mono text-xs">
                                                <?php echo number_format($loc['lat'], 6); ?>, <?php echo number_format($loc['lng'], 6); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="<?php echo $voltageClass; ?> font-semibold text-sm">
                                        <i class="fas <?php echo $voltageIcon; ?> mr-1"></i>
                                        <?php echo number_format($loc['voltage'], 2); ?>V
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 mt-1 inline-block">
                                        <?php echo $voltageStatus; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($locations) > 500): ?>
                        <div class="p-4 text-center text-sm text-gray-500 border-t border-gray-200">
                            Showing 500 of <?php echo count($locations); ?> total points
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <div class="text-center text-sm text-gray-600">
                    <p>© <?php echo date('Y'); ?> IoT Monitoring System</p>
                    <p class="text-xs mt-1">Data updates every 30 seconds</p>
                </div>
            </div>
        </div>
        
        <!-- Map Area -->
        <div class="flex-1 relative">
            <div id="map" class="w-full h-full"></div>
            
            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                    <p class="text-gray-700 font-medium">Loading Map...</p>
                    <p class="text-gray-500 text-sm mt-2">Please wait while we load the map data</p>
                </div>
            </div>
            
            <!-- Map Controls -->
            <div id="mapControls" class="absolute top-4 right-4 space-y-2 hidden">
                <button onclick="showAllMarkers()" class="bg-white p-3 rounded-lg shadow-lg hover:shadow-xl transition-shadow flex items-center">
                    <i class="fas fa-expand-arrows-alt mr-2 text-blue-600"></i>
                    <span class="text-sm font-medium">Show All</span>
                </button>
                <button onclick="toggleRoute()" id="routeBtn" class="bg-white p-3 rounded-lg shadow-lg hover:shadow-xl transition-shadow flex items-center">
                    <i class="fas fa-route mr-2 text-green-600"></i>
                    <span class="text-sm font-medium">Show Route</span>
                </button>
                <button onclick="centerToLatest()" class="bg-white p-3 rounded-lg shadow-lg hover:shadow-xl transition-shadow flex items-center">
                    <i class="fas fa-location-arrow mr-2 text-red-600"></i>
                    <span class="text-sm font-medium">Latest</span>
                </button>
                <button onclick="clearMap()" class="bg-white p-3 rounded-lg shadow-lg hover:shadow-xl transition-shadow flex items-center">
                    <i class="fas fa-trash-alt mr-2 text-gray-600"></i>
                    <span class="text-sm font-medium">Clear</span>
                </button>
            </div>
            
            <!-- Map Info -->
            <div class="absolute top-4 left-4">
                <div class="bg-white bg-blur rounded-lg shadow-lg p-4 min-w-[200px] border border-gray-200">
                    <h3 class="font-bold text-gray-800 mb-2">Map Info</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Points:</span>
                            <span class="font-semibold"><?php echo count($locations); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-semibold" id="mapStatus">Loading...</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Zoom:</span>
                            <span class="font-semibold" id="zoomLevel">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Error Message -->
            <div id="mapError" class="absolute inset-0 bg-red-50 flex items-center justify-center z-40 hidden">
                <div class="text-center p-8 max-w-md">
                    <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Map Error</h3>
                    <p class="text-gray-600 mb-4" id="errorMessage">Failed to load map</p>
                    <button onclick="initMap()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-redo-alt mr-2"></i> Retry
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <script>
        // Data dari PHP
        const gpsData = <?php echo !empty($locations) ? json_encode($locations) : '[]'; ?>;
        
        // Global variables
        let map = null;
        let markers = [];
        let routeLine = null;
        let routeVisible = false;
        
        // Initialize map when page loads
        window.addEventListener('load', function() {
            setTimeout(initMap, 500); // Delay sedikit untuk memastikan semua resource load
        });
        
        function initMap() {
            try {
                // Hide loading overlay
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (gpsData.length === 0) {
                    showMapError('No GPS data available');
                    return;
                }
                
                // Use first point as center
                const firstPoint = gpsData[0];
                
                // Initialize map
                map = L.map('map').setView([firstPoint.lat, firstPoint.lng], 15);
                
                // Add OpenStreetMap tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(map);
                
                // Add markers
                addMarkers();
                
                // Show controls
                document.getElementById('mapControls').classList.remove('hidden');
                document.getElementById('mapStatus').textContent = 'Ready';
                
                // Update zoom level
                map.on('zoomend', function() {
                    document.getElementById('zoomLevel').textContent = map.getZoom();
                });
                
                document.getElementById('zoomLevel').textContent = map.getZoom();
                
                // Auto fit bounds if multiple points
                if (gpsData.length > 1) {
                    setTimeout(showAllMarkers, 1000);
                }
                
                // Setup search/filter
                setupFilters();
                
            } catch (error) {
                showMapError('Failed to initialize map: ' + error.message);
                console.error('Map Error:', error);
            }
        }
        
        function addMarkers() {
            // Clear existing markers
            if (markers.length > 0) {
                markers.forEach(function(marker) {
                    map.removeLayer(marker);
                });
                markers = [];
            }
            
            // Create markers for each point
            gpsData.forEach(function(location, index) {
                // Determine marker color based on voltage
                let markerColor = 'green';
                let voltageStatus = 'Normal';
                
                if (location.voltage < 11.5) {
                    markerColor = 'red';
                    voltageStatus = 'Low';
                } else if (location.voltage > 12.0) {
                    markerColor = 'blue';
                    voltageStatus = 'High';
                }
                
                // Create custom icon
                const iconHtml = `
                    <div style="background-color: ${markerColor}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                        ${index + 1}
                    </div>
                `;
                
                const icon = L.divIcon({
                    html: iconHtml,
                    className: 'custom-marker',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                });
                
                // Create marker
                const marker = L.marker([location.lat, location.lng], { icon: icon });
                
                // Popup content
                const timeStr = location.time ? new Date(location.time).toLocaleString('id-ID') : 'N/A';
                
                const popupContent = `
                    <div style="padding: 10px; min-width: 250px;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #dbeafe; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                <i class="fas fa-map-pin" style="color: #3b82f6;"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-weight: bold; color: #1e293b;">Point #${location.id}</h4>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">
                                    <i class="far fa-clock"></i> ${timeStr}
                                </p>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <div style="margin-bottom: 5px;">
                                <span style="color: #64748b; font-size: 13px;">Coordinates:</span><br>
                                <span style="font-family: monospace; font-size: 13px; color: #475569;">
                                    ${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}
                                </span>
                            </div>
                            <div style="margin-bottom: 5px;">
                                <span style="color: #64748b; font-size: 13px;">Voltage:</span>
                                <span style="color: ${markerColor}; font-weight: bold; margin-left: 5px;">
                                    ${location.voltage.toFixed(2)}V (${voltageStatus})
                                </span>
                            </div>
                            ${location.accuracy ? `
                            <div style="margin-bottom: 5px;">
                                <span style="color: #64748b; font-size: 13px;">Accuracy:</span>
                                <span style="margin-left: 5px;">${location.accuracy}m</span>
                            </div>` : ''}
                        </div>
                        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                            <button onclick="focusLocation(${index})" style="width: 100%; background-color: #3b82f6; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                <i class="fas fa-search-location" style="margin-right: 5px;"></i> Focus on Map
                            </button>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                
                // Click event
                marker.on('click', function() {
                    highlightSidebarItem(index);
                });
                
                marker.addTo(map);
                markers.push(marker);
            });
        }
        
        function showMapError(message) {
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('mapError').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
        }
        
        function showAllMarkers() {
            if (!map || gpsData.length === 0) return;
            
            const bounds = L.latLngBounds(gpsData.map(loc => [loc.lat, loc.lng]));
            map.fitBounds(bounds, { padding: [50, 50] });
        }
        
        function centerToLatest() {
            if (!map || gpsData.length === 0) return;
            
            const latest = gpsData[0];
            map.setView([latest.lat, latest.lng], 16);
            
            // Open popup for latest marker
            if (markers[0]) {
                markers[0].openPopup();
                highlightSidebarItem(0);
            }
        }
        
        function focusLocation(index) {
            if (!map || index < 0 || index >= gpsData.length) return;
            
            const location = gpsData[index];
            map.setView([location.lat, location.lng], 17);
            
            // Open marker popup
            if (markers[index]) {
                markers[index].openPopup();
            }
            
            // Highlight in sidebar
            highlightSidebarItem(index);
        }
        
        function highlightSidebarItem(index) {
            // Remove previous highlights
            document.querySelectorAll('.location-item').forEach(function(item) {
                item.classList.remove('bg-blue-100');
                item.style.borderLeftColor = '#3b82f6'; // blue-500
            });
            
            // Highlight selected item
            const selectedItem = document.querySelector('.location-item[data-index="' + index + '"]');
            if (selectedItem) {
                selectedItem.classList.add('bg-blue-100');
                selectedItem.style.borderLeftColor = '#1e40af'; // blue-800
                
                // Scroll to item
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function setupFilters() {
            const searchInput = document.getElementById('searchInput');
            const voltageFilter = document.getElementById('voltageFilter');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.location-item').forEach(function(item) {
                    const id = item.getAttribute('data-id').toLowerCase();
                    if (id.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            voltageFilter.addEventListener('change', function() {
                const value = this.value;
                document.querySelectorAll('.location-item').forEach(function(item) {
                    const voltage = parseFloat(item.getAttribute('data-voltage'));
                    let show = true;
                    
                    if (value === 'low' && voltage >= 11.5) show = false;
                    if (value === 'normal' && (voltage < 11.5 || voltage > 12.0)) show = false;
                    if (value === 'high' && voltage <= 12.0) show = false;
                    
                    item.style.display = show ? 'block' : 'none';
                });
            });
        }
        
        function downloadData() {
            if (gpsData.length === 0) {
                alert('No data to export');
                return;
            }
            
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "ID,Latitude,Longitude,Voltage,Time,Accuracy\n";
            
            gpsData.forEach(function(loc) {
                csvContent += `${loc.id},${loc.lat},${loc.lng},${loc.voltage},"${loc.time}",${loc.accuracy || ''}\n`;
            });
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `gps_data_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Toggle sidebar for mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        });
        
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Show map loading status
        console.log('GPS Data Points:', gpsData.length);
        console.log('Map initialization started...');
        
    </script>
</body>
</html>
