<?php

namespace App\Http\Requests\Pos;

use App\Models\Soat;

class UpdateSoatRequest extends StoreSoatRequest
{
    /**
     * Solo quien opera la sede del registro puede modificarlo.
     */
    public function authorize(): bool
    {
        $soat = $this->route('soat');

        return $soat instanceof Soat && $this->user()->can('operar-sede', $soat->sede);
    }
}
