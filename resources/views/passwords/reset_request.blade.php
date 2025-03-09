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
                <img src="{{ asset('images/aastu-logo.png') }}" 
                     alt="AASTU Research Grant Management System" 
                     class="h-16">
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-semibold text-center text-gray-800 mb-8">
                Reset your password
            </h1>

            <!-- Form -->
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <!-- Email Input -->
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Id :
                    </label>
                    <div class="relative">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               placeholder="info@provistechnologies.com"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-colors"
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" 
                                 class="h-5 w-5 text-gray-400" 
                                 viewBox="0 0 20 20" 
                                 fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="text-red-500 text-sm mt-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Success Message -->
                @if (session('status'))
                    <div class="text-green-500 text-sm mt-1">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Help Text -->
                <p class="text-sm text-gray-600">
                    Need to reset your password? No problem! Just click the button below and 
                    you'll be on your way. If you did not make this request, please ignore this email
                </p>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-[#1e2f97] text-white py-2 px-4 rounded-md hover:bg-[#162073] transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Reset your password
                </button>
            </form>
        </div>
    </div>

    <!-- Add any additional scripts here -->
    @vite('resources/js/app.js')
</body>
</html>