# Estándares del equipo — SICOP

**Proyecto:** SICOP — Sistema Integral COVIACOL de Operaciones (POS, contabilidad y nómina)
**Versión:** 1.0 — 30 de septiembre de 2026
**Stack:** PHP · Laravel · Inertia.js · Vue 3 · PostgreSQL · Docker (Laravel Sail)

---

## 1. Guía de estilo y nombres

### 1.1 Guías oficiales adoptadas

| Capa                             | Guía oficial                                                      | Herramienta que la aplica                                                                       | Archivo de configuración         |
| -------------------------------- | ----------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------- |
| PHP (Laravel)                    | PSR-12 + convenciones de Laravel                                  | Laravel Pint, preset `laravel`                                                                  | `pint.json`                      |
| Vue 3 / TypeScript               | Guía de estilo oficial de Vue (Composition API, `<script setup>`) | Oxlint vía Vite+ (`vp lint`), con reglas sensibles a tipos y advertencias tratadas como errores | `vite.config.ts` (bloque `lint`) |
| Formato de `.vue`, `.ts` y `.js` | —                                                                 | Oxfmt vía Vite+ (`vp fmt`), compatible con Prettier; ordena las clases de Tailwind              | `vite.config.ts` (bloque `fmt`)  |
| Mensajes de commit               | Conventional Commits (sección 2)                                  | commitlint                                                                                      | `commitlint.config.js`           |
| Base de datos (PostgreSQL)       | Convenciones de nombres de Eloquent                               | Migraciones de Laravel                                                                          | `database/migrations/`           |

Los componentes Vue se escriben con Composition API y `<script setup>`.

### 1.2 Idioma del código

