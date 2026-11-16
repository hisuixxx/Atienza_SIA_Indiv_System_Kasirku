@extends('layouts.auth')

@section('title', 'Admin Dashboard')

@section('content')
<div class="text-center py-4">
    <h2 class="font-serif fw-bold mb-2">Administrator Dashboard</h2>
    <p class="text-muted mb-4">BrewSync Management Operations</p>
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-coffee">Sign Out</button>
    </form>
</div>
@endsection
