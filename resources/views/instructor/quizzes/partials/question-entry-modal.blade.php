{{--
    Question Entry Modal
    Expected Alpine scope (from parent x-data="questionEntryModal(...)"):
      open, tab, images, imageLoading, uploadLoading,
      loadImages(), uploadImage($event), copyFilename(filename)
--}}
<div
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.5);"
    @click.self="open = false"
    x-cloak
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col"
        style="max-height: 90vh;"
        @click.stop
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div>
                <h2 class="text-base font-bold text-gray-900">Add Question</h2>
                <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $quiz->title }}</p>
            </div>
            <button
                @click="open = false"
                type="button"
                class="p-2 rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tab Bar --}}
        <div class="flex border-b border-gray-100 bg-gray-50 flex-shrink-0">
            <button
                @click="tab = 'manual'"
                :class="tab === 'manual' ? 'bg-white text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="relative flex items-center gap-2 px-6 py-3 text-sm font-semibold transition"
                type="button"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Manually
                <span
                    x-show="tab === 'manual'"
                    class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full"
                    style="background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                ></span>
            </button>
            <button
                @click="tab = 'import'; loadImages()"
                :class="tab === 'import' ? 'bg-white text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="relative flex items-center gap-2 px-6 py-3 text-sm font-semibold transition"
                type="button"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                Import from CSV
                <span
                    x-show="tab === 'import'"
                    class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full"
                    style="background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                ></span>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div class="overflow-y-auto flex-1">

            {{-- ── MANUAL TAB: Question Type Bank ── --}}
            <div x-show="tab === 'manual'" class="p-6" x-cloak>
                <p class="text-sm text-gray-500 mb-5">Choose the type of question you want to create. Each type works differently — read the description to pick the right one.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @php
                    $questionTypes = [
                        [
                            'slug'  => 'multiple_choice',
                            'label' => 'Multiple Choice',
                            'bg'    => 'bg-brand-50',
                            'icon'  => 'text-brand-600',
                            'desc'  => 'Learner picks ONE correct answer from A–D options. Classic single-select format.',
                            'path'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        [
                            'slug'  => 'true_false',
                            'label' => 'True or False',
                            'bg'    => 'bg-green-50',
                            'icon'  => 'text-green-600',
                            'desc'  => 'A statement learners mark as True or False. Options are auto-generated — nothing extra to create.',
                            'path'  => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                        ],
                        [
                            'slug'  => 'multiple_select',
                            'label' => 'Multiple Select',
                            'bg'    => 'bg-purple-50',
                            'icon'  => 'text-purple-600',
                            'desc'  => 'Learner picks ALL correct answers. More than one option may be correct. Uses checkboxes.',
                            'path'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                        ],
                        [
                            'slug'  => 'fill_blank_text',
                            'label' => 'Fill in the Blank (Text)',
                            'bg'    => 'bg-yellow-50',
                            'icon'  => 'text-yellow-600',
                            'desc'  => 'Learner types the missing word(s). Place _____ (5 underscores) in the question for each blank.',
                            'path'  => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                        ],
                        [
                            'slug'  => 'fill_blank_select',
                            'label' => 'Fill in the Blank (Word Bank)',
                            'bg'    => 'bg-orange-50',
                            'icon'  => 'text-orange-600',
                            'desc'  => 'Learner selects words from a pool you provide to fill in the blanks. You define both the word bank and the correct answers.',
                            'path'  => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                        ],
                        [
                            'slug'  => 'identification',
                            'label' => 'Identification',
                            'bg'    => 'bg-pink-50',
                            'icon'  => 'text-pink-600',
                            'desc'  => 'Learner writes a short-answer response. You can optionally attach an image as context.',
                            'path'  => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                        ],
                    ];
                    @endphp

                    @foreach ($questionTypes as $qType)
                    <a
                        href="{{ route($contentRoutePrefix . '.quizzes.add-question', ['quiz' => $quiz, 'type' => $qType['slug']]) }}"
                        class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 bg-white hover:border-purple-200 hover:bg-purple-50/20 hover:shadow-sm transition-all group"
                    >
                        <div class="flex-shrink-0 w-10 h-10 {{ $qType['bg'] }} rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $qType['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $qType['path'] }}"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-purple-800 transition">{{ $qType['label'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $qType['desc'] }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-purple-400 flex-shrink-0 mt-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- ── IMPORT TAB ── --}}
            <div x-show="tab === 'import'" class="p-6 space-y-4" x-cloak>
                <p class="text-sm text-gray-500">Add many questions at once by uploading a CSV file. Follow the three steps below — don't skip ahead!</p>

                {{-- Step 1: Download Template --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-brand-100 rounded-xl flex items-center justify-center">
                            <span class="text-xs font-bold text-brand-700">1</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Download the Template</p>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                Get our ready-made CSV file. It already has column headers and one filled example for each of the 6 question types so you can see exactly what to type in.
                            </p>
                            <a
                                href="{{ route($contentRoutePrefix . '.quizzes.import.template', $quiz) }}"
                                class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-95"
                                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download Template (.csv)
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Image Gallery --}}
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <div class="flex items-start gap-4 p-5">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center">
                            <span class="text-xs font-bold text-purple-700">2</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">
                                Upload Question Images
                                <span class="text-xs font-normal text-gray-400 ml-1">(only if your questions need images)</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                If any of your Identification questions need an image, upload those images here first. After uploading, copy the filename shown in the grid below and paste it into the <code class="bg-gray-100 px-1 rounded font-mono">image_filename</code> column in your CSV.
                            </p>

                            {{-- Upload trigger --}}
                            <label
                                class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl cursor-pointer transition"
                                :class="uploadLoading ? 'opacity-60 pointer-events-none' : ''"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span x-text="uploadLoading ? 'Uploading...' : 'Upload Image'"></span>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/jpg,image/png"
                                    class="hidden"
                                    @change="uploadImage($event)"
                                >
                            </label>
                            <p class="text-xs text-gray-400 mt-1">JPG or PNG only, max 2 MB per image.</p>
                        </div>
                    </div>

                    {{-- Image grid --}}
                    <div class="px-5 pb-5 border-t border-gray-50 pt-3">
                        <p class="text-xs font-medium text-gray-500 mb-2">Uploaded Images — click a filename to copy it</p>

                        <div x-show="imageLoading" class="flex items-center gap-2 text-xs text-gray-400 py-4">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Loading images...
                        </div>

                        <div x-show="!imageLoading && images.length === 0" class="text-xs text-gray-400 py-3 italic">
                            No images uploaded yet. Use the button above to upload your first one.
                        </div>

                        <div x-show="!imageLoading && images.length > 0" class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <template x-for="img in images" :key="img.filename">
                                <div
                                    class="rounded-xl border border-gray-100 overflow-hidden bg-gray-50 cursor-pointer hover:border-purple-300 hover:shadow-sm transition group"
                                    @click="copyFilename(img.filename)"
                                    :title="'Click to copy: ' + img.filename"
                                >
                                    <img :src="img.url" :alt="img.filename" class="w-full h-14 object-cover">
                                    <div class="p-1.5">
                                        <p class="text-xs text-gray-500 font-mono truncate group-hover:text-purple-600" x-text="img.filename"></p>
                                        <p class="text-xs text-purple-400 font-semibold mt-0.5">tap to copy</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Copy feedback toast (inline) --}}
                        <div
                            x-show="showCopied"
                            x-transition
                            class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5"
                            x-cloak
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Filename copied to clipboard!
                        </div>
                    </div>
                </div>

                {{-- Step 3: Upload CSV --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-xl flex items-center justify-center">
                            <span class="text-xs font-bold text-green-700">3</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Upload Your CSV File</p>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                Fill in the template in Excel or Google Sheets, save it as <strong>.csv</strong> (not .xlsx), then upload it here. You'll see a preview of every row so you can fix any errors before anything is saved.
                            </p>
                            <form
                                method="POST"
                                action="{{ route($contentRoutePrefix . '.quizzes.import.preview', $quiz) }}"
                                enctype="multipart/form-data"
                                class="mt-3"
                            >
                                @csrf
                                <label
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl cursor-pointer transition hover:opacity-90 active:scale-95"
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                    </svg>
                                    Choose CSV File & Preview
                                    <input
                                        type="file"
                                        name="csv_file"
                                        accept=".csv"
                                        required
                                        class="hidden"
                                        onchange="this.form.submit()"
                                    >
                                </label>
                                <p class="text-xs text-gray-400 mt-1">.csv files only — the system will show a preview before saving.</p>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Expandable Tips --}}
                <div x-data="{ showTips: false }" class="rounded-2xl border border-amber-100 bg-amber-50 overflow-hidden">
                    <button
                        @click="showTips = !showTips"
                        class="flex items-center justify-between w-full px-5 py-4 text-left"
                        type="button"
                    >
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <span class="text-sm font-semibold text-amber-800">Helpful Tips — Read Before Filling Your CSV</span>
                        </div>
                        <svg
                            class="w-4 h-4 text-amber-500 transition-transform duration-200"
                            :class="showTips ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="showTips" x-transition class="px-5 pb-5 space-y-3">
                        @php
                        $importTips = [
                            ['For Fill in the Blank questions: type <code class="bg-amber-100 px-1 rounded font-mono">_____</code> (that\'s five underscores) in your question wherever you want a blank to appear.'],
                            ['To allow more than one correct answer for the same blank, separate them with a <strong>pipe symbol</strong>: <code class="bg-amber-100 px-1 rounded font-mono">Paris|paris|PARIS</code>'],
                            ['If a question has multiple blanks, separate each blank\'s answer with a <strong>semicolon</strong>: <code class="bg-amber-100 px-1 rounded font-mono">Hydrogen;Oxygen</code> — first blank = Hydrogen, second = Oxygen.'],
                            ['For <strong>True/False</strong> questions: leave the option columns (option_a, option_b, etc.) completely empty. The system creates True and False automatically.'],
                            ['Always save your file as <strong>.csv format</strong>, not .xlsx. In Excel: File → Save As → CSV (Comma delimited). In Google Sheets: File → Download → Comma Separated Values.'],
                        ];
                        @endphp
                        @foreach ($importTips as $i => $tip)
                        <div class="flex items-start gap-2.5 text-xs text-amber-900 leading-relaxed">
                            <span class="flex-shrink-0 w-4 h-4 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center font-bold text-xs mt-0.5">{{ $i + 1 }}</span>
                            <span>{!! $tip[0] !!}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>{{-- end scrollable --}}
    </div>
</div>
