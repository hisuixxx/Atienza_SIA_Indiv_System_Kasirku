@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
    <div class="mb-4">
        <h3 class="font-serif fw-bold text-center mb-1">Join BrewSync</h3>
        <p class="text-muted text-center small">Create your account to order artisan brews and track deliveries in real time.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Full Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-regular fa-user"></i>
                </span>
                <input id="name" type="text" class="form-control border-start-0 ps-0 @error('name') is-invalid @enderror" 
                       name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                       placeholder="e.g. Maria Santos">
            </div>
            @error('name')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-regular fa-envelope"></i>
                </span>
                <input id="email" type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" required autocomplete="email" 
                       placeholder="maria@example.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Phone Number -->
        <div class="mb-3">
            <label for="phone" class="form-label">Contact Number <span class="text-muted fw-normal">(Optional)</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-phone"></i>
                </span>
                <input id="phone" type="text" class="form-control border-start-0 ps-0 @error('phone') is-invalid @enderror" 
                       name="phone" value="{{ old('phone') }}" placeholder="0917-000-0000">
            </div>
            @error('phone')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Delivery Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Default Delivery Address <span class="text-muted fw-normal">(Optional)</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-location-dot"></i>
                </span>
                <input id="address" type="text" class="form-control border-start-0 ps-0 @error('address') is-invalid @enderror" 
                       name="address" value="{{ old('address') }}" placeholder="House/Unit #, Street, City">
            </div>
            @error('address')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input id="password" type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="new-password" placeholder="At least 8 characters">
            </div>
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password Confirmation -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <input id="password_confirmation" type="password" class="form-control border-start-0 ps-0" 
                       name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid mb-2">
            <button type="submit" class="btn btn-coffee">
                <i class="fa-solid fa-user-plus me-2"></i> Complete Registration
            </button>
        </div>
    </form>
@endsection

@section('footer_link')
    <div class="text-end">
        <span class="text-muted small">Already registered?</span>
        <a href="{{ route('login') }}" class="auth-link ms-1">Sign In</a>
    </div>
@endsection
