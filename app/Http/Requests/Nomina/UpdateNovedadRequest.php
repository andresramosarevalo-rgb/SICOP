<?php

namespace App\Http\Requests\Nomina;

use App\Models\Novedad;

class UpdateNovedadRequest extends StoreNovedadRequest
{
    /**
     * Una novedad que ya entró en una liquidación no se puede modificar.
     */
    public function authorize(): bool
    {
        /** @var Novedad $novedad */
        $novedad = $this->route('novedad');

        return parent::authorize() && ! $novedad->estaLiquidada();
    }
}
