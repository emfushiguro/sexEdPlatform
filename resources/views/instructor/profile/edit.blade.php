@extends('layouts.instructor-app')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <div class="bg-white shadow-sm sm:rounded-xl p-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Instructor Profile</h1>
        <p class="text-sm text-gray-600 mt-1">Update your professional details and specializations to stand out to learners.</p>
    </div>

    <form method="POST" action="{{ route('instructor.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Details Card -->
        <div class="bg-white shadow-sm sm:rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-3 mb-6">Basic Details</h2>
            
            <div class="space-y-6">
                <!-- Avatar Upload -->
                <div x-data="{ photoName: null, photoPreview: null }" class="flex items-center gap-x-6">
                    <div class="shrink-0 relative h-24 w-24 rounded-full overflow-hidden border-2 border-blue-100 bg-gray-50 flex items-center justify-center">
                        <template x-if="!photoPreview">
                            <img src="{{ $profile->profile_photo_path ? Storage::url($profile->profile_photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=1d4ed8&background=eff6ff' }}" class="h-full w-full object-cover">
                        </template>
                        <template x-if="photoPreview">
                            <img :src="photoPreview" class="h-full w-full object-cover">
                        </template>
                    </div>
                    <div>
                        <input type="file" name="profile_photo" class="hidden" x-ref="photo" accept="image/*" @change="
                                photoName = $refs.photo.files[0].name;
                                const reader = new FileReader();
                                reader.onload = (e) => { photoPreview = e.target.result; };
                                reader.readAsDataURL($refs.photo.files[0]);
                            ">
                        <button type="button" @click="$refs.photo.click()" class="rounded-lg bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm ring-1 ring-inset ring-blue-200 hover:bg-blue-100 transition-colors">Change avatar</button>
                        <p class="mt-2 text-xs text-gray-500">JPG, PNG or WEBP. Max 2MB.</p>
                        @error('profile_photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Professional Bio</label>
                    <textarea name="bio" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Expertise</label>
                        <input type="text" name="primary_expertise" value="{{ old('primary_expertise', $profile->primary_expertise) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('primary_expertise')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                        <input type="number" min="0" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('years_experience')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Expanded Background Card -->
        <div class="bg-white shadow-sm sm:rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-3 mb-6">Detailed Background</h2>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Educational Background</label>
                    <input type="text" name="educational_background" value="{{ old('educational_background', $profile->educational_background) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('educational_background')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Professional Background</label>
                    <textarea name="professional_background" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('professional_background', $profile->professional_background) }}</textarea>
                    @error('professional_background')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Dynamic Arrays Card -->
        <div class="bg-white shadow-sm sm:rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-3 mb-6">Skills & Credentials</h2>
            
            <div class="space-y-8">
                <!-- Expertise Tags -->
                <div x-data="{
                    tags: {{ json_encode(old('expertise_tags', $profile->expertise_tags ?? [])) }},
                    newTag: '',
                    addTag() {
                        if(this.newTag.trim() !== '') {
                            this.tags.push(this.newTag.trim());
                            this.newTag = '';
                        }
                    },
                    removeTag(index) {
                        this.tags.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expertise Tags</label>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <template x-if="tags.length === 0">
                            <span class="text-sm text-gray-400 italic">No tags added yet.</span>
                        </template>
                        <template x-for="(tag, index) in tags" :key="index">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg">
                                <input type="hidden" :name="`expertise_tags[${index}]`" :value="tag">
                                <span x-text="tag"></span>
                                <button type="button" @click="removeTag(index)" class="text-blue-500 hover:text-blue-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="newTag" @keydown.enter.prevent="addTag()" placeholder="Add a tag..." class="block w-full max-w-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <button type="button" @click="addTag()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm ring-1 ring-inset ring-blue-300 hover:bg-blue-50 transition-colors">Add</button>
                    </div>
                    @error('expertise_tags')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Certifications -->
                <div x-data="{
                    items: {{ json_encode(old('certifications', $profile->certifications ?? [])) }},
                    newItem: '',
                    addItem() {
                        if(this.newItem.trim() !== '') {
                            this.items.push(this.newItem.trim());
                            this.newItem = '';
                        }
                    },
                    removeItem(index) {
                        this.items.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certifications</label>
                    <div class="flex flex-col gap-2 mb-3 max-w-2xl">
                        <template x-if="items.length === 0">
                            <span class="text-sm text-gray-400 italic">No certifications added yet.</span>
                        </template>
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg">
                                <input type="hidden" :name="`certifications[${index}]`" :value="item">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button type="button" @click="removeItem(index)" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-2 max-w-2xl">
                        <input type="text" x-model="newItem" @keydown.enter.prevent="addItem()" placeholder="Add certification..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <button type="button" @click="addItem()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors">Add</button>
                    </div>
                    @error('certifications')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Credentials -->
                <div x-data="{
                    items: {{ json_encode(old('credentials', $profile->credentials ?? [])) }},
                    newItem: '',
                    addItem() {
                        if(this.newItem.trim() !== '') {
                            this.items.push(this.newItem.trim());
                            this.newItem = '';
                        }
                    },
                    removeItem(index) {
                        this.items.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Credentials (Degrees, Titles)</label>
                    <div class="flex flex-col gap-2 mb-3 max-w-2xl">
                        <template x-if="items.length === 0">
                            <span class="text-sm text-gray-400 italic">No credentials added yet.</span>
                        </template>
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg">
                                <input type="hidden" :name="`credentials[${index}]`" :value="item">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button type="button" @click="removeItem(index)" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-2 max-w-2xl">
                        <input type="text" x-model="newItem" @keydown.enter.prevent="addItem()" placeholder="Add credential..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <button type="button" @click="addItem()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors">Add</button>
                    </div>
                    @error('credentials')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        <div class="flex items-center justify-end gap-4 py-2">
            <a href="{{ route('instructor.profile.show') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Cancel</a>
            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors">Save Profile Changes</button>
        </div>
    </form>

    <div class="bg-white shadow-sm sm:rounded-xl p-6 mt-8">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-3 mb-4">Update Password</h2>    
        <p class="text-sm text-gray-600 mb-6">For security, your current password is required.</p>
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4 max-w-xl">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                <input type="password" name="current_password" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div class="flex justify-start pt-2">
                <button type="submit" class="rounded-xl bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 transition-colors">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection

