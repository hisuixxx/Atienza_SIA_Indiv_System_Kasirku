@extends('layouts.app')

@section('title', 'Artisan Coffee Shop & Online Ordering')

@section('content')
    <!-- Hero Section -->
    <section class="position-relative overflow-hidden text-white py-5" style="background: linear-gradient(rgba(36, 20, 14, 0.78), rgba(36, 20, 14, 0.90)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1800&q=85') center/cover no-repeat; min-height: 84vh; display: flex; align-items: center;">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-7 text-center text-lg-start">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background-color: rgba(198, 139, 89, 0.25); border: 1px solid var(--bs-caramel);">
                        <i class="fa-solid fa-fire-flame-curved text-warning"></i>
                        <span class="small fw-semibold text-warning letter-spacing-1 text-uppercase">Handcrafted Fresh Daily</span>
                    </div>
                    <h1 class="display-3 font-serif fw-bold mb-3 leading-tight">
                        {{ $settings['hero_title'] ?? 'Artisan Coffee, Crafted For Every Moment' }}
                    </h1>
                    <p class="lead text-white-50 mb-4 pe-lg-4 fs-5">
                        {{ $settings['hero_subtitle'] ?? 'Discover single-origin roasted beans, handcrafted espresso creations, and artisanal pastries tailored to your palate.' }}
                    </p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                        <a href="{{ route('menu') }}" class="btn btn-coffee btn-lg px-4 py-3 shadow">
                            <i class="fa-solid fa-mug-saucer me-2"></i> {{ $settings['hero_cta_text'] ?? 'Explore Coffee Menu' }}
                        </a>
                        <a href="#about" class="btn btn-outline-coffee btn-lg px-4 py-3 text-white border-white">
                            <i class="fa-solid fa-book-open-reader me-2"></i> Our Story
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center position-relative">
                    <div class="p-3 bg-white bg-opacity-10 rounded-4 backdrop-blur shadow-lg border border-white border-opacity-10 d-inline-block">
                        <img src="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=700&q=85" 
                             alt="Featured BrewSync Coffee" 
                             class="rounded-4 img-fluid shadow-lg" 
                             style="max-height: 480px; width: 100%; object-fit: cover;">
                        <div class="mt-3 text-start px-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="font-serif fw-bold mb-0 text-white">Spanish Latte Signature</h5>
                                    <small class="text-warning">Double Shot &bull; Silky Condensed Milk</small>
                                </div>
                                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill">₱175.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose BrewSync / Highlights -->
    <section class="py-5 bg-white border-bottom border-light">
        <div class="container py-4">
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: var(--bs-beige);">
                            <i class="fa-solid fa-seedling fs-4 text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Single-Origin Beans</h5>
                        <p class="text-muted small mb-0">Sourced ethically from premium high-altitude farms across the globe.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: var(--bs-beige);">
                            <i class="fa-solid fa-sliders fs-4 text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Full Customization</h5>
                        <p class="text-muted small mb-0">Customize your size, temperature, sweetness, milk choice, and extra shots.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: var(--bs-beige);">
                            <i class="fa-solid fa-motorcycle fs-4 text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Pickup & Delivery</h5>
                        <p class="text-muted small mb-0">Order ahead for instant store pickup or doorstep express delivery.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: var(--bs-beige);">
                            <i class="fa-solid fa-qrcode fs-4 text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Instant GCash Payment</h5>
                        <p class="text-muted small mb-0">Seamless GCash payments with fast administrator receipt verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Showcase -->
    <section class="py-5" style="background-color: var(--bs-cream);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="text-uppercase text-muted fw-bold small letter-spacing-1">Handcrafted Selections</span>
                <h2 class="display-6 font-serif fw-bold mt-1">Explore Our Categories</h2>
                <div class="mx-auto" style="width: 60px; height: 3px; background-color: var(--bs-caramel); border-radius: 2px;"></div>
            </div>

            <div class="row g-3 row-cols-2 row-cols-md-4">
                @foreach ($categories as $cat)
                    <div class="col">
                        <a href="{{ route('menu', ['category' => $cat->slug]) }}" class="text-decoration-none text-dark">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden text-center position-relative transition-hover" style="background-color: #FFFFFF;">
                                <div style="height: 140px; overflow: hidden;" class="position-relative">
                                    <img src="{{ $cat->image_path ?: 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=400&q=80' }}" 
                                         alt="{{ $cat->name }}" 
                                         class="w-100 h-100 object-fit-cover transition-transform">
                                    <div class="position-absolute inset-0 bg-dark bg-opacity-25"></div>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1 font-serif text-truncate">{{ $cat->name }}</h6>
                                    <small class="text-muted">{{ $cat->products_count ?? $cat->products()->count() }} Items</small>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('menu') }}" class="btn btn-outline-dark px-4 py-2 rounded-pill font-weight-bold" style="border-color: var(--bs-roast); color: var(--bs-roast);">
                    View All 12 Categories <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products Showcase -->
    <section class="py-5 bg-white">
        <div class="container py-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
                <div>
                    <span class="text-uppercase text-muted fw-bold small letter-spacing-1">Barista's Choice</span>
                    <h2 class="display-6 font-serif fw-bold mt-1 mb-0">Featured Brews & Pastries</h2>
                </div>
                <a href="{{ route('menu') }}" class="btn btn-outline-coffee btn-sm mt-3 mt-md-0">
                    See Full Menu <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-4">
                @forelse ($featuredProducts as $product)
                    <div class="col-md-6 col-lg-4">
                        <div class="coffee-card">
                            <div class="coffee-card-img-wrap">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="coffee-card-img">
                                <span class="category-badge">{{ $product->category->name }}</span>
                                <span class="availability-badge {{ $product->is_available ? 'badge-available' : 'badge-unavailable' }}">
                                    {{ $product->is_available ? 'Available' : 'Sold Out' }}
                                </span>
                            </div>
                            <div class="coffee-card-body">
                                <h3 class="coffee-title">{{ $product->name }}</h3>
                                <p class="coffee-desc">{{ Str::limit($product->description, 95) }}</p>
                                
                                <div class="coffee-card-footer">
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">Base Price</small>
                                        <span class="coffee-price">₱{{ number_format($product->base_price, 2) }}</span>
                                    </div>
                                    <!-- Customization button only (No direct add to cart on catalog per requirements) -->
                                    <a href="{{ route('menu.show', $product->slug) }}" class="btn btn-coffee btn-sm">
                                        <i class="fa-solid fa-sliders"></i> Customize & Order
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No featured products currently available.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5" style="background-color: var(--bs-beige-light);">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=800&q=80" 
                             alt="BrewSync Roastery" 
                             class="rounded-4 img-fluid shadow-lg">
                        <div class="position-absolute bottom-0 start-0 translate-middle-y bg-white p-3 rounded-4 shadow-sm border border-light d-none d-sm-block ms-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning p-2 rounded-circle text-white">
                                    <i class="fa-solid fa-award fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">100% Arabica Quality</h6>
                                    <small class="text-muted">Master Roasted in Small Batches</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <span class="text-uppercase text-muted fw-bold small letter-spacing-1">Our Story & Roastery</span>
                    <h2 class="display-6 font-serif fw-bold mt-1 mb-4">
                        {{ $settings['about_title'] ?? 'Our Passion For Exceptional Coffee' }}
                    </h2>
                    <p class="lead text-muted mb-4 fs-6">
                        {{ $settings['about_story'] ?? 'BrewSync was founded with a singular conviction: that coffee is an art form that brings people together. Every cup we brew honors the diligent farmers, the meticulous roasters, and the passionate baristas who transform a simple coffee cherry into pure liquid joy.' }}
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span class="fw-semibold">18-Hour Steeped Cold Brew</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span class="fw-semibold">Artisanal Oat & Almond Milks</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span class="fw-semibold">Daily Freshly Baked Pastries</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span class="fw-semibold">Catering & Event Services</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('menu') }}" class="btn btn-coffee">
                        Discover the BrewSync Experience <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact & Store Information -->
    <section id="contact" class="py-5 bg-white">
        <div class="container py-4">
            <div class="row g-4 justify-content-between align-items-center">
                <div class="col-lg-5">
                    <span class="text-uppercase text-muted fw-bold small letter-spacing-1">Get In Touch</span>
                    <h2 class="display-6 font-serif fw-bold mt-1 mb-3">Visit BrewSync Cafe</h2>
                    <p class="text-muted mb-4">
                        Stop by our cozy espresso bar or reach out for catering inquiries and special corporate coffee reservations.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-circle bg-light text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-location-dot fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Cafe Address</h6>
                                <p class="text-muted small mb-0">{{ $settings['contact_address'] ?? '742 Evergreen Avenue, Ground Floor, Makati City, Metro Manila' }}</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-circle bg-light text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-clock fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Operating Hours</h6>
                                <p class="text-muted small mb-0">{{ $settings['operating_hours'] ?? 'Monday - Sunday: 7:00 AM - 10:00 PM' }}</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-circle bg-light text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-phone fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Phone & Hotline</h6>
                                <p class="text-muted small mb-0">{{ $settings['contact_phone'] ?? '+63 917-123-4567' }}</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-circle bg-light text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-credit-card fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">GCash Official Payment</h6>
                                <p class="text-muted small mb-0">{{ $settings['gcash_account_name'] ?? 'BrewSync Coffee' }} &bull; {{ $settings['gcash_account_number'] ?? '0917-123-4567' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="p-4 p-md-5 rounded-4 shadow-sm border" style="background-color: var(--bs-cream);">
                        <h4 class="font-serif fw-bold mb-2">Have a Question or Special Order?</h4>
                        <p class="text-muted small mb-4">Send us a direct message and our barista team will get back to you shortly.</p>
                        
                        <form onsubmit="event.preventDefault(); alert('Thank you for contacting BrewSync! We will reach out shortly.');">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Your Name</label>
                                <input type="text" class="form-control" required placeholder="e.g. Alex Reyes">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Contact Email</label>
                                <input type="email" class="form-control" required placeholder="alex@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Message / Inquiry</label>
                                <textarea class="form-control" rows="3" required placeholder="Tell us what you'd like to ask..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-coffee w-100">
                                <i class="fa-solid fa-paper-plane me-1"></i> Send Inquiry
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
