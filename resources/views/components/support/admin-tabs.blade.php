<nav aria-label="Support management" class="overflow-x-auto">
    <div class="inline-flex gap-1 rounded-xl border border-purple-100 bg-white p-1 shadow-sm">
        @foreach([
            ['route' => 'admin.help.articles.index', 'pattern' => 'admin.help.articles.*', 'label' => 'Help Articles'],
            ['route' => 'admin.help.categories.index', 'pattern' => 'admin.help.categories.*', 'label' => 'Categories'],
        ] as $item)
            <a href="{{ route($item['route']) }}" @class(['inline-flex min-h-10 items-center whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-400', 'bg-brand-700 text-white' => request()->routeIs($item['pattern']), 'text-gray-600 hover:bg-purple-50 hover:text-purple-800' => ! request()->routeIs($item['pattern'])]) @if(request()->routeIs($item['pattern'])) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
