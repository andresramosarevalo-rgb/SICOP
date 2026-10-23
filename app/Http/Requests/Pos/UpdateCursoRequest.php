<?php

namespace App\Http\Requests\Pos;

class UpdateCursoRequest extends StoreCursoRequest
{
    /**
     * Solo quien opera la sede del registro puede modificarlo.
     */
    public function authorize(): bool
    {
        return $this->user()->can('operar-sede', $this->route('curso')->sede);
    }
}
