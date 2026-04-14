@if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100" data-users-pagination>
        {{ $users->withQueryString()->links() }}
    </div>
@endif
