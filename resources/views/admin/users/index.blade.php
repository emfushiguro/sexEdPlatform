@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">User Management</h1>
        <a href="{{ route('admin.users.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow font-semibold">
            + Create User
        </a>
    </div>

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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-blue-600">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">Total Users</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-green-600">{{ $stats['active'] }}</div>
            <div class="text-sm text-gray-500">Active Users</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-indigo-600">{{ $stats['learners'] }}</div>
            <div class="text-sm text-gray-500">Learners</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-yellow-600">{{ $stats['premium'] }}</div>
            <div class="text-sm text-gray-500">Premium Users</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white p-3 rounded shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user..." class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
            <select name="role" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
                <option value="">All Roles</option>
                <option value="learner" @selected(request('role')=='learner')>Learner</option>
                <option value="instructor" @selected(request('role')=='instructor')>Instructor</option>
                <option value="counselor" @selected(request('role')=='counselor')>Counselor</option>
                <option value="clinic" @selected(request('role')=='clinic')>Clinic</option>
                <option value="organization" @selected(request('role')=='organization')>Organization</option>
                <option value="admin" @selected(request('role')=='admin')>Admin</option>
            </select>
            <select name="status" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
                <option value="">All Status</option>
                <option value="active" @selected(request('status')=='active')>Active</option>
                <option value="inactive" @selected(request('status')=='inactive')>Inactive</option>
                <option value="suspended" @selected(request('status')=='suspended')>Suspended</option>
            </select>
            <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded shadow text-sm" type="submit">Filter</button>
        </div>
    </form>

    <!-- Users Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
        <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 border font-semibold text-sm">Name</th>
                    <th class="px-4 py-3 border font-semibold text-sm">Email</th>
                    <th class="px-4 py-3 border font-semibold text-sm">Role</th>
                    <th class="px-4 py-3 border font-semibold text-sm">Status</th>
                    <th class="px-4 py-3 border font-semibold text-sm">Joined</th>
                    <th class="px-4 py-3 border font-semibold text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr class="hover:bg-gray-50 align-middle">
                    <td class="border px-4 py-3 align-middle">
                        <div class="font-semibold text-base">{{ $user->name }}</div>
                    </td>
                    <td class="border px-4 py-3 align-middle text-sm">{{ $user->email }}</td>
                    <td class="border px-4 py-3 align-middle">
                        @php
                            $roleColors = [
                                'learner' => 'bg-blue-100 text-blue-800',
                                'instructor' => 'bg-purple-100 text-purple-800',
                                'counselor' => 'bg-green-100 text-green-800',
                                'clinic' => 'bg-teal-100 text-teal-800',
                                'organization' => 'bg-indigo-100 text-indigo-800',
                                'admin' => 'bg-red-100 text-red-800',
                            ];
                            $roleColor = $roleColors[$user->role] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-block {{ $roleColor }} text-xs px-3 py-1 rounded font-semibold">{{ ucfirst($user->role) }}</span>
                    </td>
                    <td class="border px-4 py-3 align-middle">
                        @if($user->status=='active')
                            <span class="inline-block bg-green-100 text-green-800 text-xs px-3 py-1 rounded font-semibold">Active</span>
                        @elseif($user->status=='suspended')
                            <span class="inline-block bg-red-100 text-red-800 text-xs px-3 py-1 rounded font-semibold">Suspended</span>
                        @else
                            <span class="inline-block bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded font-semibold">{{ ucfirst($user->status) }}</span>
                        @endif
                    </td>
                    <td class="border px-4 py-3 align-middle text-sm">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="border px-4 py-3 align-middle">
                        <a href="{{ route('admin.users.show', $user) }}" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-1 rounded text-xs font-semibold mr-1">View</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-4 py-1 rounded text-xs font-semibold mr-1">Edit</a>
                        @if($user->id !== auth()->id())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-800 px-4 py-1 rounded text-xs font-semibold" 
                                onclick="return confirm('Are you sure you want to delete this user?')">
                                Delete
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
