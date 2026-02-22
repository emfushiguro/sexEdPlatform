<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Child Account - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create Child Account</h1>
                    <p class="mt-2 text-sm text-gray-600">Add a learning account for your child</p>
                </div>
                <a href="{{ route('parent.children.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    ← Back to My Children
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-8">
                
                <!-- Parent Info Banner -->
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">
                                <strong>Registered by:</strong> {{ auth()->user()->full_name }} ({{ auth()->user()->email }})
                            </p>
                            <p class="text-xs text-green-700 mt-1">
                                You'll be able to monitor this child's progress, view quiz results, and manage their learning.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('parent.create-child.store') }}"
                      x-data="{
                          birthdate: '',
                          age: null,
                          cityCode: '{{ old('city_code', $parentProfile?->city_code) }}',
                          calculateAge() {
                              if (!this.birthdate) {
                                  this.age = null;
                                  return;
                              }
                              const today = new Date();
                              const birth = new Date(this.birthdate);
                              let age = today.getFullYear() - birth.getFullYear();
                              const monthDiff = today.getMonth() - birth.getMonth();
                              if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                                  age--;
                              }
                              this.age = age;
                          },
                          async loadBarangays() {
                              if (!this.cityCode) return;
                              const response = await fetch(`/api/barangays/${this.cityCode}`);
                              const barangays = await response.json();
                              const select = document.getElementById('barangay_code');
                              select.innerHTML = '<option value=\"\">Select barangay</option>';
                              barangays.forEach(b => {
                                  select.innerHTML += `<option value=\"${b.code}\">${b.name}</option>`;
                              });
                            }
                      x-init="if (cityCode) loadBarangays()">
                        }
                    }"
                    @csrf

                    <!-- Child's Personal Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Child's Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input id="first_name" name="first_name" type="text" required 
                                       value="{{ old('first_name') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Maria">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Middle Initial -->
                            <div>
                                <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">
                                    Middle Initial
                                </label>
                                <input id="middle_initial" name="middle_initial" type="text" 
                                       value="{{ old('middle_initial') }}"
                                       maxlength="10"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="C.">
                                @error('middle_initial')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input id="last_name" name="last_name" type="text" required 
                                       value="{{ old('last_name') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Santos">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Suffix -->
                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">
                                    Suffix
                                </label>
                                <select id="suffix" name="suffix"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">None</option>
                                    <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                                    <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                                    <option value="II" {{ old('suffix') == 'II' ? 'selected' : '' }}>II</option>
                                    <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                                    <option value="IV" {{ old('suffix') == 'IV' ? 'selected' : '' }}>IV</option>
                                    <option value="V" {{ old('suffix') == 'V' ? 'selected' : '' }}>V</option>
                                </select>
                                @error('suffix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Birthdate -->
                        <div class="mt-4">
                            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">
                                Birthdate <span class="text-red-500">*</span>
                            </label>
                            <input id="birthdate" name="birthdate" type="date" required 
                                   value="{{ old('birthdate') }}"
                                   x-model="birthdate"
                                   @change="calculateAge()"
                                   min="{{ now()->subYears(18)->format('Y-m-d') }}"
                                   max="{{ now()->subYears(5)->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            
                            <!-- Age Display -->
                            <div x-show="age !== null" class="mt-2">
                                <template x-if="age >= 5 && age <= 17">
                                    <div class="flex items-center text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                        <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Child is <strong x-text="age"></strong> years old - eligible for child account!</span>
                                    </div>
                                </template>
                                <template x-if="age < 5">
                                    <div class="flex items-center text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Child must be at least 5 years old to use the platform.</span>
                                    </div>
                                </template>
                                <template x-if="age >= 18">
                                    <div class="flex items-center text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>This person is 18+ and can create their own account.</span>
                                    </div>
                                </template>
                            </div>

                            @error('birthdate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Gender -->
                        <div class="mt-4">
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select id="gender" name="gender" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="prefer_not_to_say" {{ old('gender') === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Child's Location (Same Household as Parent) -->
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Location (Same Household)</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            📍 Your child lives with you, so we'll use your home address.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Municipality/City -->
                            <div>
                                <label for="city_code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Municipality/City (Cavite) <span class="text-red-500">*</span>
                                </label>
                                <select id="city_code" name="city_code" required
                                        x-model="cityCode" @change="loadBarangays()"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select municipality/city</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->code }}" {{ old('city_code', $parentProfile?->city_code) === $city->code ? 'selected' : '' }}>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('city_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Barangay -->
                            <div>
                                <label for="barangay_code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Barangay <span class="text-red-500">*</span>
                                </label>
                                <select id="barangay_code" name="barangay_code" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select municipality first</option>
                                    @if($parentProfile && $parentProfile->city_code && count($barangays) > 0)
                                        @foreach($barangays as $barangay)
                                            <option value="{{ $barangay->code }}" {{ old('barangay_code', $parentProfile?->barangay_code) === $barangay->code ? 'selected' : '' }}>
                                                {{ $barangay->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('barangay_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Account Credentials Section -->
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Login Credentials</h3>

                        <!-- Username -->
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input id="username" name="username" type="text" required 
                                   value="{{ old('username') }}"
                                   pattern="[a-z0-9_-]+"
                                   minlength="3"
                                   maxlength="30"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="maria_santos123">
                            <p class="mt-1 text-xs text-gray-500">
                                <strong>Important:</strong> This will be used to log in. Use lowercase letters, numbers, underscores, and hyphens only. Make it easy for your child to remember!
                            </p>
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input id="password" name="password" type="password" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="••••••••">
                                <p class="mt-1 text-xs text-gray-500">Choose a simple password for your child</p>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="••••••••">
                            </div>
                        </div>

                        <!-- Security Notice -->
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800">
                                        <strong>Important:</strong> Write down these credentials and keep them secure. 
                                        Your child will need them to log in.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Permissions (Always Enabled for Safety) -->
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">🔒 Your Parental Monitoring Access</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            For your child's safety and COPPA compliance, you will have the following access:
                        </p>
                        <div class="space-y-3 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">View Learning Progress</p>
                                    <p class="text-xs text-gray-600">See module completions, lesson views, and overall progress</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">View Quiz Answers</p>
                                    <p class="text-xs text-gray-600">See quiz attempts, selected answers, and scores</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-gray-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Content Approval (Coming Soon)</p>
                                    <p class="text-xs text-gray-500">Require your approval before child can access certain modules</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="{{ route('parent.children.index') }}" class="text-gray-600 hover:text-gray-700">Cancel</a>
                        <button type="submit" 
                                class="bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150">
                            Create Child Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- What Happens Next -->
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">After Creating the Account</h4>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Your child can log in using the username and password you created</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>View and monitor their activity from your parent dashboard</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You can create additional child accounts at any time</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
