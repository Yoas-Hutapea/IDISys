<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Log in') - IDI System</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="{{ asset('assets/css/account.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/custom-bootstrap.css') }}" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicons.png') }}">
    <style>
        body {
            background-image: url('{{ asset('assets/media/login-background.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Remove zoom wrapper for better responsive design */
        .login-container {
            min-height: 100vh;
        }

        .zoom-wrapper {
            /* zoom: 90%; */
            /* transform: scale(0.8);
            transform-origin: top left;
            width: 125%; */
            position: absolute;
            top: 0;
            left: 0;
            transform: scale(0.9);
            transform-origin: top left;
            width: 111%;
        }
    </style>
</head>
<body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        // Basic functionality for smooth scrolling and general enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for better UX
            document.documentElement.style.scrollBehavior = 'smooth';
        });
    </script>
    @yield('scripts')
</body>
</html>
