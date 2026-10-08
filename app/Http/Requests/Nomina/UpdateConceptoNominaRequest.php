<?php

namespace App\Http\Requests\Nomina;

use App\Models\ConceptoNomina;

class UpdateConceptoNominaRequest extends StoreConceptoNominaRequest
{
    /**
     * Los conceptos de sistema no se editan: su cálculo está definido por ley.
     */
    public function authorize(): bool
    {
        /** @var ConceptoNomina $concepto */
        $concepto = $this->route('concepto');

        return parent::authorize() && ! $concepto->es_sistema;
    }
}
