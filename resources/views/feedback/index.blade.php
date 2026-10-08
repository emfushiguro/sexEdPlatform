@extends($supportLayout)

@section('title', 'My Tickets | Conscious Connections')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10">
        <nav aria-label="Breadcrumb" class="mb-7 mt-2 text-sm">
            <a href="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to Help Center</a>
        </nav>

        <div>
            <x-support.page-header icon="feedback" title="My Tickets" description="Track your support tickets and read updates from the platform team.">
                <x-slot:action>
                    <a href="{{ route($supportRoutes['feedback']['create']['name'], $supportRoutes['feedback']['create']['parameters']) }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">Submit a Ticket</a>
                </x-slot:action>
            </x-support.page-header>
        </div>

        <nav aria-label="Ticket views" class="mt-7 overflow-x-auto">
            <div class="inline-flex rounded-xl border border-purple-100 bg-white p-1 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                @foreach(['all' => 'All', 'active' => 'Active', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                    <a href="{{ route($supportRoutes['feedback']['index']['name'], array_merge($supportRoutes['feedback']['index']['parameters'], $value === 'all' ? [] : ['view' => $value])) }}" @class(['inline-flex min-h-10 items-center rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-400', 'bg-brand-700 text-white' => $viewFilter === $value, 'text-gray-600 hover:bg-purple-50 hover:text-purple-800 dark:text-gray-300 dark:hover:bg-purple-950' => $viewFilter !== $value]) @if($viewFilter === $value) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </nav>

        <section aria-label="Support tickets" class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @forelse($feedback as $item)
                <a href="{{ route($supportRoutes['feedback']['show']['name'], array_merge($supportRoutes['feedback']['show']['parameters'], ['platformFeedback' => $item])) }}" class="group flex items-start gap-4 border-b border-gray-100 p-5 transition last:border-b-0 hover:bg-purple-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-400 sm:p-6 dark:border-gray-800 dark:hover:bg-purple-950/30">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                        <x-support.type-icon :type="$item->type" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-start justify-between gap-2">
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $item->subject }}</span>
                            <x-support.status-badge :status="$item->status" />
                        </span>
                        <span class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $item->type->label() }}</span>
                            <span>{{ $item->reference_number }}</span>
                            <span>Submitted {{ $item->created_at->format('M j, Y') }}</span>
                        </span>
                        <span class="mt-2 block text-sm leading-6 text-gray-600 dark:text-gray-300">{{ str($item->description)->squish()->limit(150) }}</span>
                    </span>
                    <svg aria-hidden="true" class="mt-3 h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-purple-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @empty
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300"><x-ui.support-icon name="feedback" class="h-6 w-6" /></span>
                    @if($viewFilter === 'all')
                        <h2 class="mt-4 font-semibold text-gray-950 dark:text-white">No tickets submitted yet</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Submit a ticket when you need help or want to share a suggestion.</p>
                        <a href="{{ route($supportRoutes['feedback']['create']['name'], $supportRoutes['feedback']['create']['parameters']) }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Submit your first ticket</a>
                    @else
                        <h2 class="mt-4 font-semibold text-gray-950 dark:text-white">No {{ $viewFilter }} tickets</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">There are no submissions in this view.</p>
                        <a href="{{ route($supportRoutes['feedback']['index']['name'], $supportRoutes['feedback']['index']['parameters']) }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl border border-purple-200 px-4 py-2.5 text-sm font-semibold text-purple-700 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300 dark:hover:bg-purple-950">View all tickets</a>
                    @endif
                </div>
            @endforelse
        </section>

        @if($feedback->hasPages())
            <div class="mt-6">{{ $feedback->links() }}</div>
        @endif
    </div>
</main>
@endsection
