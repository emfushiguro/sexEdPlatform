<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $lesson->title }}
            </h2>
            <a href="{{ route('learner.modules.show', $module) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Module
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <!-- Lesson Content -->
                        <div class="p-6">
                            <h1 class="text-2xl font-bold mb-4">{{ $lesson->title }}</h1>
                            
                            <!-- Video Content -->
                            @if($lesson->content_type === 'video')
                                @if($lesson->video_embed_url)
                                    <!-- Embedded Video (YouTube/Vimeo) -->
                                    <div class="aspect-video bg-black rounded-lg overflow-hidden mb-6">
                                        <iframe 
                                            src="{{ $lesson->video_embed_url }}" 
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                @elseif($lesson->video_file_path)
                                    <!-- Uploaded Video File -->
                                    <div class="aspect-video bg-black rounded-lg overflow-hidden mb-6">
                                        <video class="w-full h-full" controls>
                                            <source src="{{ asset('storage/' . $lesson->video_file_path) }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
                                        <p class="text-yellow-800">No video available for this lesson yet.</p>
                                    </div>
                                @endif
                                
                                @if($lesson->description)
                                    <div class="prose max-w-none mb-4">
                                        <h3 class="text-lg font-semibold mb-3">About this video:</h3>
                                        <p>{!! nl2br(e($lesson->description)) !!}</p>
                                    </div>
                                @endif
                            @endif

                            <!-- Text Content -->
                            @if($lesson->content_type === 'text')
                                @if($lesson->text_content)
                                    <div class="prose max-w-none mb-6">
                                        {!! $lesson->text_content !!}
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                        <p class="text-yellow-800">No text content available for this lesson yet.</p>
                                    </div>
                                @endif
                                
                                <!-- Display Image Attachments - None, Gallery, or Slideshow Mode -->
                                @if($lesson->image_attachments && is_array($lesson->image_attachments) && count($lesson->image_attachments) > 0)
                                    @php
                                        $slideshowData = $lesson->slideshow_data;
                                        $displayMode = is_array($slideshowData) && isset($slideshowData['mode']) ? $slideshowData['mode'] : 'none';
                                        $transition = $slideshowData['transition'] ?? 'fade';
                                    @endphp

                                    @if($displayMode === 'slideshow')
                                        <!-- SLIDESHOW MODE -->
                                        <div class="mt-8" x-data="{
                                            currentSlide: 0,
                                            totalSlides: {{ count($lesson->image_attachments) }},
                                            transition: '{{ $transition }}',
                                            prevSlide() {
                                                this.currentSlide = this.currentSlide === 0 ? this.totalSlides - 1 : this.currentSlide - 1;
                                            },
                                            nextSlide() {
                                                this.currentSlide = this.currentSlide === this.totalSlides - 1 ? 0 : this.currentSlide + 1;
                                            },
                                            goToSlide(index) {
                                                this.currentSlide = index;
                                            }
                                        }">
                                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-lg p-6">
                                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                                    </svg>
                                                    Image Slideshow
                                                </h3>

                                                <!-- Main Image Display -->
                                                <div class="relative bg-white rounded-lg shadow-md overflow-hidden">
                                                    <div class="relative aspect-video bg-gray-100">
                                                        @foreach($lesson->image_attachments as $index => $imageData)
                                                            @php
                                                                $imagePath = is_array($imageData) ? $imageData['path'] : $imageData;
                                                                $caption = is_array($imageData) ? ($imageData['caption'] ?? null) : null;
                                                            @endphp
                                                            <div x-show="currentSlide === {{ $index }}"
                                                                 x-transition:enter="transition ease-out duration-300"
                                                                 x-transition:enter-start="opacity-0 {{ $transition === 'slide' ? 'translate-x-full' : '' }}"
                                                                 x-transition:enter-end="opacity-100 {{ $transition === 'slide' ? 'translate-x-0' : '' }}"
                                                                 x-transition:leave="transition ease-in duration-300"
                                                                 x-transition:leave-start="opacity-100"
                                                                 x-transition:leave-end="opacity-0"
                                                                 class="absolute inset-0 flex flex-col">
                                                                <div class="flex-1 flex items-center justify-center p-4">
                                                                    <img src="{{ asset('storage/' . $imagePath) }}" 
                                                                         alt="Slide {{ $index + 1 }}"
                                                                         class="max-w-full max-h-full object-contain">
                                                                </div>
                                                                
                                                                <!-- Caption directly below image -->\n                                                                @if($caption)
                                                                    <div class="bg-blue-50 px-4 py-3 border-t border-blue-100">
                                                                        <p class="text-sm text-gray-700 text-center">
                                                                            {{ $caption }}
                                                                        </p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach

                                                        <!-- Previous Button - More Visible -->
                                                        <button @click="prevSlide" 
                                                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-2xl transition-all duration-200 hover:scale-110 z-20">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                                            </svg>
                                                        </button>
                                                        
                                                        <!-- Next Button - More Visible -->
                                                        <button @click="nextSlide"
                                                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-2xl transition-all duration-200 hover:scale-110 z-20">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                            </svg>
                                                        </button>

                                                        <!-- Slide Counter -->
                                                        <div class="absolute top-4 right-4 bg-gray-900/80 text-white px-3 py-1 rounded-full text-sm font-medium z-10">
                                                            <span x-text="currentSlide + 1"></span> / <span x-text="totalSlides"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Thumbnail Strip -->
                                                <div class="flex gap-2 overflow-x-auto pb-2 mt-4">
                                                    @foreach($lesson->image_attachments as $index => $imageData)
                                                        @php
                                                            $imagePath = is_array($imageData) ? $imageData['path'] : $imageData;
                                                        @endphp
                                                        <button @click="goToSlide({{ $index }})"
                                                                :class="currentSlide === {{ $index }} ? 'ring-2 ring-blue-500 opacity-100' : 'opacity-50 hover:opacity-75'"
                                                                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden transition">
                                                            <img src="{{ asset('storage/' . $imagePath) }}" 
                                                                 alt="Thumbnail {{ $index + 1 }}"
                                                                 class="w-full h-full object-cover">
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($displayMode === 'gallery')
                                        <!-- GALLERY MODE -->
                                        <div class="mt-8" x-data="{ 
                                            lightboxOpen: false, 
                                            currentImage: 0,
                                            images: {{ json_encode(array_map(function($img) { 
                                                return is_array($img) ? $img : ['path' => $img, 'caption' => null]; 
                                            }, $lesson->image_attachments)) }},
                                            openLightbox(index) {
                                                this.currentImage = index;
                                                this.lightboxOpen = true;
                                            },
                                            closeLightbox() {
                                                this.lightboxOpen = false;
                                            },
                                            nextImage() {
                                                this.currentImage = (this.currentImage + 1) % this.images.length;
                                            },
                                            prevImage() {
                                                this.currentImage = this.currentImage === 0 ? this.images.length - 1 : this.currentImage - 1;
                                            }
                                        }">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                                </svg>
                                                Image Gallery
                                            </h3>

                                            <!-- Image Grid -->
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                                @foreach($lesson->image_attachments as $index => $imageData)
                                                    @php
                                                        $imagePath = is_array($imageData) ? $imageData['path'] : $imageData;
                                                        $caption = is_array($imageData) ? ($imageData['caption'] ?? null) : null;
                                                    @endphp
                                                    <div @click="openLightbox({{ $index }})" 
                                                         class="group relative cursor-pointer overflow-hidden rounded-lg shadow-md hover:shadow-xl transition">
                                                        <img src="{{ asset('storage/' . $imagePath) }}" 
                                                             alt="Image {{ $index + 1 }}" 
                                                             class="w-full h-48 object-cover group-hover:scale-110 transition duration-300">
                                                        
                                                        <!-- Overlay on hover -->
                                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-center justify-center">
                                                            <svg class="w-12 h-12 text-white opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                                                            </svg>
                                                        </div>

                                                        @if($caption)
                                                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/75 to-transparent p-3">
                                                                <p class="text-white text-xs line-clamp-2">{{ $caption }}</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Lightbox Modal -->
                                            <div x-show="lightboxOpen" 
                                                 x-cloak
                                                 @keydown.escape.window="closeLightbox"
                                                 @keydown.arrow-right.window="nextImage"
                                                 @keydown.arrow-left.window="prevImage"
                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4">
                                                
                                                <!-- Close Button - Top Right -->
                                                <button @click="closeLightbox" 
                                                        class="absolute top-6 right-6 bg-white/20 hover:bg-white/30 text-white rounded-full p-3 transition-all hover:scale-110 z-50">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>

                                                <!-- Image Container -->
                                                <div class="max-w-6xl w-full px-16">
                                                    <img :src="'{{ asset('storage') }}/' + images[currentImage].path" 
                                                         :alt="'Image ' + (currentImage + 1)"
                                                         class="max-h-[85vh] w-full object-contain rounded-lg shadow-2xl">
                                                    
                                                    <!-- Caption in Lightbox -->
                                                    <div x-show="images[currentImage].caption" 
                                                         class="mt-4 text-center">
                                                        <p class="text-white text-base" x-text="images[currentImage].caption"></p>
                                                    </div>

                                                    <!-- Image Counter -->
                                                    <div class="mt-3 text-center text-white/90 text-sm font-medium">
                                                        Image <span x-text="currentImage + 1"></span> of <span x-text="images.length"></span>
                                                    </div>
                                                </div>

                                                <!-- Navigation Buttons - Fixed Position -->
                                                <button @click="prevImage" 
                                                        class="fixed left-6 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-2xl transition-all hover:scale-110 z-50">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                                    </svg>
                                                </button>
                                                <button @click="nextImage"
                                                        class="fixed right-6 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-2xl transition-all hover:scale-110 z-50">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <!-- SIMPLE/NONE MODE - Just display images -->
                                        <div class="mt-8">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                                </svg>
                                                Images
                                            </h3>

                                            <div class="space-y-6">
                                                @foreach($lesson->image_attachments as $index => $imageData)
                                                    @php
                                                        $imagePath = is_array($imageData) ? $imageData['path'] : $imageData;
                                                        $caption = is_array($imageData) ? ($imageData['caption'] ?? null) : null;
                                                    @endphp
                                                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                        <img src="{{ asset('storage/' . $imagePath) }}" 
                                                             alt="Image {{ $index + 1 }}" 
                                                             class="w-full h-auto">
                                                        @if($caption)
                                                            <div class="bg-gray-50 px-4 py-3 border-t">
                                                                <p class="text-sm text-gray-700">{{ $caption }}</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endif

                            <!-- Worksheet Download -->
                            @if($lesson->content_type === 'worksheet')
                                @if($lesson->file_path)
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-lg">Downloadable Worksheet</h3>
                                                <p class="text-gray-600">{{ basename($lesson->file_path) }}</p>
                                            </div>
                                            <a href="{{ asset('storage/' . $lesson->file_path) }}" 
                                               target="_blank"
                                               download
                                               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
                                        <p class="text-yellow-800">No worksheet file available for this lesson yet.</p>
                                    </div>
                                @endif
                                @if($lesson->text_content)
                                    <div class="prose max-w-none">
                                        <h3 class="text-lg font-semibold mb-3">Instructions:</h3>
                                        {!! $lesson->text_content !!}
                                    </div>
                                @endif
                            @endif

                            <!-- Interactive Content -->
                            @if($lesson->content_type === 'interactive')
                                @if($lesson->video_embed_url)
                                    <!-- Interactive Embed (H5P, Google Forms, etc.) -->
                                    <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-6">
                                        <iframe 
                                            src="{{ $lesson->video_embed_url }}" 
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" 
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                @endif
                                @if($lesson->text_content)
                                    <div class="prose max-w-none">
                                        <h3 class="text-lg font-semibold mb-3">Activity Instructions:</h3>
                                        {!! $lesson->text_content !!}
                                    </div>
                                @endif
                                @if(!$lesson->video_embed_url && !$lesson->text_content)
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                        <p class="text-yellow-800">No interactive content available for this lesson yet.</p>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Lesson Quiz Section (Optional - can take anytime) -->
                        @if($lessonQuiz)
                            <div class="border-t p-6 bg-purple-50">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white text-xl">📝</span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">Lesson Quiz: {{ $lessonQuiz->title }}</h3>
                                        @if($lessonQuiz->description)
                                            <p class="text-sm text-gray-600 mt-1">{{ $lessonQuiz->description }}</p>
                                        @endif
                                        <div class="flex gap-3 mt-2 text-xs text-gray-600">
                                            <span>{{ $lessonQuiz->questions->count() }} questions</span>
                                            <span>Passing: {{ $lessonQuiz->passing_score }}%</span>
                                            @if($lessonQuiz->time_limit)
                                                <span>{{ $lessonQuiz->time_limit }} min</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($quizAttempt)
                                    <div class="mb-3 p-3 bg-white rounded border {{ $quizAttempt->passed ? 'border-green-300' : 'border-yellow-300' }}">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Your Best Score:</span>
                                            <span class="font-bold {{ $quizAttempt->passed ? 'text-green-600' : 'text-yellow-600' }}">
                                                {{ $quizAttempt->score }}% {{ $quizAttempt->passed ? '✓' : '' }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('quizzes.start', $lessonQuiz) }}" 
                                   class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                    {{ $quizAttempt ? 'Retake Quiz' : 'Take Quiz' }}
                                </a>
                                <p class="text-xs text-gray-600 text-center mt-2">
                                    Test your knowledge of this lesson (optional)
                                </p>
                            </div>
                        @endif

                        <!-- Lesson Navigation -->
                        <div class="border-t p-6">
                            <div class="flex items-center justify-between">
                                @if($previousLesson)
                                    <a href="{{ route('learner.lessons.show', $previousLesson) }}" 
                                       class="flex items-center gap-2 text-blue-600 hover:text-blue-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        <span>Previous Lesson</span>
                                    </a>
                                @else
                                    <div></div>
                                @endif

                                @if(!$isLessonCompleted)
                                    <form method="POST" action="{{ route('learner.lessons.complete', $lesson) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                            ✓ Mark as Completed
                                        </button>
                                    </form>
                                @else
                                    <div class="bg-green-100 text-green-800 font-semibold py-2 px-6 rounded-lg flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Completed</span>
                                    </div>
                                @endif

                                @if($nextLesson)
                                    <a href="{{ route('learner.lessons.show', $nextLesson) }}" 
                                       class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition">
                                        <span>Next Lesson</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                @else
                                    <div></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Lesson List -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-6">
                        <div class="p-4 border-b">
                            <h3 class="font-semibold">{{ $module->title }}</h3>
                            <p class="text-sm text-gray-500">{{ $allLessons->count() }} lessons</p>
                        </div>
                        <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
                            @foreach($allLessons as $index => $l)
                                <a href="{{ route('learner.lessons.show', $l) }}" 
                                   class="block p-4 border-b hover:bg-gray-50 {{ $l->id === $lesson->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}">
                                    <div class="flex items-start gap-3">
                                        @if(in_array($l->id, $completedLessonIds))
                                            <div class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="flex-shrink-0 w-6 h-6 {{ $l->id === $lesson->id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }} rounded-full flex items-center justify-center text-xs font-semibold">
                                                {{ $index + 1 }}
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $l->title }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $l->duration }} min</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
