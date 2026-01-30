<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Users']
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('User Management') }}
                </h2>
            </div>
            <a href="{{ route('admin.users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New User
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
            @endif

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..." 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <select name="role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Roles</option>
                                <option value="learner" {{ request('role') == 'learner' ? 'selected' : '' }}>Learner</option>
                                <option value="counselor" {{ request('role') == 'counselor' ? 'selected' : '' }}>Counselor</option>
                                <option value="clinic" {{ request('role') == 'clinic' ? 'selected' : '' }}>Clinic</option>
                                <option value="organization" {{ request('role') == 'organization' ? 'selected' : '' }}>Organization</option>
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div>
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                                Filter
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($user->status === 'active') bg-green-100 text-green-800
                                            @elseif($user->status === 'suspended') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.users.show', $user) }}" class="inline-block px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 mr-2">View</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-block px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 mr-2">Edit</a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700" 
                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
