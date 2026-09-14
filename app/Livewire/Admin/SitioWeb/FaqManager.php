<?php

namespace App\Livewire\Admin\SitioWeb;

use App\Models\Faq;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FaqManager extends Component
{
    /** @var array<int, array{pregunta: string, respuesta: string, publicada: bool}> */
    public array $filas = [];

    public ?string $guardado = null;

    protected function rules(): array
    {
        return [
            'filas.*.pregunta' => ['required', 'string', 'max:255'],
            'filas.*.respuesta' => ['required', 'string', 'max:2000'],
            'filas.*.publicada' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'filas.*.pregunta.required' => 'La pregunta no puede quedar vacía.',
            'filas.*.respuesta.required' => 'La respuesta no puede quedar vacía.',
        ];
    }

    public function mount(): void
    {
        $this->cargar();
    }

    #[Computed]
    public function preguntas()
    {
        return Faq::query()->orderBy('orden')->get();
    }

    private function cargar(): void
    {
        $this->filas = $this->preguntas->map(fn (Faq $f) => [
            'id' => $f->id,
            'pregunta' => $f->pregunta,
            'respuesta' => $f->respuesta,
            'publicada' => $f->publicada,
        ])->all();
    }

    public function agregar(): void
    {
        $this->filas[] = ['id' => null, 'pregunta' => '', 'respuesta' => '', 'publicada' => true];
    }

    public function eliminar(int $indice): void
    {
        $fila = $this->filas[$indice] ?? null;

        if ($fila && $fila['id']) {
            Faq::whereKey($fila['id'])->delete();
        }

        unset($this->filas[$indice]);
        $this->filas = array_values($this->filas);
        unset($this->preguntas);
        $this->guardado = 'Pregunta eliminada.';
    }

    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            foreach (array_values($this->filas) as $orden => $fila) {
                Faq::updateOrCreate(
                    ['id' => $fila['id']],
                    [
                        'pregunta' => trim($fila['pregunta']),
                        'respuesta' => trim($fila['respuesta']),
                        'publicada' => (bool) $fila['publicada'],
                        'orden' => $orden,
                    ],
                );
            }
        });

        unset($this->preguntas);
        $this->cargar();
        $this->guardado = 'Preguntas frecuentes actualizadas. Los cambios ya están en vivo en el sitio.';
    }

    public function render()
    {
        return view('livewire.admin.sitio-web.faq-manager');
    }
}
