<?php

namespace App\Actions\Nomina;

use App\Enums\TipoConcepto;
use BcMath\Number;

/**
 * Línea calculada de un recibo: un devengo o una deducción, con su valor ya redondeado al peso.
 */
final readonly class LineaLiquidacion
{
    public function __construct(
        public string $codigoConcepto,
        public TipoConcepto $tipo,
        public string $descripcion,
        public ?int $cantidad,
        public Number $valor,
    ) {}
}
