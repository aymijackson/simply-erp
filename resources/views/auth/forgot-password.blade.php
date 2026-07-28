<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap 5 CSS --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="text-center mb-4">
                        Forgot Password
                    </h4>

                    <p class="text-muted small">
                        Enter your email address and we'll send you a password reset link.
                    </p>

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Email Password Reset Link
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            Back to login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
