<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Booking Bridge') }}</title>

    <style>
        :root {
            color-scheme: dark;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #0f172a;
            /* slate-900 */
            color: #e5e7eb;
        }

        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
        }

        h1 {
            margin: 0;
            font-size: 60px;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #ffffff;
        }

        .subtitle {
            margin-top: 12px;
            font-size: 18px;
            font-weight: 500;
            color: #94a3b8;
            /* slate-400 */
        }

        .desc {
            margin: 28px auto 0;
            max-width: 560px;
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1;
            /* slate-300 */
        }

        .actions {
            margin-top: 36px;
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #2563eb;
            /* blue-600 */
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            /* blue-700 */
        }

        .btn-secondary {
            background: transparent;
            border-color: #334155;
            /* slate-700 */
            color: #cbd5e1;
        }

        .btn-secondary:hover {
            background: #1e293b;
            /* slate-800 */
        }

        .footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            /* slate-500 */
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 42px;
            }
        }
    </style>
</head>

<body>

    <div class="wrap">

        <div>
            <h1>Party Legacy</h1>
            <div class="subtitle">Management Engine</div>

            <div class="desc">
                Sistema gestionale centralizzato per eventi e prenotazioni.
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="{{ route('login') }}">Login</a>
                <a class="btn btn-secondary" href="{{ route('register') }}">Register</a>
            </div>
        </div>

    </div>

    <div class="footer">
        © {{ date('Y') }} Party Legacy - All rights reserved.
    </div>

</body>

</html>
