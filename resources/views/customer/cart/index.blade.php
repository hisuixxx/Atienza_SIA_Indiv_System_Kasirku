@extends('layouts.app')

@section('title', 'Your Shopping Cart')

@section('content')
    <div class="py-3 bg-white border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('menu') }}" class="text-decoration-none text-muted">Menu</a></li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Your Order Cart</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="py-5" style="background-color: var(--bs-cream); min-height: 65vh;">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <span class="text-uppercase text-warning fw-bold small letter-spacing-1">Current Order</span>
                    <h2 class="font-serif fw-bold mt-1 mb-0">Your Shopping Cart</h2>
                </div>
                <a href="{{ route('menu') }}" class="btn btn-outline-dark btn-sm rounded-pill">
                    <i class="fa-solid fa-plus me-1"></i> Add More Items
                </a>
            </div>

            @if ($cart && $cart->items->count() > 0)
                <div class="row g-4">
                    <!-- Left: Cart Items List -->
                    <div class="col-lg-8">
                        <div class="bg-white rounded-4 shadow-sm border overflow-hidden">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th class="ps-4 py-3">Product & Customizations</th>
                                            <th class="py-3 text-center">Unit Price</th>
                                            <th class="py-3 text-center" style="width: 140px;">Quantity</th>
                                            <th class="py-3 text-end pe-4">Subtotal</th>
                                            <th class="py-3 text-center" style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cart->items as $item)
                                            <tr class="border-bottom">
                                                <!-- Product Info & Customizations Breakdown -->
                                                <td class="ps-4 py-4">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <img src="{{ $item->product->image_url }}" 
                                                             alt="{{ $item->product->name }}" 
                                                             class="rounded-3" 
                                                             style="width: 64px; height: 64px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="font-serif fw-bold mb-1">
                                                                <a href="{{ route('menu.show', $item->product->slug) }}" class="text-decoration-none text-dark">
                                                                    {{ $item->product->name }}
                                                                </a>
                                                            </h6>
                                                            <small class="badge bg-light text-muted border mb-2">{{ $item->product->category->name }}</small>

                                                            <!-- Detailed Customizations List -->
                                                            @if (!empty($item->customizations))
                                                                <div class="small text-muted bg-light p-2 rounded-2" style="font-size: 0.8rem;">
                                                                    @foreach ($item->customizations as $group => $data)
                                                                        <div>
                                                                            <span class="fw-semibold">{{ $group }}:</span> 
                                                                            {{ is_array($data) ? $data['option'] : $data }}
                                                                            @if (is_array($data) && !empty($data['price']) && $data['price'] > 0)
                                                                                <span class="text-warning fw-bold">(+₱{{ number_format($data['price'], 2) }})</span>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            @if ($item->special_instructions)
                                                                <div class="small text-secondary mt-1">
                                                                    <i class="fa-regular fa-note-sticky text-warning me-1"></i>
                                                                    <em>"{{ $item->special_instructions }}"</em>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Unit Price -->
                                                <td class="py-4 text-center fw-medium">
                                                    ₱{{ number_format($item->unit_price, 2) }}
                                                </td>

                                                <!-- Quantity Controls -->
                                                <td class="py-4 text-center">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <form method="POST" action="{{ route('cart.update', $item->id) }}" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="action" value="decrease">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm p-1 px-2 rounded-2" title="Decrease">
                                                                <i class="fa-solid fa-minus small"></i>
                                                            </button>
                                                        </form>

                                                        <span class="fw-bold px-2">{{ $item->quantity }}</span>

                                                        <form method="POST" action="{{ route('cart.update', $item->id) }}" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="action" value="increase">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm p-1 px-2 rounded-2" title="Increase">
                                                                <i class="fa-solid fa-plus small"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>

                                                <!-- Subtotal -->
                                                <td class="py-4 text-end pe-4 fw-bold fs-6">
                                                    ₱{{ number_format($item->item_subtotal, 2) }}
                                                </td>

                                                <!-- Remove Button -->
                                                <td class="py-4 text-center pe-3">
                                                    <form method="POST" action="{{ route('cart.destroy', $item->id) }}" onsubmit="return confirm('Remove this customized item from your cart?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link text-danger p-0" title="Remove item">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Order Summary & Checkout Card -->
                    <div class="col-lg-4">
                        <div class="bg-white rounded-4 shadow-sm border p-4 sticky-top" style="top: 100px;">
                            <h5 class="font-serif fw-bold mb-3">Order Summary</h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Items:</span>
                                <span class="fw-semibold">{{ $cart->items->sum('quantity') }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold">₱{{ number_format($cart->items->sum('item_subtotal'), 2) }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3 text-muted small">
                                <span>Estimated Delivery:</span>
                                <span>Calculated at checkout</span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold fs-5">Estimated Total:</span>
                                <span class="fs-4 fw-bold text-success">₱{{ number_format($cart->items->sum('item_subtotal'), 2) }}</span>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="{{ route('checkout.index') }}" class="btn btn-coffee btn-lg py-3 shadow">
                                    <i class="fa-solid fa-credit-card me-2"></i> Proceed to Checkout
                                </a>
                                <a href="{{ route('menu') }}" class="btn btn-outline-dark">
                                    Continue Shopping
                                </a>
                            </div>

                            <div class="mt-4 p-3 bg-light rounded-3 text-center small text-muted">
                                <i class="fa-solid fa-lock text-success me-1"></i>
                                Safe checkout via GCash, Cash on Pickup, or Cash on Delivery.
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty Cart State -->
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                    <div class="d-inline-flex p-4 rounded-circle bg-light text-muted mb-3" style="width: 80px; height: 80px; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-cart-shopping fs-2"></i>
                    </div>
                    <h3 class="font-serif fw-bold mb-2">Your Shopping Cart is Empty</h3>
                    <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                        Looks like you haven't added any handcrafted coffee or pastries to your order yet.
                    </p>
                    <a href="{{ route('menu') }}" class="btn btn-coffee px-4 py-2">
                        <i class="fa-solid fa-mug-saucer me-2"></i> Browse Our Coffee Menu
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
