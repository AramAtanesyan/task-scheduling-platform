@extends('layouts.app')

@section('title', 'Login - Task Scheduling Platform')

@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div id="app">
        <login-form></login-form>
    </div>
@endsection

@push('scripts')
    <script src="{{ mix('js/pages/login.js') }}"></script>
@endpush
