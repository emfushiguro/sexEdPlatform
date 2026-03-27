<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Profile</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Tab Navigation -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('profile')" id="tab-profile" 
                                class="tab-button active py-4 px-6 text-center border-b-2 font-medium text-sm">
                            Profile Information
                        </button>
                        <button onclick="switchTab('avatar')" id="tab-avatar" 
                                class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm">
                            Avatar
                        </button>
                        <button onclick="switchTab('password')" id="tab-password" 
                                class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm">
                            Change Password
                        </button>
                        <button onclick="switchTab('subscription')" id="tab-subscription" 
                                class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm">
                            Subscription
                        </button>
                        <button onclick="switchTab('danger')" id="tab-danger" 
                                class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm text-red-600">
                            Delete Account
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Profile Information Tab -->
            <div id="content-profile" class="tab-content">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Profile Information</h3>
                        <p class="text-sm text-gray-600 mb-6">Update your basic profile information. Note: Age range and gender are finalized and cannot be changed.</p>
                        
                        <form method="POST" action="{{ route('profile.learner.update') }}">
                            @csrf
                            @method('PUT')

                            <!-- Username (Editable with limits) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" name="username" value="{{ old('username', $learnerProfile->username) }}" 
                                    minlength="3" maxlength="30"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @if(!auth()->user()->isPremium())
                                    @if($learnerProfile->username_changed_at)
                                        @php
                                            $daysSinceChange = now()->diffInDays($learnerProfile->username_changed_at);
                                            $daysRemaining = max(0, 7 - $daysSinceChange);
                                        @endphp
                                        @if($daysRemaining > 0)
                                            <p class="mt-1 text-xs text-orange-600">
                                                🔒 Free users can change username every 7 days. Next change available in {{ $daysRemaining }} day(s).
                                                <a href="{{ route('subscription.index') }}" class="underline">Upgrade to Premium</a> for unlimited changes!
                                            </p>
                                        @else
                                            <p class="mt-1 text-xs text-green-600">✓ You can change your username now!</p>
                                        @endif
                                    @else
                                        <p class="mt-1 text-xs text-gray-500">Free users: Change username once every 7 days</p>
                                    @endif
                                @else
                                    <p class="mt-1 text-xs text-yellow-600">⭐ Premium: Unlimited username changes!</p>
                                @endif
                                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <!-- Age Range (Read-only) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Age Range (Finalized)</label>
                                <input type="text" value="{{ $learnerProfile->age_range }}" disabled
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed">
                                <p class="mt-1 text-xs text-gray-500">Age range cannot be changed after registration</p>
                            </div>

                            <!-- Gender (Read-only) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Gender (Finalized)</label>
                                <input type="text" value="{{ ucfirst(str_replace('_', ' ', $learnerProfile->gender)) }}" disabled
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed">
                                <p class="mt-1 text-xs text-gray-500">Gender cannot be changed after registration</p>
                            </div>

                            <!-- School (Editable, Optional, Private) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">School (Optional & Private)</label>
                                <input type="text" name="school" value="{{ old('school', $learnerProfile->school) }}" maxlength="255"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Only visible to you and counselors</p>
                                @error('school')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <!-- About (Editable) -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700">About</label>
                                <textarea name="about" rows="4" maxlength="500"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('about', $learnerProfile->about) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
                                @error('about')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Avatar Tab -->
            <div id="content-avatar" class="tab-content hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Profile Avatar</h3>
                        <p class="text-sm text-gray-600 mb-6">Upload a profile picture (JPEG, PNG, JPG - Max 2MB)</p>
                        
                        <form method="POST" action="{{ route('profile.learner.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Current Avatar -->
                            <div class="mb-6 text-center">
                                @if($learnerProfile->avatar_path)
                                    <img src="{{ Storage::url($learnerProfile->avatar_path) }}" alt="Avatar" 
                                         class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200">
                                @else
                                    <div class="w-32 h-32 rounded-full mx-auto bg-blue-500 text-white flex items-center justify-center text-4xl font-bold border-4 border-gray-200">
                                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            <!-- Upload New Avatar -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Choose New Avatar</label>
                                <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg"
                                    class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100">
                                @error('avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Upload Avatar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div id="content-password" class="tab-content hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Change Password</h3>
                        <p class="text-sm text-gray-600 mb-6">Ensure your account is using a strong password.</p>
                        
                        <form method="POST" action="{{ route('profile.password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Min 8 characters, include uppercase, lowercase, number, and special character</p>
                                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="password_confirmation" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Subscription Tab -->
            <div id="content-subscription" class="tab-content hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Manage Subscription</h3>
                        
                        @if(auth()->user()->isPremium())
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-yellow-800">Premium Member</p>
                                        <p class="text-sm text-yellow-700">You have access to all premium features</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-blue-700 mb-4">Upgrade to Premium for exclusive features!</p>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Upgrade to Premium
                                </button>
                            </div>
                        @endif

                        <div class="border-t pt-6">
                            <h4 class="font-semibold mb-2">Subscription Details</h4>
                            <p class="text-sm text-gray-600">Feature coming soon - manage your subscription here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Account Tab -->
            <div id="content-danger" class="tab-content hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-red-200">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">Delete Account</h3>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-red-700 font-semibold mb-2">⚠️ Warning: This action is permanent!</p>
                            <p class="text-sm text-red-600">Once you delete your account, all of your data will be permanently removed. This includes:</p>
                            <ul class="list-disc list-inside text-sm text-red-600 mt-2 ml-4">
                                <li>Your profile and progress</li>
                                <li>All enrolled modules and certificates</li>
                                <li>Your learning history</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="{{ route('profile.account.delete') }}" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!');">
                            @csrf
                            @method('DELETE')

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Enter your password to confirm</label>
                                <input type="password" name="password" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Permanently Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('active', 'border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            
            // Show selected content
            document.getElementById('content-' + tab).classList.remove('hidden');
            const button = document.getElementById('tab-' + tab);
            button.classList.add('active', 'border-blue-600', 'text-blue-600');
            button.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }
        
        // Initialize tabs on load
        document.addEventListener('DOMContentLoaded', function() {
            switchTab('profile');
        });
    </script>

    <style>
        .tab-button.active {
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
</x-app-layout>
