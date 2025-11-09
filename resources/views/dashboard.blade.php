@extends('layouts.app')

@section('title', 'Dashboard - Task Scheduling Platform')

@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div id="app">
        <dashboard-layout></dashboard-layout>
    </div>
@endsection

@push('scripts')
    <script src="{{ mix('js/pages/dashboard.js') }}"></script>
@endpush
