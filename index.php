<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracking - Satellite View</title>
    
    <!-- Leaflet CSS (ringan) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    
    <!-- Simple CSS (tanpa Tailwind) -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a202c; 
            color: white;
            height: 100vh;
            overflow: hidden;
        }
        .container { 
            display: flex; 
            height: 100vh; 
        }
        /* Sidebar */
        .sidebar {
            width: 320px;
            background: rgba(26, 32, 44, 0.95);
            border-right: 1px solid #2d3748;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .header {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            padding: 20px;
            border-bottom: 1px solid #4a5568;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            color: white;
        }
        .header p {
            font-size: 12px;
            color: #cbd5e0;
        }
        /* Stats */
        .stats {
            padding: 15px;
            border-bottom: 1px solid #2d3748;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .stat-card {
            background: rgba(45, 55, 72, 0.7);
            border: 1px solid #4a5568;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .stat-card .label {
            font-size: 11px;
            color: #a0aec0;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 18px;
            font-weight: bold;
            color: white;
        }
        /* Controls */
        .controls {
            padding: 15px;
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #2d3748;
        }
        .btn {
            flex: 1;
            background: #4299e1;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        .btn:hover {
            background: #3182ce;
        }
        .btn-danger {
            background: #f56565;
        }
        .btn-danger:hover {
            background: #e53e3e;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        /* Location List */
        .location-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .location-item {
            background: rgba(45, 55, 72, 0.6);
            border: 1px solid #4a5568;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .location-item:hover {
            background: rgba(66, 153, 225, 0.2);
            border-color: #4299e1;
        }
        .location-item.active {
            background: rgba(66, 153, 225, 0.3);
            border-color: #4299e1;
        }
        .loc-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .loc-id {
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        .loc-time {
            font-size: 11px;
            color: #a0aec0;
        }
        .loc-coords {
            font-family: monospace;
            font-size: 11px;
            color: #cbd5e0;
            margin-bottom: 3px;
        }
        .loc-voltage {
            font-size: 12px;
            font-weight: bold;
        }
        .voltage-low { color: #fc8181; }
        .voltage-normal { color: #68d391; }
        .voltage-high { color: #63b3ed; }
        /* Map */
        #map {
            flex: 1;
            height: 100%;
        }
        /* Map Controls */
        .map-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }
        .map-btn {
            background: rgba(26, 32, 44, 0.9);
            color: white;
            border: 1px solid #4a5568;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        .map-btn:hover {
            background: rgba(45, 55, 72, 0.9);
            border-color: #4299e1;
        }
        /* Status Bar */
        .status-bar {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(26, 32, 44, 0.9);
            border: 1px solid #4a5568;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 12px;
            color: #cbd5e0;
            backdrop-filter: blur(10px);
        }
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #2d3748;
        }
        ::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #718096;
        }
        /* Mobile */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: 40vh; }
            #map { height: 60vh; }
            .map-controls { top: 10px; right: 10px; }
            .status-bar { bottom: 10px; left: 10px; }
        }
    </style>
