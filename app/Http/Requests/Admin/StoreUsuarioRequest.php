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
                Rule::enum(Rol::class)->only($this->rolesPermitidos()),
            ],
            'sede_id' => $this->reglasSede(),
        ];
    }

    /**
     * Roles que quien registra puede asignar: solo el super administrador crea otros super administradores.
     *
     * @return list<Rol>
     */
    private function rolesPermitidos(): array
    {
        $roles = [Rol::Administrador, Rol::AuxiliarNomina, Rol::Contador, Rol::Cajero];

        return $this->user()->esSuperAdmin() ? [Rol::SuperAdmin, ...$roles] : $roles;
    }

    /**
     * La sede la elige el super administrador; el administrador de sede registra siempre en la suya.
     *
     * @return array<int, mixed>
     */
    private function reglasSede(): array
    {
        if (! $this->user()->esSuperAdmin()) {
            return ['prohibited'];
        }

        return [
            Rule::requiredIf(fn (): bool => $this->input('rol') !== Rol::SuperAdmin->value),
            Rule::prohibitedIf(fn (): bool => $this->input('rol') === Rol::SuperAdmin->value),
            'integer',
            Rule::exists('sedes', 'id')->where('es_activa', true),
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
            'sede_id.required' => 'Seleccione la sede del usuario.',
            'sede_id.prohibited' => 'Este usuario no lleva sede o se registra en su propia sede.',
            'sede_id.exists' => 'Seleccione una sede activa.',
        ];
    }
}
