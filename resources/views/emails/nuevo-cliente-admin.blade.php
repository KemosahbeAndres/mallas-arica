<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nuevo contacto — {{ $cliente->nombre }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FAF7F0; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF7F0; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background-color:#211D1C; padding:20px 28px;">
                        <span style="display:inline-block; background-color:#CA1E1E; color:#ffffff; font-size:12px; font-weight:bold; letter-spacing:0.05em; padding:6px 14px; border-radius:999px;">NUEVO CONTACTO</span>
                        <p style="color:#ffffff; font-size:18px; font-weight:bold; margin:12px 0 0;">{{ $cliente->nombre }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 28px;">
                        <p style="font-size:14px; color:#211D1C; margin:0 0 4px;">
                            <strong>Teléfono:</strong>
                            <a href="tel:{{ $cliente->telefono }}" style="color:#CA1E1E; text-decoration:none;">{{ $cliente->telefono }}</a>
                            @if ($cliente->telefono)
                                ·
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $cliente->telefono) }}" style="color:#CA1E1E; text-decoration:none;">WhatsApp</a>
                            @endif
                        </p>
                        @if ($cliente->email)
                            <p style="font-size:14px; color:#211D1C; margin:0 0 4px;"><strong>Correo:</strong> {{ $cliente->email }}</p>
                        @endif
                        @foreach ($cliente->direcciones as $direccion)
                            <p style="font-size:14px; color:#211D1C; margin:0 0 4px;"><strong>Dirección:</strong> {{ $direccion->direccion }}</p>
                        @endforeach

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; background-color:#F3EAE1; border-left:4px solid #CA1E1E; border-radius:4px;">
                            <tr>
                                <td style="padding:12px 16px; font-size:13px; color:#3A3533;">
                                    Contáctalo para coordinar la visita técnica y armar la cotización desde el panel.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
