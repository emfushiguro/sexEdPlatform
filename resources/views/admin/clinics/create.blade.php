<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Clinics', 'url' => route('admin.clinics.index')],
            ['label' => 'Create']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('admin.clinics.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Clinic</h2>
        </div>
    </x-slot>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-bold">Please fix the following errors:</h4>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.clinics.store') }}">
                        @csrf

                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Clinic Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="type" class="block text-sm font-medium text-gray-700">Clinic Type</label>
                                    <select name="type" id="type" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select Type</option>
                                        @foreach(\App\Enums\ClinicType::cases() as $type)
                                            <option value="{{ $type->value }}" {{ old('type') == $type->value ? 'selected' : '' }}>
                                                {{ $type->getDisplayName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Municipality/City *</label>
                                    <select name="city" id="city" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select your municipality/city</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->name }}" data-code="{{ $city->code }}" {{ old('city') == $city->name ? 'selected' : '' }}>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Barangay *</label>
                                    <select name="barangay" id="barangay" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select municipality first</option>
                                        @if(old('barangay'))
                                            <option value="{{ old('barangay') }}" selected>{{ old('barangay') }}</option>
                                        @endif
                                    </select>
                                    @error('barangay')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const citySelect = document.getElementById('city');
                                        const barangaySelect = document.getElementById('barangay');
                                        citySelect.addEventListener('change', function() {
                                            const selectedOption = citySelect.options[citySelect.selectedIndex];
                                            const cityCode = selectedOption.getAttribute('data-code');
                                            barangaySelect.innerHTML = '<option value="">Loading...</option>';
                                            if (!cityCode) {
                                                barangaySelect.innerHTML = '<option value="">Select municipality first</option>';
                                                return;
                                            }
                                            fetch(`/api/barangays/${cityCode}`)
                                                .then(response => response.json())
                                                .then(data => {
                                                    let options = '<option value="">Select barangay</option>';
                                                    data.forEach(function(barangay) {
                                                        options += `<option value="${barangay.name}">${barangay.name}</option>`;
                                                    });
                                                    barangaySelect.innerHTML = options;
                                                });
                                        });
                                    });
                                </script>

                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700">Complete Address</label>
                                    <textarea name="address" id="address" rows="3" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Operating Hours -->
                                @push('scripts')
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const citySelect = document.getElementById('city_code');
                                    const barangaySelect = document.getElementById('barangay_code');
                                    citySelect.addEventListener('change', function() {
                                        const cityCode = this.value;
                                        barangaySelect.innerHTML = '<option value="">Loading...</option>';
                                        if (!cityCode) {
                                            barangaySelect.innerHTML = '<option value="">Select municipality first</option>';
                                            return;
                                        }
                                        fetch(`/api/barangays/${cityCode}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                let options = '<option value="">Select barangay</option>';
                                                data.forEach(function(barangay) {
                                                    options += `<option value="${barangay.code}">${barangay.name}</option>`;
                                                });
                                                barangaySelect.innerHTML = options;
                                            });
                                    });
                                });
                                </script>
                                @endpush
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Operating Hours</h3>
                            <div>
                                <label for="operating_hours" class="block text-sm font-medium text-gray-700 mb-2">Select Operating Schedule</label>
                                <select name="operating_hours" id="operating_hours" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select operating hours...</option>
                                    <option value="Mon to Fri 8:00 AM - 5:00 PM" {{ old('operating_hours') == 'Mon to Fri 8:00 AM - 5:00 PM' ? 'selected' : '' }}>Mon to Fri 8:00 AM - 5:00 PM</option>
                                    <option value="Mon to Sat 8:00 AM - 5:00 PM" {{ old('operating_hours') == 'Mon to Sat 8:00 AM - 5:00 PM' ? 'selected' : '' }}>Mon to Sat 8:00 AM - 5:00 PM</option>
                                    <option value="Mon to Sun 8:00 AM - 5:00 PM" {{ old('operating_hours') == 'Mon to Sun 8:00 AM - 5:00 PM' ? 'selected' : '' }}>Mon to Sun 8:00 AM - 5:00 PM</option>
                                    <option value="24/7" {{ old('operating_hours') == '24/7' ? 'selected' : '' }}>24/7</option>
                                    <option value="Mon to Fri 9:00 AM - 6:00 PM" {{ old('operating_hours') == 'Mon to Fri 9:00 AM - 6:00 PM' ? 'selected' : '' }}>Mon to Fri 9:00 AM - 6:00 PM</option>
                                    <option value="Mon to Sat 9:00 AM - 6:00 PM" {{ old('operating_hours') == 'Mon to Sat 9:00 AM - 6:00 PM' ? 'selected' : '' }}>Mon to Sat 9:00 AM - 6:00 PM</option>
                                </select>
                                @error('operating_hours')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="contact" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="text" name="contact" id="contact" value="{{ old('contact') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('contact')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Location Selection</h3>
                            
                            <!-- Interactive Map -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Click on the map to set clinic location</label>
                                <div id="locationMap" class="h-96 w-full border border-gray-300 rounded-lg">
                                    <div class="h-full flex items-center justify-center bg-gray-100">
                                        <div class="text-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">Loading Map...</h3>
                                            <p class="mt-1 text-sm text-gray-500">Interactive map will appear here</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Click anywhere on the map to place a pin and set the clinic's location. The coordinates will be automatically filled.</p>
                            </div>

                            <!-- Hidden coordinate inputs -->
                            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                        </div>

                        <!-- Services -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Services Offered</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach(\App\Enums\ClinicService::cases() as $service)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="services[]" value="{{ $service->value }}" 
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                               {{ in_array($service->value, old('services', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ $service->getDisplayName() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('services')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_active" value="1" 
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="verified" value="1" 
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                               {{ old('verified') ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm font-medium text-gray-700">Verified</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea name="notes" id="notes" rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Any additional notes or special instructions...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('admin.clinics.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                Create Clinic
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        let map, currentMarker;
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            
            // Validate form submission
            document.getElementById('submitBtn').addEventListener('click', function(e) {
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;
                
                if (!lat || !lng) {
                    e.preventDefault();
                    alert('Please select a location on the map before submitting.');
                    return false;
                }
            });
        });
        
        function initializeMap() {
            // Center on Cavite Province (approximate center)
            const caviteCenter = [14.2456, 120.8792];
            
            // Initialize the map
            map = L.map('locationMap').setView(caviteCenter, 11);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Add click event to map
            map.on('click', function(e) {
                setLocation(e.latlng.lat, e.latlng.lng);
            });
            
            // If we have old values (form validation failed), show them
            const oldLat = document.getElementById('latitude').value;
            const oldLng = document.getElementById('longitude').value;
            
            if (oldLat && oldLng) {
                setLocation(parseFloat(oldLat), parseFloat(oldLng));
                map.setView([parseFloat(oldLat), parseFloat(oldLng)], 15);
            }
            
            // Add instruction popup
            L.popup()
                .setLatLng(caviteCenter)
                .setContent('Click anywhere on the map to place the clinic location pin.')
                .openOn(map);
        }
        
        function setLocation(lat, lng) {
            // Remove existing marker if any
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            
            // Add new marker
            currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);
            
            // Update form fields
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            
            // Add popup to marker
            currentMarker.bindPopup(`
                <div class="text-center">
                    <strong>Clinic Location</strong><br>
                    <small>Lat: ${lat.toFixed(6)}</small><br>
                    <small>Lng: ${lng.toFixed(6)}</small><br>
                    <em>You can drag this pin to adjust</em>
                </div>
            `).openPopup();
            
            // Make marker draggable and update coordinates when dragged
            currentMarker.on('dragend', function(e) {
                const newPos = e.target.getLatLng();
                setLocation(newPos.lat, newPos.lng);
            });
            
            // Enable submit button now that location is selected
            document.getElementById('submitBtn').disabled = false;
        }
    </script>
</x-app-layout>