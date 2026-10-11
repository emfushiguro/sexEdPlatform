@props(['routes'])

@auth
    @php
        $helpActive = request()->routeIs('help.*', 'connector.help.*');
        $createActive = request()->routeIs(
            'feedback.create',
            'feedback.create.legacy',
            'connector.feedback.create',
            'connector.feedback.create.legacy',
        );
        $historyActive = request()->routeIs(
            'feedback.index',
            'feedback.show',
            'feedback.attachment.show',
            'connector.feedback.index',
            'connector.feedback.show',
            'connector.feedback.attachment.show',
        );
    @endphp

    <nav data-support-workspace-nav aria-label="Support pages" class="overflow-x-auto pb-1">
        <div class="inline-flex min-w-full gap-1 rounded-xl border border-purple-100 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:min-w-0">
            <a
                href="{{ route($routes['help']['index']['name'], $routes['help']['index']['parameters']) }}"
                @class([
                    'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                    'bg-brand-700 text-white shadow-sm' => $helpActive,
                    'text-gray-600 hover:bg-purple-50 hover:text-brand-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' => ! $helpActive,
                ])
                @if($helpActive) aria-current="page" @endif
            >
                <x-ui.support-icon name="help" class="h-4 w-4 shrink-0" />
                <span>Help Center</span>
            </a>

            <a
                href="{{ route($routes['feedback']['create']['name'], $routes['feedback']['create']['parameters']) }}"
                @class([
                    'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                    'bg-brand-700 text-white shadow-sm' => $createActive,
                    'text-gray-600 hover:bg-purple-50 hover:text-brand-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' => ! $createActive,
                ])
                @if($createActive) aria-current="page" @endif
            >
                <x-ui.support-icon name="feedback" class="h-4 w-4 shrink-0" />
                <span>Submit a Ticket</span>
            </a>

            <a
                href="{{ route($routes['feedback']['index']['name'], $routes['feedback']['index']['parameters']) }}"
                @class([
                    'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                    'bg-brand-700 text-white shadow-sm' => $historyActive,
                    'text-gray-600 hover:bg-purple-50 hover:text-brand-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' => ! $historyActive,
                ])
                @if($historyActive) aria-current="page" @endif
            >
                <x-ui.support-icon name="feedback" class="h-4 w-4 shrink-0" />
                <span>My Tickets</span>
            </a>
        </div>
    </nav>
@endauth
