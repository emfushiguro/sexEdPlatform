@extends('layouts.instructor-app')

@section('content')
@php
    $hasPasswordErrors = $errors->hasAny(['current_password', 'password', 'password_confirmation']);
    $fallbackPhotoPath = $profile->profile_photo_path ?: $user->learnerProfile?->avatar_path;
    $fallbackPhotoUrl = $fallbackPhotoPath ? Storage::url($fallbackPhotoPath) : null;
@endphp

<div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ activeTab: '{{ $hasPasswordErrors ? 'password' : 'profile' }}' }">
    <section class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
        <div class="px-6 py-8 md:px-8" style="background: linear-gradient(120deg, #A30EB2 0%, #730DB1 55%, #3B0CB1 100%);">
            <h1 class="text-2xl font-bold text-white md:text-3xl">Edit Instructor Profile</h1>
            <p class="mt-2 max-w-2xl text-sm text-white/90">Update your professional details and specializations to stand out to learners.</p>
        </div>

        <div class="border-t border-brand-100 bg-white px-4 py-3 sm:px-6">
            <nav class="flex flex-wrap gap-2" aria-label="Profile tabs">
                <button type="button" @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                    Profile Information
                </button>
                <button type="button" @click="activeTab = 'details'" :class="activeTab === 'details' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                    Professional Details
                </button>
                <button type="button" @click="activeTab = 'password'" :class="activeTab === 'password' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                    Change Password
                </button>
            </nav>
        </div>
    </section>

    <form method="POST" action="{{ route('instructor.profile.update') }}" enctype="multipart/form-data" class="space-y-6" x-show="activeTab !== 'password'" x-cloak>
        @csrf
        @method('PUT')

        <section x-show="activeTab === 'profile'" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Basic Details</h2>

            <div class="mt-6 space-y-6">
                <div x-data="{ photoPreview: null }" class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <div class="h-24 w-24 overflow-hidden rounded-full border-2 border-brand-100 bg-gray-50">
                        <template x-if="!photoPreview">
                            <img src="{{ $fallbackPhotoUrl ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=1d4ed8&background=eff6ff' }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        </template>
                        <template x-if="photoPreview">
                            <img :src="photoPreview" alt="Profile photo preview" class="h-full w-full object-cover">
                        </template>
                    </div>

                    <div>
                        <input type="file" name="profile_photo" class="hidden" x-ref="photo" accept="image/*" @change="
                            const file = $refs.photo.files[0];
                            if (!file) return;
                            const reader = new FileReader();
                            reader.onload = (e) => { photoPreview = e.target.result; };
                            reader.readAsDataURL(file);
                        ">
                        <button type="button" @click="$refs.photo.click()" class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">Change avatar</button>
                        <p class="mt-2 text-xs text-gray-500">JPG, PNG or WEBP. Max 2MB.</p>
                        @error('profile_photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Professional Bio</label>
                    <textarea name="bio" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Expertise</label>
                        <input type="text" name="primary_expertise" value="{{ old('primary_expertise', $profile->primary_expertise) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('primary_expertise')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                        <input type="number" min="0" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('years_experience')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div x-data="{
                    tags: @js(old('expertise_tags', $profile->expertise_tags ?? [])),
                    newTag: '',
                    addTag() {
                        if (this.newTag.trim() === '') return;
                        this.tags.push(this.newTag.trim());
                        this.newTag = '';
                    },
                    removeTag(index) {
                        this.tags.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700">Expertise Tags</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-if="tags.length === 0">
                            <p class="text-sm italic text-gray-400">No tags added yet.</p>
                        </template>
                        <template x-for="(tag, index) in tags" :key="index">
                            <div class="inline-flex items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-medium text-brand-700">
                                <input type="hidden" :name="`expertise_tags[${index}]`" :value="tag">
                                <span x-text="tag"></span>
                                <button type="button" @click="removeTag(index)" class="text-brand-500 transition hover:text-brand-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <input type="text" x-model="newTag" @keydown.enter.prevent="addTag()" placeholder="Add a tag" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-sm sm:text-sm">
                        <button type="button" @click="addTag()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-300 transition hover:bg-brand-50">Add</button>
                    </div>
                    @error('expertise_tags')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section x-show="activeTab === 'details'" x-cloak class="space-y-6">
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Professional Details</h2>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700">Professional Background</label>
                    <textarea name="professional_background" rows="5" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('professional_background', $profile->professional_background) }}</textarea>
                    @error('professional_background')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" x-data="{
                entries: @js(old('educational_background_entries', $educationalEntriesForForm ?? [])),
                addEntry() {
                    this.entries.push({ school_name: '', degree_program: '', graduation_date: '' });
                },
                removeEntry(index) {
                    this.entries.splice(index, 1);
                }
            }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Educational Background</h2>
                        <p class="text-sm text-gray-600">Add one or more educational entries with graduation dates.</p>
                    </div>
                    <button type="button" @click="addEntry()" class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">Add Entry</button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-if="entries.length === 0">
                        <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400">No educational entries yet.</p>
                    </template>

                    <template x-for="(entry, index) in entries" :key="index">
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">School Name</label>
                                    <input type="text" x-model="entry.school_name" :name="`educational_background_entries[${index}][school_name]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Degree or Program</label>
                                    <input type="text" x-model="entry.degree_program" :name="`educational_background_entries[${index}][degree_program]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Graduation Date</label>
                                    <input type="date" x-model="entry.graduation_date" :name="`educational_background_entries[${index}][graduation_date]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="button" @click="removeEntry(index)" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-red-600 ring-1 ring-inset ring-red-200 transition hover:bg-red-50">Remove Entry</button>
                            </div>
                        </div>
                    </template>
                </div>
                @error('educational_background_entries')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('educational_background_entries.*.school_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('educational_background_entries.*.degree_program')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('educational_background_entries.*.graduation_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" x-data="{
                certProofBaseUrl: @js(Storage::url('/')),
                filePreviews: {},
                certifications: @js(old('certifications', $certificationsForForm ?? [])),
                addCertification() {
                    this.certifications.push({ title: '', organization: '', completion_date: '', attachment_path: '', existing_attachment: '' });
                },
                removeCertification(index) {
                    const preview = this.filePreviews[index];
                    if (preview && preview.source === 'upload' && preview.url.startsWith('blob:')) {
                        URL.revokeObjectURL(preview.url);
                    }

                    this.certifications.splice(index, 1);

                    const shiftedPreviews = {};
                    Object.entries(this.filePreviews).forEach(([key, value]) => {
                        const currentIndex = Number(key);
                        if (currentIndex < index) {
                            shiftedPreviews[currentIndex] = value;
                        } else if (currentIndex > index) {
                            shiftedPreviews[currentIndex - 1] = value;
                        }
                    });
                    this.filePreviews = shiftedPreviews;
                },
                resolveProofPath(certification) {
                    return certification.attachment_path || certification.existing_attachment || '';
                },
                resolveProofUrl(certification) {
                    const path = this.resolveProofPath(certification);
                    if (!path) return '';
                    if (path.startsWith('http://') || path.startsWith('https://')) {
                        return path;
                    }
                    return this.certProofBaseUrl + path.replace(/^\/+/, '');
                },
                resolveProofType(fileName) {
                    if (!fileName) return 'other';
                    const extension = fileName.split('.').pop()?.toLowerCase() || '';
                    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) return 'image';
                    if (extension === 'pdf') return 'pdf';
                    return 'other';
                },
                getPreview(index, certification) {
                    if (this.filePreviews[index]) {
                        return this.filePreviews[index];
                    }

                    const url = this.resolveProofUrl(certification);
                    if (!url) return null;

                    return {
                        url,
                        type: this.resolveProofType(this.resolveProofPath(certification)),
                        source: 'existing'
                    };
                },
                onFileSelected(event, index) {
                    const file = event.target.files[0];
                    const existingPreview = this.filePreviews[index];
                    if (existingPreview && existingPreview.source === 'upload' && existingPreview.url.startsWith('blob:')) {
                        URL.revokeObjectURL(existingPreview.url);
                    }

                    if (!file) {
                        delete this.filePreviews[index];
                        this.filePreviews = { ...this.filePreviews };
                        return;
                    }

                    const type = file.type.startsWith('image/')
                        ? 'image'
                        : (file.type === 'application/pdf' ? 'pdf' : this.resolveProofType(file.name));

                    this.filePreviews[index] = {
                        url: URL.createObjectURL(file),
                        type,
                        source: 'upload'
                    };
                    this.filePreviews = { ...this.filePreviews };
                }
            }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Certifications</h2>
                        <p class="text-sm text-gray-600">Include title, issuing organization, completion date, and optional proof file.</p>
                    </div>
                    <button type="button" @click="addCertification()" class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">Add Certification</button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-if="certifications.length === 0">
                        <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400">No certifications added yet.</p>
                    </template>

                    <template x-for="(certification, index) in certifications" :key="index">
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Certification Title</label>
                                    <input type="text" x-model="certification.title" :name="`certifications[${index}][title]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Issuing Organization</label>
                                    <input type="text" x-model="certification.organization" :name="`certifications[${index}][organization]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Completion Date</label>
                                    <input type="date" x-model="certification.completion_date" :name="`certifications[${index}][completion_date]`" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Certificate Proof (Optional)</label>
                                    <input type="hidden" :name="`certifications[${index}][existing_attachment]`" :value="certification.attachment_path || certification.existing_attachment || ''">
                                    <input type="file" :name="`certifications[${index}][attachment]`" accept=".pdf,.jpg,.jpeg,.png" @change="onFileSelected($event, index)" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                    <p class="mt-1 text-xs text-gray-500">Supported formats: PDF, JPG, PNG. Max 5MB.</p>

                                    <div x-show="getPreview(index, certification)" x-cloak class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <template x-if="getPreview(index, certification)?.type === 'image'">
                                            <img :src="getPreview(index, certification).url" alt="Certificate proof image preview" class="h-32 w-full object-cover">
                                        </template>

                                        <template x-if="getPreview(index, certification)?.type === 'pdf'">
                                            <iframe :src="getPreview(index, certification).url" class="h-32 w-full" title="Certificate proof PDF preview"></iframe>
                                        </template>

                                        <template x-if="getPreview(index, certification)?.type !== 'image' && getPreview(index, certification)?.type !== 'pdf'">
                                            <div class="flex h-32 items-center justify-center bg-gray-50 px-3 text-center">
                                                <p class="text-xs font-medium text-gray-500">Preview is unavailable for this file type.</p>
                                            </div>
                                        </template>

                                        <div class="flex items-center justify-between border-t border-gray-200 px-3 py-2">
                                            <p class="text-xs font-medium text-gray-500" x-text="getPreview(index, certification)?.source === 'upload' ? 'New upload preview' : 'Current attachment preview'"></p>
                                            <a :href="getPreview(index, certification)?.url" target="_blank" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Open</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="button" @click="removeCertification(index)" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-red-600 ring-1 ring-inset ring-red-200 transition hover:bg-red-50">Remove Certification</button>
                            </div>
                        </div>
                    </template>
                </div>
                @error('certifications')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('certifications.*.title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('certifications.*.organization')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('certifications.*.completion_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('certifications.*.attachment')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" x-data="{
                credentials: @js(old('credentials', $profile->credentials ?? [])),
                newCredential: '',
                addCredential() {
                    if (this.newCredential.trim() === '') return;
                    this.credentials.push(this.newCredential.trim());
                    this.newCredential = '';
                },
                removeCredential(index) {
                    this.credentials.splice(index, 1);
                }
            }">
                <h2 class="text-lg font-semibold text-gray-900">Credentials</h2>
                <p class="text-sm text-gray-600">List your relevant degrees, titles, or professional designations.</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-if="credentials.length === 0">
                        <p class="text-sm italic text-gray-400">No credentials listed yet.</p>
                    </template>

                    <template x-for="(credential, index) in credentials" :key="index">
                        <div class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                            <input type="hidden" :name="`credentials[${index}]`" :value="credential">
                            <span x-text="credential"></span>
                            <button type="button" @click="removeCredential(index)" class="text-gray-400 transition hover:text-red-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input type="text" x-model="newCredential" @keydown.enter.prevent="addCredential()" placeholder="Add credential" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-sm sm:text-sm">
                    <button type="button" @click="addCredential()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50">Add</button>
                </div>
                @error('credentials')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </article>
        </section>

        <div class="flex items-center justify-end gap-4 py-2">
            <a href="{{ route('instructor.profile.show') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Cancel</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Save Profile Changes</button>
        </div>
    </form>

    <section x-show="activeTab === 'password'" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Update Password</h2>
        <p class="mt-1 text-sm text-gray-600">For security, your current password is required.</p>

        <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 max-w-xl space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                <input type="password" name="current_password" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                @error('password_confirmation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="rounded-xl bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900">Update Password</button>
            </div>
        </form>
    </section>
</div>
@endsection

