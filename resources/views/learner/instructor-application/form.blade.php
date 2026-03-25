@extends('layouts.learner-app')

@section('title', 'Apply as Instructor')

@section('content')
<div x-data="instructorWizard()" class="max-w-4xl mx-auto space-y-6">

    <div :class="showModal ? 'blur-md scale-[0.99] pointer-events-none select-none' : ''" class="transition duration-300 ease-out">

    {{-- Error Alert Banner (if server validation fails on submit) --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 mb-6 relative" x-init="showModal = true; step = 1">
            <h3 class="text-sm font-semibold text-red-800">There were errors with your submission:</h3>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Landing Card --}}
    <div class="rounded-2xl border border-purple-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-purple-700 to-indigo-800 p-8 sm:p-12 text-center">
            <div class="w-20 h-16 mx-auto mb-4 text-purple-200">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 tracking-tight">Become an Instructor</h1>
            <p class="text-lg text-purple-100 max-w-2xl mx-auto">
                Join our community of verified educators. Share your knowledge and make an impact. Provide your credentials and let's get started.
            </p>
        </div>
        <div class="px-8 py-10 text-center bg-gray-50/50">
            <h2 class="text-gray-900 font-semibold mb-6">What you'll need before starting:</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto text-left mb-10">
                <div class="flex items-start gap-4 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 text-purple-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">Identity & Clearance</h3>
                        <p class="text-sm text-gray-500 mt-1">Government ID and an NBI/Police clearance to verify your identity.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 text-indigo-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">Teaching Expertise</h3>
                        <p class="text-sm text-gray-500 mt-1">At least one credential, certificate, or professional license.</p>
                    </div>
                </div>
            </div>
            <button type="button" @click="showModal = true" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-purple-500/30 hover:bg-purple-800 hover:shadow-xl hover:shadow-purple-500/40 transition-all active:scale-95 text-center">
                Start Application
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            </button>
        </div>
    </div>
    </div>

    {{-- Wizard Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900/65 backdrop-blur-md transition-opacity" @click="showModal = false"></div>

        <!-- Modal Panel -->
        <div x-show="showModal" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all w-full max-w-3xl max-h-[90vh] flex flex-col">
            
            {{-- Modal Header with Stepper --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900" id="modal-title">Apply as an Educator</h2>
                    <button type="button" @click="showModal = false" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                {{-- Reactive Stepper Visual --}}
                <div class="max-w-xl mx-auto">
                    <div class="flex items-center justify-between relative">
                        <template x-for="s in [1,2,3]" :key="s">
                            <div class="flex flex-col items-center relative z-10 w-24">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 shadow-sm"
                                     :class="{
                                         'bg-gradient-to-br from-purple-600 to-indigo-700 text-white ring-4 ring-purple-100': step === s,
                                         'bg-purple-600 text-white': step > s,
                                         'bg-white border-2 border-gray-200 text-gray-400': step < s
                                     }">
                                    <template x-if="step > s">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </template>
                                    <template x-if="step <= s">
                                        <span class="text-sm font-bold" x-text="s"></span>
                                    </template>
                                </div>
                                <span class="mt-2 text-xs font-semibold uppercase tracking-wider text-center"
                                      :class="step >= s ? 'text-purple-700' : 'text-gray-400'"
                                      x-text="s === 1 ? 'Documents' : (s === 2 ? 'Expertise' : 'Confirm')"></span>
                            </div>
                        </template>
                        <!-- Connecting Lines -->
                        <div class="absolute top-5 left-12 right-12 h-0.5 bg-gray-200 -z-10">
                            <div class="h-full bg-purple-600 transition-all duration-500 ease-in-out"
                                 :style="'width: ' + ((step - 1) / 2 * 100) + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Body --}}
            <form id="instructorApplicationForm" method="POST" action="{{ route('learner.instructor.apply.submit') }}" enctype="multipart/form-data" class="flex flex-col h-full overflow-hidden">
                @csrf
                
                <div class="p-6 overflow-y-auto flex-1 bg-white">
                    {{-- STEP 1: Tier 1 Required Documents --}}
                    <div x-show="step === 1" x-transition.opacity class="space-y-6">
                        <div class="mb-2">
                            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Tier 1: Basic Requirements</h3>
                            <p class="mt-2 text-sm text-gray-500">Please provide your basic identity and background information.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div x-data="{ fileName: null, previewUrl: null }">
                                <label for="government_id" class="block text-sm font-semibold text-gray-700 mb-1">Government ID <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <input id="government_id" name="government_id" type="file" accept=".pdf,.jpg,.jpeg,.png" x-bind:required="step === 1"
                                           @change="
                                                const file = $event.target.files && $event.target.files.length ? $event.target.files[0] : null;
                                                if (!file) {
                                                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                    fileName = null;
                                                    previewUrl = null;
                                                    return;
                                                }
                                                fileName = file.name;
                                                if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                previewUrl = file.type && file.type.startsWith('image/')
                                                    ? URL.createObjectURL(file)
                                                    : null;
                                           "
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 border border-gray-200 rounded-lg p-1.5 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all">
                                </div>
                                <div x-show="fileName" class="mt-2 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3" x-cloak>
                                    <template x-if="previewUrl">
                                        <div class="relative group cursor-zoom-in" @click.stop="zoomImage = previewUrl">
                                            <img :src="previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200 group-hover:opacity-75 transition-opacity">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                    </template>
                                    <span x-text="fileName" class="text-xs text-gray-600 font-medium truncate"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Accepted: JPG, PNG, PDF. Max size: 5MB.</p>
                                @error('government_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div x-data="{ fileName: null, previewUrl: null }">
                                <label for="clearance" class="block text-sm font-semibold text-gray-700 mb-1">NBI / Police Clearance <span class="text-red-500">*</span></label>
                                <div>
                                    <input id="clearance" name="clearance" type="file" accept=".pdf,.jpg,.jpeg,.png" x-bind:required="step === 1"
                                           @change="
                                                const file = $event.target.files && $event.target.files.length ? $event.target.files[0] : null;
                                                if (!file) {
                                                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                    fileName = null;
                                                    previewUrl = null;
                                                    return;
                                                }
                                                fileName = file.name;
                                                if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                previewUrl = file.type && file.type.startsWith('image/')
                                                    ? URL.createObjectURL(file)
                                                    : null;
                                           "
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 border border-gray-200 rounded-lg p-1.5 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all">
                                </div>
                                <div x-show="fileName" class="mt-2 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3" x-cloak>
                                    <template x-if="previewUrl">
                                        <div class="relative group cursor-zoom-in" @click.stop="zoomImage = previewUrl">
                                            <img :src="previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200 group-hover:opacity-75 transition-opacity">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                    </template>
                                    <span x-text="fileName" class="text-xs text-gray-600 font-medium truncate"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Accepted: JPG, PNG, PDF. Max size: 5MB.</p>
                                @error('clearance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="pt-2">
                            <label for="educational_background" class="block text-sm font-semibold text-gray-700 mb-1">Educational Background <span class="text-red-500">*</span></label>
                            <select id="educational_background" name="educational_background" x-model="eduBg" required
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500/20 shadow-sm py-2.5">
                                <option value="" disabled>Select your highest educational attainment...</option>
                                <option value="high_school">High School Graduate</option>
                                <option value="college_undergrad">College Undergraduate</option>
                                <option value="college_graduate">College Graduate</option>
                                <option value="masters">Master's Degree</option>
                                <option value="doctorate">Doctorate Degree</option>
                                <option value="other">Other</option>
                            </select>

                            <div x-show="eduBg === 'other'" x-collapse class="mt-3">
                                <label for="educational_background_other" class="block text-sm font-semibold text-gray-700 mb-1">Please specify <span class="text-red-500">*</span></label>
                                <input type="text" id="educational_background_other" name="educational_background_other" value="{{ old('educational_background_other') }}"
                                       class="w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500/20 shadow-sm py-2.5"
                                       placeholder="Enter your educational background"
                                       x-bind:required="eduBg === 'other'">
                                @error('educational_background_other') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @error('educational_background') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-2">
                            <div class="flex justify-between items-end mb-1">
                                <label for="bio" class="block text-sm font-semibold text-gray-700">Professional Background <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <span class="text-xs font-medium" :class="bioLength > 500 ? 'text-red-500' : 'text-gray-400'" x-text="bioLength + ' / 500 characters'"></span>
                            </div>
                            <textarea id="bio" name="bio" rows="4" x-model="bioText"
                                      class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500/20 resize-none" 
                                      placeholder="Describe your teaching, counseling, or health education background."></textarea>
                            @error('bio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- STEP 2: Tier 2 Expertise --}}
                    <div x-show="step === 2" x-transition.opacity class="space-y-6" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Tier 2: Proof of Expertise</h3>
                            <div class="mt-3 flex items-start gap-2 bg-indigo-50 border border-indigo-100 p-3 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-sm text-indigo-800">You must provide <strong>at least one</strong> of the following documents to establish your expertise in the subject matter.</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div x-data="{ fileName: null, previewUrl: null }" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 transition-colors">
                                <label for="teaching_credential" class="block text-sm font-semibold text-gray-800 mb-1">Teaching Credential</label>
                                <p class="text-xs text-gray-500 mb-3">Copy of your teaching license or certification.</p>
                                <input id="teaching_credential" name="teaching_credential" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                       @change="
                                            const file = $event.target.files && $event.target.files.length ? $event.target.files[0] : null;
                                            if (!file) {
                                                if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                fileName = null;
                                                previewUrl = null;
                                                return;
                                            }
                                            fileName = file.name;
                                            if (previewUrl) URL.revokeObjectURL(previewUrl);
                                            previewUrl = file.type && file.type.startsWith('image/')
                                                ? URL.createObjectURL(file)
                                                : null;
                                       "
                                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 outline-none transition-all">
                                <div x-show="fileName" class="mt-3 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3" x-cloak>
                                    <template x-if="previewUrl">
                                        <div class="relative group cursor-zoom-in" @click.stop="zoomImage = previewUrl">
                                            <img :src="previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200 group-hover:opacity-75 transition-opacity">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                    </template>
                                    <span x-text="fileName" class="text-xs text-gray-600 font-medium truncate"></span>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">Accepted: JPG, PNG, PDF. Max size: 5MB.</p>
                                @error('teaching_credential') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div x-data="{ fileName: null, previewUrl: null }" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 transition-colors">
                                <label for="sexed_certificate" class="block text-sm font-semibold text-gray-800 mb-1">Sex Education Training Certificate</label>
                                <p class="text-xs text-gray-500 mb-3">A valid certificate from recognized health or sex education training.</p>
                                <input id="sexed_certificate" name="sexed_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                       @change="
                                            const file = $event.target.files && $event.target.files.length ? $event.target.files[0] : null;
                                            if (!file) {
                                                if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                fileName = null;
                                                previewUrl = null;
                                                return;
                                            }
                                            fileName = file.name;
                                            if (previewUrl) URL.revokeObjectURL(previewUrl);
                                            previewUrl = file.type && file.type.startsWith('image/')
                                                ? URL.createObjectURL(file)
                                                : null;
                                       "
                                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 outline-none transition-all">
                                <div x-show="fileName" class="mt-3 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3" x-cloak>
                                    <template x-if="previewUrl">
                                        <div class="relative group cursor-zoom-in" @click.stop="zoomImage = previewUrl">
                                            <img :src="previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200 group-hover:opacity-75 transition-opacity">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                    </template>
                                    <span x-text="fileName" class="text-xs text-gray-600 font-medium truncate"></span>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">Accepted: JPG, PNG, PDF. Max size: 5MB.</p>
                                @error('sexed_certificate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div x-data="{ fileName: null, previewUrl: null }" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 transition-colors">
                                <label for="professional_license" class="block text-sm font-semibold text-gray-800 mb-1">Professional License</label>
                                <p class="text-xs text-gray-500 mb-3">Relevant licenses (e.g. Registered Nurse, Medical Doctor, Psychologist).</p>
                                <input id="professional_license" name="professional_license" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                       @change="
                                            const file = $event.target.files && $event.target.files.length ? $event.target.files[0] : null;
                                            if (!file) {
                                                if (previewUrl) URL.revokeObjectURL(previewUrl);
                                                fileName = null;
                                                previewUrl = null;
                                                return;
                                            }
                                            fileName = file.name;
                                            if (previewUrl) URL.revokeObjectURL(previewUrl);
                                            previewUrl = file.type && file.type.startsWith('image/')
                                                ? URL.createObjectURL(file)
                                                : null;
                                       "
                                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 outline-none transition-all">
                                <div x-show="fileName" class="mt-3 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3" x-cloak>
                                    <template x-if="previewUrl">
                                        <img :src="previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200">
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                    </template>
                                    <span x-text="fileName" class="text-xs text-gray-600 font-medium truncate"></span>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">Accepted: JPG, PNG, PDF. Max size: 5MB.</p>
                                @error('professional_license') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        
                        @error('tier2') 
                        <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-600 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    {{-- STEP 3: Confirmation --}}
                    <div x-show="step === 3" x-transition.opacity class="space-y-6" x-cloak>
                        <div class="mb-4 text-center">
                            <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Final Confirmation</h3>
                            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Please confirm that all the documents and information you've provided are accurate and authentic. Falsified documents will result in account suspension.</p>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mt-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <div class="flex items-center h-6">
                                    <input type="checkbox" name="confirmation" value="1" required
                                           class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500 transition-colors cursor-pointer" {{ old('confirmation') ? 'checked' : '' }}>
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 mb-1 group-hover:text-purple-700 transition-colors">I accept full responsibility</span>
                                    <span class="block text-sm text-gray-500">I confirm that all submitted information is authentic, belongs to me, and I agree to the platform's terms for educators.</span>
                                </div>
                            </label>
                            @error('confirmation') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0 rounded-b-2xl">
                    <button type="button" 
                            x-show="step > 1" 
                            @click="step--" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        Back
                    </button>
                    <div x-show="step === 1" class="w-20"></div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="showModal = false" class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors px-4">Cancel</button>
                        
                        <button type="button" 
                                x-show="step < 3" 
                                @click="nextStep()" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                            Continue
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        
                        <button type="submit" 
                                x-show="step === 3" 
                                x-cloak
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                            Submit Application
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Lightbox Zoom Modal --}}
    <div x-show="zoomImage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4" 
         @click="zoomImage = null"
         style="display: none;">
        
        <!-- Close Button -->
        <button @click="zoomImage = null" class="absolute top-4 right-4 text-white/70 hover:text-white transition-colors p-2 rounded-full hover:bg-white/10">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Image Container -->
        <div class="relative max-w-5xl max-h-screen w-full flex items-center justify-center" @click.stop>
            <img :src="zoomImage" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl ring-1 ring-white/20">
        </div>
    </div>
</div>

<script>
    function instructorWizard() {
        return {
            showModal: {{ $errors->any() ? 'true' : 'false' }},
            zoomImage: null, // For lightbox
            step: 1,
            eduBg: @json(old('educational_background', '')),
            bioText: @json(old('bio', '')),
            get bioLength() {
                return this.bioText ? this.bioText.length : 0;
            },
            nextStep() {
                // simple client-side validation logic
                if (this.step === 1) {
                    const govId = document.getElementById('government_id').value;
                    const clearance = document.getElementById('clearance').value;
                    
                    if (this.eduBg === 'other') {
                        const otherBg = document.getElementById('educational_background_other').value;
                        if (!otherBg.trim()) {
                            alert("Please specify your educational background.");
                            return;
                        }
                    }
                    
                    // If no files attached yet on first load, browser native validation kicks in on sumbit, 
                    // but for wizard next-step we just bypass if strict client side isn't perfect, 
                    // or we check required fields manually.
                    
                    // We'll let them pass to step 2 visually, server validates everything on submit.
                    // But to prevent obvious errors:
                    if (this.bioLength > 500) {
                        alert("Professional background must not exceed 500 characters.");
                        return;
                    }
                    this.step++;
                } else if (this.step === 2) {
                    this.step++;
                }
            }
        }
    }
</script>
@endsection
