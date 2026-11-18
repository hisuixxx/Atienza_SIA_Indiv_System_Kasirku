@extends('layouts.app')

@section('title', 'Track Order #' . $order->order_number)

@section('content')
    <div class="py-3 bg-white border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orders.index') }}" class="text-decoration-none text-muted">My Orders</a></li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Order #{{ $order->order_number }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="py-5" style="background-color: var(--bs-cream); min-height: 70vh;">
        <div class="container">
            <!-- Header with Order Status -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-dark font-monospace fs-6 px-3 py-2 rounded-pill">#{{ $order->order_number }}</span>
                        @if ($order->order_type === 'delivery')
                            <span class="badge bg-info bg-opacity-10 text-info fw-bold"><i class="fa-solid fa-motorcycle me-1"></i> Delivery</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold"><i class="fa-solid fa-store me-1"></i> Store Pickup</span>
                        @endif
                    </div>
                    <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y h:i A') }}</small>
                </div>
                <div class="mt-3 mt-md-0 d-flex gap-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-dark btn-sm rounded-pill">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
                    </a>
                    <a href="{{ route('menu') }}" class="btn btn-coffee btn-sm rounded-pill">
                        <i class="fa-solid fa-plus me-1"></i> Order More
                    </a>
                </div>
            </div>

            <!-- Visual Progress Tracker Card -->
            <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                <h5 class="font-serif fw-bold mb-4">
                    <i class="fa-solid fa-route text-warning me-2"></i> Real-Time Order Progress
                </h5>

                @php
                    $statusOrder = ['pending', 'confirmed', 'preparing', 'ready'];
                    if ($order->order_type === 'delivery') {
                        $statusOrder[] = 'out_for_delivery';
                    }
                    $statusOrder[] = 'completed';
                    $currentStatus = $order->order_status === 'ready_for_pickup' ? 'ready' : $order->order_status;
                    $currentIndex = array_search($currentStatus, $statusOrder);
                    if ($currentIndex === false && $order->order_status === 'cancelled') {
                        $currentIndex = -1;
                    }
                @endphp

                @if ($order->order_status === 'cancelled')
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="fa-solid fa-circle-xmark fs-4"></i>
                        <div>
                            <strong>Order Cancelled:</strong> This order has been cancelled. If payment was made, our team will process your refund request.
                        </div>
                    </div>
                @else
                    <div class="row g-3 text-center position-relative">
                        @foreach ($steps as $key => $step)
                            @php
                                $stepIndex = array_search($key, $statusOrder);
                                $isCompleted = $currentIndex !== false && $stepIndex <= $currentIndex;
                                $isCurrent = $currentIndex !== false && $stepIndex === $currentIndex;
                            @endphp
                            <div class="col">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm mb-2" 
                                         style="width: 52px; height: 52px; {{ $isCurrent ? 'background-color: var(--bs-caramel); color: #fff; box-shadow: 0 0 0 4px rgba(198, 139, 89, 0.25) !important;' : ($isCompleted ? 'background-color: #198754; color: #fff;' : 'background-color: var(--bs-beige); color: var(--bs-muted);') }}">
                                        <i class="fa-solid {{ $step['icon'] }} fs-5"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1 small {{ $isCurrent ? 'text-primary' : ($isCompleted ? 'text-dark' : 'text-muted') }}">{{ $step['label'] }}</h6>
                                    <small class="text-muted d-none d-md-block" style="font-size: 0.75rem;">{{ $step['desc'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="row g-4">
                <!-- Left: Ordered Items Breakdown -->
                <div class="col-lg-7">
                    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                        <h5 class="font-serif fw-bold mb-3">Order Items ({{ $order->items->count() }})</h5>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($order->items as $item)
                                <div class="d-flex align-items-start gap-3 pb-3 border-bottom">
                                    <img src="{{ $item->product ? $item->product->image_url : 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=400&q=80' }}" 
                                         alt="{{ $item->product_name }}" 
                                         class="rounded-3" 
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="font-serif fw-bold mb-1">{{ $item->product_name }}</h6>
                                            <span class="fw-bold">₱{{ number_format($item->subtotal, 2) }}</span>
                                        </div>
                                        <small class="text-muted d-block">
                                            Qty: {{ $item->quantity }} &bull; Unit Price: ₱{{ number_format($item->unit_price, 2) }}
                                        </small>

                                        @if (!empty($item->customizations_summary))
                                            <div class="bg-light p-2 rounded-2 mt-2 small text-secondary" style="font-size: 0.8rem;">
                                                @foreach ($item->customizations_summary as $grp => $opt)
                                                    <div>
                                                        <span class="fw-semibold">{{ $grp }}:</span> 
                                                        {{ is_array($opt) ? $opt['option'] : $opt }}
                                                        @if (is_array($opt) && !empty($opt['price']) && $opt['price'] > 0)
                                                            <span class="text-warning fw-bold">(+₱{{ number_format($opt['price'], 2) }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($item->special_instructions)
                                            <div class="small text-muted mt-1">
                                                <i class="fa-regular fa-note-sticky text-warning me-1"></i>
                                                <em>"{{ $item->special_instructions }}"</em>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Price Breakdown -->
                        <div class="pt-3">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal:</span>
                                <span>₱{{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Delivery Fee:</span>
                                <span>₱{{ number_format($order->delivery_fee, 2) }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5 font-serif">Total Paid:</span>
                                <span class="fs-4 fw-bold text-success">₱{{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Customer, Delivery & Payment Info -->
                <div class="col-lg-5">
                    <!-- Delivery / Pickup Info -->
                    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                        <h6 class="font-serif fw-bold mb-3 text-uppercase small letter-spacing-1 text-muted">
                            <i class="fa-solid fa-location-dot text-warning me-1"></i> Delivery & Customer Details
                        </h6>

                        <div class="mb-2">
                            <small class="text-muted d-block">Recipient Name:</small>
                            <span class="fw-semibold">{{ $order->customer_name }}</span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Contact Number:</small>
                            <span class="fw-semibold">{{ $order->customer_phone }}</span>
                        </div>

                        @if ($order->order_type === 'delivery')
                            <div class="mb-2">
                                <small class="text-muted d-block">Delivery Address:</small>
                                <span class="fw-semibold">{{ $order->delivery_address }}</span>
                            </div>
                        @else
                            <div class="mb-2">
                                <small class="text-muted d-block">Pickup Location:</small>
                                <span class="fw-semibold">BrewSync Cafe Main Branch (Counter Pickup)</span>
                            </div>
                        @endif

                        @if ($order->order_notes)
                            <div class="mt-3 p-2 bg-light rounded-2 small">
                                <span class="fw-semibold d-block">Order Notes:</span>
                                <em>{{ $order->order_notes }}</em>
                            </div>
                        @endif
                    </div>

                    <!-- Payment Information -->
                    @if ($order->payment)
                        <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                            <h6 class="font-serif fw-bold mb-3 text-uppercase small letter-spacing-1 text-muted">
                                <i class="fa-solid fa-credit-card text-warning me-1"></i> Payment Information
                            </h6>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Method:</span>
                                <span class="badge bg-light text-dark border text-uppercase">{{ str_replace('_', ' ', $order->payment->payment_method) }}</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Verification Status:</span>
                                @if ($order->payment->payment_status === 'approved')
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Payment Approved</span>
                                @elseif ($order->payment->payment_status === 'rejected')
                                    <span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Payment Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Pending Verification</span>
                                @endif
                            </div>

                            @if ($order->payment->gcash_reference_number)
                                <div class="mb-3">
                                    <small class="text-muted d-block">GCash Ref Number:</small>
                                    <span class="font-monospace fw-bold text-primary">{{ $order->payment->gcash_reference_number }}</span>
                                </div>
                            @endif

                            @if ($order->payment->receipt_url)
                                <div>
                                    <small class="text-muted d-block mb-1">Attached Receipt:</small>
                                    <a href="{{ $order->payment->receipt_url }}" target="_blank" class="d-inline-block border rounded-3 p-1 overflow-hidden" title="View Full Receipt">
                                        <img src="{{ $order->payment->receipt_url }}" alt="Receipt" style="max-height: 120px; object-fit: contain;">
                                    </a>
                                </div>
                            @endif

                            @if ($order->payment->rejection_reason)
                                <div class="alert alert-danger mt-3 mb-0 small">
                                    <strong>Rejection Reason:</strong> {{ $order->payment->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Status History Audit Trail -->
                    @if ($order->statusHistories->count() > 0)
                        <div class="bg-white rounded-4 shadow-sm border p-4">
                            <h6 class="font-serif fw-bold mb-3 text-uppercase small letter-spacing-1 text-muted">
                                <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Order Activity Log
                            </h6>
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                @foreach ($order->statusHistories as $history)
                                    <li class="pb-2 border-bottom">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-capitalize">{{ str_replace('_', ' ', $history->to_status) }}</strong>
                                            <span class="text-muted">{{ $history->created_at->format('h:i A') }}</span>
                                        </div>
                                        @if ($history->remarks)
                                            <span class="text-secondary small">{{ $history->remarks }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
