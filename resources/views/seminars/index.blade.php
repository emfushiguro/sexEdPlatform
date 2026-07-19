@extends(auth()->user()?->isInstructor() ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'Seminars | '.config('app.name', 'Conscious Connections'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Seminars</h1>
            <p class="mt-1 text-sm text-gray-600">Browse upcoming connector-hosted learning sessions available to your account.</p>
        </div>

        <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-5">
            <input name="search" value="{{ request('search') }}" placeholder="Search seminars..." class="rounded-lg border-gray-300 text-sm">
            <select name="type" class="rounded-lg border-gray-300 text-sm">
                <option value="">All formats</option>
                @foreach(\App\Enums\SeminarType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded-lg border-gray-300 text-sm">
                <option value="">All categories</option>
                @foreach(config('seminars.categories') as $key => $label)
                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <input type="checkbox" name="upcoming" value="1" @checked(request()->boolean('upcoming')) class="rounded border-gray-300 text-purple-700 focus:ring-purple-500">
                <span>Upcoming only</span>
            </label>
            <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Filter</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($seminars as $seminar)
                @php($showRoute = auth()->user()?->isInstructor() ? 'instructor.seminars.show' : (auth()->user()?->isLearner() ? 'learner.seminars.show' : 'seminars.show'))
                <a href="{{ route($showRoute, $seminar) }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-purple-200 hover:shadow-md">
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">{{ $seminar->type }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $seminar->categoryDisplayName() }}</span>
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-gray-900">{{ $seminar->title }}</h2>
                    <p class="mt-2 line-clamp-3 text-sm text-gray-600">{{ $seminar->purpose }}</p>
                    <div class="mt-4 text-sm font-semibold text-gray-800">{{ $seminar->localStartsAt()?->format('M d, Y g:i A') }} PHT</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $seminar->connector?->name }}</div>
                    @php($registered = $seminar->active_registrants_count ?? $seminar->registrants()->active()->count())
                    <div class="mt-2 text-xs text-gray-500">{{ $registered }} / {{ $seminar->capacity ?? 'Open' }} registered</div>
                    <div class="mt-1 text-xs font-semibold {{ $seminar->capacity !== null && $registered >= $seminar->capacity ? 'text-rose-700' : 'text-emerald-700' }}">
                        {{ $seminar->capacity !== null && $registered >= $seminar->capacity ? 'Full' : 'Registration open' }}
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">
                    No eligible seminars are available right now.
                </div>
            @endforelse
        </div>
    </div>
@endsection
