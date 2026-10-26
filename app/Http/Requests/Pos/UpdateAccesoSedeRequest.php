<?php

namespace App\Http\Requests\Pos;

use App\Models\Sede;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAccesoSedeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-pos');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * La contraseña solo es obligatoria al crear el acceso; después se deja vacía para conservarla.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Sede $sede */
        $sede = $this->route('sede');
        $usuario = $sede->usuario;

        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'email.email' => 'Escriba un correo válido.',
            'email.unique' => 'Ese correo ya está en uso.',
            'password.confirmed' => 'La confirmación no coincide con la contraseña.',
        ];
    }
}
