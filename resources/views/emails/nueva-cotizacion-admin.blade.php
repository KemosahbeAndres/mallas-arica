<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cotización N° {{ $cotizacion->numero }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FAF7F0; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF7F0; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background-color:#211D1C; padding:20px 28px;">
                        <span style="display:inline-block; background-color:#CA1E1E; color:#ffffff; font-size:12px; font-weight:bold; letter-spacing:0.05em; padding:6px 14px; border-radius:999px;">NUEVA COTIZACIÓN</span>
                        <p style="color:#ffffff; font-size:20px; font-weight:bold; margin:12px 0 0;">N° {{ $cotizacion->numero }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 28px;">
                        <p style="font-size:14px; color:#211D1C; margin:0 0 4px;"><strong>Nombre:</strong> {{ $cotizacion->nombre }}</p>
                        <p style="font-size:14px; color:#211D1C; margin:0 0 4px;">
                            <strong>Teléfono:</strong>
                            <a href="tel:{{ $cotizacion->telefono }}" style="color:#CA1E1E; text-decoration:none;">{{ $cotizacion->telefono }}</a>
                            ·
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $cotizacion->telefono) }}" style="color:#CA1E1E; text-decoration:none;">WhatsApp</a>
                        </p>
                        @if ($cotizacion->email)
                            <p style="font-size:14px; color:#211D1C; margin:0 0 4px;"><strong>Correo:</strong> {{ $cotizacion->email }}</p>
                        @endif
                        @if ($cotizacion->direccion)
                            <p style="font-size:14px; color:#211D1C; margin:0 0 4px;"><strong>Dirección:</strong> {{ $cotizacion->direccion }}</p>
                        @endif

                        @if ($cotizacion->requiere_visita)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; background-color:#F3EAE1; border-left:4px solid #CA1E1E; border-radius:4px;">
                                <tr>
                                    <td style="padding:12px 16px; font-size:13px; color:#3A3533;">
                                        Requiere visita técnica para medir y cotizar en terreno.
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if ($cotizacion->items->isNotEmpty())
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th align="left" style="background-color:#211D1C; color:#ffffff; font-size:11px; padding:8px 10px;">Espacio</th>
                                        <th align="right" style="background-color:#211D1C; color:#ffffff; font-size:11px; padding:8px 10px;">Metros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cotizacion->items as $item)
                                        <tr>
                                            <td style="border-bottom:1px solid #EAE1D8; font-size:13px; padding:8px 10px; color:#211D1C;">{{ $item->tipoEspacio?->nombre ?? 'Espacio' }}</td>
                                            <td align="right" style="border-bottom:1px solid #EAE1D8; font-size:13px; padding:8px 10px; color:#211D1C;">{{ number_format((float) $item->metros_lineales, 1, ',', '.') }} ml</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p style="font-size:15px; color:#211D1C; margin-top:16px;">
                                <strong>Rango total:</strong> ${{ number_format($cotizacion->total_min, 0, ',', '.') }} – ${{ number_format($cotizacion->total_max, 0, ',', '.') }}
                            </p>
                        @else
                            <p style="font-size:13px; color:#3A3533; margin-top:16px; font-style:italic;">
                                Solicitud de visita técnica sin cálculo de precio (formulario de contacto).
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
