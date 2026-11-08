# Controles de CI

El workflow `.github/workflows/tests.yml` conserva `composer ci:check` y agrega
tres pasos de la DoD: título del PR, búsqueda de depuración y reconstrucción de
PostgreSQL con seeders. Se vuelve a ejecutar al editar el PR, incluido su título.
Commitlint usa `commitlint.config.js`; se valida el título porque será el mensaje
del squash. En los pushes a `main` se omite este paso porque no hay título de PR.

## Reproducir con Sail

Desde la raíz del repositorio y con las dependencias instaladas:

```bash
printf '%s\n' 'ci(ci): completar controles de la DoD' | ./vendor/bin/sail npx --no-install commitlint
bash scripts/check-debug.sh
./vendor/bin/sail composer ci:check
```

La búsqueda aplica la regla textual de la DoD en `app`, `resources/js` y `routes`,
y admite espacios antes del paréntesis. Sale con error si encuentra coincidencias
o no puede leer un directorio. Es una búsqueda textual: también detecta ejemplos
en comentarios o cadenas; no es un analizador sintáctico y no detecta todas las
variantes de JavaScript ni llamadas repartidas entre varias líneas.

## Migraciones y seeders

CI usa exclusivamente el servicio PostgreSQL desechable `testing`. La conexión
se fija explícitamente en `pgsql`, para evitar validar accidentalmente SQLite.
El paso se ejecuta antes de los controles y tests existentes.

Para reproducirlo, usa una base PostgreSQL local **desechable** y comprueba la
conexión del `.env`: este comando elimina todas las tablas de esa base.

```bash
./vendor/bin/sail artisan migrate:fresh --seed --force
```

El título del PR se pasa al shell mediante una variable de entorno, sin insertarlo
como código en el comando. No se agregan permisos de escritura al workflow.
