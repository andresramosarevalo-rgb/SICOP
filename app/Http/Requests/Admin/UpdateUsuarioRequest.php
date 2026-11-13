<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
     * Se edita el nombre y el correo, y opcionalmente se restablece la contraseña; el rol y la sede no cambian.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
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
            'email.unique' => 'Ya existe un usuario con este correo.',
        ];
    }
}
