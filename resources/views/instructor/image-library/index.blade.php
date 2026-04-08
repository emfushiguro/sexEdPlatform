@extends('layouts.instructor-app')

@section('content')
<div x-data='{
    images: @json($images),
    selectedImage: null,
    copiedImage: null,
    deleteModalOpen: false,
    deleteForm: null,
    init() {
        if (this.images.length > 0) {
            this.selectedImage = this.images[0];
        }
    },
    selectImage(image) {
        this.selectedImage = image;
    },
    formatUploaded(unixTs) {
        if (!unixTs) return "Unknown";
        return new Date(unixTs * 1000).toLocaleString();
    },
    formatSize(bytes) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    },
    copyFilename(filename) {
        navigator.clipboard.writeText(filename).then(() => {
            this.copiedImage = filename;
            setTimeout(() => this.copiedImage = null, 1200);
        });
    },
    openDeleteConfirm(form) {
        this.deleteForm = form;
        this.deleteModalOpen = true;
    },
    closeDeleteConfirm() {
        this.deleteModalOpen = false;
        this.deleteForm = null;
    },
    confirmDelete() {
        if (this.deleteForm) {
            this.deleteForm.submit();
        }
    }
}' class="space-y-6">

    <div class="rounded-3xl border border-indigo-100 dark:border-indigo-900/40 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 via-brand-500 to-cyan-500 px-6 py-5 text-white">
            <h1 class="text-xl font-bold">Image Library</h1>
            <p class="text-sm text-white/90 mt-1">Curate quiz assets with quick copy, preview metadata, and safe deletion controls.</p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('instructor.image-library.upload') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end" data-image-action-controls>
                @csrf
                <div class="md:col-span-2">
                    <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Image (JPG, PNG, max 2MB)</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/*"
                        required
                        class="w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                    >
                    @error('image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold transition-colors">Upload Image</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 rounded-3xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Gallery</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300" x-text="`${images.length} items`"></span>
            </div>

            <div id="image-gallery-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="image in images" :key="image.filename">
                    <article class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-md"
                             :class="selectedImage && selectedImage.filename === image.filename ? 'ring-2 ring-cyan-400' : ''"
                             @click="selectImage(image)">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <img :src="image.url" :alt="image.filename" class="w-full h-full object-cover">
                        </div>
                        <div class="p-2.5">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate" x-text="image.filename"></p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400" x-text="formatSize(image.size)"></p>
                        </div>
                    </article>
                </template>
            </div>

            <div x-show="images.length === 0" class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                No images uploaded yet. Upload your first asset to populate the gallery.
            </div>
        </section>

        <aside id="image-metadata-drawer" class="rounded-3xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5 h-fit">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Preview (Image Information)</h2>
            <template x-if="selectedImage">
                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700">
                        <img :src="selectedImage.url" :alt="selectedImage.filename" class="w-full h-40 object-cover">
                    </div>

                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Filename</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white break-all" x-text="selectedImage.filename"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">File Size</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white" x-text="formatSize(selectedImage.size)"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Uploaded</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white" x-text="formatUploaded(selectedImage.uploaded)"></dd>
                        </div>
                    </dl>

                    <div class="grid grid-cols-2 gap-2" data-image-action-controls>
                        <button type="button" @click="copyFilename(selectedImage.filename)" class="inline-flex justify-center items-center rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-semibold px-3 py-2 transition-colors" x-text="copiedImage === selectedImage.filename ? 'Copied!' : 'Copy Name'"></button>
                        <form method="POST" :action="`{{ url('instructor/image-library') }}/${selectedImage.filename}`" @submit.prevent="openDeleteConfirm($event.target)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-2 transition-colors">Delete</button>
                        </form>
                    </div>
                </div>
            </template>

            <template x-if="!selectedImage">
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Select an image from the gallery to view metadata and actions.</p>
            </template>
        </aside>
    </div>

    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDeleteConfirm()"></div>
    <div x-show="deleteModalOpen" x-cloak id="image-library-delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Image Deletion</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">This action permanently removes the selected image from your library.</p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" data-delete-confirm-cancel @click="closeDeleteConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                <button type="button" data-delete-confirm-submit @click="confirmDelete()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
