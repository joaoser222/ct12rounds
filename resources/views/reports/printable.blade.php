<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 2.2cm 2.5cm;
        }

        body {
            color: #222;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.55;
        }

        h1 {
            font-size: 16pt;
            margin: 0 0 18pt;
            text-align: center;
        }

        h2 {
            font-size: 12pt;
            margin: 16pt 0 6pt;
        }

        p {
            margin: 0 0 9pt;
            text-align: justify;
        }

        a {
            color: #1f4e79;
            text-decoration: underline;
        }
    </style>
</head>
<body>
{!! $content !!}
</body>
</html>
