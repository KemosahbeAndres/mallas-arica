<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * FAQ inicial (Sprint 8) — las 7 preguntas que antes vivían hardcodeadas en
 * resources/views/components/landing/faq.blade.php y alimentaban el acordeón
 * y el JSON-LD FAQPage (CLAUDE.md §4.9). Idempotente por pregunta.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $preguntas = [
            [
                'pregunta' => '¿Cuánto cuesta la instalación en una ventana o balcón?',
                'respuesta' => 'Cotizamos por metro lineal, y el valor varía según la altura del espacio y si es ventana o balcón. Completa el formulario de esta página para coordinar una visita técnica gratuita y te entregamos una cotización exacta según tu medida.',
            ],
            [
                'pregunta' => '¿Tienen distintos tipos de malla?',
                'respuesta' => 'Sí, contamos con malla estándar transparente y una malla reforzada especial para mascotas, con rombo más pequeño para evitar que perros y gatos saquen la cabeza.',
            ],
            [
                'pregunta' => '¿Cómo puedo pedir una cotización?',
                'respuesta' => 'Puedes completar el formulario en esta página, escribirnos por WhatsApp o llamarnos directamente. Coordinamos una visita técnica gratuita para confirmar la medida exacta.',
            ],
            [
                'pregunta' => '¿Cuánto se demoran en hacer el trabajo?',
                'respuesta' => 'La visita técnica para cotizar y la instalación son dos citas distintas: primero medimos y te confirmamos el precio, y luego agendamos un día y hora aparte para instalar. La instalación es rápida y llegamos puntuales a la hora acordada — si algo cambia, siempre te avisamos.',
            ],
            [
                'pregunta' => '¿El material es seguro?',
                'respuesta' => 'Sí, trabajamos con malla de monofilamento de poliamida certificada por el fabricante, con resistencia comprobada de más de 200 kg/m² y filtro UV.',
            ],
            [
                'pregunta' => '¿Qué medios de pago aceptan?',
                'respuesta' => 'Aceptamos efectivo, transferencia bancaria y tarjetas de débito y crédito (hasta 3 cuotas), directamente en terreno al finalizar la instalación.',
            ],
            [
                'pregunta' => '¿Dónde puedo obtener más información?',
                'respuesta' => 'Escríbenos por WhatsApp al +56 9 8645 5205, por correo a contacto@mallasarica.cl o ventas@mallasarica.cl, o visítanos en Av. Diego Portales #1333, Arica.',
            ],
        ];

        foreach ($preguntas as $orden => $item) {
            Faq::firstOrCreate(
                ['pregunta' => $item['pregunta']],
                ['respuesta' => $item['respuesta'], 'orden' => $orden, 'publicada' => true],
            );
        }
    }
}
