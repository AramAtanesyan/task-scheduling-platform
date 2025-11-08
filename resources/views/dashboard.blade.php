<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Dashboard - Task Scheduling Platform</title>
    
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        #app {
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        /* Dashboard Styles */
        .dashboard {
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .dashboard-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.5rem;
            color: #333;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            color: #666;
            font-weight: 500;
        }

        .btn-logout {
            padding: 0.5rem 1rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .btn-secondary {
            padding: 0.5rem 1rem;
            background: white;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .dashboard-content {
            padding: 2rem;
        }

        /* Loader Styles */
        .loader-overlay {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 400px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-overlay p {
            margin-top: 1rem;
            color: #666;
        }

        /* Task Board Styles */
        .task-board {
            max-width: 1400px;
            margin: 0 auto;
        }

        .board-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input,
        .filter-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .search-input {
            min-width: 200px;
        }

        .filter-select {
            min-width: 150px;
        }

        .btn-create {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-create:hover {
            background: #5568d3;
        }

        .view-toggle {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .toggle-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .kanban-column {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .column-header {
            margin-bottom: 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        .column-content {
            min-height: 200px;
        }

        .empty-column {
            text-align: center;
            color: #999;
            padding: 2rem;
        }

        .list-view {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .empty-list {
            text-align: center;
            color: #999;
            padding: 3rem;
            background: white;
            border-radius: 8px;
        }

        /* Task Card Styles */
        .task-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: box-shadow 0.2s;
        }

        .task-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin: 0;
            flex: 1;
        }

        .task-actions {
            display: flex;
            gap: 0.25rem;
        }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .btn-icon:hover {
            opacity: 1;
        }

        .task-description {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .task-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .task-assignee {
            color: #555;
        }

        .task-dates {
            display: flex;
            gap: 1rem;
            color: #666;
        }

        .task-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
            width: fit-content;
        }

        /* Task Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-text {
            display: block;
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .error-message {
            background: #fee2e2;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-primary {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-primary:hover:not(:disabled) {
            background: #5568d3;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Confirm Modal Styles */
        .confirm-modal {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .confirm-modal .modal-body {
            padding: 1.5rem 1.5rem 0.5rem;
        }
        
        .confirm-modal .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
        }

        .confirm-modal .modal-body p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .btn-danger {
            padding: 0.5rem 1rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
        }

        .btn-danger:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-footer .spinner {
            width: 16px;
            height: 16px;
            border-width: 2px;
            display: inline-block;
            margin: 0;
        }

        /* Modal fade animation */
        .modal-fade-enter-active,
        .modal-fade-leave-active {
            transition: opacity 0.3s;
        }

        .modal-fade-enter-active .modal,
        .modal-fade-leave-active .modal {
            transition: transform 0.3s;
        }

        .modal-fade-enter,
        .modal-fade-leave-to {
            opacity: 0;
        }

        .modal-fade-enter .modal,
        .modal-fade-leave-to .modal {
            transform: scale(0.9);
        }
    </style>
</head>
<body>
    <div id="app">
        <dashboard-layout></dashboard-layout>
    </div>
    
    <script src="{{ mix('js/pages/dashboard.js') }}"></script>
</body>
</html>

