<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Clinics', 'url' => route('admin.clinics.index')],
            ['label' => $clinic->name]
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.clinics.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $clinic->name }}</h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.clinics.edit', $clinic) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                    Edit
                </a>
                @if($clinic->approval_status == 'pending')
                    <form action="{{ route('admin.clinics.approve', $clinic) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                onclick="return confirm('Approve this clinic?')">
                            Approve
                        </button>
                    </form>
                    <form action="{{ route('admin.clinics.reject', $clinic) }}" method="POST" class="inline" onsubmit="return rejectClinicPrompt(this);">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="rejection_reason" value="">
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Reject
                        </button>
                    </form>
                    <script>
                    function rejectClinicPrompt(form) {
                        var reason = prompt('Please enter a reason for rejection:');
                        if (reason === null || reason.trim() === '') {
                            alert('Rejection reason is required.');
                            return false;
                        }
                        form.querySelector('input[name=\'rejection_reason\']').value = reason;
                        return true;
                    }
                    </script>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Basic Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Clinic Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->type->getDisplayName() }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">City</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->city }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Barangay</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->barangay }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->contact ?: 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->email ?: 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services -->
                    @if($clinic->services_display && count($clinic->services_display) > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Services Offered</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($clinic->services_display as $display)
                                    <div class="flex items-center p-2 bg-gray-50 rounded">
                                        <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ $display }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Operating Hours -->
                    @if($clinic->operating_hours)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Operating Hours</h3>
                            <div class="text-sm text-gray-700">{{ $clinic->operating_hours }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Location Map -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Location</h3>
                            <div id="clinicMap" class="h-64 w-full rounded-lg border mb-4"></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Latitude</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->latitude ?? 'Not set' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Longitude</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->longitude ?? 'Not set' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information section removed as requested -->

                    <!-- Additional Notes -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Notes</h3>
                            @if($clinic->notes)
                                <p class="text-gray-700 leading-relaxed">{{ $clinic->notes }}</p>
                            @else
                                <div class="text-center py-4 bg-gray-50 rounded-lg">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No additional notes available</p>
                                    <p class="text-xs text-gray-400">Edit this clinic to add notes or special information</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Status Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Approval Status</label>
                                    <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($clinic->approval_status == 'approved') bg-green-100 text-green-800
                                        @elseif($clinic->approval_status == 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $clinic->approval_status->getDisplayName() }}
                                    </span>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Active Status</label>
                                    <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $clinic->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $clinic->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Verified</label>
                                    <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $clinic->verified ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $clinic->verified ? 'Verified' : 'Unverified' }}
                                    </span>
                                </div>

                                @if($clinic->is_premium)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Premium Status</label>
                                    <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Premium
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Approval Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Created At</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->created_at->format('F j, Y \a\t g:i A') }}</p>
                                </div>

                                @if($clinic->approved_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Approved At</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->approved_at->format('F j, Y \a\t g:i A') }}</p>
                                </div>
                                @endif

                                @if($clinic->approvedBy)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Approved By</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $clinic->approvedBy->name }}</p>
                                </div>
                                @endif

                                @if($clinic->rejection_reason)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Rejection Reason</label>
                                    <p class="mt-1 text-sm text-red-600">{{ $clinic->rejection_reason }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- User Information -->
                    @if($clinic->user)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Associated User</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $clinic->user->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $clinic->user->email }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.users.show', $clinic->user) }}" 
                                   class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                                    View User Details
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                            <div class="space-y-3">
                                <form action="{{ route('admin.clinics.approve', $clinic) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded"
                                            onclick="return confirm('Approve this clinic?')">
                                        Approve Clinic
                                    </button>
                                </form>
                                <form action="{{ route('admin.clinics.reject', $clinic) }}" method="POST" onsubmit="return rejectClinicPrompt(this);">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="rejection_reason" value="">
                                    <button type="submit" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded">
                                        Reject Clinic
                                    </button>
                                </form>
                                <script>
                                function rejectClinicPrompt(form) {
                                    var reason = prompt('Please enter a reason for rejection:');
                                    if (reason === null || reason.trim() === '') {
                                        alert('Rejection reason is required.');
                                        return false;
                                    }
                                    form.querySelector('input[name=\'rejection_reason\']').value = reason;
                                    return true;
                                }
                                </script>

                                <form action="{{ route('admin.clinics.toggle-active', $clinic) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="w-full {{ $clinic->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white py-2 px-4 rounded">
                                        {{ $clinic->is_active ? 'Deactivate' : 'Activate' }} Clinic
                                    </button>
                                </form>

                                <a href="{{ route('health-centers.show', $clinic) }}" 
                                   target="_blank"
                                   class="w-full inline-flex justify-center items-center bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                                    View Public Page
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            @if($clinic->latitude && $clinic->longitude)
            // Initialize map
            const map = L.map('clinicMap').setView([{{ $clinic->latitude }}, {{ $clinic->longitude }}], 16);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add marker
            L.marker([{{ $clinic->latitude }}, {{ $clinic->longitude }}])
                .addTo(map)
                .bindPopup(`
                    <div>
                        <h4 class="font-bold">{{ $clinic->name }}</h4>
                        <p class="text-sm">{{ $clinic->address }}</p>
                        <p class="text-xs text-gray-600">{{ $clinic->city }}, {{ $clinic->barangay }}</p>
                    </div>
                `)
                .openPopup();
            @endif
        });
    </script>
</x-app-layout>