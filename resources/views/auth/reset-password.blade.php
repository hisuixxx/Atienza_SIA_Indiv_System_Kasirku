@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
    <div class="mb-4">
        <h3 class="font-serif fw-bold text-center mb-1">Set New Password</h3>
        <p class="text-muted text-center small">Please choose a strong, secure new password for your BrewSync account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $token }}">

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-regular fa-envelope"></i>
                </span>
                <input id="email" type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email', $email) }}" required autofocus placeholder="you@example.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
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

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <input id="password_confirmation" type="password" class="form-control border-start-0 ps-0" 
                       name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your new password">
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid mb-2">
            <button type="submit" class="btn btn-coffee">
                <i class="fa-solid fa-key me-2"></i> Reset & Update Password
            </button>
        </div>
    </form>
@endsection

@section('footer_link')
    <div class="text-end">
        <a href="{{ route('login') }}" class="auth-link small">Return to Sign In</a>
    </div>
@endsection
