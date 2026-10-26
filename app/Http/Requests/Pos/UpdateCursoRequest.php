<?php

namespace App\Http\Requests\Pos;

use App\Models\Curso;

class UpdateCursoRequest extends StoreCursoRequest
{
    /**
     * Solo quien opera la sede del registro puede modificarlo.
     */
    public function authorize(): bool
    {
        $curso = $this->route('curso');

        return $curso instanceof Curso && $this->user()->can('operar-sede', $curso->sede);
    }
}
