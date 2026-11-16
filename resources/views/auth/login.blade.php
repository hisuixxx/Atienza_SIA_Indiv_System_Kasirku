@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
    <div class="mb-4">
        <h3 class="font-serif fw-bold text-center mb-1">Welcome Back</h3>
        <p class="text-muted text-center small">Sign in to customize drinks, track your orders, and manage BrewSync.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-regular fa-envelope"></i>
                </span>
                <input id="email" type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                       placeholder="you@example.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link small">Forgot password?</a>
                @endif
            </div>
            <div class="input-group mt-1">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input id="password" type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-4 form-check">
            <input id="remember" type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" class="form-check-label text-muted small">Keep me signed in on this device</label>
        </div>

        <!-- Submit Button -->
        <div class="d-grid mb-2">
            <button type="submit" class="btn btn-coffee">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Sign In to BrewSync
            </button>
        </div>
    </form>
@endsection

@section('footer_link')
    <div class="text-end">
        <span class="text-muted small">New here?</span>
        <a href="{{ route('register') }}" class="auth-link ms-1">Create Account</a>
    </div>
@endsection
