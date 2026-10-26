<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\CalcularPagoGestor;
use App\Enums\CodigoInfraccion;
use App\Enums\FormaPago;
use App\Enums\Referido;
use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreCursoRequest;
use App\Http\Requests\Pos\UpdateCursoRequest;
use App\Models\Curso;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CursoController extends Controller
{
    /**
     * Muestra los cursos de la sede, los más recientes primero.
     */
    public function index(Request $request, Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);

        return Inertia::render('Pos/Cursos/Index', [
            'sede' => $sede,
            'cursos' => $this->sinPagoGestorParaSedes($request, $sede->cursos()->orderByDesc('fecha')->orderByDesc('id')->get()),
        ]);
    }

    /**
     * Muestra el formulario para registrar un curso. Solo las sedes activas registran ventas.
     */
    public function create(Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        return Inertia::render('Pos/Cursos/Create', [
            'sede' => $sede,
            ...$this->opcionesFormulario(),
        ]);
    }

    /**
     * Registra un curso en la sede.
     */
    public function store(StoreCursoRequest $request, Sede $sede, CalcularPagoGestor $calcularPagoGestor): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $datos = $request->datosParaGuardar();

        $sede->cursos()->create([...$datos, 'valor_pago_gestor' => $calcularPagoGestor->ejecutar(
            $sede->id,
            CodigoInfraccion::from($datos['codigo_infraccion']),
            Referido::tryFrom((string) ($datos['referido'] ?? '')),
        )]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso registrado.']);

        return to_route('pos.sedes.cursos.index', $sede);
    }

    /**
     * Muestra el formulario de edición del curso.
     */
    public function edit(Request $request, Curso $curso): Response
    {
        Gate::authorize('operar-sede', $curso->sede);

        return Inertia::render('Pos/Cursos/Edit', [
            'curso' => $this->sinPagoGestorParaSedes($request, collect([$curso]))->first(),
            'sede' => $curso->sede,
            ...$this->opcionesFormulario(),
        ]);
    }

    /**
     * Actualiza los datos del curso.
     */
    public function update(UpdateCursoRequest $request, Curso $curso, CalcularPagoGestor $calcularPagoGestor): RedirectResponse
    {
        $curso->fill($request->datosParaGuardar());

        if ($curso->isDirty(['codigo_infraccion', 'referido'])) {
            $curso->valor_pago_gestor = $calcularPagoGestor->ejecutar($curso->sede_id, $curso->codigo_infraccion, $curso->referido);
        }

        $curso->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso actualizado.']);

        return to_route('pos.sedes.cursos.index', $curso->sede_id);
    }

    /**
     * Opciones de los selectores del formulario de curso.
     *
     * @return array{tiposDocumento: list<array{valor: string, etiqueta: string}>, codigosInfraccion: list<array{valor: string, etiqueta: string}>, formasPago: list<array{valor: string, etiqueta: string}>, referidos: list<array{valor: string, etiqueta: string}>, porcentajesDescuento: list<int>}
     */
    private function opcionesFormulario(): array
    {
        return [
            'tiposDocumento' => TipoDocumento::opciones(),
            'codigosInfraccion' => CodigoInfraccion::opciones(),
            'formasPago' => FormaPago::opciones(),
            'referidos' => Referido::opciones(),
            'porcentajesDescuento' => Curso::PORCENTAJES_DESCUENTO,
        ];
    }

    /**
     * Oculta el pago al gestor a quien no gestiona POS: el instructor de la sede no lo ve.
     *
     * @param  Collection<int, Curso>  $cursos
     * @return Collection<int, Curso>
     */
    private function sinPagoGestorParaSedes(Request $request, Collection $cursos): Collection
    {
        if (! $request->user()->can('gestionar-pos')) {
            $cursos->each->makeHidden('valor_pago_gestor');
        }

        return $cursos;
    }
}
