<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización N° {{ $numero }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #211D1C; }

        .header { width: 100%; }
        .header td { vertical-align: top; }
        .logo { width: 46px; height: auto; }
        .brand-nombre { font-size: 20px; font-weight: bold; margin: 0 0 0 10px; }
        .brand-sub { font-size: 10px; color: #3A3533; margin: 2px 0 0 10px; }
        .badge {
            display: inline-block;
            background: #CA1E1E;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.05em;
            padding: 6px 14px;
            border-radius: 999px;
        }
        .numero-label { font-size: 9px; color: #3A3533; margin-top: 10px; }
        .numero { font-size: 20px; font-weight: bold; color: #CA1E1E; }
        .fecha { font-size: 10px; margin-top: 4px; }

        .partes { width: 100%; margin-top: 22px; border-collapse: separate; border-spacing: 0; background: #F3EAE1; border-radius: 10px; }
        .partes td { padding: 14px 18px; vertical-align: top; width: 50%; }
        .partes-label { color: #CA1E1E; font-size: 9px; font-weight: bold; letter-spacing: 0.05em; }
        .partes-nombre { font-weight: bold; font-size: 12px; margin-top: 3px; }
        .partes-detalle { color: #3A3533; font-size: 10px; margin-top: 2px; line-height: 1.5; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 22px; }
        table.items thead th {
            background: #211D1C;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
            padding: 10px 12px;
        }
        table.items thead th.num { text-align: right; }
        table.items tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #EAE1D8;
            font-size: 10.5px;
            vertical-align: top;
        }
        table.items tbody td.num { text-align: right; white-space: nowrap; }
        table.items tbody td.subtotal { font-weight: bold; }
        .pendiente { color: #3A3533; font-style: italic; }

        table.totales { width: 100%; margin-top: 16px; }
        table.totales td { padding: 3px 0; font-size: 11px; }
        table.totales td.label { text-align: right; padding-right: 24px; color: #3A3533; width: 78%; }
        table.totales td.valor { text-align: right; white-space: nowrap; }
        .total-row td { padding-top: 10px; }
        .total-box {
            background: #CA1E1E;
            color: #fff;
            border-radius: 8px;
            padding: 12px 18px;
        }
        .total-box .label { color: #fff; font-weight: bold; font-size: 13px; }
        .total-box .valor { font-weight: bold; font-size: 16px; }

        .vigencia {
            margin-top: 28px;
            background: #F3EAE1;
            border-left: 4px solid #CA1E1E;
            padding: 12px 16px;
            font-size: 10px;
            color: #3A3533;
            line-height: 1.5;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #8a8280;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 60%;">
                <table>
                    <tr>
                        <td style="width: 46px;">
                            <img class="logo" src="{{ public_path('images/isologo.png') }}" alt="Mallas Arica">
                        </td>
                        <td>
                            <p class="brand-nombre">Mallas Arica</p>
                            <p class="brand-sub">Instalación de mallas de protección · Arica</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; text-align: right;">
                <span class="badge">COTIZACIÓN</span>
                <p class="numero-label">N°</p>
                <p class="numero">{{ $numero }}</p>
                <p class="fecha">Fecha: {{ $fecha }}</p>
            </td>
        </tr>
    </table>

    <table class="partes">
        <tr>
            <td>
                <p class="partes-label">EMPRESA</p>
                <p class="partes-nombre">Mallas Arica</p>
                <p class="partes-detalle">
                    RUT {{ $empresa['rut'] }} · {{ $empresa['direccion'] }}<br>
                    {{ $empresa['telefono'] }} · {{ $empresa['email'] }}
                </p>
            </td>
            <td>
                <p class="partes-label">CLIENTE</p>
                <p class="partes-nombre">{{ $cotizacion->nombre }}</p>
                <p class="partes-detalle">
                    {{ $cotizacion->direccion ?: 'Arica' }}<br>
                    {{ $cotizacion->telefono }}{{ $cotizacion->email ? ' · '.$cotizacion->email : '' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="num">P. unitario</th>
                <th class="num">Cant.</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lineas as $linea)
                <tr>
                    <td>{{ $linea['descripcion'] }}</td>
                    @if ($linea['pendiente'])
                        <td class="num" colspan="3"><span class="pendiente">A confirmar en visita técnica</span></td>
                    @else
                        <td class="num">${{ number_format($linea['precioUnitario'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($linea['cantidad'], 1, ',', '.') }}</td>
                        <td class="num subtotal">${{ number_format($linea['subtotal'], 0, ',', '.') }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td class="label">Neto</td>
            <td class="valor">${{ number_format($neto, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">IVA (19%)</td>
            <td class="valor">${{ number_format($iva, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="label"></td>
            <td class="valor">
                <table class="total-box" style="width: 100%;">
                    <tr>
                        <td class="label">Total</td>
                        <td class="valor" style="text-align: right;">${{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="vigencia">
        Esta cotización tiene una <strong>vigencia de 10 días</strong> a contar de la fecha de emisión.
        Los valores están expresados en pesos chilenos (CLP) e incluyen IVA según se detalla.
    </div>

    <p class="footer">
        Mallas Arica · {{ $empresa['telefono'] }} · {{ $empresa['email'] }} · Gracias por su preferencia
    </p>
</body>
</html>
