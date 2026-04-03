<div x-show="previewOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
     @click.self="previewOpen = false">
    <div class="w-full max-w-4xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl flex flex-col h-[85vh]">
        
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 bg-gray-50/50 shrink-0">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center rounded-lg bg-violet-100 p-2 text-violet-700">
                    <svg x-show="previewNodeType === 'quiz'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="previewNodeType === 'topic'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        <span class="text-xs text-gray-500 font-medium">Quizzes</span>
                        <span class="text-xs text-gray-400">/</span>
                        <span class="text-xs text-gray-500 font-medium" x-text="previewNodeType === 'quiz' ? (previewQuiz ? previewQuiz.title : 'Quiz Preview') : (previewTopic ? previewTopic.title : 'Topic Preview')"></span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mt-1" x-text="previewNodeType === 'quiz' ? (previewQuiz ? previewQuiz.title : 'Quiz Preview') : (previewTopic ? previewTopic.title : 'Topic Preview')"></h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Module Quiz
                        </span>
                        <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
                            <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                            Active
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 flex items-center gap-1 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </button>
                <button type="button" class="ml-2 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 border border-transparent transition-colors" @click="previewOpen = false">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto px-8 py-8 flex-1 bg-white">
            <template x-if="previewLoading">
                <div class="flex items-center justify-center py-12">
                    <p class="text-sm text-gray-500 flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading preview...
                    </p>     
                </div>
            </template>

            <template x-if="previewError && !previewLoading">
                <p class="text-sm rounded-lg bg-rose-50 text-rose-600 p-4 border border-rose-100" x-text="previewError"></p> 
            </template>

            <template x-if="previewTopic && !previewLoading">
                <div class="space-y-6 max-w-3xl mx-auto">
                    <div class="prose max-w-none prose-sm sm:prose-base text-gray-600" x-html="previewTopic.text_content"></div>

                    <template x-if="previewTopic.video_url">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <iframe class="h-[400px] w-full rounded-lg" :src="previewTopic.video_url" allowfullscreen></iframe>
                        </div>
                    </template>

                    <template x-if="previewTopic.video_file_url">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <video controls class="w-full rounded-lg" :src="previewTopic.video_file_url"></video>
                        </div>
                    </template>

                    <template x-if="previewTopic.file_url">
                        <a class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 hover:bg-sky-100 transition-colors" :href="previewTopic.file_url" target="_blank" rel="noopener">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Attachment
                        </a>        
                    </template>

                    <template x-if="Array.isArray(previewTopic.image_attachment_urls) && previewTopic.image_attachment_urls.length">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"> 
                            <template x-for="(imageUrl, imageIndex) in previewTopic.image_attachment_urls" :key="'image-' + imageIndex">
                                <img :src="imageUrl" alt="Topic attachment" class="w-full rounded-xl border border-gray-200 object-cover shadow-sm">
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="previewQuiz && !previewLoading">
                <div class="space-y-8 max-w-3xl mx-auto">
                    
                    <p class="text-sm text-gray-600 mb-6">Answer the following.</p>

                    <div class="flex flex-col md:flex-row gap-6 mb-8">
                        <div class="flex-1 rounded-xl bg-[#FCFAFF] border-none shadow-[0_2px_10px_rgba(0,0,0,0.03)] p-5">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-1">Questions</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="Array.isArray(previewQuiz.questions) ? previewQuiz.questions.length : 0"></p>
                        </div>
                        <div class="flex-1 rounded-xl bg-[#FCFAFF] border-none shadow-[0_2px_10px_rgba(0,0,0,0.03)] p-5">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-1">Total Points</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="Array.isArray(previewQuiz.questions) ? previewQuiz.questions.reduce((sum, q) => sum + (parseInt(q?.attributes?.points) || 1), 0) : 0"></p>
                        </div>
                        <div class="flex-1 rounded-xl bg-[#FCFAFF] border-none shadow-[0_2px_10px_rgba(0,0,0,0.03)] p-5">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-1">Passing Score</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="(previewQuiz.passing_score ?? '-') + '%'"></p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white shadow-sm p-6 relative">
                        <div class="flex items-center justify-between mb-8">
                            <div class="border-l-[3px] border-violet-600 pl-3">
                                <h4 class="text-lg font-bold text-gray-900">Questions</h4>
                                <p class="text-xs text-gray-500 mt-0.5"><span x-text="Array.isArray(previewQuiz.questions) ? previewQuiz.questions.length : 0"></span> questions total</p>
                            </div>
                        </div>

                        <div class="space-y-10" x-show="Array.isArray(previewQuiz.questions) && previewQuiz.questions.length">
                            <template x-for="(question, questionIndex) in previewQuiz.questions" :key="'quiz-question-' + questionIndex">
                                <div class="flex gap-4 group">
                                    <div class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-full bg-gray-100/80 text-[13px] font-bold text-gray-700">
                                        <span x-text="questionIndex + 1"></span>
                                    </div>
                                    <div class="flex-1 pt-1 pb-8 border-b border-gray-100 last:border-0 last:pb-0">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-[15px] font-bold text-gray-900 leading-relaxed" x-text="question?.attributes?.question_text || 'Untitled question'"></p>
                                                
                                                <div class="mt-2.5 flex items-center gap-2">
                                                    <span class="rounded-[4px] bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-100" x-text="formatLabel(question?.attributes?.question_type)"></span>
                                                    <span class="rounded-[4px] bg-gray-50 px-2 py-0.5 text-[11px] font-semibold text-gray-600 border border-gray-200" x-text="(question?.attributes?.points || 1) + ' pt' + ((question?.attributes?.points || 1) > 1 ? 's' : '')"></span>
                                                </div>
                                            </div>
                                            <!-- The edit/delete icons from the screenshot omitted since this is admin preview, we only have preview mode, but I added inactive icons so it looks the same -->
                                            <div class="flex items-center gap-1 opacity-20 transition-opacity">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </div>
                                        </div>

                                        <!-- Fill Blank Answer Preview -->
                                        <template x-if="question?.attributes?.question_type === 'fill_in_the_blank' && Array.isArray(question?.options) && question.options.length">
                                            <div class="mt-4">
                                                <p class="text-[13px] text-gray-500">Acceptable answers: <span class="font-medium text-gray-700" x-text="question.options.map(opt => opt?.option_text || '').filter(Boolean).join(' / ')"></span></p>
                                            </div>
                                        </template>

                                         <!-- Identification Answer Preview -->
                                         <template x-if="question?.attributes?.question_type === 'identification' && Array.isArray(question?.options) && question.options.length">
                                            <div class="mt-4 space-y-4">
                                                <p class="text-[13px] text-gray-500">Acceptable answers: <span class="font-medium text-gray-700" x-text="question.options.map(opt => opt?.option_text || '').filter(Boolean).join(' / ')"></span></p>
                                            </div>
                                        </template>

                                        <!-- Multiple Choice / Boolean Options -->
                                        <template x-if="(question?.attributes?.question_type === 'multiple_choice' || question?.attributes?.question_type === 'true_false') && Array.isArray(question?.options) && question.options.length">
                                            <div class="mt-5 space-y-2.5 pl-1">
                                                <template x-for="(option, optionIndex) in question.options" :key="'quiz-option-' + questionIndex + '-' + optionIndex">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center justify-center w-4 h-4">
                                                            <template x-if="Boolean(option?.is_correct)">
                                                                <div class="flex items-center justify-center h-4 w-4 rounded-full bg-emerald-100 text-emerald-600">
                                                                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                </div>
                                                            </template>
                                                            <template x-if="!Boolean(option?.is_correct)">
                                                                <div class="h-1.5 w-1.5 rounded-full bg-gray-300"></div>
                                                            </template>
                                                        </div>
                                                        <span :class="{'text-emerald-700 font-bold': Boolean(option?.is_correct), 'text-gray-600 text-sm': !Boolean(option?.is_correct)}" x-text="String.fromCharCode(65 + optionIndex) + '. ' + (option?.option_text || 'Option')"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
