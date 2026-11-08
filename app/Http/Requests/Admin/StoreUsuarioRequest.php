<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Enums\Rol;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Determina si el usuario puede registrar otros usuarios.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-usuarios') ?? false;
    }

    /**
     * Reglas de validación para registrar usuarios.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => $this->passwordRules(),
            'rol' => [
                'required',
                Rule::enum(Rol::class)->only([
                    Rol::Administrador,
                    Rol::AuxiliarNomina,
                    Rol::Contador,
                ]),
            ],
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
            'rol.enum' => 'Seleccione un rol válido.',
        ];
    }
}
