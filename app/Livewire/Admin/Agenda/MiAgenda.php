<?php

namespace App\Livewire\Admin\Agenda;

use App\Models\Trabajo;
use App\Services\GoogleCalendarService;
use App\Services\GoogleOAuthConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Agenda de solo lectura para el colaborador: sus propias OT asignadas
 * (fuente de verdad de esta plataforma), ordenadas por fecha de agenda, más
 * — solo como comodidad visual — sus próximos eventos personales de Google
 * Calendar que no vienen de una OT de esta plataforma (para no duplicar lo
 * que ya se ve arriba). Sin CRUD de eventos ni acceso a clientes (eso es
 * Agenda completa, ver AgendaIndex); esta vista nunca escribe en Google.
 */
class MiAgenda extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->esColaborador(), 403);
    }

    #[Computed]
    public function misTrabajos(): Collection
    {
        return Trabajo::query()
            ->with(['cliente:id,nombre', 'clienteDireccion:id,direccion', 'evento'])
            ->whereHas('colaboradores', fn ($q) => $q->where('users.id', auth()->id()))
            ->whereNotIn('estado', ['cancelada'])
            ->get()
            ->sortBy(fn (Trabajo $t) => $t->evento?->inicio ?? now()->addYears(10))
            ->values();
    }

    /**
     * Próximos eventos del Google Calendar personal del colaborador que NO
     * corresponden a una OT ya listada arriba (se identifican por el
     * google_event_id que esta plataforma generó al sincronizar). Si el
     * usuario no conectó Calendar, o la API falla, la lista queda vacía sin
     * romper la página — es solo información de comodidad.
     *
     * @return Collection<int, array{titulo: string, inicio: ?Carbon}>
     */
    #[Computed]
    public function eventosGoogle(): Collection
    {
        $usuario = auth()->user();

        if (! $usuario->tieneGoogleCalendarConectado()) {
            return collect();
        }

        $idsPropios = $this->misTrabajos
            ->pluck('evento')
            ->filter()
            ->map(fn ($evento) => $evento->googleEventIdDe($usuario->id))
            ->filter()
            ->all();

        try {
            $eventos = (new GoogleCalendarService($usuario, app(GoogleOAuthConfig::class)))->listEvents(10);
        } catch (Throwable) {
            return collect();
        }

        return collect($eventos)
            ->reject(fn ($evento) => in_array($evento->getId(), $idsPropios, true))
            ->map(fn ($evento) => [
                'titulo' => $evento->getSummary() ?: '(Sin título)',
                'inicio' => $this->inicioDe($evento),
            ])
            ->values();
    }

    private function inicioDe($evento): ?Carbon
    {
        $inicio = $evento->getStart();
        $valor = $inicio?->getDateTime() ?? $inicio?->getDate();

        return $valor ? Carbon::parse($valor) : null;
    }

    public function render()
    {
        return view('livewire.admin.agenda.mi-agenda')
            ->layout('components.layouts.admin', [
                'title' => 'Mi agenda',
                'subtitle' => 'Tus trabajos asignados',
            ]);
    }
}
