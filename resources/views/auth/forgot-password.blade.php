@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="mb-4">
        <h3 class="font-serif fw-bold text-center mb-1">Recover Password</h3>
        <p class="text-muted text-center small">Enter your account email address and we will generate a secure password reset link for you.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="form-label">Account Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-regular fa-envelope"></i>
                </span>
                <input id="email" type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="d-grid mb-2">
            <button type="submit" class="btn btn-coffee">
                <i class="fa-solid fa-paper-plane me-2"></i> Send Password Reset Link
            </button>
        </div>
    </form>
@endsection

@section('footer_link')
    <div class="text-end">
        <span class="text-muted small">Remember password?</span>
        <a href="{{ route('login') }}" class="auth-link ms-1">Sign In</a>
    </div>
@endsection
