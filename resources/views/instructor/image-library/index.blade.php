@extends('layouts.instructor')
@section('title', 'Image Library')
@section('page-title', 'Image Library')
@section('content')

<!-- Upload Form -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Upload New Image</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload images to use in identification questions. Reference by filename in CSV imports.</p>
    </div>
    <div class="p-6">
        <form method="POST" action="{{ route('instructor.image-library.upload') }}" enctype="multipart/form-data" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Select Image <span class="text-gray-400 dark:text-gray-500 font-normal">(JPG, PNG, max 2MB)</span>
                </label>
                <input type="file" id="image" name="image" accept="image/*" required
                    class="w-full text-sm text-gray-500 dark:text-gray-400
                           file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700
                           dark:file:bg-brand-500/10 dark:file:text-brand-400
                           hover:file:bg-brand-100 dark:hover:file:bg-brand-500/20 file:transition-colors">
                @error('image')
                    <p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                Upload
            </button>
        </form>
    </div>
</div>

<!-- Image Grid -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Your Images <span class="text-gray-400 dark:text-gray-500 font-normal">({{ count($images) }})</span></h3>
    </div>
    <div class="p-6">
        @if(count($images) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($images as $image)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-brand-300 dark:hover:border-brand-600 hover:shadow-theme-sm transition-all" x-data="{ copied: false }">
                    <!-- Preview -->
                    <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                        <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" class="w-full h-full object-cover">
                    </div>
                    <!-- Info + Actions -->
                    <div class="p-3 bg-white dark:bg-white/[0.02]">
                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate mb-0.5" title="{{ $image['filename'] }}">{{ $image['filename'] }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mb-2">{{ number_format($image['size'] / 1024, 1) }} KB</p>
                        <div class="flex gap-1.5">
                            <button @click="navigator.clipboard.writeText('{{ $image['filename'] }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="flex-1 text-xs py-1.5 rounded-lg transition-colors"
                                :class="copied ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 dark:bg-white/[0.05] text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400'">
                                <span x-show="!copied">Copy Name</span>
                                <span x-show="copied" x-cloak>Copied!</span>
                            </button>
                            <form method="POST" action="{{ route('instructor.image-library.delete', $image['filename']) }}"
                                  onsubmit="return confirm('Delete this image?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2 py-1.5 rounded-lg text-xs bg-gray-100 dark:bg-white/[0.05] text-gray-400 hover:bg-error-50 hover:text-error-700 dark:hover:bg-error-500/10 dark:hover:text-error-400 transition-colors" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-14">
                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No images uploaded yet. Upload your first image above.</p>
            </div>
        @endif
    </div>
</div>
@endsection
