<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Health Centers in Cavite') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Search and Filter Bar -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Health Centers</label>
                        <input type="text" 
                               id="search" 
                               name="search"
                               placeholder="Search by name or services..." 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <select id="city" 
                                name="city"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Cities</option>
                            @foreach($allCities as $city)
                                <option value="{{ $city }}">{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" 
                                onclick="searchClinics()" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map and List Container -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Interactive Map -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b">
                            <h3 class="text-lg font-semibold text-gray-900">Health Centers Map</h3>
                            <p class="text-sm text-gray-600">Click on markers to view clinic details</p>
                        </div>
                        <div id="clinicsMap" class="h-96 w-full">
                            <!-- Map will be loaded here -->
                            <div class="h-full flex items-center justify-center bg-gray-100">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Loading Map...</h3>
                                    <p class="mt-1 text-sm text-gray-500">Interactive map will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinics List -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border">
                        <div class="p-4 bg-gray-50 border-b">
                            <h3 class="text-lg font-semibold text-gray-900">Health Centers</h3>
                            <p class="text-sm text-gray-600" id="clinicsCount">Loading...</p>
                        </div>
                        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto" id="clinicsList">
                            <!-- Clinics will be loaded here -->
                            <div class="p-4 text-center text-gray-500">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                                </svg>
                                Loading health centers...
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Statistics -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Total Centers</p>
                            <p class="text-lg font-semibold text-gray-900" id="totalClinics">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg border shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Active Today</p>
                            <p class="text-lg font-semibold text-gray-900" id="activeClinics">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Cities</p>
                            <p class="text-lg font-semibold text-gray-900" id="totalCities">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Open 24/7</p>
                            <p class="text-lg font-semibold text-gray-900" id="emergency24h">{{ $open247Count }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Include Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Map initialization
        let map, clinicsData = [];
        let allMarkers = [];

        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            loadClinics();
        });

        function initializeMap() {
            // Center on Cavite Province
            map = L.map('clinicsMap').setView([14.2456, 120.8754], 11);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        async function loadClinics() {
            try {
                const response = await fetch('/api/clinics');
                const data = await response.json();
                let clinics = [];
                let stats = {};
                // Accept both {success: true, data: [...]} and plain array
                if (Array.isArray(data)) {
                    clinics = data;
                } else if (data.success && Array.isArray(data.data)) {
                    clinics = data.data;
                    stats = data.statistics || {};
                } else if (data.data && Array.isArray(data.data)) {
                    clinics = data.data;
                }
                clinicsData = clinics;
                displayClinics(clinicsData);
                addMarkersToMap(clinicsData);
                updateStatistics(stats);
            } catch (error) {
                console.error('Error loading clinics:', error);
                document.getElementById('clinicsList').innerHTML = 
                    '<div class="p-4 text-center text-red-500">Error loading health centers. Please try again.</div>';
                document.getElementById('clinicsCount').textContent = 'Error';
            }
        }

        function displayClinics(clinics) {
            const clinicsList = document.getElementById('clinicsList');
            const clinicsCount = document.getElementById('clinicsCount');
            
            clinicsCount.textContent = `${clinics.length} health centers found`;
            
            if (clinics.length === 0) {
                clinicsList.innerHTML = 
                    '<div class="p-4 text-center text-gray-500">No health centers found matching your criteria.</div>';
                return;
            }

            const clinicsHTML = clinics.map(clinic => `
                <div class="p-4 hover:bg-gray-50 cursor-pointer clinic-item" data-id="${clinic.id}">
                    <h4 class="font-semibold text-gray-900">${clinic.name}</h4>
                    <p class="text-sm text-gray-600 mb-2">${clinic.city}, ${clinic.barangay}</p>
                    <div class="flex items-center text-xs text-gray-500 mb-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${clinic.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${clinic.is_active ? 'Open' : 'Closed'}
                        </span>
                        <span class="ml-2">${clinic.type}</span>
                    </div>
                    <div class="text-xs text-gray-600">
                        Services: ${clinic.services_display && clinic.services_display.length > 0 ? clinic.services_display.slice(0, 3).join(', ') + (clinic.services_display.length > 3 ? '...' : '') : 'None'}
                    </div>
                </div>
            `).join('');

            clinicsList.innerHTML = clinicsHTML;

            // Add click handlers
            document.querySelectorAll('.clinic-item').forEach(item => {
                item.addEventListener('click', function() {
                    const clinicId = this.dataset.id;
                    const clinic = clinicsData.find(c => c.id == clinicId);
                    if (clinic && clinic.latitude && clinic.longitude) {
                        map.setView([clinic.latitude, clinic.longitude], 16);
                        
                        // Find and open the marker popup
                        allMarkers.forEach(marker => {
                            if (marker.clinic.id == clinicId) {
                                marker.openPopup();
                            }
                        });
                    }
                });
            });
        }

        function addMarkersToMap(clinics) {
            // Clear existing markers
            allMarkers.forEach(marker => map.removeLayer(marker));
            allMarkers = [];

            clinics.forEach(clinic => {
                if (clinic.latitude && clinic.longitude) {
                    const marker = L.marker([clinic.latitude, clinic.longitude])
                        .addTo(map)
                        .bindPopup(`
                            <div class="p-2">
                                <h4 class="font-bold text-gray-900">${clinic.name}</h4>
                                <p class="text-sm text-gray-600">${clinic.city}, ${clinic.barangay}</p>
                                <p class="text-xs text-gray-500 mb-2">${clinic.type}</p>
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${clinic.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${clinic.is_active ? 'Open' : 'Closed'}
                                    </span>
                                </div>
                                ${clinic.contact ? `<p class="text-xs text-gray-600 mb-1">📞 ${clinic.contact}</p>` : ''}
                                <a href="/health-centers/${clinic.id}" 
                                   class="inline-block bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700">
                                   View Details
                                </a>
                            </div>
                        `);
                    
                    marker.clinic = clinic;
                    allMarkers.push(marker);
                }
            });

            // Adjust map to show all markers
            if (allMarkers.length > 0) {
                const group = new L.featureGroup(allMarkers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function updateStatistics(stats) {
            document.getElementById('totalClinics').textContent = stats.total || clinicsData.length;
            document.getElementById('activeClinics').textContent = stats.active || clinicsData.filter(c => c.is_active).length;
            document.getElementById('totalCities').textContent = stats.cities || [...new Set(clinicsData.map(c => c.city))].length;
            document.getElementById('emergency24h').textContent = stats.emergency || '0';
        }

        async function searchClinics() {
            const search = document.getElementById('search').value;
            const city = document.getElementById('city').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (city) params.append('city', city);

            try {
                const response = await fetch(`/api/clinics?${params}`);
                const data = await response.json();
                let clinics = [];
                let stats = {};
                if (Array.isArray(data)) {
                    clinics = data;
                } else if (data.success && Array.isArray(data.data)) {
                    clinics = data.data;
                    stats = data.statistics || {};
                } else if (data.data && Array.isArray(data.data)) {
                    clinics = data.data;
                }
                displayClinics(clinics);
                addMarkersToMap(clinics);
                updateStatistics(stats);
            } catch (error) {
                console.error('Error searching clinics:', error);
                document.getElementById('clinicsList').innerHTML = 
                    '<div class="p-4 text-center text-red-500">Error searching health centers. Please try again.</div>';
                document.getElementById('clinicsCount').textContent = 'Error';
            }
        }

        // Add enter key support for search
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchClinics();
            }
        });
    </script>
</x-app-layout>