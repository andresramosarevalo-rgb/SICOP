#!/usr/bin/env bash

# DoD 8: revisar los mismos directorios definidos en ESTANDARES.md.
# grep devuelve 1 cuando no encuentra coincidencias; otros errores deben fallar.
set -u

grep -rnE '\b(dd|dump|ray)[[:space:]]*\(|console[[:space:]]*\.[[:space:]]*log[[:space:]]*\(' app resources/js routes
status=$?

case "$status" in
    0)
        printf '%s\n' 'Se encontró código de depuración. Retíralo antes de integrar.' >&2
        exit 1
        ;;
    1)
        printf '%s\n' 'Sin coincidencias de código de depuración.'
        ;;
    *)
        printf '%s\n' 'No se pudieron revisar todos los directorios de código.' >&2
        exit "$status"
        ;;
esac
