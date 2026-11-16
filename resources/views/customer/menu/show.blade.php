@extends('layouts.app')

@section('title', 'Customize ' . $product->name)

@section('content')
    <div class="py-3 bg-white border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('menu') }}" class="text-decoration-none text-muted">Menu</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('menu', ['category' => $product->category->slug]) }}" class="text-decoration-none text-muted">{{ $product->category->name }}</a></li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="py-5" style="background-color: var(--bs-cream);">
        <div class="container">
            <div class="row g-5">
                <!-- Left Column: Product Photo & Details -->
                <div class="col-lg-5">
                    <div class="sticky-top" style="top: 100px;">
                        <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                            <div class="position-relative overflow-hidden rounded-3 mb-3" style="padding-top: 80%;">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" 
                                     class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                                <span class="category-badge">{{ $product->category->name }}</span>
                                <span class="availability-badge {{ $product->is_available ? 'badge-available' : 'badge-unavailable' }}">
                                    {{ $product->is_available ? 'Available In Shop' : 'Sold Out' }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h2 class="font-serif fw-bold mb-0 text-break">{{ $product->name }}</h2>
                                <span class="fs-4 fw-bold text-dark">₱{{ number_format($product->base_price, 2) }}</span>
                            </div>
                            <p class="text-muted small leading-relaxed mb-3">
                                {{ $product->description }}
                            </p>

                            <!-- Live Price Summary Card -->
                            <div class="p-3 rounded-3" style="background-color: var(--bs-beige-light); border: 1px dashed var(--bs-caramel);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Base Drink Price:</span>
                                    <span class="fw-semibold">₱<span id="summaryBasePrice">{{ number_format($product->base_price, 2) }}</span></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Customization Add-ons:</span>
                                    <span class="fw-semibold text-warning">+₱<span id="summaryAddons">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Unit Item Price:</span>
                                    <span class="fw-bold">₱<span id="summaryUnitPrice">{{ number_format($product->base_price, 2) }}</span></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total (Quantity: <span id="summaryQty">1</span>):</span>
                                    <span class="fs-4 fw-bold text-success">₱<span id="summaryTotalPrice">{{ number_format($product->base_price, 2) }}</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Related Products -->
                        @if ($relatedProducts->count() > 0)
                            <div class="d-none d-lg-block">
                                <h6 class="font-serif fw-bold text-muted text-uppercase mb-3 small letter-spacing-1">Pairs Well With</h6>
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($relatedProducts as $rel)
                                        <a href="{{ route('menu.show', $rel->slug) }}" class="text-decoration-none text-dark p-2 bg-white rounded-3 border d-flex align-items-center gap-3 transition-hover">
                                            <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="rounded-2" style="width: 52px; height: 52px; object-fit: cover;">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h6 class="mb-0 text-truncate font-serif small fw-bold">{{ $rel->name }}</h6>
                                                <small class="text-muted">₱{{ number_format($rel->base_price, 2) }}</small>
                                            </div>
                                            <i class="fa-solid fa-chevron-right text-muted small pe-2"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Customization Options Form -->
                <div class="col-lg-7">
                    <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                        <div class="border-bottom pb-3 mb-4">
                            <span class="text-uppercase text-warning fw-bold small letter-spacing-1">Custom Barista Recipe</span>
                            <h3 class="font-serif fw-bold mt-1 mb-1">Customize Your Drink</h3>
                            <p class="text-muted small mb-0">Select your preferred cup size, temperature, sweetness, dairy, and signature add-ons.</p>
                        </div>

                        @if (!$product->is_available)
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                                <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                                <div>
                                    <strong>Item Currently Unavailable:</strong> This product is temporarily sold out in our cafe inventory. Please check back later or choose another delicious drink!
                                </div>
                            </div>
                        @endif

                        <form id="customizationForm" method="POST" action="{{ route('cart.add', $product->id) }}">
                            @csrf

                            <!-- Render Customization Groups -->
                            @forelse ($product->customizations as $customization)
                                <div class="mb-4 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold mb-0 font-serif fs-6">
                                            <i class="fa-solid fa-circle-dot text-warning me-2 small"></i>
                                            {{ $customization->name }}
                                        </h5>
                                        @if ($customization->is_required)
                                            <span class="badge bg-light text-danger border">Required</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Optional</span>
                                        @endif
                                    </div>

                                    <div class="row g-2">
                                        @foreach ($customization->options as $option)
                                            <div class="col-sm-6">
                                                <label class="custom-option-card d-block p-3 rounded-3 border cursor-pointer position-relative transition-all h-100">
                                                    <input type="radio" 
                                                           name="customizations[{{ $customization->name }}]" 
                                                           value="{{ $option->name }}" 
                                                           data-price="{{ $option->additional_price }}"
                                                           class="form-check-input me-2 option-input"
                                                           {{ $option->is_default ? 'checked' : '' }}
                                                           {{ !$product->is_available ? 'disabled' : '' }}>
                                                    <span class="option-name fw-medium">{{ $option->name }}</span>
                                                    @if ($option->additional_price > 0)
                                                        <span class="badge bg-warning bg-opacity-25 text-dark fw-bold ms-1">+₱{{ number_format($option->additional_price, 2) }}</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="p-3 bg-light rounded-3 text-muted mb-4 small">
                                    <i class="fa-solid fa-info-circle me-1"></i> This artisan item is prepared according to our master signature recipe without variable options.
                                </div>
                            @endforelse

                            <!-- Special Instructions -->
                            <div class="mb-4">
                                <label for="special_instructions" class="form-label fw-bold font-serif fs-6">
                                    <i class="fa-regular fa-note-sticky text-warning me-2"></i> Special Instructions for Barista
                                </label>
                                <textarea id="special_instructions" 
                                          name="special_instructions" 
                                          rows="3" 
                                          class="form-control" 
                                          placeholder="e.g. Less ice, extra hot milk, no straw, pack pastry separately..."
                                          {{ !$product->is_available ? 'disabled' : '' }}></textarea>
                                <div class="form-text small">Please note custom ingredient requests are subject to availability.</div>
                            </div>

                            <!-- Quantity Selector -->
                            <div class="mb-4 pt-3 border-top">
                                <label class="form-label fw-bold font-serif fs-6 mb-2">Quantity</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="input-group" style="width: 140px;">
                                        <button class="btn btn-outline-secondary" type="button" id="btnDecreaseQty" {{ !$product->is_available ? 'disabled' : '' }}>
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <input type="number" id="qtyInput" name="quantity" class="form-control text-center fw-bold" value="1" min="1" max="20" readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="btnIncreaseQty" {{ !$product->is_available ? 'disabled' : '' }}>
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    <span class="text-muted small">Max 20 per order item</span>
                                </div>
                            </div>

                            <!-- Submission Button -->
                            <div class="d-grid mt-4">
                                @if ($product->is_available)
                                    <button type="submit" class="btn btn-coffee btn-lg py-3 shadow">
                                        <i class="fa-solid fa-bag-shopping me-2"></i> Add to Order &bull; ₱<span id="btnTotalPrice">{{ number_format($product->base_price, 2) }}</span>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-secondary btn-lg py-3" disabled>
                                        <i class="fa-solid fa-ban me-2"></i> Product Out of Stock
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .custom-option-card:hover {
        background-color: var(--bs-beige-light);
        border-color: var(--bs-caramel) !important;
    }
    .custom-option-card:has(input:checked) {
        background-color: #FAF4EE;
        border-color: var(--bs-caramel) !important;
        box-shadow: 0 0 0 2px rgba(198, 139, 89, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const basePrice = {{ (float) $product->base_price }};
        const qtyInput = document.getElementById('qtyInput');
        const btnDecrease = document.getElementById('btnDecreaseQty');
        const btnIncrease = document.getElementById('btnIncreaseQty');
        const optionInputs = document.querySelectorAll('.option-input');

        const summaryBasePrice = document.getElementById('summaryBasePrice');
        const summaryAddons = document.getElementById('summaryAddons');
        const summaryUnitPrice = document.getElementById('summaryUnitPrice');
        const summaryQty = document.getElementById('summaryQty');
        const summaryTotalPrice = document.getElementById('summaryTotalPrice');
        const btnTotalPrice = document.getElementById('btnTotalPrice');

        function calculatePrice() {
            let addOnsTotal = 0;

            optionInputs.forEach(input => {
                if (input.checked) {
                    const price = parseFloat(input.getAttribute('data-price')) || 0;
                    addOnsTotal += price;
                }
            });

            const quantity = parseInt(qtyInput.value) || 1;
            const unitPrice = basePrice + addOnsTotal;
            const totalPrice = unitPrice * quantity;

            // Update UI
            if (summaryAddons) summaryAddons.textContent = addOnsTotal.toFixed(2);
            if (summaryUnitPrice) summaryUnitPrice.textContent = unitPrice.toFixed(2);
            if (summaryQty) summaryQty.textContent = quantity;
            if (summaryTotalPrice) summaryTotalPrice.textContent = totalPrice.toFixed(2);
            if (btnTotalPrice) btnTotalPrice.textContent = totalPrice.toFixed(2);
        }

        // Attach event listeners to option radios
        optionInputs.forEach(input => {
            input.addEventListener('change', calculatePrice);
        });

        // Quantity selector controls
        if (btnIncrease) {
            btnIncrease.addEventListener('click', function () {
                let current = parseInt(qtyInput.value) || 1;
                if (current < 20) {
                    qtyInput.value = current + 1;
                    calculatePrice();
                }
            });
        }

        if (btnDecrease) {
            btnDecrease.addEventListener('click', function () {
                let current = parseInt(qtyInput.value) || 1;
                if (current > 1) {
                    qtyInput.value = current - 1;
                    calculatePrice();
                }
            });
        }

        // Run initial price calculation on load
        calculatePrice();
    });
</script>
@endpush