- **Identificadores de dominio en español:** modelos, tablas, columnas, variables, métodos propios, componentes y rutas (`Factura`, `liquidarNomina()`, `valor_total`, `TablaEmpleados.vue`). Motivo: los conceptos del negocio vienen de la normativa colombiana (PUC, retención en la fuente, cesantías, prima de servicios) y no tienen traducción exacta.
- **Lo que impone el framework se deja en inglés:** métodos de controlador de recurso (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`), `up()`/`down()` de migraciones, `created_at`, `updated_at`, `deleted_at`.
- **Comentarios, mensajes de commit, títulos de PR y documentación:** en español.

### 1.3 Formateadores configurados

`pint.json`

```json
{
    "preset": "laravel"
}
```

Bloque `fmt` de `vite.config.ts` (lo trae el starter kit de Laravel; no se usan ESLint ni Prettier para que no choquen con Vite+):

```ts
fmt: {
    printWidth: 80,
    tabWidth: 4,
    singleQuote: true,
    semi: true,
}
```

Scripts en `package.json`:

```json
"scripts": {
    "lint": "vp lint resources/js",
    "format": "vp fmt resources/js",
    "format:check": "vp fmt --check resources/js"
}
```

Comandos de verificación:

```bash
sail pint --test            # PHP
sail npm run lint           # Vue/JS (reglas)
sail npm run format:check   # Vue/JS (formato)
```

### 1.4 Convenciones generales de nombres

| Elemento                | Convención                                                                                                                                                                       | Ejemplo                                    |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| Modelo Eloquent         | PascalCase, singular                                                                                                                                                             | `Factura`, `Empleado`, `CuentaContable`    |
| Controlador             | PascalCase + `Controller`                                                                                                                                                        | `FacturaController`                        |
| Form Request            | PascalCase, acción + modelo + `Request`                                                                                                                                          | `StoreFacturaRequest`                      |
| Métodos y variables PHP | camelCase                                                                                                                                                                        | `calcularRetencion()`, `$totalDevengado`   |
| Constantes              | UPPER_SNAKE_CASE                                                                                                                                                                 | `SALARIO_MINIMO_REFERENCIA`                |
| Tablas                  | snake_case, plural                                                                                                                                                               | `facturas`, `cuentas_contables`            |
| Tabla pivote            | snake_case, singulares en orden alfabético                                                                                                                                       | `empleado_novedad`                         |
| Columnas                | snake_case                                                                                                                                                                       | `fecha_emision`                            |
| Llave foránea           | `<tabla_en_singular>_id`                                                                                                                                                         | `empleado_id`                              |
| Nombre de ruta          | `modulo.recurso.accion`                                                                                                                                                          | `nomina.liquidaciones.store`               |
| Página Inertia          | `resources/js/pages/<Modulo>/<Recurso>/<Accion>.vue`; carpeta `pages` en minúscula (la configura el starter kit en `config/inertia.php`), módulo, recurso y acción en PascalCase | `pages/Nomina/Liquidaciones/Index.vue`     |
| Componente Vue          | PascalCase, mínimo dos palabras                                                                                                                                                  | `TablaFacturas.vue`, `ModalCierreCaja.vue` |
| Props y eventos Vue     | camelCase en `<script>`, kebab-case en `<template>`                                                                                                                              | `valorTotal` / `:valor-total`              |

Módulos válidos para `<Modulo>`: `Pos`, `Contabilidad`, `Nomina`, `Admin`. Se exceptúan las páginas generadas por el starter kit (`auth/`, `settings/`, `Dashboard.vue` y `Welcome.vue`).

### 1.5 Reglas propias de nombres

**R1. Booleanos con prefijo `es_` o `tiene_`.** Toda columna booleana se llama `es_<estado>` o `tiene_<atributo>` (`es_activo`, `tiene_retencion`, `es_contrato_indefinido`). Las variables PHP y JS equivalentes usan `esX` o `tieneX` en camelCase.
_Verificación:_ el siguiente comando no devuelve resultados:
​`bash
grep -rn "boolean('" database/migrations | grep -vE "boolean\('(es|tiene)_"
​`

**R2. Todo modelo declara su tabla.** Como el pluralizador de Laravel es inglés (`Rol` → `rols`), cada modelo en `app/Models` declara `protected $table = '<plural_en_español>';`.
_Verificación:_ el siguiente comando no devuelve resultados:

```bash
grep -L 'protected \$table' app/Models/*.php
```

**R3. Dinero con prefijo `valor_` y tipo decimal.** Toda columna monetaria se llama `valor_<concepto>` (`valor_total`, `valor_iva`, `valor_salario_base`) y se define como `$table->decimal('valor_...', 15, 2)`. Los porcentajes usan el prefijo `porcentaje_`. Nunca `float` ni `double`.
_Verificación:_ ambos comandos no devuelven resultados:

```bash
grep -rnE "->(float|double)\(" database/migrations
grep -rn "decimal('" database/migrations | grep -vE "'(valor|porcentaje)_"
```

---

## 2. Convención de commits y ramas

### 2.1 Formato del mensaje

Se usa **Conventional Commits** con **alcance obligatorio en camelCase**:

```
<tipo>(<alcance>)[!]: <descripción>

[cuerpo opcional]

[pie opcional: Closes #<número de issue>]
```

Reglas:

- `tipo` en minúscula, de la lista de 2.2.
- `alcance` obligatorio, en camelCase, de la lista de 2.3.
- `descripción` en español, en imperativo, sin punto final, empieza en minúscula.
- Encabezado completo de máximo 72 caracteres.
- `!` después del alcance indica un cambio incompatible (p. ej., una migración que elimina columnas).

Ejemplos:

```
feat(cierreCaja): agregar arqueo por medio de pago
fix(liquidacionNomina): corregir cálculo de horas extra nocturnas
refactor(planCuentas): extraer validación de código PUC a Form Request
feat(facturacion)!: reemplazar columna total por valor_total
docs(estandaresEquipo): agregar documento de estándares del equipo
```

### 2.2 Tipos permitidos

| Tipo       | Uso                                                      |
| ---------- | -------------------------------------------------------- |
| `feat`     | Funcionalidad nueva para el usuario                      |
| `fix`      | Corrección de un error                                   |
| `refactor` | Cambio de código que no altera comportamiento            |
| `perf`     | Mejora de rendimiento (p. ej., índices en PostgreSQL)    |
| `test`     | Agregar o corregir tests                                 |
| `docs`     | Documentación                                            |
| `style`    | Formato sin cambio de lógica (salida de Pint o Prettier) |
| `build`    | Dependencias, Composer, npm, Vite, Docker/Sail           |
| `ci`       | Flujos de integración continua                           |
| `chore`    | Tareas de mantenimiento que no entran en otro tipo       |

### 2.3 Alcances permitidos (camelCase)

`pos`, `cierreCaja`, `facturacion`, `contabilidad`, `planCuentas`, `comprobantes`, `nomina`, `liquidacionNomina`, `empleados`, `auth`, `usuarios`, `baseDatos`, `docker`, `dependencias`, `ci`, `estandaresEquipo`.

Agregar un alcance nuevo requiere un commit `docs(estandaresEquipo)` que modifique esta lista y la de `commitlint.config.js`.

### 2.4 Esquema de ramas

| Rama                                          | Propósito                                                       | Quién integra                             |
| --------------------------------------------- | --------------------------------------------------------------- | ----------------------------------------- |
| `main`                                        | Versión entregada a COVIACOL. Protegida: sin push directo.      | Solo desde `develop` o `hotfix/*`, por PR |
| `develop`                                     | Integración del trabajo terminado. Protegida: sin push directo. | Solo por PR                               |
| `<tipo>/<numeroIssue>-<descripcionCamelCase>` | Trabajo de una historia o tarea                                 | Se borra al integrarse                    |

- `<tipo>` es uno de: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `hotfix`.
- La descripción usa camelCase, igual que el alcance: `feat/12-cierreCaja`, `fix/27-horasExtraNocturnas`, `hotfix/31-totalFactura`.
- Las ramas `hotfix/*` salen de `main` y se integran a `main` y a `develop`.
- Los PR se integran con **squash merge**; el título del PR se convierte en el mensaje del commit, por lo que debe cumplir la convención de 2.1.

---

## 3. Definition of Ready (DoR)

Una historia puede entrar al sprint solo si su issue cumple todas estas condiciones:

1. **Formato de historia:** el issue está redactado como "Como `<rol>` quiero `<acción>` para `<beneficio>`", con un rol real del sistema (cajero, contador, auxiliar de nómina, administrador).
2. **Criterios de aceptación:** tiene al menos dos criterios en formato _Dado / Cuando / Entonces_.
3. **Módulo y alcance:** tiene una etiqueta de módulo (`pos`, `contabilidad` o `nomina`) y el alcance de commit que usará, tomado de la lista de 2.3.
4. **Estimación:** tiene una estimación en puntos acordada por el equipo, de máximo 8 puntos; si supera 8, se divide.
5. **Reglas de negocio confirmadas:** si depende de un dato de COVIACOL (tarifa, cuenta del PUC, concepto de nómina, medio de pago), el dato está escrito en el issue junto con la confirmación de Salomón González (comentario, correo adjunto o acta enlazada).
6. **Dependencias explícitas:** los issues de los que depende están enlazados y cerrados, o marcados como bloqueantes en el tablero.

---

## 4. Definition of Done (DoD)

Una historia está terminada solo si se cumplen todas estas condiciones. Cada una se comprueba abriendo el repositorio o ejecutando el comando indicado:

1. **Integrada en `develop`** mediante un PR aprobado por al menos un integrante distinto del autor (visible en la pestaña del PR).
2. **PHP formateado:** `sail pint --test` termina sin errores sobre `develop`.
3. **Vue/JS sin errores:** `sail npm run lint` y `sail npm run format:check` terminan sin errores.
4. **Tests pasan y cubren la historia:** `sail artisan test` termina sin fallos, y cada criterio de aceptación tiene al menos un test en `tests/Feature/` cuyo nombre o comentario cita el número del issue (p. ej. `// Issue #12, criterio 2`).
5. **Base de datos reproducible:** `sail artisan migrate:fresh --seed` corre sin errores sobre PostgreSQL.
6. **Commits válidos:** el título del PR (mensaje del squash) cumple la convención; `sail npx commitlint --from origin/develop~1 --to origin/develop` no reporta errores.
7. **Trazabilidad:** el PR incluye `Closes #<número>` y el issue aparece cerrado y enlazado al PR.
8. **Sin código de depuración:** el siguiente comando no devuelve resultados:
    ```bash
    grep -rnE "\b(dd|dump|ray)\(|console\.log\(" app resources/js routes
    ```

---

## 5. Política de revisión

El equipo tiene dos integrantes, así que cada PR lo revisa siempre el otro. El objetivo es que ningún cambio llegue a `develop` sin que lo haya leído alguien distinto de quien lo escribió.

### 5.1 Quién revisa

- Todo PR hacia `develop` o `main` lo revisa y aprueba el integrante que no es el autor.
- Nadie integra su propio PR sin la aprobación del otro.
- El PR de `develop` hacia `main` se revisa entre los dos en una sesión sincrónica (clase, reunión o llamada), y lo aprueba en GitHub quien no lo abrió.
- Un PR cambia como máximo 400 líneas, según el contador del PR en GitHub. No cuentan `composer.lock`, `package-lock.json` ni los archivos generados por `artisan` o el starter kit. Si una historia necesita más, se divide en varios PR.

### 5.2 Plazo

- El revisor responde en un máximo de **48 horas** desde que se solicita la revisión.
- Si faltan 72 horas o menos para una entrega del curso, el plazo baja a **12 horas**.
- Si vence el plazo, el autor avisa por el chat del equipo y la revisión se hace entre los dos en la siguiente sesión compartida. El PR no se integra mientras no tenga aprobación.
- Después de recibir cambios solicitados, el autor responde dentro del mismo plazo.

### 5.3 Qué bloquea (causales de "Request changes")

Solo estas causales justifican bloquear un PR. Todo comentario bloqueante cita el código de la causal.

| Código | Causal                                                                                                                                         |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| B1     | Falla cualquier comando de la DoD (Pint, ESLint, Prettier, tests, `migrate:fresh --seed`, commitlint, búsqueda de código de depuración).       |
| B2     | Un criterio de aceptación del issue no tiene test que lo cubra.                                                                                |
| B3     | Incumple una regla de nombres R1, R2 o R3, o las convenciones de la sección 1.4.                                                               |
| B4     | Hay credenciales, contraseñas o llaves en el código, o se versiona el archivo `.env`.                                                          |
| B5     | Hay una consulta SQL que concatena datos del usuario en lugar de usar Eloquent, el Query Builder o bindings (`DB::select('... ?', [$valor])`). |
| B6     | Se modifica una migración que ya está en `develop` en lugar de crear una migración nueva.                                                      |
| B7     | Hay una ruta nueva sin middleware `auth`, salvo las de autenticación.                                                                          |
| B8     | El PR supera las 400 líneas cambiadas definidas en 5.1.                                                                                        |

### 5.4 Qué no bloquea

Estos puntos se comentan, pero el PR puede aprobarse aunque el autor no los atienda:

- Nombres mejorables que ya cumplen las reglas.
- Refactorizaciones sugeridas que no corrigen un error.
- Preferencias de estilo que el formateador no cubre.
- Optimizaciones de rendimiento sin una medición que muestre el problema.
- Errores de redacción en comentarios o documentación.
- Ideas fuera del alcance del issue. Se registran como un issue nuevo en lugar de pedirse en el PR.

### 5.5 Cómo se comenta

- Cada comentario empieza con una etiqueta:
    - `[bloqueante B<n>]` cita la causal, y el revisor marca "Request changes".
    - `[sugerencia]` no bloquea.
    - `[pregunta]` no bloquea, pero el autor debe responderla.
    - `[nit]` señala un detalle menor y no bloquea.
- El comentario se deja sobre la línea concreta del diff. Dice qué falla y, cuando aplica, propone el cambio con la función _suggestion_ de GitHub.
- El autor responde cada comentario, con el commit que lo resuelve o con su argumento. Solo quien abrió un comentario bloqueante lo marca como resuelto.

---

## 6. Aceptación

| Integrante                    | Declaración                        |
| ----------------------------- | ---------------------------------- |
| Andrés Jerónimo Ramos Arévalo | Conozco y acepto estos estándares. |
| Juan Andrés González Díaz     | Conozco y acepto estos estándares. |
