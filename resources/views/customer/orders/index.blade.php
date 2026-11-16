@extends('layouts.app')

@section('title', 'Your Order History')

@section('content')
    <div class="py-3 bg-white border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">My Orders</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="py-5" style="background-color: var(--bs-cream); min-height: 70vh;">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <div>
                    <span class="text-uppercase text-warning fw-bold small letter-spacing-1">Customer Portal</span>
                    <h2 class="font-serif fw-bold mt-1 mb-0">Your Order History</h2>
                </div>
                <a href="{{ route('menu') }}" class="btn btn-coffee btn-sm mt-3 mt-md-0">
                    <i class="fa-solid fa-mug-hot me-1"></i> Order Coffee Again
                </a>
            </div>

            @if ($orders->count() > 0)
                <div class="bg-white rounded-4 shadow-sm border overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-muted">
                                    <th class="ps-4 py-3">Order Number</th>
                                    <th class="py-3">Date & Time</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Total Amount</th>
                                    <th class="py-3">Order Status</th>
                                    <th class="py-3">Payment</th>
                                    <th class="py-3 text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr class="border-bottom">
                                        <td class="ps-4 py-3">
                                            <span class="fw-bold font-monospace text-dark fs-6">{{ $order->order_number }}</span>
                                            <small class="text-muted d-block">{{ $order->items->count() }} item{{ $order->items->count() === 1 ? '' : 's' }}</small>
                                        </td>
                                        <td class="py-3 text-muted small">
                                            {{ $order->created_at->format('M d, Y') }}<br>
                                            <span class="text-secondary">{{ $order->created_at->format('h:i A') }}</span>
                                        </td>
                                        <td class="py-3">
                                            @if ($order->order_type === 'delivery')
                                                <span class="badge bg-info bg-opacity-10 text-info fw-semibold"><i class="fa-solid fa-motorcycle me-1"></i> Delivery</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold"><i class="fa-solid fa-store me-1"></i> Pickup</span>
                                            @endif
                                        </td>
                                        <td class="py-3 fw-bold">
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </td>
                                        <td class="py-3">
                                            @php
                                                $statusClasses = [
                                                    'pending' => 'bg-warning text-dark',
                                                    'confirmed' => 'bg-info text-white',
                                                    'preparing' => 'bg-primary text-white',
                                                    'ready_for_pickup' => 'bg-success text-white',
                                                    'out_for_delivery' => 'bg-primary text-white',
                                                    'completed' => 'bg-success text-white',
                                                    'cancelled' => 'bg-danger text-white',
                                                ];
                                                $badgeClass = $statusClasses[$order->order_status] ?? 'bg-secondary text-white';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-2 py-1 rounded-pill">
                                                {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                                            </span>
                                        </td>
                                        <td class="py-3 small">
                                            @if ($order->payment)
                                                @if ($order->payment->payment_status === 'approved')
                                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">Verified</span>
                                                @elseif ($order->payment->payment_status === 'rejected')
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">Rejected</span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-10 text-dark fw-bold">Pending Review</span>
                                                @endif
                                                <small class="d-block text-muted text-uppercase" style="font-size: 0.7rem;">{{ str_replace('_', ' ', $order->payment->payment_method) }}</small>
                                            @endif
                                        </td>
                                        <td class="py-3 text-end pe-4">
                                            <a href="{{ route('orders.show', $order->order_number) }}" class="btn btn-outline-coffee btn-sm">
                                                <i class="fa-solid fa-route me-1"></i> Track Order
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $orders->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                    <div class="d-inline-flex p-4 rounded-circle bg-light text-muted mb-3">
                        <i class="fa-solid fa-receipt fs-2"></i>
                    </div>
                    <h3 class="font-serif fw-bold mb-2">No Past Orders Yet</h3>
                    <p class="text-muted mb-4">You haven't placed any orders with BrewSync yet. Fresh brews await!</p>
                    <a href="{{ route('menu') }}" class="btn btn-coffee">
                        <i class="fa-solid fa-mug-hot me-1"></i> Explore Coffee Menu
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
