@extends(auth()->user()?->isInstructor() ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', $connector->name)
@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6">
    <a href="{{ route('connectors.index') }}" class="text-sm font-semibold text-purple-700">Back to connectors</a>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
            <div class="flex gap-4">
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-purple-100 text-2xl font-bold text-purple-700">
                    {{ strtoupper(mb_substr($connector->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">{{ $categories[$connector->category] ?? str($connector->category)->headline() }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">{{ $connector->description ?: 'No description provided yet.' }}</p>
                </div>
            </div>
            @include('connectors.partials.membership-button', ['connector' => $connector, 'state' => $membershipState])
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm font-semibold text-gray-500">Members</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($connector->memberships_count) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm font-semibold text-gray-500">Capacity</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">Open</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm font-semibold text-gray-500">Seminars hosted</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($connector->seminars_count) }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="font-bold text-gray-900">Public information</h2>
        <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="text-gray-500">Location</dt><dd class="mt-1 font-semibold text-gray-900">{{ $connector->address_line ?: 'Not provided' }}</dd></div>
            <div><dt class="text-gray-500">Contact</dt><dd class="mt-1 font-semibold text-gray-900">{{ $connector->organization_email ?: 'Not published' }}</dd></div>
            <div><dt class="text-gray-500">Website</dt><dd class="mt-1 font-semibold text-gray-900">{{ $connector->website_url ?: 'Not published' }}</dd></div>
            <div><dt class="text-gray-500">Status</dt><dd class="mt-1 font-semibold text-gray-900">{{ str($connector->status)->headline() }}</dd></div>
        </dl>
    </section>
</div>
@endsection
