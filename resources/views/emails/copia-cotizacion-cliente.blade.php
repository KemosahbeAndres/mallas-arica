<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Tu solicitud N° {{ $cotizacion->numero }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FAF7F0; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF7F0; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background-color:#211D1C; padding:24px 28px;">
                        <p style="color:#ffffff; font-size:18px; font-weight:bold; margin:0;">Mallas Arica</p>
                        <p style="color:#FAF7F0; font-size:13px; margin:4px 0 0;">Instalación de mallas de protección · Arica</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="font-size:15px; color:#211D1C; margin:0 0 16px;">Hola {{ $cotizacion->nombre }},</p>
                        <p style="font-size:14px; color:#3A3533; line-height:1.6; margin:0 0 16px;">
                            Gracias por contactar a Mallas Arica. Recibimos tu solicitud
                            <strong>N° {{ $cotizacion->numero }}</strong> y muy pronto te vamos a
                            escribir para coordinar la visita técnica gratuita.
                        </p>

                        @if ($tieneItems)
                            <p style="font-size:14px; color:#3A3533; line-height:1.6; margin:0 0 16px;">
                                Adjuntamos el PDF con el detalle y el rango estimado de tu cotización.
                            </p>
                        @endif

                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                            <tr>
                                <td style="background-color:#CA1E1E; border-radius:6px;">
                                    <a href="https://wa.me/56986455205" style="display:inline-block; padding:12px 22px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                                        Escríbenos por WhatsApp
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:12px; color:#8a8280; margin-top:24px;">
                            Si no solicitaste esta cotización, puedes ignorar este correo.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px; background-color:#F3EAE1; text-align:center;">
                        <p style="font-size:11px; color:#3A3533; margin:0;">
                            Mallas Arica · Av. Diego Portales #1333, Arica · +56 9 8645 5205 · contacto@mallasarica.cl
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
