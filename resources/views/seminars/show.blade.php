@extends(auth()->user()?->isInstructor() ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', $seminar->title.' | '.config('app.name', 'Conscious Connections'))

@section('content')
    @php($speakerApplication = $speakerApplication ?? null)
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">{{ $seminar->status }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $seminar->type }}</span>
                    </div>
                    <h1 class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $seminar->title }}</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $seminar->connector?->name }}</p>
                </div>

                <div class="w-full rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950 lg:w-72">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $seminar->localStartsAt()?->format('M d, Y') }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $seminar->localStartsAt()?->format('g:i A') }} - {{ $seminar->localEndsAt()?->format('g:i A') }} PHT</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $seminar->capacity ? $seminar->registrants()->active()->count().' / '.$seminar->capacity.' registered' : 'Open capacity' }}</div>

                    <div class="mt-4">
                        @if($canJoinLivestream)
                            <a href="{{ route('seminars.join', $seminar) }}" class="block w-full rounded-lg bg-gray-900 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-black">Join Livestream</a>
                        @elseif($registration)
                            <form method="POST" action="{{ route('seminars.cancel-registration', $seminar) }}">
                                @csrf
                                <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancel Registration</button>
                            </form>
                        @elseif($canRegister)
                            <form method="POST" action="{{ route('seminars.register', $seminar) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Register</button>
                            </form>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $registrationError }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_18rem]">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">About</h2>
                    <div class="mt-2 space-y-4 text-sm leading-6 text-gray-700 dark:text-gray-300">
                        @if($seminar->purpose)
                            <p>{{ $seminar->purpose }}</p>
                        @endif
                    </div>
                </div>
                <dl class="space-y-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm dark:border-gray-800 dark:bg-gray-950">
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Category</dt>
                        <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $seminar->categoryDisplayName() }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Speakers</dt>
                        <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $seminar->speakers->pluck('display_name')->filter()->join(', ') ?: 'To be announced' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Audience</dt>
                        <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $seminar->target_participants) }}</dd>
                    </div>
                    @if($seminar->location)
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Location</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-300">{{ $seminar->location }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @auth
                @if(auth()->user()->isInstructor())
                    <section class="mt-8 rounded-2xl border border-gray-100 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950">
                        <h2 class="font-bold text-gray-900 dark:text-white">Apply as speaker</h2>
                        @if($speakerApplication)
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Application status: <span class="font-semibold capitalize text-gray-900 dark:text-white">{{ $speakerApplication->status }}</span></p>
                            @if($speakerApplication->review_note)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $speakerApplication->review_note }}</p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('seminars.apply-speaker', $seminar) }}" class="mt-4 grid gap-3">
                                @csrf
                                <textarea name="motivation" rows="3" required placeholder="Motivation statement" class="rounded-xl border-gray-300 text-sm">{{ old('motivation') }}</textarea>
                                <textarea name="expertise" rows="3" required placeholder="Expertise" class="rounded-xl border-gray-300 text-sm">{{ old('expertise', auth()->user()->instructorProfile?->primary_expertise) }}</textarea>
                                <textarea name="experience" rows="3" required placeholder="Relevant experience" class="rounded-xl border-gray-300 text-sm">{{ old('experience') }}</textarea>
                                <textarea name="supporting_info" rows="2" placeholder="Optional supporting information" class="rounded-xl border-gray-300 text-sm">{{ old('supporting_info') }}</textarea>
                                <button class="w-fit rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Submit Application</button>
                            </form>
                        @endif
                    </section>
                @endif
            @endauth
        </div>
    </div>
@endsection
