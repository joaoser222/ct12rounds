@php
    $failureLabels = [
        'card_tokenization' => 'Recusa do cartão de crédito',
        'invoice_issuance' => 'Falha na emissão da fatura',
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body
    style="margin:0;padding:0;background-color:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                    style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;padding:24px;">
                    <tr>
                        <td style="font-size:18px;font-weight:700;padding-bottom:16px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:8px;">
                            {{ $title ?? 'Não foi possível concluir a cobrança' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:22px;padding-bottom:16px;">
                            O contrato abaixo permaneceu <strong>pendente</strong> e nenhuma cobrança foi emitida.
                            O cliente já recebeu a orientação na tela e precisa ser contatado para concluir a contratação.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:22px;padding:16px;background-color:#f4f5f7;border-radius:4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="font-size:13px;line-height:20px;">
                                <tr>
                                    <td style="padding:2px 0;width:160px;color:#6b7280;">Motivo</td>
                                    <td style="padding:2px 0;font-weight:700;">
                                        {{ $failureLabels[$reason ?? ''] ?? 'Falha na cobrança' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">Contrato</td>
                                    <td style="padding:2px 0;">#{{ $contractId }} — {{ $planName ?? 'sem plano' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">Cliente</td>
                                    <td style="padding:2px 0;">{{ $clientName ?? 'não informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">Documento</td>
                                    <td style="padding:2px 0;">{{ $clientDocument ?? 'não informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">E-mail</td>
                                    <td style="padding:2px 0;">{{ $clientEmail ?? 'não informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">Valor do contrato</td>
                                    <td style="padding:2px 0;">{{ $total ?? 'não informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;color:#6b7280;">Detalhe técnico</td>
                                    <td style="padding:2px 0;word-break:break-word;">{{ $detail ?? 'não informado' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @isset($actionUrl)
                    <tr>
                        <td style="padding-top:20px;">
                            <a href="{{ $actionUrl }}"
                                style="background-color:#0057ff;color:#ffffff;padding:12px 20px;border-radius:4px;text-decoration:none;display:inline-block;">
                                {{ $actionText ?? 'Acessar' }}
                            </a>
                        </td>
                    </tr>
                    @endisset
                    <tr>
                        <td style="padding-top:24px;font-size:12px;color:#6b7280;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
