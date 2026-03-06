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
                        <h3 class="text-lg font-semibold mb-1">My Subscription</h3>
                        <p class="text-sm text-gray-500 mb-6">View your current plan and upgrade anytime.</p>

                        {{-- Current Plan Card --}}
                        <div class="mb-8">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Current Plan</h4>

                            @if($currentSubscription && $currentPlan)
                                <div class="border-2 border-blue-500 rounded-xl p-5 bg-blue-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-lg font-bold text-blue-700">{{ $currentPlan->name }}</span>
                                            <span class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">Active</span>
                                        </div>
                                        @if($currentPlan->description)
                                            <p class="text-sm text-blue-600 mb-2">{{ $currentPlan->description }}</p>
                                        @endif
                                        <div class="flex flex-wrap gap-3 text-sm text-blue-700">
                                            <span>
                                                💰
                                                @if($currentPlan->isFree())
                                                    Free
                                                @else
                                                    ₱{{ number_format($currentSubscription->price_paid, 2) }} paid
                                                @endif
                                            </span>
                                            @if($currentSubscription->end_date)
                                                <span>📅 Expires {{ $currentSubscription->end_date->format('M d, Y') }}</span>
                                            @endif
                                            @if($currentSubscription->auto_renew)
                                                <span class="text-green-700">🔄 Auto-renew on</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!$currentPlan->isFree())
                                        <div class="flex flex-col items-end gap-2">
                                            @if($isRefundEligible && $refundDeadline)
                                                {{-- Countdown display --}}
                                                <div class="text-right">
                                                    <p class="text-xs text-gray-500 mb-0.5">Refund window closes:</p>
                                                    <p class="text-xs font-medium text-orange-600">{{ $refundDeadline->format('M d, Y h:i A') }}</p>
                                                    <div class="flex items-center justify-end gap-1 mt-1">
                                                        <span class="text-xs text-gray-400">⏱</span>
                                                        <span id="refund-countdown" class="text-sm font-mono font-bold text-orange-600">--:--:--</span>
                                                        <span class="text-xs text-gray-400">remaining</span>
                                                    </div>
                                                </div>
                                                {{-- Refund button --}}
                                                <button onclick="document.getElementById('refund-modal').classList.remove('hidden')"
                                                        class="text-sm text-orange-600 hover:text-orange-800 border border-orange-300 hover:border-orange-500 bg-orange-50 hover:bg-orange-100 px-4 py-2 rounded-lg transition font-medium">
                                                    💸 Request Refund
                                                </button>
                                            @else
                                                <div class="text-right">
                                                    <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1.5 rounded-lg block">Refund window expired</span>
                                                    @if($refundDeadline)
                                                        <p class="text-xs text-gray-400 mt-1">Closed {{ $refundDeadline->format('M d, Y h:i A') }}</p>
                                                    @elseif(!$latestPayment)
                                                        <p class="text-xs text-gray-400 mt-1">No payment record found</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Free / No subscription --}}
                                <div class="border-2 border-gray-300 rounded-xl p-5 bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <span class="text-lg font-bold text-gray-700">Free Plan</span>
                                        <p class="text-sm text-gray-500 mt-1">Basic access — upgrade to unlock more features.</p>
                                    </div>
                                    <span class="text-xs bg-gray-200 text-gray-600 px-3 py-1 rounded-full">Active</span>
                                </div>
                            @endif
                        </div>

                        {{-- Manage Subscription CTA --}}
                        <div class="border-t border-gray-100 pt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Want to change or upgrade your plan?</p>
                                <p class="text-xs text-gray-400 mt-0.5">View all available plans, switch, or manage your subscription.</p>
                            </div>
                            <a href="{{ route('subscription.index') }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-gray-900 hover:bg-gray-700 text-white transition whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Manage Subscription
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Refund Modal (placed here at top level so fixed positioning is never clipped) --}}
            @if($isRefundEligible && $refundDeadline)
            <div id="refund-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-y-auto max-h-[90vh]">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Request a Refund</h3>
                        <p class="text-sm text-gray-600 mb-1">You are requesting a refund for your <strong>{{ $currentPlan->name }}</strong>.</p>
                        <p class="text-sm text-orange-600 mb-4">⚠️ Your subscription will be cancelled immediately upon refund.</p>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-2 mb-4 text-sm text-orange-700">
                            ⏱ Window closes: <strong>{{ $refundDeadline->format('M d, Y h:i A') }}</strong>
                            &nbsp;—&nbsp;<span id="modal-countdown" class="font-mono font-bold">--:--:--</span> remaining
                        </div>
                        <form method="POST" action="{{ route('subscription.refund') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Reason for refund <span class="text-gray-400">(optional)</span>
                                </label>
                                <textarea name="reason" rows="3"
                                          placeholder="Tell us why you're requesting a refund..."
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 focus:border-transparent"></textarea>
                            </div>
                            <div class="flex gap-3 justify-end">
                                <button type="button" onclick="document.getElementById('refund-modal').classList.add('hidden')"
                                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    Go Back
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-semibold text-white rounded-lg transition"
                                        style="background-color: #f97316;"
                                        onmouseover="this.style.backgroundColor='#ea580c'"
                                        onmouseout="this.style.backgroundColor='#f97316'">
                                    Confirm Refund
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

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

            @if($isRefundEligible && $refundDeadline)
            // Refund countdown timer
            const refundDeadline = new Date('{{ $refundDeadline->toIso8601String() }}');

            function updateCountdown() {
                const now  = new Date();
                const diff = refundDeadline - now;

                const countdownEls = document.querySelectorAll('#refund-countdown, #modal-countdown');

                if (diff <= 0) {
                    countdownEls.forEach(el => {
                        if (el) {
                            el.textContent = 'EXPIRED';
                            el.classList.remove('text-orange-600');
                            el.classList.add('text-red-600');
                        }
                    });
                    return;
                }

                const totalSecs = Math.floor(diff / 1000);
                const hours     = Math.floor(totalSecs / 3600);
                const minutes   = Math.floor((totalSecs % 3600) / 60);
                const seconds   = totalSecs % 60;
                const formatted = String(hours).padStart(2,'0') + ':' +
                                  String(minutes).padStart(2,'0') + ':' +
                                  String(seconds).padStart(2,'0');

                // Turn red when under 1 hour
                countdownEls.forEach(el => {
                    if (!el) return;
                    el.textContent = formatted;
                    if (hours < 1) {
                        el.classList.remove('text-orange-600');
                        el.classList.add('text-red-600');
                    }
                });
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
            @endif
        });
    </script>

    <style>
        .tab-button.active {
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
</x-app-layout>
