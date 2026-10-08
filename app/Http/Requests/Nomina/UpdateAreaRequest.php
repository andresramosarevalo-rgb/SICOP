<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends StoreAreaRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('areas', 'nombre')->ignore($this->route('area'))],
            'es_activa' => ['required', 'boolean'],
        ];
    }
}
