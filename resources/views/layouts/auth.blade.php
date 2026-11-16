<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Authentication') - BrewSync Coffee</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bs-espresso: #24140E;
            --bs-roast: #3E2317;
            --bs-caramel: #C68B59;
            --bs-caramel-hover: #B07545;
            --bs-cream: #FAF6F0;
            --bs-beige: #EFE6DD;
            --bs-muted: #8C7B73;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bs-cream);
            color: var(--bs-espresso);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image: radial-gradient(#EFE6DD 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }

        .auth-card {
            background: #FFFFFF;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(36, 20, 14, 0.08);
            border: 1px solid var(--bs-beige);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--bs-espresso) 0%, var(--bs-roast) 100%);
            padding: 2.25rem 2rem 2rem;
            text-align: center;
            color: #FFFFFF;
            position: relative;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #FFFFFF;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .brand-logo i {
            color: var(--bs-caramel);
        }

        .auth-body {
            padding: 2.25rem 2rem;
        }

        .btn-coffee {
            background-color: var(--bs-caramel);
            border-color: var(--bs-caramel);
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .btn-coffee:hover, .btn-coffee:focus {
            background-color: var(--bs-caramel-hover);
            border-color: var(--bs-caramel-hover);
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(198, 139, 89, 0.35);
        }

        .btn-outline-coffee {
            background-color: transparent;
            border: 1.5px solid var(--bs-caramel);
            color: var(--bs-roast);
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-outline-coffee:hover {
            background-color: var(--bs-beige);
            color: var(--bs-espresso);
            border-color: var(--bs-caramel-hover);
        }

        .form-control {
            border: 1.5px solid #E2D9D0;
            border-radius: 0.65rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            border-color: var(--bs-caramel);
            box-shadow: 0 0 0 4px rgba(198, 139, 89, 0.15);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--bs-espresso);
            margin-bottom: 0.4rem;
        }

        .auth-footer-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--bs-beige);
            font-size: 0.875rem;
        }

        .auth-link {
            color: var(--bs-caramel);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-link:hover {
            color: var(--bs-caramel-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <a href="{{ route('home') }}" class="brand-logo">
                <i class="fa-solid fa-mug-hot"></i> BrewSync
            </a>
            <p class="text-white-50 mb-0 small">Coffee Shop Management & Online Ordering</p>
        </div>

        <div class="auth-body">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <div>{{ session('status') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')

            <div class="auth-footer-nav">
                <a href="{{ route('home') }}" class="btn-outline-coffee">
                    <i class="fa-solid fa-arrow-left"></i> Back to Home
                </a>
                @yield('footer_link')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
