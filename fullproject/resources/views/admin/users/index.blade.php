@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
    <h1 class="text-2xl font-semibold mb-6">Manage Users</h1>

    <div class="bg-white p-6 rounded-lg shadow-sm border mb-8">
        <h2 class="font-medium mb-4">Add New User</h2>
        <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <input name="name" placeholder="Full name" required class="border rounded px-3 py-2 text-sm">
            <input name="email" type="email" placeholder="Email" required class="border rounded px-3 py-2 text-sm">
            <input name="password" type="password" placeholder="Password" required class="border rounded px-3 py-2 text-sm">
            <select name="role" required class="border rounded px-3 py-2 text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->label }}</option>
                @endforeach
            </select>
            <button class="bg-blue-600 text-white rounded px-3 py-2 text-sm hover:bg-blue-700">Add User</button>
        </form>
        @error('email')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr class="border-t">
                        <td class="px-4 py-3">{{ $u->name }}</td>
                        <td class="px-4 py-3">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <select name="role" class="border rounded px-2 py-1 text-xs">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected($u->roles->pluck('name')->contains($role->name))>
                                            {{ $role->label }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="text-blue-600 text-xs hover:underline">Update</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $u->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs text-gray-600 hover:underline">
                                    {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
