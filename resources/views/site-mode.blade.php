<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — CT 12 Rounds</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #0f1115;
            color: #f5f5f5;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 520px;
            text-align: center;
            padding: 48px 32px;
            border-radius: 16px;
            background: #1a1d23;
            border: 1px solid #2a2e37;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .35);
        }
        .brand {
            font-size: 13px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #e63946;
            margin-bottom: 24px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 28px;
            line-height: 1.2;
        }
        p {
            margin: 0 auto 28px;
            max-width: 380px;
            color: #b3b8c2;
            line-height: 1.6;
        }
        a {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            background: #e63946;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover { background: #d62839; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">CT 12 Rounds</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ route('login') }}">Acesso da equipe</a>
    </main>
</body>
</html>
