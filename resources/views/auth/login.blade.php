<!doctype html>
<html lang="en" data-menu-color="gradient">

<head>
    <meta charset="utf-8" />
    <title>Sign In | {{ $settings->get('site_name', 'Sukaina Gems') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ $settings->faviconUrl() ?? asset('assets/images/favicon.ico') }}" />

    {{-- Theme config (must load before vendors.min.css so skin/theme is applied) --}}
    <script src="{{ asset('assets/js/config.js') }}"></script>

    {{-- Vendor + App css (same stack as the layout's app.blade.php) --}}
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />
    <link id="app-style" href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- A serif display face for the brand mark only — everything else stays on the app's default font stack --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --auth-teal-deep: #0b3d3a;
            --auth-teal: #0f5e57;
            --auth-teal-light: #1a8a7e;
            --auth-gold: #c9a35c;
            --auth-border: #e5e7eb;
            --auth-text-muted: #6b7280;
        }

        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: var(--bs-body-font-family, 'Inter', sans-serif);
            background: #fff;
        }

        .auth-shell { min-height: 100vh; display: flex; }

        /* ── Left branding panel ─────────────────────────────── */
        .auth-brand-panel {
            position: relative;
            flex: 0 0 44%;
            max-width: 44%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 56px;
            color: #fff;
            background:
                radial-gradient(circle at 15% 15%, rgba(201, 163, 92, 0.18), transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(26, 138, 126, 0.35), transparent 50%),
                linear-gradient(160deg, var(--auth-teal-deep) 0%, #08211f 100%);
            overflow: hidden;
        }
        .auth-brand-panel::before {
            /* Faceted diamond watermark, pure CSS */
            content: '';
            position: absolute;
            width: 620px;
            height: 620px;
            right: -180px;
            bottom: -180px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 40px;
            transform: rotate(24deg);
            background:
                linear-gradient(135deg, rgba(255,255,255,0.05), transparent 60%);
        }
        .auth-brand-panel::after {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            right: 60px;
            top: -80px;
            border: 1px solid rgba(201, 163, 92, 0.18);
            border-radius: 32px;
            transform: rotate(-16deg);
        }

        .auth-brand-mark {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            margin-bottom: 28px;
        }
        .auth-brand-mark img { max-height: 42px; max-width: 42px; width: auto; }

        .auth-brand-name {
            position: relative;
            z-index: 1;
            font-family: 'Playfair Display', 'Georgia', serif;
            font-weight: 700;
            font-size: 2.25rem;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .auth-brand-tagline {
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.9375rem;
            max-width: 340px;
            margin-bottom: 40px;
        }

        .auth-feature-list {
            position: relative;
            z-index: 1;
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .auth-feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.85);
        }
        .auth-feature-icon {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--auth-gold);
            font-size: 1rem;
        }

        /* ── Right form panel ────────────────────────────────── */
        .auth-form-panel {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            background: #fff;
        }
        .auth-form-inner { width: 100%; max-width: 400px; }

        .auth-form-mobile-brand {
            display: none;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .auth-form-mobile-brand img { max-height: 40px; width: auto; }
        .auth-form-mobile-brand span {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: #111827;
        }

        .auth-form-heading { font-weight: 700; font-size: 1.5rem; color: #111827; margin-bottom: 6px; }
        .auth-form-sub { color: var(--auth-text-muted); font-size: 0.9rem; margin-bottom: 28px; }

        .auth-field-label { font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .auth-input-group { position: relative; }
        .auth-input-group .auth-input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: 1.05rem; pointer-events: none;
        }
        .auth-input-group input.form-control {
            padding-left: 42px;
            height: 46px;
            border-radius: 10px;
            border-color: var(--auth-border);
        }
        .auth-input-group input.form-control:focus {
            border-color: var(--auth-teal-light);
            box-shadow: 0 0 0 3px rgba(26, 138, 126, 0.14);
        }
        .auth-toggle-password {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; color: #9ca3af;
            width: 32px; height: 32px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .auth-toggle-password:hover { color: #4b5563; background: #f3f4f6; }

        .auth-submit-btn {
            height: 46px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            background: linear-gradient(135deg, var(--auth-teal), var(--auth-teal-light));
            color: #fff;
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .auth-submit-btn:hover {
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 94, 87, 0.28);
            transform: translateY(-1px);
        }
        .auth-submit-btn:active { transform: translateY(0); }

        .auth-footer-note { color: var(--auth-text-muted); font-size: 0.8125rem; }

        @media (max-width: 991.98px) {
            .auth-brand-panel { display: none; }
            .auth-form-panel { flex: 1 1 100%; max-width: 100%; }
            .auth-form-mobile-brand { display: flex; }
        }
    </style>
</head>

<body>
    <div class="auth-shell">

        {{-- ── Left: brand panel (hidden on small screens) ───────── --}}
        <div class="auth-brand-panel">
            <div class="auth-brand-mark">
                <img src="{{ $settings->logoUrl() ?? asset('assets/images/logo-black.png') }}"
                    alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" />
            </div>
            <div class="auth-brand-name">{{ $settings->get('site_name', 'Sukaina Gems') }}</div>
            <p class="auth-brand-tagline">
                Precision inventory, purchasing, and sales for fine gemstones — from intake to the showroom floor.
            </p>
            <ul class="auth-feature-list">
                <li>
                    <span class="auth-feature-icon"><i class="ti ti-shopping-cart"></i></span>
                    Purchases &amp; supplier management
                </li>
                <li>
                    <span class="auth-feature-icon"><i class="ti ti-receipt"></i></span>
                    Point-of-sale &amp; online orders
                </li>
                <li>
                    <span class="auth-feature-icon"><i class="ti ti-clipboard-list"></i></span>
                    Piece-level stock audits
                </li>
                <li>
                    <span class="auth-feature-icon"><i class="ti ti-chart-donut-2"></i></span>
                    Real-time reporting
                </li>
            </ul>
        </div>

        {{-- ── Right: sign-in form ────────────────────────────────── --}}
        <div class="auth-form-panel">
            <div class="auth-form-inner">

                <div class="auth-form-mobile-brand">
                    <img src="{{ $settings->logoUrl() ?? asset('assets/images/logo-black.png') }}"
                        alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" />
                    <span>{{ $settings->get('site_name', 'Sukaina Gems') }}</span>
                </div>

                <div class="auth-form-heading">Welcome back</div>
                <p class="auth-form-sub">Sign in with your email and password to continue.</p>

                {{-- Status flash (e.g. "You have been signed out.") --}}
                @if (session('status'))
                    <div class="alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-circle-check fs-18"></i> {{ session('status') }}
                    </div>
                @endif

                {{-- Non-field-specific errors (rare; auth errors are bound to `email`) --}}
                @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
                    <div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-alert-circle fs-18"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="userEmail" class="auth-field-label">
                            Email address <span class="text-danger">*</span>
                        </label>
                        <div class="auth-input-group">
                            <i class="ti ti-mail auth-input-icon"></i>
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
                        <label for="userPassword" class="auth-field-label">
                            Password <span class="text-danger">*</span>
                        </label>
                        <div class="auth-input-group">
                            <i class="ti ti-lock auth-input-icon"></i>
                            <input type="password"
                                   name="password"
                                   id="userPassword"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••"
                                   autocomplete="current-password"
                                   required
                                   style="padding-right: 42px;" />
                            <button type="button" class="auth-toggle-password" id="togglePassword" tabindex="-1" aria-label="Show password">
                                <i class="ti ti-eye" id="togglePasswordIcon"></i>
                            </button>
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
                        <button type="submit" class="btn auth-submit-btn">
                            Sign In
                        </button>
                    </div>
                </form>

                <p class="text-center auth-footer-note mt-4 mb-0">
                    &copy;
                    <script>document.write(new Date().getFullYear())</script>
                    {{ $settings->get('site_name', 'Sukaina Gems') }} &mdash; by <span class="fw-semibold">macromend.com</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Theme JS --}}
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <script>
        (function () {
            var toggleBtn = document.getElementById('togglePassword');
            var pwdInput = document.getElementById('userPassword');
            var icon = document.getElementById('togglePasswordIcon');
            if (!toggleBtn || !pwdInput || !icon) return;

            toggleBtn.addEventListener('click', function () {
                var isHidden = pwdInput.type === 'password';
                pwdInput.type = isHidden ? 'text' : 'password';
                icon.classList.toggle('ti-eye', !isHidden);
                icon.classList.toggle('ti-eye-off', isHidden);
                toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>

</html>
