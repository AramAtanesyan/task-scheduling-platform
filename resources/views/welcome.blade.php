@extends('layouts.app')

@section('title', 'Task Scheduling Platform')

@push('styles')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="welcome-container">
        <div class="welcome-content">
            <h1>Task Scheduling Platform</h1>
            <p>Manage your tasks efficiently</p>
            <div class="welcome-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary">Login</a>
                @endauth
            </div>
        </div>
    </div>
@endsection
