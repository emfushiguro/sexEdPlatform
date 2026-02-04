<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Topic Header -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                @if($currentTopic->type === 'video')
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                    </svg>
                @elseif($currentTopic->type === 'text')
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                @elseif($currentTopic->type === 'worksheet')
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-2xl font-bold text-white">{{ $currentTopic->title }}</h3>
                <p class="text-blue-100 text-sm mt-1">
                    Topic {{ $currentTopicIndex + 1 }} of {{ $lessonTopics->count() }} 
                    <span class="mx-2">•</span> 
                    {{ $currentTopic->duration }} minutes
                    <span class="mx-2">•</span>
                    {{ ucfirst($currentTopic->type) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Topic Content -->
    <div class="p-6">
        @if($currentTopic->type === 'video')
            <!-- Video Content -->
            <div class="space-y-4">
                @if($currentTopic->video_file_path)
                    <!-- Uploaded Video File -->
                    <div class="bg-black rounded-lg overflow-hidden">
                        <video 
                            id="lesson-video" 
                            controls 
                            class="w-full aspect-video"
                            controlsList="nodownload"
                            oncontextmenu="return false;">
                            <source src="{{ asset('storage/' . $currentTopic->video_file_path) }}" type="video/mp4">
                            <source src="{{ asset('storage/' . $currentTopic->video_file_path) }}" type="video/webm">
                            <source src="{{ asset('storage/' . $currentTopic->video_file_path) }}" type="video/ogg">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                @elseif($currentTopic->video_embed_url)
                    <!-- Embedded Video (YouTube/Vimeo) -->
                    <div class="relative bg-black rounded-lg overflow-hidden aspect-video">
                        <iframe 
                            src="{{ $currentTopic->video_embed_url }}"
                            class="absolute inset-0 w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    </div>
                @else
                    <div class="bg-gray-100 rounded-lg p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500">No video available for this topic.</p>
                    </div>
                @endif

                @if($currentTopic->text_content)
                    <div class="prose max-w-none mt-6">
                        {!! $currentTopic->text_content !!}
                    </div>
                @endif
            </div>

        @elseif($currentTopic->type === 'text')
            <!-- Text Content -->
            <div class="space-y-6" x-data="{ 
                displayMode: 'slideshow',
                currentImageIndex: 0,
                images: {{ json_encode($currentTopic->image_attachments ?? []) }}
            }">
                @if($currentTopic->text_content)
                    <div class="prose max-w-none">
                        {!! $currentTopic->text_content !!}
                    </div>
                @endif

                @if($currentTopic->image_attachments && count($currentTopic->image_attachments) > 0)
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900">Images</h4>
                            <div class="flex gap-2">
                                <button 
                                    @click="displayMode = 'slideshow'" 
                                    :class="displayMode === 'slideshow' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                                    class="px-3 py-1 rounded-lg text-sm font-medium transition">
                                    Slideshow
                                </button>
                                <button 
                                    @click="displayMode = 'gallery'" 
                                    :class="displayMode === 'gallery' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                                    class="px-3 py-1 rounded-lg text-sm font-medium transition">
                                    Gallery
                                </button>
                            </div>
                        </div>

                        <!-- Slideshow Mode -->
                        <div x-show="displayMode === 'slideshow'" class="space-y-4">
                            <div class="relative bg-gray-100 rounded-lg overflow-hidden">
                                <template x-for="(image, index) in images" :key="index">
                                    <div x-show="currentImageIndex === index" class="aspect-video">
                                        <img 
                                            :src="`/storage/${image.path}`" 
                                            :alt="image.caption || 'Image'"
                                            class="w-full h-full object-contain bg-gray-900">
                                    </div>
                                </template>

                                <!-- Navigation Arrows -->
                                <template x-if="images.length > 1">
                                    <div>
                                        <button 
                                            @click="currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : images.length - 1"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-3 rounded-full transition">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                        </button>
                                        <button 
                                            @click="currentImageIndex = currentImageIndex < images.length - 1 ? currentImageIndex + 1 : 0"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-3 rounded-full transition">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Image Caption -->
                            <template x-for="(image, index) in images" :key="index">
                                <div x-show="currentImageIndex === index">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-gray-700" x-text="image.caption || 'No caption'"></p>
                                        <p class="text-xs text-gray-500 mt-1" x-text="`Image ${index + 1} of ${images.length}`"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Gallery Mode -->
                        <div x-show="displayMode === 'gallery'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="(image, index) in images" :key="index">
                                <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition">
                                    <div class="aspect-video bg-gray-900">
                                        <img 
                                            :src="`/storage/${image.path}`" 
                                            :alt="image.caption || 'Image'"
                                            class="w-full h-full object-cover">
                                    </div>
                                    <template x-if="image.caption">
                                        <div class="p-3 bg-white">
                                            <p class="text-sm text-gray-700" x-text="image.caption"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                @endif
            </div>

        @elseif($currentTopic->type === 'worksheet')
            <!-- Worksheet Content -->
            <div class="space-y-6">
                @if($currentTopic->text_content)
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 mb-2">Instructions:</h4>
                        <div class="text-yellow-800 prose prose-sm max-w-none">
                            {!! $currentTopic->text_content !!}
                        </div>
                    </div>
                @endif

                @if($currentTopic->file_path)
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900 mb-1">Worksheet File</h4>
                                <p class="text-sm text-gray-500 truncate">{{ basename($currentTopic->file_path) }}</p>
                            </div>
                            <a href="{{ asset('storage/' . $currentTopic->file_path) }}" 
                               download
                               class="flex-shrink-0 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-100 rounded-lg p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500">No worksheet file available.</p>
                    </div>
                @endif
            </div>

        @elseif($currentTopic->type === 'interactive')
            <!-- Interactive Content -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-lg p-12 text-center">
                <svg class="mx-auto h-20 w-20 text-purple-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-bold text-purple-900 mb-2">Interactive Activity</h3>
                <p class="text-purple-700 mb-6">This interactive content is coming soon!</p>
                @if($currentTopic->text_content)
                    <div class="bg-white bg-opacity-60 rounded-lg p-4 max-w-2xl mx-auto">
                        <div class="prose prose-sm max-w-none">
                            {!! $currentTopic->text_content !!}
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Topic Actions -->
    <div class="border-t border-gray-200 p-6 bg-gray-50">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                @if(in_array($currentTopic->id, $completedTopicIds))
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Completed</span>
                    </div>
                @else
                    <form action="{{ route('learner.topics.complete', $currentTopic) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mark as Complete & Continue
                        </button>
                    </form>
                @endif
            </div>

            <div class="flex gap-2">
                @if($currentTopicIndex > 0)
                    <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $currentTopicIndex - 1]) }}" 
                       class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Previous
                    </a>
                @endif

                @if($currentTopicIndex < $lessonTopics->count() - 1)
                    <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $currentTopicIndex + 1]) }}" 
                       class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition flex items-center gap-2">
                        Next
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
