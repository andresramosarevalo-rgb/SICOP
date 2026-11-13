<?php

namespace App\Providers;

use App\Enums\Rol;
use App\Models\AsignacionConcepto;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->definirPermisos();
        $this->limitarNominaALaSedeDelUsuario();
    }

    /**
     * Define los permisos de acceso a los módulos según el rol del usuario.
     */
    protected function definirPermisos(): void
    {
        Gate::define('gestionar-nomina', fn (User $user): bool => $user->rol?->puedeGestionarNomina() ?? false);
        Gate::define('gestionar-pos', fn (User $user): bool => $user->rol?->puedeGestionarPos() ?? false);
        Gate::define('ver-auditoria', fn (User $user): bool => $user->rol?->puedeVerAuditoria() ?? false);
        Gate::define('usar-pos', fn (User $user): bool => $user->esSuperAdmin() || (in_array($user->rol, [Rol::Administrador, Rol::Cajero], true) && $user->sede?->es_activa === true));
        Gate::define('operar-sede', fn (User $user, Sede $sede): bool => $user->esSuperAdmin() || (in_array($user->rol, [Rol::Administrador, Rol::Cajero], true) && $user->sede_id === $sede->id && $sede->es_activa));
        Gate::define('gestionar-sedes', fn (User $user): bool => $user->esSuperAdmin());
        Gate::define('gestionar-usuario', fn (User $actor, User $objetivo): bool => ($actor->rol?->puedeGestionarUsuarios() ?? false)
            && $actor->id !== $objetivo->id
            && ($actor->esSuperAdmin() || (! $objetivo->esSuperAdmin() && $objetivo->sede_id !== null && $objetivo->sede_id === $actor->sede_id)));
        Gate::define('gestionar-usuarios', fn (User $user): bool => $user->rol?->puedeGestionarUsuarios() ?? false);
    }

    /**
     * Los registros de nómina de otra sede no se resuelven en las rutas: responden 404.
     */
    protected function limitarNominaALaSedeDelUsuario(): void
    {
        foreach ([
            'empleado' => Empleado::class,
            'novedad' => Novedad::class,
            'asignacion' => AsignacionConcepto::class,
            'periodo' => PeriodoNomina::class,
            'recibo' => ReciboNomina::class,
        ] as $parametro => $modelo) {
            Route::bind($parametro, function (string $valor) use ($modelo) {
                $usuario = Auth::user();

                // Quien no gestiona nómina recibe 403 de la ruta, no un 404 de este enlace.
                if ($usuario === null || ! $usuario->can('gestionar-nomina')) {
                    return $modelo::query()->findOrFail($valor);
                }

                return $modelo::query()->visiblesPara($usuario)->findOrFail($valor);
            });
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
