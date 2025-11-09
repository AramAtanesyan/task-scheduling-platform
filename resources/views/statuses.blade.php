@extends('layouts.app')

@section('title', 'Status Management - Task Scheduling Platform')

@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div id="app">
        <status-management></status-management>
    </div>
@endsection

@push('scripts')
    <script src="{{ mix('js/pages/statuses.js') }}"></script>
@endpush
