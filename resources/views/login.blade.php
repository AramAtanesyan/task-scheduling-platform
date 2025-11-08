<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Login - Task Scheduling Platform</title>
    
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

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-card h2 {
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }

        .login-form {
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover:not(:disabled) {
            background: #5568d3;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .error-message {
            color: #ef4444;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #fee2e2;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .login-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #666;
        }

        .login-info p {
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <div id="app">
        <login-form></login-form>
    </div>
    
    <script src="{{ mix('js/pages/login.js') }}"></script>
</body>
</html>

