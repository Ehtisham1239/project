<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CRM Platform')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    @auth
    <nav class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="font-semibold text-lg">CRM Platform</div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-600">{{ auth()->user()->name }} ({{ auth()->user()->roles->pluck('label')->join(', ') }})</span>
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">Manage Users</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 hover:underline">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-7xl mx-auto px-4 py-8">
        @if(session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
