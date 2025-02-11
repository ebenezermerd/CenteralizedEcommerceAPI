<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - AASTU Research Grant Management System</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-b from-purple-50 to-blue-50">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl p-8 transform transition-all hover:scale-[1.01]">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <img src="{{ asset('images/aastu-logo.png') }}" alt="AASTU Research Grant Management System" class="h-16">
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-semibold text-center text-gray-800 mb-8">
                Enter New Password
            </h1>

            <!-- Form -->
            <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <!-- New Password Input -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        New Password:
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-colors"
                        >
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div class="space-y-2">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm New Password:
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-colors"
                        >
                    </div>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="text-red-500 text-sm">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Help Text -->
                <p class="text-sm text-gray-600">
                    Please enter your new password. Make sure it's at least 8 characters long and includes a mix of letters, numbers, and symbols for better security.
                </p>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-[#1e2f97] text-white py-2 px-4 rounded-md hover:bg-[#162073] transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Set New Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>