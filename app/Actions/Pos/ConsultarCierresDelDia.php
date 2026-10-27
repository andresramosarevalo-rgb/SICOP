<?php

namespace App\Actions\Pos;

use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConsultarCierresDelDia
{
    /**
     * Estado del cierre de caja de cada sede en un día: las sedes activas y las que cerraron ese día,
     * con su cierre si ya lo cerraron (y su consignación, si la hay) o nulo si aún no.
     *
     * @return Collection<int, array{sede: Sede, cierre: CierreCaja|null}>
     */
    public function ejecutar(string $fecha): Collection
    {
        $cierres = CierreCaja::query()
            ->with(['consignacion', 'cerradoPor:id,name'])
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('sede_id');

        return Sede::query()
            ->where(fn (Builder $query) => $query->where('es_activa', true)->orWhereIn('id', $cierres->filter->estaCerrado()->keys()))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'ciudad'])
            ->map(fn (Sede $sede) => [
                'sede' => $sede,
                'cierre' => $cierres->get($sede->id)?->estaCerrado() ? $cierres->get($sede->id) : null,
            ])
            ->values();
    }
}
