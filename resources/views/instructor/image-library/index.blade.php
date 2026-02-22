<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('Image Library') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Upload Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload New Image</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Upload images to use in identification questions. Reference by filename in CSV imports.
                    </p>
                    
                    <form method="POST" action="{{ route('instructor.image-library.upload') }}" enctype="multipart/form-data" class="flex items-end gap-4">
                        @csrf
                        <div class="flex-1">
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                Select Image (JPG, PNG, max 2MB)
                            </label>
                            <input 
                                type="file" 
                                id="image" 
                                name="image" 
                                accept="image/*"
                                required
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
                            Upload
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Image Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Your Images ({{ count($images) }})
                    </h3>
                    
                    @if(count($images) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($images as $image)
                                <div class="border border-gray-300 rounded-lg overflow-hidden hover:shadow-lg transition">
                                    <!-- Image Preview -->
                                    <div class="aspect-square bg-gray-100">
                                        <img src="{{ $image['url'] }}" 
                                             alt="{{ $image['filename'] }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    
                                    <!-- Image Info -->
                                    <div class="p-3 bg-white">
                                        <p class="text-xs font-medium text-gray-900 truncate mb-1" title="{{ $image['filename'] }}">
                                            {{ $image['filename'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 mb-2">
                                            {{ number_format($image['size'] / 1024, 1) }} KB
                                        </p>
                                        
                                        <!-- Actions -->
                                        <div class="flex gap-2">
                                            <button 
                                                onclick="copyFilename('{{ $image['filename'] }}')"
                                                class="flex-1 text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition">
                                                Copy Name
                                            </button>
                                            <form method="POST" action="{{ route('instructor.image-library.delete', $image['filename']) }}" 
                                                  onsubmit="return confirm('Delete this image?');" class="flex-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">
                            No images uploaded yet. Upload your first image above.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyFilename(filename) {
            navigator.clipboard.writeText(filename).then(() => {
                // Show temporary success message
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('bg-green-100', 'text-green-700');
                btn.classList.remove('bg-gray-100', 'text-gray-700');
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('bg-green-100', 'text-green-700');
                    btn.classList.add('bg-gray-100', 'text-gray-700');
                }, 2000);
            });
        }
    </script>
</x-app-layout>
