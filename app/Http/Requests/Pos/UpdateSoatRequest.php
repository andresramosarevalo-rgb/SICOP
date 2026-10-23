<?php

namespace App\Http\Requests\Pos;

class UpdateSoatRequest extends StoreSoatRequest
{
    /**
     * Solo quien opera la sede del registro puede modificarlo.
     */
    public function authorize(): bool
    {
        return $this->user()->can('operar-sede', $this->route('soat')->sede);
    }
}
