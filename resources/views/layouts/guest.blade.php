<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Steam Store BD') }}</title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='url(%23g)'/><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%232563EB'/><stop offset='1' stop-color='%231D4ED8'/></linearGradient></defs><text x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='36'>🎮</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        gray: {
                            950: '#040D1A',
                            900: '#071428',
                            800: '#0E1F35',
                            700: '#152E4F',
                            600: '#214263',
                            500: '#3A5E80',
                            400: '#557AA0',
                            300: '#7898BB',
                            200: '#9BB5D5',
                            100: '#D4E0F5',
                            50:  '#EEF4FF',
                        },
                        brand: {
                            900: '#0D1B3E',
                            800: '#112249',
                            700: '#1A3460',
                            600: '#1D4ED8',
                            500: '#2563EB',
                            400: '#4B8FEF',
                            300: '#7CB3F5',
                            200: '#BFDBFE',
                            100: '#DBEAFE',
                            50:  '#EFF6FF',
                        },
                    },
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        .auth-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(7, 20, 40, 0.6);
            border: 1px solid rgba(85, 122, 160, 0.35);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .auth-input::placeholder { color: #557AA0; }
        .auth-input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        .auth-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #9BB5D5;
            margin-bottom: 0.4rem;
        }
        .auth-btn {
            width: 100%;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #fff;
            font-weight: 600;
            font-size: 0.9375rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: opacity 0.2s, box-shadow 0.2s, transform 0.1s;
            letter-spacing: 0.01em;
        }
        .auth-btn:hover {
            opacity: 0.92;
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.5);
            transform: translateY(-1px);
        }
        .auth-btn:active { transform: translateY(0); }

        .auth-error {
            font-size: 0.75rem;
            color: #F87171;
            margin-top: 0.35rem;
        }

        .left-panel-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .grid-overlay {
            background-image:
                linear-gradient(rgba(37,99,235,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37,99,235,0.07) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        @media (max-width: 768px) {
            .auth-left-panel { display: none; }
            .auth-right-panel { width: 100% !important; }
        }
    </style>
</head>
<body class="antialiased" style="background:#040D1A; min-height:100vh;">
    {{ $slot }}
</body>
</html>
