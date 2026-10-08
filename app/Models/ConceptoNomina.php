<?php

namespace App\Models;

use App\Enums\FormaCalculo;
use App\Enums\TipoConcepto;
use Database\Factories\ConceptoNominaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Concepto que puede aparecer en un recibo de nómina: un devengo (suma) o una deducción (resta).
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property TipoConcepto $tipo
 * @property FormaCalculo $forma_calculo
 * @property string|null $valor_base
 * @property string|null $porcentaje_base
 * @property bool $es_constitutivo_salario
 * @property bool $es_sistema
 * @property bool $es_activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'codigo', 'nombre', 'tipo', 'forma_calculo', 'valor_base', 'porcentaje_base',
    'es_constitutivo_salario', 'es_sistema', 'es_activo',
])]
class ConceptoNomina extends Model
{
    /** @use HasFactory<ConceptoNominaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'conceptos_nomina';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoConcepto::class,
            'forma_calculo' => FormaCalculo::class,
            'valor_base' => 'decimal:2',
            'porcentaje_base' => 'decimal:2',
            'es_constitutivo_salario' => 'boolean',
            'es_sistema' => 'boolean',
            'es_activo' => 'boolean',
        ];
    }
}
