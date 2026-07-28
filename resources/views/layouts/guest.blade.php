{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sign in</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Prevent browsers from re-submitting cached POST pages (helps with "page expired" on back nav) --}}
    <meta http-equiv="Cache-Control" content="no-store" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    {{-- CSRF token for this page (also used by any JS/AJAX if needed) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Tailwind via CDN (swap for your asset pipeline if preferred) --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-xl rounded-2xl p-8">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold">Welcome back</h1>
                <p class="text-gray-500">Sign in to continue</p>
            </div>

            {{-- Session flash (e.g., password reset link sent) --}}
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 text-green-700 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Global error (e.g., auth failed) --}}
            @if ($errors->has('email') || $errors->has('password') || $errors->has('auth'))
                <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3 text-sm">
                    {{ $errors->first('auth') ?? 'Please check your credentials and try again.' }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" autocomplete="on" novalidate>
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                        placeholder="you@example.com"
                    />
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-gray-700 hover:underline">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                        placeholder="••••••••"
                    />
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="mb-6 flex items-center">
                    <input id="remember" name="remember" type="checkbox"
                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                           {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full inline-flex justify-center rounded-xl bg-gray-900 px-4 py-2.5 text-white font-medium hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    Sign in
                </button>
            </form>

            {{-- Optional: registration link --}}
            @if (Route::has('register'))
                <p class="mt-6 text-center text-sm text-gray-600">
                    Don’t have an account?
                    <a href="{{ route('register') }}" class="font-medium text-gray-900 hover:underline">Create one</a>
                </p>
            @endif
        </div>

        {{-- Footer / app name --}}
        <p class="mt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>

    {{-- Optional: set default CSRF for any axios/fetch use later --}}
    <script>
        // If you use axios:
        // if (window.axios) {
        //     window.axios.defaults.headers.common['X-CSRF-TOKEN'] =
        //         document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // }
        // Prevent double-submit on slow networks
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) { btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); }
            });
        }
    </script>
</body>
</html>