</head>
<body>
    <?php
    // URL API
    $api_url = "https://daffaiotdev.alwaysdata.net/apigps/api/get/monitoring";
    
    // Ambil data dengan timeout pendek
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 5
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ));
    
    $response = @file_get_contents($api_url, false, $context);
    $locations = array();
    
    if ($response !== FALSE && !empty($response)) {
        $data = json_decode($response, true);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                if (!empty($item['latitude']) && !empty($item['longitude'])) {
                    $locations[] = array(
                        'id' => isset($item['id']) ? $item['id'] : 'N/A',
                        'lat' => floatval($item['latitude']),
                        'lng' => floatval($item['longitude']),
                        'voltage' => isset($item['voltage']) ? floatval($item['voltage']) : 0,
                        'time' => isset($item['created_at']) ? $item['created_at'] : '',
                        'accuracy' => isset($item['accurate']) ? $item['accurate'] : null
                    );
                }
            }
            // Balik urutan - terbaru pertama
            $locations = array_reverse($locations);
            // Batasi untuk performa
            $locations = array_slice($locations, 0, 100);
        }
    }
    
    $total_points = count($locations);
    $avg_voltage = 0;
    if ($total_points > 0) {
        $total_voltage = 0;
        foreach ($locations as $loc) {
            $total_voltage += $loc['voltage'];
        }
        $avg_voltage = $total_voltage / $total_points;
    }
    ?>
    
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="header">
                <h1>🚀 GPS Satellite Tracking</h1>
                <p>Real-time satellite view monitoring</p>
            </div>
            
            <div class="stats">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="label">Total Points</div>
                        <div class="value"><?php echo $total_points; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Avg Voltage</div>
                        <div class="value"><?php echo number_format($avg_voltage, 2); ?>V</div>
                    </div>
                </div>
                <div style="font-size: 11px; color: #a0aec0; text-align: center;">
                    Last update: <?php echo date('H:i:s'); ?>
                </div>
            </div>
            
            <div class="controls">
                <button class="btn" onclick="window.location.reload()">
                    🔄 Refresh
                </button>
                <button class="btn btn-success" onclick="showAllMarkers()">
                    📍 Show All
                </button>
                <button class="btn btn-danger" onclick="clearSelection()">
                    ✖️ Clear
                </button>
            </div>
            
            <div class="location-list">
                <?php if ($total_points > 0): ?>
                    <?php foreach ($locations as $index => $loc): 
                        $time = !empty($loc['time']) ? date('H:i:s', strtotime($loc['time'])) : 'N/A';
                        $voltage_class = 'voltage-normal';
                        if ($loc['voltage'] < 11.5) $voltage_class = 'voltage-low';
                        if ($loc['voltage'] > 12.0) $voltage_class = 'voltage-high';
                    ?>
                    <div class="location-item" data-index="<?php echo $index; ?>" onclick="focusLocation(<?php echo $index; ?>)">
                        <div class="loc-header">
                            <div class="loc-id">📍 Point #<?php echo $loc['id']; ?></div>
                            <div class="loc-time"><?php echo $time; ?></div>
                        </div>
                        <div class="loc-coords">
                            <?php echo number_format($loc['lat'], 6); ?>, <?php echo number_format($loc['lng'], 6); ?>
                        </div>
                        <div class="loc-voltage <?php echo $voltage_class; ?>">
                            ⚡ <?php echo number_format($loc['voltage'], 2); ?>V
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #a0aec0;">
                        No GPS data available
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Map Area -->
        <div id="map"></div>
        
        <!-- Map Controls -->
        <div class="map-controls">
            <button class="map-btn" onclick="showAllMarkers()">
                📍 Show All Points
            </button>
            <button class="map-btn" onclick="centerToLatest()">
                🎯 Go to Latest
            </button>
            <button class="map-btn" onclick="toggleMarkers()" id="toggleMarkersBtn">
                👁️ Show Markers
            </button>
            <button class="map-btn" onclick="drawRoute()" id="routeBtn">
                🧭 Draw Route
            </button>
        </div>
        
        <!-- Status Bar -->
        <div class="status-bar">
            <div>📍 Points: <span id="pointCount"><?php echo $total_points; ?></span> | 
                 Zoom: <span id="zoomLevel">-</span> | 
                 🛰️ Satellite View
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <script>
        // Data dari PHP
        const gpsData = <?php echo json_encode($locations); ?>;
        
        // Variabel global
        let map = null;
        let markers = [];
        let markersVisible = true;
        let routeLine = null;
        let routeVisible = false;
        
        // Initialize map
        document.addEventListener('DOMContentLoaded', function() {
            if (gpsData.length > 0) {
                initMap();
            } else {
                document.getElementById('map').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2d3748; color: #cbd5e0;">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 20px;">🛰️</div>
                            <h3>No GPS Data Available</h3>
                            <p style="margin-top: 10px; font-size: 14px;">Waiting for GPS data from device...</p>
                            <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #4299e1; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                Refresh
                            </button>
                        </div>
                    </div>
                `;
            }
        });
        
        function initMap() {
            try {
                // Gunakan titik pertama sebagai center
                const firstPoint = gpsData[0];
                
                // Initialize map dengan SATELLITE VIEW
                map = L.map('map', {
                    center: [firstPoint.lat, firstPoint.lng],
                    zoom: 17,
                    zoomControl: true,
                    attributionControl: false
                });
                
                // TILE LAYER SATELITE - OpenStreetMap HOT (high contrast untuk satelite)
                L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors, Tiles style by Humanitarian OpenStreetMap Team'
                }).addTo(map);
                
                // TAMBAHAN: ESRI Satellite (jika ingin benar-benar satelite)
                const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles © Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                });
                
                // Layer control sederhana
                const baseLayers = {
                    "Satellite View": esriSatellite,
                    "Street Map": L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png')
                };
                
                esriSatellite.addTo(map); // Default pakai Esri Satellite
                L.control.layers(baseLayers).addTo(map);
                
                // Tambahkan marker
                addMarkers();
                
                // Update zoom level
                map.on('zoomend', updateZoomLevel);
                updateZoomLevel();
                
                // Auto fit bounds jika banyak titik
                if (gpsData.length > 1) {
                    setTimeout(function() {
                        showAllMarkers();
                    }, 1000);
                }
                
                console.log('Map initialized with', gpsData.length, 'points');
                
            } catch (error) {
                console.error('Map initialization failed:', error);
                alert('Failed to load map. Please check your internet connection.');
            }
        }
        
        function addMarkers() {
            // Hapus marker lama
            clearMarkers();
            
            // Buat marker untuk setiap titik
            gpsData.forEach(function(location, index) {
                // Tentukan warna berdasarkan voltage
                let color = '#10B981'; // hijau default (normal)
                if (location.voltage < 11.5) color = '#EF4444'; // merah (low)
                if (location.voltage > 12.0) color = '#3B82F6'; // biru (high)
                
                // Buat custom icon yang lebih visible di satelite
                const icon = L.divIcon({
                    html: `
                        <div style="
                            background-color: ${color};
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            border: 3px solid white;
                            box-shadow: 0 0 10px rgba(0,0,0,0.7);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 11px;
                        ">
                            ${index + 1}
                        </div>
                    `,
                    className: 'custom-marker',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                });
                
                // Buat marker
                const marker = L.marker([location.lat, location.lng], { 
                    icon: icon,
                    title: `Point #${location.id}`
                });
                
                // Popup content
                const timeStr = location.time ? new Date(location.time).toLocaleString('id-ID') : 'N/A';
                const voltageStatus = location.voltage < 11.5 ? 'Low' : (location.voltage > 12.0 ? 'High' : 'Normal');
                
                const popupContent = `
                    <div style="min-width: 220px; padding: 5px;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #4299e1; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                <span style="color: white; font-weight: bold;">${index + 1}</span>
                            </div>
                            <div>
                                <div style="font-weight: bold; color: #2d3748;">Point #${location.id}</div>
                                <div style="font-size: 11px; color: #718096;">${timeStr}</div>
                            </div>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <div style="font-size: 12px; color: #4a5568; margin-bottom: 3px;">Coordinates:</div>
                            <div style="font-family: monospace; font-size: 11px; background: #f7fafc; padding: 4px 6px; border-radius: 4px; color: #2d3748;">
                                ${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                            <div>
                                <div style="font-size: 12px; color: #4a5568;">Voltage:</div>
                                <div style="color: ${color}; font-weight: bold; font-size: 14px;">
                                    ${location.voltage.toFixed(2)}V
                                </div>
                                <div style="font-size: 11px; color: #718096;">${voltageStatus}</div>
                            </div>
                            <button onclick="focusLocation(${index})" style="
                                background: #4299e1;
                                color: white;
                                border: none;
                                padding: 6px 12px;
                                border-radius: 4px;
                                cursor: pointer;
                                font-size: 11px;
                                font-weight: 500;
                            ">
                                📍 Focus
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
        
        function clearMarkers() {
            markers.forEach(function(marker) {
                map.removeLayer(marker);
            });
            markers = [];
        }
        
        function showAllMarkers() {
            if (!map || gpsData.length === 0) return;
            
            const bounds = L.latLngBounds([]);
            gpsData.forEach(function(loc) {
                bounds.extend([loc.lat, loc.lng]);
            });
            
            map.fitBounds(bounds, { 
                padding: [50, 50],
                maxZoom: 17
            });
        }
        
        function centerToLatest() {
            if (!map || gpsData.length === 0) return;
            
            const latest = gpsData[0];
            map.setView([latest.lat, latest.lng], 18);
            
            if (markers[0]) {
                markers[0].openPopup();
                highlightSidebarItem(0);
            }
        }
        
        function focusLocation(index) {
            if (!map || index < 0 || index >= gpsData.length) return;
            
            const location = gpsData[index];
            map.setView([location.lat, location.lng], 18);
            
            if (markers[index]) {
                markers[index].openPopup();
                highlightSidebarItem(index);
            }
        }
        
        function highlightSidebarItem(index) {
            // Hapus highlight sebelumnya
            document.querySelectorAll('.location-item').forEach(function(item) {
                item.classList.remove('active');
            });
            
            // Highlight yang dipilih
            const selectedItem = document.querySelector('.location-item[data-index="' + index + '"]');
            if (selectedItem) {
                selectedItem.classList.add('active');
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function toggleMarkers() {
            if (markersVisible) {
                markers.forEach(function(marker) {
                    map.removeLayer(marker);
                });
                markersVisible = false;
                document.getElementById('toggleMarkersBtn').innerHTML = '👁️‍🗨️ Show Markers';
            } else {
                markers.forEach(function(marker) {
                    marker.addTo(map);
                });
                markersVisible = true;
                document.getElementById('toggleMarkersBtn').innerHTML = '👁️ Hide Markers';
            }
        }
        
        function drawRoute() {
            if (routeVisible) {
                if (routeLine) map.removeLayer(routeLine);
                routeVisible = false;
                document.getElementById('routeBtn').innerHTML = '🧭 Draw Route';
            } else if (gpsData.length > 1) {
                // Hapus route lama
                if (routeLine) map.removeLayer(routeLine);
                
                // Buat array koordinat
                const coordinates = [];
                gpsData.forEach(function(loc) {
                    coordinates.push([loc.lat, loc.lng]);
                });
                
                // Buat polyline
                routeLine = L.polyline(coordinates, {
                    color: '#4299e1',
                    weight: 4,
                    opacity: 0.8,
                    dashArray: '10, 10',
                    lineCap: 'round'
                }).addTo(map);
                
                routeVisible = true;
                document.getElementById('routeBtn').innerHTML = '🧭 Remove Route';
            }
        }
        
        function clearSelection() {
            highlightSidebarItem(-1);
            if (routeLine) {
                map.removeLayer(routeLine);
                routeVisible = false;
                document.getElementById('routeBtn').innerHTML = '🧭 Draw Route';
            }
        }
        
        function updateZoomLevel() {
            if (map) {
                document.getElementById('zoomLevel').textContent = map.getZoom();
            }
        }
        
        // Auto-refresh setiap 30 detik
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
    </script>
</body>
</html>
