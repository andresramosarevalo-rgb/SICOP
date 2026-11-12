<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario puede editar al usuario de la ruta.
     */
    public function authorize(): bool
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return $this->user()?->can('gestionar-usuario', $usuario) ?? false;
    }

    /**
     * Solo se edita el nombre; el correo, el rol y la sede no cambian.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Mensajes de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
        ];
    }
}
