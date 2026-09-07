<?php

namespace App\Livewire\Admin\SitioWeb;

use App\Models\SiteContent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ContenidoForm extends Component
{
    /** @var array<int, string> valores editables indexados por id de SiteContent */
    public array $valores = [];

    public ?string $guardado = null;

    protected function rules(): array
    {
        return collect($this->campos())
            ->mapWithKeys(fn (SiteContent $c) => ["valores.{$c->id}" => ['nullable', 'string', 'max:2000']])
            ->all();
    }

    public function mount(): void
    {
        $this->valores = $this->campos()
            ->mapWithKeys(fn (SiteContent $c) => [$c->id => (string) $c->value])
            ->all();
    }

    /**
     * @return Collection<int, SiteContent>
     */
    #[Computed]
    public function campos(): Collection
    {
        return SiteContent::query()->orderBy('orden')->get();
    }

    /**
     * @return Collection<string, Collection<int, SiteContent>>
     */
    #[Computed]
    public function grupos(): Collection
    {
        return $this->campos()->groupBy('grupo');
    }

    public function guardar(): void
    {
        $this->validate();

        foreach ($this->campos() as $campo) {
            $nuevo = trim($this->valores[$campo->id] ?? '');

            if ($nuevo !== (string) $campo->value) {
                $campo->update(['value' => $nuevo === '' ? null : $nuevo]);
            }
        }

        unset($this->campos, $this->grupos);
        $this->guardado = 'Contenido actualizado. Los cambios ya están en vivo en el sitio.';
    }

    public function render()
    {
        return view('livewire.admin.sitio-web.contenido-form');
    }
}
