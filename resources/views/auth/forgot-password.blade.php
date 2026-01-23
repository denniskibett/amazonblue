<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | {{ SystemHelper::appName() }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>
<body
    x-data="{ darkMode: false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="relative z-1 flex h-screen w-full overflow-hidden bg-white dark:bg-gray-900">

    <div class="flex flex-1 flex-col rounded-2xl p-6 sm:rounded-none sm:border-0 sm:p-8">
        <div class="mx-auto w-full max-w-md pt-5 sm:py-10">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                ← Back to dashboard
            </a>
        </div>

        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
            <div class="mb-5 sm:mb-8">
                <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90 sm:text-title-md">
                    Forgot Password
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Enter your email and we will send you a link to reset your password.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="space-y-5">
                    <!-- Email Address -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email<span class="text-error-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="info@gmail.com"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                        />
                        @error('email')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600"
                        >
                            Send Password Reset Link
                        </button>
                    </div>

                    <div class="mt-5 text-center text-sm text-gray-700 dark:text-gray-400">
                        <a href="{{ route('login') }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400">
                            Back to Sign In
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right side (Optional design or illustration) -->
    <div class="relative z-1 hidden flex-1 items-center justify-center bg-brand-950 p-8 dark:bg-white/5 lg:flex">
        <div class="flex max-w-xs flex-col items-center">
            <a href="{{ route('dashboard') }}" class="mb-4 block">
                  <img
                        class=" dark:block h-40 w-auto"
                        src="{{ SystemHelper::logoUrl(true) ?? asset('images/logo/auth-logo-dark.svg') }}"
                        alt="{{ SystemHelper::appName() }} Logo"
                    />
            </a>
            <p class="text-center text-gray-400 dark:text-white/60">{{ SystemHelper::slogan() }}</p>
        </div>
    </div>

</div>

</body>
</html>
