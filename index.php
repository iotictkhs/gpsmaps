<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracking - IoT Monitoring</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS (OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #map { height: 100%; }
        .leaflet-popup-content { font-size: 14px; }
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 8px;
            padding: 0;
        }
        .custom-popup .leaflet-popup-content {
            margin: 0;
            min-width: 250px;
        }
        .marker-cluster-small { background-color: rgba(59, 130, 246, 0.6); }
        .marker-cluster-small div { background-color: rgba(30, 64, 175, 0.8); }
        .marker-cluster-medium { background-color: rgba(59, 130, 246, 0.7); }
        .marker-cluster-medium div { background-color: rgba(30, 64, 175, 0.9); }
        .marker-cluster-large { background-color: rgba(59, 130, 246, 0.8); }
        .marker-cluster-large div { background-color: rgba(30, 64, 175, 1); }
    </style>
</head>
<body class="bg-gray-50">
    <?php
    // URL API
    $api_url = "https://daffaiotdev.alwaysdata.net/apigps/api/get/monitoring";
    
    // Ambil data dari API
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => "User-Agent: IoT-GPS-Tracking/1.0\r\n",
            'timeout' => 15
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ));
    
    $response = @file_get_contents($api_url, false, $context);
    
    // Fallback ke cURL
    if ($response === FALSE && function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'IoT-GPS-Tracking/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    
    $data = null;
    $locations = array();
    
    if ($response !== FALSE && !empty($response)) {
        $data = json_decode($response, true);
        if ($data && isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                if (!empty($item['latitude']) && !empty($item['longitude'])) {
                    $accuracy = isset($item['accurate']) ? $item['accurate'] : null;
                    
                    $locations[] = array(
                        'id' => $item['id'],
                        'lat' => floatval($item['latitude']),
                        'lng' => floatval($item['longitude']),
                        'voltage' => isset($item['voltage']) ? floatval($item['voltage']) : 0,
                        'time' => isset($item['created_at']) ? $item['created_at'] : '',
                        'accuracy' => $accuracy
                    );
                }
            }
            // Reverse untuk data terbaru pertama
            $locations = array_reverse($locations);
        }
    }
    ?>
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-96 bg-white border-r border-gray-200 flex flex-col">
            <!-- Header -->
            <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-500 text-white">
                <h1 class="text-2xl font-bold mb-1"><i class="fas fa-satellite mr-2"></i>GPS Tracking</h1>
                <p class="text-blue-100 text-sm">Real-time IoT Device Monitoring</p>
            </div>
            
            <!-- Stats -->
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
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
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <i class="fas fa-battery-full text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Avg Voltage</p>
                                <p class="text-xl font-bold text-gray-800">
                                    <?php 
                                    if (!empty($locations)) {
                                        $voltages = array();
                                        foreach ($locations as $loc) {
                                            $voltages[] = $loc['voltage'];
                                        }
                                        $avg = array_sum($voltages) / count($voltages);
                                        echo number_format($avg, 2) . 'V';
                                    } else {
                                        echo '0V';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Last Update -->
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Last update: <?php echo date('H:i:s'); ?></span>
                    </div>
                    <button onclick="window.location.reload()" class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-redo-alt mr-1"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Location List -->
            <div class="flex-1 overflow-y-auto p-4">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-history mr-2"></i> Location History
                </h3>
                
                <?php if (empty($locations)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-map-marker-slash text-3xl mb-3"></i>
                        <p>No GPS data available</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($locations as $index => $loc): 
                            $time = !empty($loc['time']) ? date('H:i:s', strtotime($loc['time'])) : 'N/A';
                            $date = !empty($loc['time']) ? date('d/m/Y', strtotime($loc['time'])) : '';
                            
                            // Tentukan warna voltage
                            $voltageColor = 'text-green-600';
                            $voltageIcon = 'fa-battery-full';
                            if ($loc['voltage'] < 11.5) {
                                $voltageColor = 'text-red-600';
                                $voltageIcon = 'fa-battery-quarter';
                            } elseif ($loc['voltage'] > 12.0) {
                                $voltageColor = 'text-blue-600';
                                $voltageIcon = 'fa-battery-full';
                            }
                        ?>
                        <div class="location-item bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:shadow-sm transition-all duration-200 cursor-pointer"
                             onclick="focusLocation(<?php echo $index; ?>)"
                             data-index="<?php echo $index; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-700 rounded-full text-sm font-bold mr-3">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">Point #<?php echo $loc['id']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $date; ?> <?php echo $time; ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="<?php echo $voltageColor; ?> font-semibold">
                                        <i class="fas <?php echo $voltageIcon; ?> mr-1"></i>
                                        <?php echo number_format($loc['voltage'], 2); ?>V
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-location-dot mr-2 text-blue-500"></i>
                                <span class="font-mono"><?php echo number_format($loc['lat'], 6); ?>, <?php echo number_format($loc['lng'], 6); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <div class="text-center text-sm text-gray-600">
                    <p>© <?php echo date('Y'); ?> IoT Monitoring System</p>
                    <p class="text-xs mt-1">Data source: daffaiotdev.alwaysdata.net</p>
                </div>
            </div>
        </div>
        
        <!-- Map Area -->
        <div class="flex-1 relative">
            <div id="map" class="w-full h-full"></div>
            
            <!-- Map Controls -->
            <div class="absolute top-4 right-4 space-y-2">
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
            
            <!-- Stats Overlay -->
            <div class="absolute top-4 left-4">
                <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-lg p-4 min-w-[200px]">
                    <h3 class="font-bold text-gray-800 mb-2">Map Stats</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Points:</span>
                            <span class="font-semibold"><?php echo count($locations); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Map Type:</span>
                            <span class="font-semibold" id="mapType">Street</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Route:</span>
                            <span class="font-semibold" id="routeStatus">Hidden</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Marker Cluster -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <script>
        // Data dari PHP
        const gpsData = <?php echo json_encode($locations); ?>;
        
        // Variabel global
        let map;
        let markers = [];
        let markersCluster;
        let routeLine;
        let routeVisible = false;
        let currentPopup = null;
        
        // Initialize map ketika halaman load
        document.addEventListener('DOMContentLoaded', function() {
            if (gpsData.length > 0) {
                initMap();
            } else {
                document.getElementById('map').innerHTML = `
                    <div class="flex items-center justify-center h-full bg-gray-100">
                        <div class="text-center p-8">
                            <i class="fas fa-map-marked-alt text-5xl text-gray-400 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No GPS Data Available</h3>
                            <p class="text-gray-500 mb-4">Unable to load GPS data from server</p>
                            <button onclick="window.location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center mx-auto">
                                <i class="fas fa-redo-alt mr-2"></i> Try Again
                            </button>
                        </div>
                    </div>
                `;
            }
        });
        
        function initMap() {
            // Gunakan titik pertama sebagai pusat
            const firstPoint = gpsData[0];
            
            // Initialize map
            map = L.map('map').setView([firstPoint.lat, firstPoint.lng], 15);
            
            // Tambahkan tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(map);
            
            // Tambahkan layer alternatif
            const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
            });
            
            // Layer control
            const baseLayers = {
                "Street Map": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
                "Satellite": satelliteLayer
            };
            
            L.control.layers(baseLayers).addTo(map);
            
            // Add markers
            addMarkersToMap();
            
            // Add route jika ada lebih dari 1 titik
            if (gpsData.length > 1) {
                createRoute();
            }
            
            // Event listener untuk layer change
            map.on('baselayerchange', function(e) {
                document.getElementById('mapType').textContent = e.name;
            });
            
            // Auto fit bounds jika banyak marker
            if (gpsData.length > 1) {
                setTimeout(function() {
                    showAllMarkers();
                }, 500);
            }
        }
        
        function addMarkersToMap() {
            // Hapus marker sebelumnya
            if (markersCluster) {
                map.removeLayer(markersCluster);
            }
            
            markersCluster = L.markerClusterGroup({
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 40
            });
            
            // Buat marker untuk setiap titik
            gpsData.forEach(function(location, index) {
                // Tentukan warna berdasarkan voltage
                let markerColor = 'green';
                if (location.voltage < 11.5) {
                    markerColor = 'red';
                } else if (location.voltage > 12.0) {
                    markerColor = 'blue';
                }
                
                // Buat custom icon (menggunakan inline style untuk warna)
                let iconHtml;
                if (markerColor === 'red') {
                    iconHtml = `
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-red-500 border-2 border-white shadow-lg flex items-center justify-center">
                                <span class="text-white text-xs font-bold">${index + 1}</span>
                            </div>
                            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-red-500"></div>
                        </div>
                    `;
                } else if (markerColor === 'blue') {
                    iconHtml = `
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-white shadow-lg flex items-center justify-center">
                                <span class="text-white text-xs font-bold">${index + 1}</span>
                            </div>
                            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-blue-500"></div>
                        </div>
                    `;
                } else {
                    iconHtml = `
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-green-500 border-2 border-white shadow-lg flex items-center justify-center">
                                <span class="text-white text-xs font-bold">${index + 1}</span>
                            </div>
                            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-green-500"></div>
                        </div>
                    `;
                }
                
                const icon = L.divIcon({
                    html: iconHtml,
                    className: 'custom-marker',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });
                
                // Buat marker
                const marker = L.marker([location.lat, location.lng], { icon: icon });
                
                // Popup content
                const timeStr = location.time ? new Date(location.time).toLocaleString('id-ID') : 'N/A';
                let voltageClass = 'text-green-600';
                if (location.voltage < 11.5) {
                    voltageClass = 'text-red-600';
                } else if (location.voltage > 12.0) {
                    voltageClass = 'text-blue-600';
                }
                
                let accuracyHtml = '';
                if (location.accuracy) {
                    accuracyHtml = `
                        <div class="flex">
                            <span class="text-gray-600 w-24">Accuracy:</span>
                            <span>${location.accuracy}m</span>
                        </div>
                    `;
                }
                
                const popupContent = `
                    <div class="p-3">
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-map-pin text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Point #${location.id}</h4>
                                <p class="text-xs text-gray-500">${timeStr}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex">
                                <span class="text-gray-600 w-24">Coordinates:</span>
                                <span class="font-mono">${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}</span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Voltage:</span>
                                <span class="${voltageClass} font-semibold">${location.voltage.toFixed(2)}V</span>
                            </div>
                            ${accuracyHtml}
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <button onclick="focusLocation(${index})" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-1 rounded text-sm">
                                <i class="fas fa-search-location mr-1"></i> Focus on Map
                            </button>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    className: 'custom-popup',
                    maxWidth: 300
                });
                
                // Click event untuk marker
                marker.on('click', function() {
                    // Highlight item di sidebar
                    highlightSidebarItem(index);
                });
                
                markers.push(marker);
                markersCluster.addLayer(marker);
            });
            
            map.addLayer(markersCluster);
        }
        
        function createRoute() {
            // Hapus route sebelumnya
            if (routeLine) {
                map.removeLayer(routeLine);
            }
            
            // Buat array koordinat
            const coordinates = [];
            gpsData.forEach(function(loc) {
                coordinates.push([loc.lat, loc.lng]);
            });
            
            // Buat polyline
            routeLine = L.polyline(coordinates, {
                color: '#3b82f6',
                weight: 3,
                opacity: 0.7,
                dashArray: '5, 10',
                lineCap: 'round'
            });
            
            // Sembunyikan awalnya
            routeVisible = false;
            document.getElementById('routeStatus').textContent = 'Hidden';
        }
        
        function toggleRoute() {
            if (!routeLine || gpsData.length < 2) return;
            
            if (routeVisible) {
                map.removeLayer(routeLine);
                routeVisible = false;
                document.getElementById('routeStatus').textContent = 'Hidden';
                document.querySelector('#routeBtn i').className = 'fas fa-route mr-2 text-green-600';
                document.querySelector('#routeBtn span').textContent = 'Show Route';
            } else {
                routeLine.addTo(map);
                routeVisible = true;
                document.getElementById('routeStatus').textContent = 'Visible';
                document.querySelector('#routeBtn i').className = 'fas fa-eye-slash mr-2 text-gray-600';
                document.querySelector('#routeBtn span').textContent = 'Hide Route';
            }
        }
        
        function showAllMarkers() {
            if (gpsData.length === 0) return;
            
            const bounds = L.latLngBounds([]);
            gpsData.forEach(function(loc) {
                bounds.extend([loc.lat, loc.lng]);
            });
            
            map.fitBounds(bounds, { padding: [50, 50] });
        }
        
        function centerToLatest() {
            if (gpsData.length === 0) return;
            
            const latest = gpsData[0];
            map.setView([latest.lat, latest.lng], 16);
            
            // Buka popup marker terbaru
            if (markers[0]) {
                markers[0].openPopup();
                highlightSidebarItem(0);
            }
        }
        
        function focusLocation(index) {
            if (index < 0 || index >= gpsData.length) return;
            
            const location = gpsData[index];
            map.setView([location.lat, location.lng], 17);
            
            // Buka popup marker
            if (markers[index]) {
                markers[index].openPopup();
            }
            
            // Highlight di sidebar
            highlightSidebarItem(index);
        }
        
        function highlightSidebarItem(index) {
            // Hapus highlight sebelumnya
            const allItems = document.querySelectorAll('.location-item');
            allItems.forEach(function(item) {
                item.classList.remove('border-blue-500', 'bg-blue-50');
                item.classList.add('border-gray-200', 'bg-white');
            });
            
            // Highlight item yang dipilih
            const selectedItem = document.querySelector('.location-item[data-index="' + index + '"]');
            if (selectedItem) {
                selectedItem.classList.remove('border-gray-200', 'bg-white');
                selectedItem.classList.add('border-blue-500', 'bg-blue-50');
                
                // Scroll ke item
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function clearMap() {
            if (confirm('Clear all markers and route from map?')) {
                if (markersCluster) {
                    map.removeLayer(markersCluster);
                }
                if (routeLine) {
                    map.removeLayer(routeLine);
                    routeVisible = false;
                    document.getElementById('routeStatus').textContent = 'Hidden';
                }
                markers = [];
            }
        }
        
        // Auto-refresh setiap 30 detik
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
