<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-40 h-40 bg-white/5 rounded-br-full"></div>
            <div class="absolute bottom-0 right-0 w-56 h-56 bg-white/5 rounded-tl-full"></div>

            <div class="relative mb-6 z-10">
                <div class="w-32 h-32 bg-white/15 rounded-3xl flex items-center justify-center shadow-2xl backdrop-blur-sm border border-white/20">
                    <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="w-24 h-24 object-contain drop-shadow-lg">
                </div>
            </div>

            <h1 class="text-2xl font-bold text-white mb-1 tracking-wide z-10">Concious Connections</h1>
            <div class="w-12 h-0.5 bg-white/40 rounded-full mx-auto mb-6 z-10"></div>

            <h2 class="text-3xl font-bold text-white mb-3 leading-tight z-10">Guide their journey</h2>
            <p class="text-white/75 text-base max-w-[200px] leading-relaxed z-10">Register as a parent or guardian</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Personal Info', 'active' => true, 'done' => false],
        ['label' => 'Account Info', 'active' => false, 'done' => false],
        ['label' => 'Verify Email', 'active' => false, 'done' => false],
        ['label' => 'Profile', 'active' => false, 'done' => false],
    ]" />

    <!-- Header -->
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-purple-900">Personal Information</h2>
        <p class="mt-1 text-sm text-gray-500">Step 1 of 2 — Tell us about yourself</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-5 rounded-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.register.store') }}"
          x-data="{
              birthdate: '{{ old('birthdate') }}',
              age: null,
              calculateAge() {
                  if (!this.birthdate) { this.age = null; return; }
                  const today = new Date();
                  const birth = new Date(this.birthdate);
                  let age = today.getFullYear() - birth.getFullYear();
                  const m = today.getMonth() - birth.getMonth();
                  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
                  this.age = age;
              }
          }"
          x-init="calculateAge()">
        @csrf

        <div class="space-y-4">

            <!-- First Name + Last Name -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input id="first_name" name="first_name" type="text" required value="{{ old('first_name') }}"
                           placeholder="Juan"
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input id="last_name" name="last_name" type="text" required value="{{ old('last_name') }}"
                           placeholder="dela Cruz"
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Middle Initial + Suffix -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">Middle Initial <span class="text-gray-400 font-normal text-xs">(Optional)</span></label>
                    <input id="middle_initial" name="middle_initial" type="text" value="{{ old('middle_initial') }}"
                           maxlength="10" placeholder="D."
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('middle_initial')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-gray-400 font-normal text-xs">(Optional)</span></label>
                    <select id="suffix" name="suffix"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                        <option value="">-- None --</option>
                        <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                        <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                        <option value="II"  {{ old('suffix') == 'II'  ? 'selected' : '' }}>II</option>
                        <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                        <option value="IV"  {{ old('suffix') == 'IV'  ? 'selected' : '' }}>IV</option>
                        <option value="V"   {{ old('suffix') == 'V'   ? 'selected' : '' }}>V</option>
                    </select>
                    @error('suffix')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Birthdate -->
            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                <input id="birthdate" name="birthdate" type="date"
                       x-model="birthdate" @change="calculateAge()"
                       value="{{ old('birthdate') }}" required
                       min="{{ now()->subYears(100)->format('Y-m-d') }}"
                       max="{{ now()->subYears(18)->format('Y-m-d') }}"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">

                <div x-show="age !== null" class="mt-2">
                    <div x-show="age >= 18" class="flex items-center text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        You are <strong class="mx-1" x-text="age"></strong> years old — eligible!
                    </div>
                    <div x-show="age < 18" class="flex items-center text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        You must be 18 or older to register as a parent.
                    </div>
                </div>
                @error('birthdate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Continue button -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full bg-brand-purple-primary text-white py-3 px-6 rounded-xl font-semibold text-sm hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    Continue — Account Info →
                </button>
                <div class="mt-4 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('learner.login') }}" class="text-brand-purple-primary font-medium hover:underline">Login</a>
                </div>
            </div>

        </div>
    </form>

</x-auth-split-layout>
