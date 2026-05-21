<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Sign In | Paces</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />

    {{-- Theme config (must load before vendors.min.css so skin/theme is applied) --}}
    <script src="{{ asset('assets/js/config.js') }}"></script>

    {{-- Vendor + App css (same stack as the layout's app.blade.php) --}}
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />
    <link id="app-style" href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
    {{-- Decorative auth background, matches the theme's auth-sign-in.html --}}
    <div class="position-absolute top-0 end-0">
        <img src="{{ asset('assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" alt="auth-card-bg" />
    </div>
    <div class="position-absolute bottom-0 start-0" style="transform: rotate(180deg)">
        <img src="{{ asset('assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" alt="auth-card-bg" />
    </div>

    <div class="auth-box overflow-hidden align-items-center d-flex">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-5 col-md-6 col-sm-8">
                    <div class="card p-4">
                        <div class="auth-brand text-center mb-2">
                            <a href="{{ url('/') }}" class="logo-dark">
                                <img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo" />
                            </a>
                            <a href="{{ url('/') }}" class="logo-light">
                                <img src="{{ asset('assets/images/logo.png') }}" alt="logo" />
                            </a>
                            <h4 class="fw-bold text-dark mt-3">Welcome back 👋</h4>
                            <p class="text-muted w-lg-75 mx-auto">
                                Sign in with your email and password to continue.
                            </p>
                        </div>

                        {{-- Status flash (e.g. "You have been signed out.") --}}
                        @if (session('status'))
                            <div class="alert alert-success py-2 px-3 mb-3" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        {{-- Non-field-specific errors (rare; auth errors are bound to `email`) --}}
                        @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
                            <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="userEmail" class="form-label">
                                    Email address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="email"
                                           name="email"
                                           id="userEmail"
                                           value="{{ old('email') }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="you@example.com"
                                           autocomplete="username"
                                           required
                                           autofocus />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="userPassword" class="form-label">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           name="password"
                                           id="userPassword"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="••••••••"
                                           autocomplete="current-password"
                                           required />
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input form-check-input-light fs-14"
                                           type="checkbox"
                                           name="remember"
                                           value="1"
                                           id="rememberMe"
                                           {{ old('remember') ? 'checked' : '' }} />
                                    <label class="form-check-label" for="rememberMe">Keep me signed in</label>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-semibold py-2">
                                    Sign In
                                </button>
                            </div>
                        </form>
                    </div>

                    <p class="text-center text-muted mt-4 mb-0">
                        ©
                        <script>document.write(new Date().getFullYear())</script>
                        Paces — by <span class="fw-semibold">Coderthemes</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Theme JS --}}
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>
