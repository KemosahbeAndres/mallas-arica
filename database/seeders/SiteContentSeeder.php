<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

/**
 * Contenido editable inicial de la landing (Sprint 8). Los valores son los que
 * antes vivían hardcodeados en los Blade — al sembrarlos pasan a ser el
 * contenido editable, no un fallback. updateOrCreate por key: re-ejecutar el
 * seeder no pisa lo que el dueño ya editó salvo que cambie la key.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // --- Hero ---
            ['hero.badge', 'text', 'Badge sobre el título', '🛡 Mallas de seguridad certificadas · Arica'],
            ['hero.titulo_1', 'text', 'Título — primera parte', 'Seguridad para tus hijos,'],
            ['hero.titulo_destacado', 'text', 'Título — palabra destacada (roja)', 'tranquilidad'],
            ['hero.titulo_2', 'text', 'Título — tercera parte', 'para tu familia.'],
            ['hero.descripcion', 'textarea', 'Descripción', 'Instalamos mallas de seguridad en ventanas, balcones y terrazas. Monofilamento de poliamida transparente que soporta más de 200 kg por m² y con filtro UV para el sol de Arica.'],
            ['hero.cta_primario', 'text', 'Texto del botón principal', 'Solicitar cotización'],
            ['hero.cta_secundario', 'text', 'Texto del botón secundario', 'Agendar visita'],
            ['hero.whatsapp', 'text', 'WhatsApp de contacto (solo dígitos, formato internacional)', '56986455205'],
            ['hero.check_1', 'text', 'Check de confianza 1', 'Instalación rápida y puntual'],
            ['hero.check_2', 'text', 'Check de confianza 2', 'Material certificado'],
            ['hero.check_3', 'text', 'Check de confianza 3', 'Efectivo, transferencia o tarjeta'],

            // --- Nosotros ---
            ['nosotros.titulo', 'text', 'Título de la sección', 'Locales de Arica, cuidando a las familias de Arica'],
            ['nosotros.texto_1', 'textarea', 'Párrafo 1', 'Mallas Arica Jacob está 100% dedicada a dar tranquilidad y seguridad a tu hogar. Instalamos mallas para terrazas, balcones y ventanas con el objetivo de proteger a niños, mascotas y adultos de posibles caídas.'],
            ['nosotros.texto_2', 'textarea', 'Párrafo 2', 'Somos los únicos que instalamos con esta malla certificada directamente por el fabricante, un material completamente seguro y resistente al clima de nuestra ciudad.'],
            ['nosotros.texto_3', 'textarea', 'Párrafo 3', 'Somos de confianza: llegamos puntuales a la hora acordada y siempre respondemos tus mensajes.'],
            ['nosotros.direccion', 'text', 'Dirección', 'Av. Diego Portales #1333, Arica'],
            ['nosotros.telefono', 'text', 'Teléfono (mostrado)', '+56 9 8645 5205'],
            ['nosotros.telefono_tel', 'text', 'Teléfono (enlace tel:, solo dígitos)', '+56986455205'],
            ['nosotros.medios_pago', 'textarea', 'Medios de pago', 'Efectivo, transferencia o tarjeta (débito y crédito hasta 3 cuotas)'],

            // --- Cotizaciones (PDF) ---
            ['cotizaciones.mensaje_vigencia', 'textarea', 'Mensaje de vigencia (pie del PDF de cotización)', 'Esta cotización tiene una vigencia de 10 días a contar de la fecha de emisión. Los valores están expresados en pesos chilenos (CLP) e incluyen IVA según se detalla.'],
        ];

        $orden = 0;

        foreach ($items as [$key, $tipo, $label, $value]) {
            $fila = SiteContent::firstOrNew(['key' => $key]);

            // Metadata: siempre la del seeder (define cómo se renderiza el form).
            $fila->grupo = explode('.', $key)[0];
            $fila->label = $label;
            $fila->tipo = $tipo;
            $fila->orden = $orden++;

            // Valor: solo al crear. Si la fila ya existía, respeta lo que editó el dueño.
            if (! $fila->exists) {
                $fila->value = $value;
            }

            $fila->save();
        }
    }
}
