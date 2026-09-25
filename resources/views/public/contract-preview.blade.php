<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato de contratação</title>
    <style>
        body {
            background: #f5f5f5;
            color: #222;
            font-family: Arial, sans-serif;
            line-height: 1.5;
            margin: 0;
            padding: 24px;
        }

        .contract-document {
            background: #fff;
            margin: 0 auto;
            max-width: 900px;
            padding: 40px;
        }

        .no-print {
            display: flex;
            justify-content: center;
            margin-top: 16px;
        }

        .no-print button {
            background: #0057ff;
            border: 0;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            padding: 10px 18px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .contract-document {
                max-width: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="contract-document">
        {!! $content !!}
    </main>

    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir contrato</button>
    </div>
</body>
</html>
