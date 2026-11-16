<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Artisan Coffee') - BrewSync Coffee Shop</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bs-espresso: #24140E;
            --bs-roast: #381F14;
            --bs-caramel: #C68B59;
            --bs-caramel-hover: #AF7545;
            --bs-gold: #D4A373;
            --bs-cream: #FAF6F0;
            --bs-beige: #EFE6DD;
            --bs-beige-light: #F5EFEB;
            --bs-muted: #7E6E66;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bs-cream);
            color: var(--bs-espresso);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }

        /* Navbar Styling */
        .navbar-brewsync {
            background-color: var(--bs-espresso);
            border-bottom: 2px solid rgba(198, 139, 89, 0.25);
            padding: 0.85rem 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: #FFFFFF !important;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .navbar-brand i {
            color: var(--bs-caramel);
            font-size: 1.5rem;
        }

        .nav-link {
            color: #E2D9D0 !important;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem !important;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #FFFFFF !important;
            background-color: rgba(198, 139, 89, 0.15);
        }

        /* Buttons */
        .btn-coffee {
            background-color: var(--bs-caramel);
            border-color: var(--bs-caramel);
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.6rem 1.35rem;
            border-radius: 0.65rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-coffee:hover, .btn-coffee:focus {
            background-color: var(--bs-caramel-hover);
            border-color: var(--bs-caramel-hover);
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 139, 89, 0.35);
        }

        .btn-outline-coffee {
            background-color: transparent;
            border: 1.5px solid var(--bs-caramel);
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.55rem 1.25rem;
            border-radius: 0.65rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-coffee:hover {
            background-color: var(--bs-caramel);
            color: #FFFFFF;
            border-color: var(--bs-caramel);
        }

        .cart-pill {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(198, 139, 89, 0.4);
            color: #FFFFFF;
            padding: 0.45rem 0.9rem;
            border-radius: 2rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .cart-pill:hover {
            background-color: var(--bs-caramel);
            color: #FFFFFF;
        }

        /* Product Cards */
        .coffee-card {
            background: #FFFFFF;
            border: 1px solid var(--bs-beige);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 4px 15px rgba(36, 20, 14, 0.04);
        }

        .coffee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(36, 20, 14, 0.1);
            border-color: var(--bs-gold);
        }

        .coffee-card-img-wrap {
            position: relative;
            padding-top: 66%;
            background-color: var(--bs-beige-light);
            overflow: hidden;
        }

        .coffee-card-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .coffee-card:hover .coffee-card-img {
            transform: scale(1.05);
        }

        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(36, 20, 14, 0.85);
            backdrop-filter: blur(4px);
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .availability-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
        }

        .badge-available {
            background-color: #198754;
            color: #FFFFFF;
        }

        .badge-unavailable {
            background-color: #DC3545;
            color: #FFFFFF;
        }

        .coffee-card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .coffee-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--bs-espresso);
            margin-bottom: 0.4rem;
        }

        .coffee-desc {
            color: var(--bs-muted);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }

        .coffee-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid var(--bs-beige);
        }

        .coffee-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--bs-roast);
        }

        /* Footer */
        .footer-brewsync {
            background: linear-gradient(180deg, var(--bs-roast) 0%, var(--bs-espresso) 100%);
            color: #FAF6F0;
            padding: 4.5rem 0 2rem;
            border-top: 3px solid var(--bs-caramel);
        }

        .footer-brewsync a {
            color: #D6C8BF;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-brewsync a:hover {
            color: var(--bs-caramel);
        }

        .footer-bottom {
            margin-top: 3.5rem;
            padding-top: 1.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.875rem;
            color: #A89B93;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-brewsync sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fa-solid fa-mug-hot"></i>
                <span>BrewSync</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-dismiss="collapse" data-bs-target="#brewsyncNavbar" aria-controls="brewsyncNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu Links -->
            <div class="collapse navbar-collapse" id="brewsyncNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('menu*') ? 'active' : '' }}" href="{{ route('menu') }}">Coffee Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#contact">Contact</a>
                    </li>
                </ul>

                <!-- Right Action Controls -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Shopping Cart Pill -->
                    <a href="{{ route('cart.index') }}" class="cart-pill" title="Shopping Cart">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span>Cart</span>
                        @php
                            $cartCount = (auth()->check() && auth()->user()->cart) ? auth()->user()->cart->items->sum('quantity') : 0;
                        @endphp
                        <span class="badge bg-light text-dark rounded-pill">{{ $cartCount }}</span>
                    </a>

                    <!-- Authentication Navigation (Strictly no duplicate buttons) -->
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-outline-coffee btn-sm">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-coffee btn-sm">
                            <i class="fa-solid fa-user-plus"></i> Register
                        </a>
                    @else
                        <div class="dropdown">
                            <button class="btn btn-outline-coffee btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-regular fa-circle-user"></i> {{ Auth::user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 0.75rem;">
                                @if (Auth::user()->isAdmin())
                                    <li><a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-gauge-high me-2 text-muted"></i> Admin Dashboard</a></li>
                                @elseif (Auth::user()->isStaff())
                                    <li><a class="dropdown-item py-2" href="{{ route('staff.dashboard') }}"><i class="fa-solid fa-bell-concierge me-2 text-muted"></i> Staff Dashboard</a></li>
                                @endif
                                <li><a class="dropdown-item py-2" href="{{ route('orders.index') }}"><i class="fa-solid fa-receipt me-2 text-muted"></i> My Orders</a></li>
                                <li><a class="dropdown-item py-2" href="{{ route('cart.index') }}"><i class="fa-solid fa-cart-shopping me-2 text-muted"></i> View Cart</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 text-danger">
                                            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Toast / Alert Notifications -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-0 mb-0 py-3 text-center border-0 border-bottom" role="alert">
            <div class="container d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-circle-check fs-5"></i>
                <span class="fw-semibold">{{ session('success') }}</span>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-0 mb-0 py-3 text-center border-0 border-bottom" role="alert">
            <div class="container d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                <span class="fw-semibold">{{ session('error') }}</span>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Main Content Injection -->
    <main>
        @yield('content')
    </main>

    <!-- Master Footer -->
    <footer class="footer-brewsync">
        <div class="container">
            <div class="row g-4 justify-content-between">
                <div class="col-lg-4">
                    <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none font-serif fs-3 fw-bold mb-3">
                        <i class="fa-solid fa-mug-hot text-warning"></i> BrewSync
                    </a>
                    <p class="text-white-50 pe-lg-4 leading-relaxed mb-4">
                        BrewSync is your neighborhood artisan coffee haven. We roast carefully selected single-origin beans, craft bespoke beverages, and deliver fresh brews straight to your door.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-outline-coffee btn-sm rounded-circle p-2" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-coffee btn-sm rounded-circle p-2" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-coffee btn-sm rounded-circle p-2" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 letter-spacing-1">Quick Links</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('menu') }}">Coffee Menu</a></li>
                        <li><a href="{{ route('home') }}#about">Our Story</a></li>
                        <li><a href="{{ route('home') }}#contact">Contact & Location</a></li>
                        <li><a href="{{ route('login') }}">Customer Portal</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 letter-spacing-1">Categories</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('menu', ['category' => 'espresso']) }}">Espresso</a></li>
                        <li><a href="{{ route('menu', ['category' => 'latte']) }}">Latte & Spanish</a></li>
                        <li><a href="{{ route('menu', ['category' => 'cold-brew']) }}">Cold Brews</a></li>
                        <li><a href="{{ route('menu', ['category' => 'frappes']) }}">Frappes</a></li>
                        <li><a href="{{ route('menu', ['category' => 'pastries']) }}">Artisan Pastries</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 letter-spacing-1">Visit & Reach Us</h6>
                    <ul class="list-unstyled d-flex flex-column gap-3 text-white-50 mb-0">
                        <li class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-location-dot text-warning mt-1"></i>
                            <span>742 Evergreen Avenue, Ground Floor, Makati City, Metro Manila</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-phone text-warning"></i>
                            <span>+63 917-123-4567</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-envelope text-warning"></i>
                            <span>hello@brewsync.com</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-clock text-warning"></i>
                            <span>Daily: 7:00 AM - 10:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div>
                    &copy; {{ date('Y') }} <strong>BrewSync</strong>. All rights reserved. Artisan Coffee Shop Management & Online Ordering System.
                </div>
                <div class="d-flex gap-4">
                    <span class="text-white-50 small"><i class="fa-solid fa-shield-halved text-warning me-1"></i> Verified GCash & Cash Supported</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
