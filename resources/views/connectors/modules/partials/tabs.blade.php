<nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-3" aria-label="Connector module navigation">
    <a href="{{ route('connector.modules', $connector) }}" class="rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700">
        Marketplace
    </a>
    <a href="{{ route('connector.modules.library.index', $connector) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
        Purchased Modules
    </a>
    @if(isset($entitlement) && $entitlement)
        <a href="{{ route('connector.modules.library.access', [$connector, $entitlement]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
            Access Management
        </a>
    @endif
</nav>
