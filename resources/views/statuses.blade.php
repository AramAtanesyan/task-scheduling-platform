<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Status Management - Task Scheduling Platform</title>
    
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
            background-color: #f5f5f5;
        }

        #app {
            min-height: 100vh;
        }

        /* Header Styles */
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

        /* Status Management Styles */
        .status-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .status-form-section,
        .status-list-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status-form-section h2,
        .status-list-section h2 {
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            color: #333;
        }

        .status-form {
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 1rem;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .color-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .color-picker {
            width: 60px;
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .color-text {
            flex: 1;
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

        .form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
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

        /* Status List Styles */
        .status-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: box-shadow 0.2s;
        }

        .status-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .status-color-badge {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .status-name {
            font-weight: 500;
            color: #333;
            flex: 1;
        }

        .status-color-code {
            color: #666;
            font-family: monospace;
            font-size: 0.875rem;
        }

        .status-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.25rem;
            padding: 0.25rem;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .btn-icon:hover {
            opacity: 1;
        }

        .empty-list {
            text-align: center;
            color: #999;
            padding: 3rem;
        }
    </style>
</head>
<body>
    <div id="app">
        <status-management></status-management>
    </div>
    
    <script src="{{ mix('js/pages/statuses.js') }}"></script>
</body>
</html>

