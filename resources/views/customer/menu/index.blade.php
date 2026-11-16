@extends('layouts.app')

@section('title', 'Coffee Menu & Pastries')

@section('content')
    <!-- Menu Header Banner -->
    <section class="py-5 text-white position-relative" style="background: linear-gradient(rgba(36, 20, 14, 0.85), rgba(36, 20, 14, 0.92)), url('https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;">
        <div class="container py-4 text-center">
            <span class="text-uppercase text-warning fw-bold small letter-spacing-1">Handcrafted Drink Collection</span>
            <h1 class="display-4 font-serif fw-bold mt-1 mb-2">Our Coffee Menu</h1>
            <p class="lead text-white-50 mx-auto mb-0" style="max-width: 650px;">
                Explore freshly extracted espressos, slow-steeped cold brews, artisanal frappes, and freshly baked bakery items.
            </p>
        </div>
    </section>

    <!-- Search & Filter Controls -->
    <section class="py-4 border-bottom bg-white sticky-top shadow-sm" style="top: 70px; z-index: 1000;">
        <div class="container">
            <div class="row g-3 align-items-center">
                <!-- Search Bar -->
                <div class="col-lg-5">
                    <form method="GET" action="{{ route('menu') }}" class="d-flex gap-2">
                        @if (request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                   placeholder="Search coffee, tea, pastries..." 
                                   value="{{ request('search') }}">
                            @if (request('search') || request('category'))
                                <a href="{{ route('menu') }}" class="btn btn-outline-secondary" title="Clear Filters">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-coffee px-3">Search</button>
                    </form>
                </div>

                <!-- Active Filter Tags / Count -->
                <div class="col-lg-7 text-lg-end text-muted small">
                    Showing <strong>{{ $products->total() }}</strong> delicious item{{ $products->total() === 1 ? '' : 's' }}
                    @if (request('category'))
                        &bull; Category: <span class="badge bg-warning text-dark">{{ $selectedCategory->name ?? request('category') }}</span>
                    @endif
                    @if (request('search'))
                        &bull; Keyword: <span class="badge bg-light text-dark border">"{{ request('search') }}"</span>
                    @endif
                </div>
            </div>

            <!-- Horizontal Category Scroll Bar -->
            <div class="d-flex gap-2 overflow-auto py-3 mt-2 no-scrollbar" style="white-space: nowrap;">
                <a href="{{ route('menu', array_filter(['search' => request('search')])) }}" 
                   class="btn btn-sm rounded-pill {{ !request('category') ? 'btn-coffee text-white' : 'btn-outline-dark bg-light' }} px-3 py-1 fw-semibold">
                   All Products
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ route('menu', array_filter(['category' => $cat->slug, 'search' => request('search')])) }}" 
                       class="btn btn-sm rounded-pill {{ request('category') === $cat->slug ? 'btn-coffee text-white' : 'btn-outline-dark bg-light' }} px-3 py-1 fw-semibold">
                       {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Product Grid Section -->
    <section class="py-5" style="background-color: var(--bs-cream); min-height: 50vh;">
        <div class="container">
            @if ($products->count() > 0)
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="coffee-card">
                                <div class="coffee-card-img-wrap">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="coffee-card-img">
                                    <span class="category-badge">{{ $product->category->name }}</span>
                                    <span class="availability-badge {{ $product->is_available ? 'badge-available' : 'badge-unavailable' }}">
                                        {{ $product->is_available ? 'Available' : 'Sold Out' }}
                                    </span>
                                </div>
                                <div class="coffee-card-body">
                                    <h3 class="coffee-title text-truncate" title="{{ $product->name }}">{{ $product->name }}</h3>
                                    <p class="coffee-desc" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $product->description }}
                                    </p>
                                    
                                    <div class="coffee-card-footer">
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">From</small>
                                            <span class="coffee-price">₱{{ number_format($product->base_price, 2) }}</span>
                                        </div>
                                        <!-- Customization / details button only (No direct add to cart on catalog per requirements) -->
                                        @if ($product->is_available)
                                            <a href="{{ route('menu.show', $product->slug) }}" class="btn btn-coffee btn-sm">
                                                <i class="fa-solid fa-sliders"></i> Customize
                                            </a>
                                        @else
                                            <button class="btn btn-secondary btn-sm" disabled>Sold Out</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="d-flex justify-content-center mt-5">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                    <div class="d-inline-flex p-3 rounded-circle bg-light text-muted mb-3">
                        <i class="fa-solid fa-mug-saucer fs-2"></i>
                    </div>
                    <h4 class="font-serif fw-bold mb-2">No Coffee or Products Found</h4>
                    <p class="text-muted mb-4">We could not find any items matching your selected criteria.</p>
                    <a href="{{ route('menu') }}" class="btn btn-coffee">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
