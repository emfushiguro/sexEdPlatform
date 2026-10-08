@extends('layouts.instructor-app')

@section('title', 'Instructor Guidelines')
@section('meta_description', 'Reference guidance for creating safe, educational, accessible content on Conscious Connections.')

@php
    $guidelineSections = [
        ['id' => 'overview', 'label' => 'Overview'],
        ['id' => 'content-principles', 'label' => 'Content principles'],
        ['id' => 'learner-category-guidelines', 'label' => 'Learner categories'],
        ['id' => 'sensitive-topics', 'label' => 'Sensitive topics'],
        ['id' => 'module-guidelines', 'label' => 'Content structure'],
        ['id' => 'interactive-guidelines', 'label' => 'Interactive content'],
        ['id' => 'media-guidelines', 'label' => 'Images, video & resources'],
        ['id' => 'accessibility-guidelines', 'label' => 'Accessibility'],
        ['id' => 'prohibited-content', 'label' => 'Prohibited content'],
        ['id' => 'sources-references', 'label' => 'Sources & references'],
        ['id' => 'before-you-publish', 'label' => 'Before you publish'],
    ];
@endphp

@section('content')
<div class="space-y-6" data-testid="instructor-guidelines-page">
    <header class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 px-6 py-8 text-white shadow-sm md:px-10 md:py-10">
        <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-fuchsia-300/10 blur-3xl" aria-hidden="true"></div>
        <div class="relative max-w-3xl">
            <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-white/90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3.5v5.25c0 4.1-2.8 7.8-7 9.25-4.2-1.45-7-5.15-7-9.25V6.5L12 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.7 1.7 3.5-3.6" />
                </svg>
                Instructor handbook
            </div>
            <h1 id="page-title" class="text-3xl font-bold tracking-tight md:text-4xl">Instructor Guidelines</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                A practical reference for creating educational content that is relevant, respectful, accessible, and appropriate for the learners you selected.
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-medium text-white/90">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2">Create with purpose</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2">Protect learner dignity</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2">Review before publishing</span>
            </div>
        </div>
    </header>

    <div class="lg:hidden">
        <details class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-semibold text-gray-900">
                <span>On this page</span>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <nav aria-label="Guidelines sections" class="border-t border-gray-100 px-5 py-3">
                <ol class="grid gap-1 sm:grid-cols-2">
                    @foreach($guidelineSections as $section)
                        <li>
                            <a href="#{{ $section['id'] }}" class="block rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-brand-50 hover:text-brand-700 focus-visible:bg-brand-50 focus-visible:text-brand-700">
                                {{ $loop->iteration }}. {{ $section['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </details>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="hidden lg:block">
            <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="px-3 pb-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-gray-400">On this page</p>
                <nav aria-label="Guidelines sections">
                    <ol class="space-y-1">
                        @foreach($guidelineSections as $section)
                            <li>
                                <a href="#{{ $section['id'] }}" class="block rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-brand-50 hover:text-brand-700 focus-visible:bg-brand-50 focus-visible:text-brand-700">
                                    <span class="mr-1 text-xs text-gray-400">{{ $loop->iteration }}.</span>{{ $section['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        </aside>

        <article class="min-w-0 space-y-6" aria-labelledby="page-title">
            <section id="overview" class="scroll-mt-24 rounded-2xl border border-brand-100 bg-brand-50/50 p-5 md:p-7">
                <div class="flex items-start gap-4">
                    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75v10.5m0-10.5c-1.16-.78-2.75-1.25-4.5-1.25S4.16 5.97 3 6.75v10.5c1.16-.78 2.75-1.25 4.5-1.25s3.34.47 4.5 1.25m0-10.5c1.16-.78 2.75-1.25 4.5-1.25s3.34.47 4.5 1.25v10.5c-1.16-.78-2.75-1.25-4.5-1.25s-3.34.47-4.5 1.25" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Overview / Getting Started</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            You have broad creative freedom to design useful learning experiences. That freedom is paired with a responsibility to keep each module, lesson, topic, activity, checkpoint, quiz, image, and video within Conscious Connections' educational, safety, and learner-appropriateness standards.
                        </p>
                    </div>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-white/80 bg-white/75 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Start with why</p>
                        <p class="mt-2 text-sm leading-5 text-gray-700">Name the learning objective before choosing the format, activity, or media.</p>
                    </div>
                    <div class="rounded-xl border border-white/80 bg-white/75 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Design for who</p>
                        <p class="mt-2 text-sm leading-5 text-gray-700">Use the selected learner category to guide depth, language, examples, and visuals.</p>
                    </div>
                    <div class="rounded-xl border border-white/80 bg-white/75 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Review before release</p>
                        <p class="mt-2 text-sm leading-5 text-gray-700">Use the checklist at the end, then follow the platform's existing review and authorization processes.</p>
                    </div>
                </div>
                <p class="mt-5 text-sm leading-6 text-gray-600">
                    These guidelines are an educational reference. They do not replace instructor verification, authorization, content review, moderation, or any other platform control.
                </p>
            </section>

            <section id="content-principles" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-brand-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Content Principles</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Use these principles as a decision filter while planning and reviewing content.</p>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Teach something specific</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Every item should have a defined learning objective and a clear connection to the module outcome. Content should support learning rather than entertainment or personal expression unrelated to the objective.</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Be accurate and relevant</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Use information that is accurate, current enough for the subject, and relevant to what learners are expected to understand or practice.</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Respect the learner</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Protect learner dignity and privacy. Avoid unnecessary personal or sexual disclosures, and make room for learners to participate without sharing private experiences.</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Choose proportionate detail</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Sensitive subjects should be objective, developmentally appropriate, and no more graphic, sensational, or sexualized than the learning objective requires.</p>
                    </div>
                </div>
            </section>

            <section id="learner-category-guidelines" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-indigo-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Learner Category Guidelines</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">A topic is not automatically appropriate simply because it appears in an allowed category. Adjust depth, language, examples, visuals, and activities for the learners selected.</p>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <section id="kids-guidelines" class="scroll-mt-24 rounded-xl border border-sky-100 bg-sky-50/60 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-sky-950">Kids</h3>
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700">Foundations</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-sky-950/75">Keep concepts concrete, clearly scoped, and suitable for younger learners.</p>
                        <ul class="mt-4 space-y-2 text-sm leading-5 text-sky-950/80">
                            <li class="flex gap-2"><span class="text-sky-600" aria-hidden="true">•</span><span>Body awareness and naming basic boundaries.</span></li>
                            <li class="flex gap-2"><span class="text-sky-600" aria-hidden="true">•</span><span>Feelings, emotions, respect, and personal safety.</span></li>
                            <li class="flex gap-2"><span class="text-sky-600" aria-hidden="true">•</span><span>Trusted adults and recognizing uncomfortable or unsafe situations.</span></li>
                        </ul>
                        <p class="mt-4 border-t border-sky-100 pt-4 text-xs leading-5 text-sky-900/70">Avoid unnecessary detail or presentation that is inappropriate for younger learners.</p>
                    </section>

                    <section id="teens-guidelines" class="scroll-mt-24 rounded-xl border border-violet-100 bg-violet-50/60 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-violet-950">Teens</h3>
                            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-violet-700">Development</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-violet-950/75">Use clear, age-appropriate education that supports informed decisions and healthy communication.</p>
                        <ul class="mt-4 space-y-2 text-sm leading-5 text-violet-950/80">
                            <li class="flex gap-2"><span class="text-violet-600" aria-hidden="true">•</span><span>Puberty, consent, boundaries, and healthy relationships.</span></li>
                            <li class="flex gap-2"><span class="text-violet-600" aria-hidden="true">•</span><span>Communication, sexual health, and decision-making.</span></li>
                            <li class="flex gap-2"><span class="text-violet-600" aria-hidden="true">•</span><span>Risk awareness without fear-based or sensational presentation.</span></li>
                        </ul>
                        <p class="mt-4 border-t border-violet-100 pt-4 text-xs leading-5 text-violet-900/70">Use examples and scenarios that teach skills without requiring private disclosures.</p>
                    </section>

                    <section id="adults-guidelines" class="scroll-mt-24 rounded-xl border border-emerald-100 bg-emerald-50/60 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-emerald-950">Adults</h3>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Comprehensive</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-emerald-950/75">Relevant subjects may be treated more comprehensively while remaining educational, respectful, and purposeful.</p>
                        <ul class="mt-4 space-y-2 text-sm leading-5 text-emerald-950/80">
                            <li class="flex gap-2"><span class="text-emerald-600" aria-hidden="true">•</span><span>Sexual and reproductive health, consent, and relationships.</span></li>
                            <li class="flex gap-2"><span class="text-emerald-600" aria-hidden="true">•</span><span>Communication, safer sexual practices, and health-seeking behavior.</span></li>
                            <li class="flex gap-2"><span class="text-emerald-600" aria-hidden="true">•</span><span>Clear context and evidence for more detailed educational treatment.</span></li>
                        </ul>
                        <p class="mt-4 border-t border-emerald-100 pt-4 text-xs leading-5 text-emerald-900/70">Adult access does not make content intended for sexual stimulation or unrelated personal expression educational.</p>
                    </section>
                </div>

                <div class="mt-5 rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-950/80">
                    <span class="font-semibold text-amber-950">Use judgment:</span> The same subject may require different terminology, depth, examples, visual treatment, and activity design for Kids, Teens, and Adults. When in doubt, reduce unnecessary detail and return to the learning objective.
                </div>
            </section>

            <section id="sensitive-topics" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-rose-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Sensitive &amp; Sexuality-Related Topics</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Sensitive topics can be educationally valuable when they are handled with purpose, objectivity, and care.</p>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="font-semibold text-gray-900">Do</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                            <li class="flex gap-2"><span class="font-semibold text-emerald-600" aria-hidden="true">✓</span><span>State what learners should understand, practice, or be able to recognize.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-emerald-600" aria-hidden="true">✓</span><span>Present sensitive subjects objectively and developmentally appropriately.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-emerald-600" aria-hidden="true">✓</span><span>Use respectful language and allow learners to engage without disclosure.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-emerald-600" aria-hidden="true">✓</span><span>Use reliable, current sources for health, safety, and legal claims.</span></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Avoid</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                            <li class="flex gap-2"><span class="font-semibold text-rose-600" aria-hidden="true">×</span><span>Unnecessary sexualization, sensationalism, or graphic presentation.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-rose-600" aria-hidden="true">×</span><span>Prompts that require private sexual experiences, trauma, or medical history.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-rose-600" aria-hidden="true">×</span><span>Personalized sexualized instructor-to-learner interactions.</span></li>
                            <li class="flex gap-2"><span class="font-semibold text-rose-600" aria-hidden="true">×</span><span>Statements that present uncertain or outdated claims as established fact.</span></li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="module-guidelines" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-brand-500 pl-4">
                    <h2 id="content-structure" class="text-xl font-semibold text-gray-900">Module, Lesson &amp; Topic Structure</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Use the existing authoring layers to move learners through a coherent experience.</p>
                </div>
                <div class="mt-6 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-center text-sm font-semibold text-brand-900 md:text-base">
                    Learn <span class="px-2 text-brand-500" aria-hidden="true">→</span> Practice <span class="px-2 text-brand-500" aria-hidden="true">→</span> Apply <span class="px-2 text-brand-500" aria-hidden="true">→</span> Assess
                </div>
                <ol class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <li class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">1</span>
                        <h3 class="mt-3 font-semibold text-gray-900">Module</h3>
                        <p class="mt-1 text-sm leading-5 text-gray-600">Defines the overall subject, description, objectives, and intended learning outcome.</p>
                    </li>
                    <li class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">2</span>
                        <h3 class="mt-3 font-semibold text-gray-900">Lesson</h3>
                        <p class="mt-1 text-sm leading-5 text-gray-600">Groups a coherent subtopic so learners can follow a manageable progression.</p>
                    </li>
                    <li class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">3</span>
                        <h3 class="mt-3 font-semibold text-gray-900">Topic</h3>
                        <p class="mt-1 text-sm leading-5 text-gray-600">Teaches a specific concept or body of content with enough context to understand it.</p>
                    </li>
                    <li class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">4</span>
                        <h3 class="mt-3 font-semibold text-gray-900">Learning check</h3>
                        <p class="mt-1 text-sm leading-5 text-gray-600">Use practice, application, checkpoints, and quizzes to reinforce the stated outcome.</p>
                    </li>
                </ol>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                        <h3 class="font-semibold text-indigo-950">Keep the thread visible</h3>
                        <p class="mt-2 text-sm leading-6 text-indigo-950/75">A learner should be able to tell why a topic belongs in its lesson, why an activity belongs in that topic, and how the assessment relates to the outcome.</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                        <h3 class="font-semibold text-amber-950">Keep the scope honest</h3>
                        <p class="mt-2 text-sm leading-6 text-amber-950/75">Do not use extra sections, media, or activities just to make a module longer. Add them when they improve understanding or practice.</p>
                    </div>
                </div>
            </section>

            <section id="interactive-guidelines" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-indigo-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Interactive Activities, Checkpoints &amp; Quizzes</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Interactive content should give learners a useful chance to practice or demonstrate the stated learning objective.</p>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Matching</h3><p class="mt-1 text-sm leading-5 text-gray-600">Pair related concepts, terms, examples, or responses. Keep each pairing distinct.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Sequencing</h3><p class="mt-1 text-sm leading-5 text-gray-600">Ask learners to arrange steps, stages, or ideas when the order is objectively supportable.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Perspective Feedback</h3><p class="mt-1 text-sm leading-5 text-gray-600">Use a scenario to show how choices can affect different people; avoid forcing personal disclosure.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Multiple Choice</h3><p class="mt-1 text-sm leading-5 text-gray-600">Write one clear best answer and plausible alternatives that test understanding, not test-taking tricks.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Identification</h3><p class="mt-1 text-sm leading-5 text-gray-600">Ask learners to identify a concept, signal, step, or feature that has been taught.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">True / False</h3><p class="mt-1 text-sm leading-5 text-gray-600">Use statements that are unambiguous and factually supportable; avoid tricky wording.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Fill in the Blanks</h3><p class="mt-1 text-sm leading-5 text-gray-600">Make the expected response clear enough that equivalent correct wording is not unfairly rejected.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Word Bank</h3><p class="mt-1 text-sm leading-5 text-gray-600">Include terms that are relevant to the lesson and avoid distractors that create ambiguity.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Multiple Select</h3><p class="mt-1 text-sm leading-5 text-gray-600">Tell learners whether one or several answers are expected and ensure every correct option is defensible.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4 sm:col-span-2 xl:col-span-3"><h3 class="font-semibold text-gray-900">Other activity or checkpoint types</h3><p class="mt-1 text-sm leading-5 text-gray-600">Use any existing type when it strengthens the objective. If the learner action cannot be explained in one clear sentence, simplify the interaction before publishing.</p></div>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4"><h3 class="font-semibold text-emerald-950">Support the objective</h3><p class="mt-2 text-sm leading-6 text-emerald-950/75">Every prompt, answer, and feedback message should serve the learning outcome.</p></div>
                    <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4"><h3 class="font-semibold text-sky-950">Teach through feedback</h3><p class="mt-2 text-sm leading-6 text-sky-950/75">Feedback should explain, encourage, or redirect constructively rather than shame the learner.</p></div>
                    <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-4"><h3 class="font-semibold text-rose-950">Protect privacy</h3><p class="mt-2 text-sm leading-6 text-rose-950/75">Never require private sexual experiences, trauma, medical history, or other sensitive personal information to complete an activity.</p></div>
                </div>
            </section>

            <section id="media-guidelines" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-amber-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Images, Videos &amp; External Resources</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Media should make the learning clearer, not simply make the page busier.</p>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Images and illustrations</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                            <li>Use age- and developmentally appropriate visuals that support the learning objective.</li>
                            <li>Avoid unnecessary graphic or sexualized imagery.</li>
                            <li>Give every meaningful image meaningful alt text; decorative images should be treated as decorative.</li>
                            <li>Respect copyright, licensing, and attribution requirements.</li>
                        </ul>
                    </div>
                    <div id="video-guidelines" class="scroll-mt-24 rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <h3 class="font-semibold text-gray-900">Videos</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                            <li>Choose videos for a specific instructional reason and review the whole resource before linking it.</li>
                            <li>Use age- and developmentally appropriate presentation without unnecessary graphic or sexualized material.</li>
                            <li>Use captions where appropriate and check that the video remains understandable without sound.</li>
                            <li>Do not download or re-upload copyrighted material without the appropriate rights.</li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 md:col-span-2">
                        <h3 class="font-semibold text-gray-900">External resources</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Prefer reliable and authoritative sources. Check that external links are relevant, current, accessible, and appropriate for the selected learner category. Add attribution where required, and explain why a resource is included when the purpose is not obvious.</p>
                    </div>
                </div>
                <div class="mt-5 rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-950/80">
                    The platform's existing media, image-resizer, caption, and storage infrastructure remains the source of truth for handling uploads. These guidelines document appropriate use; they do not introduce a new media system.
                </div>
            </section>

            <section id="accessibility-guidelines" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-teal-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Accessibility</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Accessible content helps more learners understand, navigate, and participate.</p>
                </div>
                <ul class="mt-6 grid gap-3 md:grid-cols-2">
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">Meaningful alt text:</strong> Describe the purpose or information an image adds.</span></li>
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">Video captions:</strong> Provide captions where appropriate and review them for accuracy.</span></li>
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">Readable structure:</strong> Use descriptive headings, short paragraphs, lists, and clear instructions.</span></li>
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">More than color:</strong> Do not communicate meaning through color alone; include text, labels, or another clear cue.</span></li>
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">Accessible activities:</strong> Give learners clear instructions and make interactions usable with keyboard and touch where applicable.</span></li>
                    <li class="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><span class="mt-0.5 text-teal-600" aria-hidden="true">✓</span><span class="text-sm leading-6 text-gray-700"><strong class="font-semibold text-gray-900">Check the learner path:</strong> Ensure focus states, controls, prompts, and feedback remain understandable at every step.</span></li>
                </ul>
            </section>

            <section id="prohibited-content" class="scroll-mt-24 rounded-2xl border border-rose-200 bg-rose-50/50 p-5 md:p-7">
                <div class="border-l-4 border-rose-600 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Prohibited / Unsafe Content</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">The following content must not be published on the platform, regardless of the selected learner category.</p>
                </div>
                <ul class="mt-6 grid gap-3 md:grid-cols-2">
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Sexualization of minors.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Pornographic or sexually explicit material intended for sexual stimulation.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Sexual exploitation or abuse content.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Content encouraging unsafe, abusive, exploitative, or illegal behavior.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Sexualized instructor-to-learner interactions.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Content designed to solicit unnecessary personal sexual disclosures.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Harassing, degrading, discriminatory, or humiliating educational material.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Deliberate misinformation presented as established health or safety information.</span></li>
                    <li class="flex gap-3 rounded-xl border border-rose-100 bg-white/80 p-4 text-sm leading-6 text-gray-700 md:col-span-2"><span class="font-bold text-rose-600" aria-hidden="true">!</span><span>Unlicensed or copyright-infringing media.</span></li>
                </ul>
                <p class="mt-5 text-sm leading-6 text-gray-700">These categories are stated in professional, policy-oriented terms. Existing authorization and moderation systems remain responsible for platform decisions and enforcement.</p>
            </section>

            <section id="sources-references" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-slate-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Sources &amp; References</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Strong sourcing helps instructors keep health, safety, and legal information responsible and current.</p>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Government and public-health agencies</h3><p class="mt-1 text-sm leading-5 text-gray-600">Use current public guidance when discussing health, safety, or public information.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Recognized health organizations</h3><p class="mt-1 text-sm leading-5 text-gray-600">Prefer established organizations with transparent, evidence-informed materials.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Peer-reviewed research</h3><p class="mt-1 text-sm leading-5 text-gray-600">Use research to support claims when the topic calls for evidence or nuance.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4"><h3 class="font-semibold text-gray-900">Educational and professional organizations</h3><p class="mt-1 text-sm leading-5 text-gray-600">Established educational institutions and professional medical or educational organizations can provide useful context.</p></div>
                    <div class="rounded-xl border border-gray-100 p-4 sm:col-span-2"><h3 class="font-semibold text-gray-900">Official legal sources</h3><p class="mt-1 text-sm leading-5 text-gray-600">When discussing laws or rights, use official government or legal sources relevant to the jurisdiction and verify them before publishing.</p></div>
                </div>
                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                    Verify health, safety, and legal claims against reliable, current sources before publishing. Cite or attribute sources where appropriate. Do not fabricate citations, and do not claim that a source endorses a specific Conscious Connections rule unless that endorsement has been verified.
                </div>
            </section>

            <section id="before-you-publish" class="scroll-mt-24 rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 via-white to-indigo-50 p-5 shadow-sm md:p-7">
                <div class="border-l-4 border-brand-600 pl-4">
                    <h2 class="text-xl font-semibold text-gray-900">Before You Publish Checklist</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Use this final pass for the module and every lesson, topic, activity, checkpoint, quiz, and media item inside it.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Educational Quality</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Clear learning objective</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Accurate information</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Reliable sources</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Activities support the objective</span></li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Learner Suitability</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Appropriate learner category</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Developmentally appropriate language</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Appropriate examples and visuals</span></li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Safety</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>No unnecessary sexualization</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>No unnecessary personal disclosures</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Sensitive topics handled appropriately</span></li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Accessibility</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Image alt text</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Video captions where appropriate</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Accessible structure and interactions</span></li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Media</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Appropriate usage rights or license</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Attribution where required</span></li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-white bg-white/85 p-5">
                        <h3 class="font-semibold text-gray-900">Final Review</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Content meets platform standards</span></li>
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">□</span><span>Content is appropriate for the selected learners</span></li>
                        </ul>
                    </div>
                </div>
            </section>
        </article>
    </div>
</div>
@endsection
