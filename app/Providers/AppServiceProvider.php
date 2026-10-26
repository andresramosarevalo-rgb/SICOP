<?php

namespace App\Providers;

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    }

    /**
     * Define los permisos de acceso a los módulos según el rol del usuario.
     */
    protected function definirPermisos(): void
    {
        Gate::define('gestionar-nomina', fn (User $user): bool => $user->rol?->puedeGestionarNomina() ?? false);
        Gate::define('gestionar-pos', fn (User $user): bool => $user->rol?->puedeGestionarPos() ?? false);
        Gate::define('ver-auditoria', fn (User $user): bool => $user->rol?->puedeVerAuditoria() ?? false);
        Gate::define('usar-pos', fn (User $user): bool => $user->rol?->puedeGestionarPos() || ($user->rol === Rol::Cajero && $user->sede?->es_activa === true));
        Gate::define('operar-sede', fn (User $user, Sede $sede): bool => $user->rol?->puedeGestionarPos() || ($user->rol === Rol::Cajero && $user->sede_id === $sede->id && $sede->es_activa));
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
