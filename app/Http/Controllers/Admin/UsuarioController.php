<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /**
     * Muestra los usuarios administrativos registrados.
     */
    public function index(): Response
    {
        $usuarios = User::query()
            ->whereIn('rol', array_map(fn (Rol $rol): string => $rol->value, Rol::cases()))
            ->with('sede:id,nombre')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'rol', 'sede_id']);

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Muestra el formulario de registro.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Usuarios/Create', [
            'roles' => [
                [
                    'valor' => Rol::Administrador->value,
                    'etiqueta' => 'Administrador',
                ],
                [
                    'valor' => Rol::AuxiliarNomina->value,
                    'etiqueta' => 'Auxiliar de nómina',
                ],
                [
                    'valor' => Rol::Contador->value,
                    'etiqueta' => 'Contador',
                ],
                [
                    'valor' => Rol::Cajero->value,
                    'etiqueta' => 'Cajero',
                ],
            ],
            'sedes' => Sede::activas()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Registra un usuario con un rol administrativo.
     */
    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string, rol: string, sede_id?: int|string|null} $datos */
        $datos = $request->validated();

        $usuario = new User([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
        ]);

        $usuario->rol = Rol::from($datos['rol']);
        $usuario->sede_id = $usuario->rol === Rol::Cajero ? (int) ($datos['sede_id'] ?? 0) : null;
        $usuario->save();
        event(new Registered($usuario));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Usuario registrado correctamente.',
        ]);

        return to_route('admin.usuarios.index');
    }
}
