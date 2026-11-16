@extends('layouts.auth')

@section('title', 'Staff Dashboard')

@section('content')
<div class="text-center py-4">
    <h2 class="font-serif fw-bold mb-2">Staff & Barista Dashboard</h2>
    <p class="text-muted mb-4">BrewSync Kitchen & Order Queue</p>
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-coffee">Sign Out</button>
    </form>
</div>
@endsection
