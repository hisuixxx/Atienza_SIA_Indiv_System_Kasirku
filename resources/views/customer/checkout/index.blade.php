@extends('layouts.app')

@section('title', 'Order Checkout & Payment')

@section('content')
    <div class="py-3 bg-white border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cart.index') }}" class="text-decoration-none text-muted">Cart</a></li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Checkout</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="py-5" style="background-color: var(--bs-cream); min-height: 70vh;">
        <div class="container">
            <div class="mb-4">
                <span class="text-uppercase text-warning fw-bold small letter-spacing-1">Final Step</span>
                <h2 class="font-serif fw-bold mt-1 mb-0">Complete Your Order</h2>
            </div>

            <form method="POST" action="{{ route('checkout.process') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">
                    <!-- Left: Customer & Delivery Details -->
                    <div class="col-lg-7">
                        <!-- Customer Information Card -->
                        <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                            <h5 class="font-serif fw-bold mb-3">
                                <i class="fa-solid fa-user text-warning me-2"></i> 1. Contact Information
                            </h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" 
                                           value="{{ old('customer_name', $user->name) }}" required placeholder="e.g. Maria Santos">
                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Mobile Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" 
                                           value="{{ old('customer_phone', $user->phone) }}" required placeholder="e.g. 0917-123-4567">
                                    @error('customer_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Fulfillment Method Card -->
                        <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                            <h5 class="font-serif fw-bold mb-3">
                                <i class="fa-solid fa-store text-warning me-2"></i> 2. Fulfillment Method
                            </h5>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="d-block p-3 rounded-3 border cursor-pointer option-type-card h-100">
                                        <input type="radio" name="order_type" value="pickup" class="form-check-input me-2" 
                                               id="typePickup" {{ old('order_type', 'pickup') === 'pickup' ? 'checked' : '' }}>
                                        <span class="fw-bold d-block"><i class="fa-solid fa-person-walking me-1"></i> Store Pickup</span>
                                        <small class="text-muted">Pick up fresh at our cafe counter (₱0.00)</small>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="d-block p-3 rounded-3 border cursor-pointer option-type-card h-100">
                                        <input type="radio" name="order_type" value="delivery" class="form-check-input me-2" 
                                               id="typeDelivery" {{ old('order_type') === 'delivery' ? 'checked' : '' }}>
                                        <span class="fw-bold d-block"><i class="fa-solid fa-motorcycle me-1"></i> Doorstep Delivery</span>
                                        <small class="text-muted">Delivered to your address (+₱50.00)</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Delivery Address (Toggled by JS) -->
                            <div id="deliveryAddressWrap" class="mb-3" style="display: {{ old('order_type') === 'delivery' ? 'block' : 'none' }};">
                                <label class="form-label small fw-semibold">Delivery Address <span class="text-danger">*</span></label>
                                <textarea name="delivery_address" rows="3" class="form-control @error('delivery_address') is-invalid @enderror" 
                                          placeholder="House/Unit #, Street, Barangay, City, Landmark">{{ old('delivery_address', $user->address) }}</textarea>
                                @error('delivery_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Order Notes -->
                            <div class="mb-0">
                                <label class="form-label small fw-semibold">Order Notes <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" name="order_notes" class="form-control" 
                                       value="{{ old('order_notes') }}" placeholder="Any special notes for packaging or pickup time">
                            </div>
                        </div>

                        <!-- Payment Method Card -->
                        <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                            <h5 class="font-serif fw-bold mb-3">
                                <i class="fa-solid fa-credit-card text-warning me-2"></i> 3. Payment Method
                            </h5>

                            <div class="d-flex flex-column gap-2 mb-4">
                                <!-- GCash Option -->
                                <label class="p-3 rounded-3 border cursor-pointer payment-method-card">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <input type="radio" name="payment_method" value="gcash" class="form-check-input me-3" 
                                                   id="payGCash" {{ old('payment_method', 'gcash') === 'gcash' ? 'checked' : '' }}>
                                            <div>
                                                <span class="fw-bold text-primary fs-6"><i class="fa-solid fa-wallet"></i> GCash (Mobile Wallet)</span>
                                                <small class="text-muted d-block">Pay via GCash QR or Express Send, then upload your receipt.</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">Recommended</span>
                                    </div>
                                </label>

                                <!-- Cash on Pickup -->
                                <label class="p-3 rounded-3 border cursor-pointer payment-method-card" id="cashPickupWrap">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" name="payment_method" value="cash_on_pickup" class="form-check-input me-3" 
                                               id="payCashPickup" {{ old('payment_method') === 'cash_on_pickup' ? 'checked' : '' }}>
                                        <div>
                                            <span class="fw-bold fs-6"><i class="fa-solid fa-money-bill-wave text-success"></i> Cash on Pickup</span>
                                            <small class="text-muted d-block">Pay with exact cash or card at our coffee bar upon collection.</small>
                                        </div>
                                    </div>
                                </label>

                                <!-- Cash on Delivery -->
                                <label class="p-3 rounded-3 border cursor-pointer payment-method-card" id="cashDeliveryWrap" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" name="payment_method" value="cash_on_delivery" class="form-check-input me-3" 
                                               id="payCashDelivery" {{ old('payment_method') === 'cash_on_delivery' ? 'checked' : '' }}>
                                        <div>
                                            <span class="fw-bold fs-6"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Cash on Delivery</span>
                                            <small class="text-muted d-block">Pay in cash directly to our delivery courier upon arrival.</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- GCash Transfer Instructions & Upload Panel -->
                            <div id="gcashPanel" class="p-4 rounded-3 border" style="background-color: var(--bs-beige-light); display: {{ old('payment_method', 'gcash') === 'gcash' ? 'block' : 'none' }};">
                                <h6 class="fw-bold font-serif mb-2 text-dark">
                                    <i class="fa-solid fa-circle-info text-primary me-1"></i> BrewSync GCash Account Details
                                </h6>
                                <p class="text-muted small mb-3">
                                    Please send the exact order total to the following official shop account, then enter your 13-digit Reference Number and attach a screenshot:
                                </p>

                                <div class="row g-2 mb-3 bg-white p-3 rounded-3 border">
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">Account Name:</small>
                                        <strong class="fs-6">{{ $settings['gcash_account_name'] ?? 'BrewSync Coffee Shop' }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">GCash Mobile Number:</small>
                                        <strong class="fs-6 text-primary">{{ $settings['gcash_account_number'] ?? '0917-123-4567' }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">GCash Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" name="gcash_reference_number" class="form-control @error('gcash_reference_number') is-invalid @enderror" 
                                           value="{{ old('gcash_reference_number') }}" placeholder="e.g. 1002 9384 1029 3">
                                    @error('gcash_reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Enter the reference number visible in your GCash SMS or transaction receipt.</small>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label small fw-semibold">Upload Payment Receipt Screenshot <span class="text-danger">*</span></label>
                                    <input type="file" name="receipt_image" class="form-control @error('receipt_image') is-invalid @enderror" accept="image/*">
                                    @error('receipt_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Attach PNG, JPG, or WEBP screenshot (Max 5MB). Payments are reviewed by our admin before preparing.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Order Summary -->
                    <div class="col-lg-5">
                        <div class="bg-white rounded-4 shadow-sm border p-4 sticky-top" style="top: 100px;">
                            <h5 class="font-serif fw-bold mb-3">Order Summary</h5>

                            <div class="d-flex flex-column gap-3 mb-3 overflow-auto" style="max-height: 280px;">
                                @foreach ($cart->items as $item)
                                    <div class="d-flex align-items-start gap-2 pb-2 border-bottom">
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="rounded-2" style="width: 48px; height: 48px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 small fw-bold font-serif">{{ $item->product->name }}</h6>
                                            <small class="text-muted d-block">Qty: {{ $item->quantity }} &bull; ₱{{ number_format($item->unit_price, 2) }} each</small>
                                            @if (!empty($item->customizations))
                                                <small class="text-secondary" style="font-size: 0.75rem;">
                                                    @foreach ($item->customizations as $g => $d)
                                                        {{ $g }}: {{ is_array($d) ? $d['option'] : $d }} |
                                                    @endforeach
                                                </small>
                                            @endif
                                        </div>
                                        <span class="fw-bold small">₱{{ number_format($item->item_subtotal, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Subtotal:</span>
                                <span class="fw-semibold text-dark">₱<span id="displaySubtotal">{{ number_format($cart->items->sum('item_subtotal'), 2) }}</span></span>
                            </div>

                            <div class="d-flex justify-content-between mb-3 small text-muted">
                                <span>Delivery Fee:</span>
                                <span class="fw-semibold text-dark">₱<span id="displayDeliveryFee">0.00</span></span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold fs-5 font-serif">Total Amount:</span>
                                <span class="fs-3 fw-bold text-success">₱<span id="displayTotal">{{ number_format($cart->items->sum('item_subtotal'), 2) }}</span></span>
                            </div>

                            <button type="submit" class="btn btn-coffee btn-lg w-100 py-3 shadow">
                                <i class="fa-solid fa-lock me-2"></i> Place Order Now
                            </button>

                            <p class="text-center text-muted small mt-3 mb-0">
                                <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Orders are reviewed promptly upon submission.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typePickup = document.getElementById('typePickup');
        const typeDelivery = document.getElementById('typeDelivery');
        const deliveryAddressWrap = document.getElementById('deliveryAddressWrap');
        const cashPickupWrap = document.getElementById('cashPickupWrap');
        const cashDeliveryWrap = document.getElementById('cashDeliveryWrap');

        const payGCash = document.getElementById('payGCash');
        const payCashPickup = document.getElementById('payCashPickup');
        const payCashDelivery = document.getElementById('payCashDelivery');
        const gcashPanel = document.getElementById('gcashPanel');

        const baseSubtotal = {{ (float) $cart->items->sum('item_subtotal') }};
        const displayDeliveryFee = document.getElementById('displayDeliveryFee');
        const displayTotal = document.getElementById('displayTotal');

        function updateFulfillment() {
            if (typeDelivery && typeDelivery.checked) {
                if (deliveryAddressWrap) deliveryAddressWrap.style.display = 'block';
                if (cashPickupWrap) cashPickupWrap.style.display = 'none';
                if (cashDeliveryWrap) cashDeliveryWrap.style.display = 'block';

                if (displayDeliveryFee) displayDeliveryFee.textContent = '50.00';
                if (displayTotal) displayTotal.textContent = (baseSubtotal + 50.00).toFixed(2);

                if (payCashPickup && payCashPickup.checked) {
                    if (payCashDelivery) payCashDelivery.checked = true;
                }
            } else {
                if (deliveryAddressWrap) deliveryAddressWrap.style.display = 'none';
                if (cashPickupWrap) cashPickupWrap.style.display = 'block';
                if (cashDeliveryWrap) cashDeliveryWrap.style.display = 'none';

                if (displayDeliveryFee) displayDeliveryFee.textContent = '0.00';
                if (displayTotal) displayTotal.textContent = baseSubtotal.toFixed(2);

                if (payCashDelivery && payCashDelivery.checked) {
                    if (payCashPickup) payCashPickup.checked = true;
                }
            }
            updatePaymentPanels();
        }

        function updatePaymentPanels() {
            if (payGCash && payGCash.checked) {
                if (gcashPanel) gcashPanel.style.display = 'block';
            } else {
                if (gcashPanel) gcashPanel.style.display = 'none';
            }
        }

        if (typePickup) typePickup.addEventListener('change', updateFulfillment);
        if (typeDelivery) typeDelivery.addEventListener('change', updateFulfillment);

        document.querySelectorAll('input[name="payment_method"]').forEach(el => {
            el.addEventListener('change', updatePaymentPanels);
        });

        // Run once on load
        updateFulfillment();
    });
</script>
@endpush
