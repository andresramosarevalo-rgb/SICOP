# Saldos de vacaciones

La causación es de 15 días hábiles por año de servicio, con proporcionalidad.
La jornada es de lunes a sábado, excluyendo domingos y festivos nacionales
colombianos.

## Cálculo y decisiones técnicas

`CalcularSaldoVacaciones::ejecutar(Empleado, ?CarbonInterface)` consulta los contratos,
la referencia histórica y las novedades de vacaciones en cada llamada. No persiste saldos calculados ni depende de
recibos o de `periodo_nomina_id`, por lo que las reliquidaciones no agregan consumo.
El corte predeterminado es la fecha en `America/Bogota`; puede recibirse una fecha
explícita. Los límites de inicio, terminación y corte son inclusivos.

- **Servicio:** se unen contratos contiguos o solapados y se recortan al corte y a
  su terminación. Los huecos y fechas futuras no causan. Se excluyen con advertencia
  los contratos no vigentes sin terminación y los rangos invertidos. El ingreso
  procede del primer intervalo válido, nunca de `created_at`.
- **Causación:** se adopta el aniversario laboral como base de proporcionalidad.
  Cada intervalo continuo causa 15 días por año completo, más
  `15 × días restantes de servicio / días entre el último aniversario y el siguiente`.
  Para incluir el último día se utiliza como fin exclusivo el día siguiente.
  Los aniversarios parten siempre del inicio del intervalo; un ingreso el 29 de
  febrero se ajusta al 28 en años no bisiestos.
- **Consumo:** las novedades se intersectan con el servicio válido y el corte.
  Una fecha final nula representa un día; un rango invertido se excluye con
  advertencia. Se unen los intervalos antes de contar días hábiles para evitar
  duplicados. Si hay referencia histórica aplicable, se suma su total disfrutado
  y solo se cuentan novedades desde el día siguiente a su corte.
- **Disponible:** causados menos utilizados, conservando resultados negativos.
  BCMath opera con 12 decimales; causados y disponibles se devuelven como cadenas
  con cuatro decimales, redondeadas al final.

La proporcionalidad por aniversarios es una decisión técnica, no una convención
adicional confirmada por el cliente. Este cálculo es independiente de los días
calendario de `Novedad::dias()` y de la base monetaria de `CalculadoraNomina`.

## Calendario

`CalendarioVacaciones` usa la configuración global de `config/vacaciones.php`:
`dias_habiles` contiene los días ISO 1 a 6 y `excluir_festivos_colombianos` está
activo. `festivos` permite excluir fechas adicionales en formato `YYYY-MM-DD`.
`es_calendario_confirmado` controla el aviso de calendario provisional; no cambia
el conteo.

`FestivosColombia` calcula por año las fechas fijas, los descansos trasladados al
lunes y los derivados de la Pascua gregoriana, deduplicando coincidencias. Aplica
la [Ley 51 de 1983](https://jurinfo.jep.gov.co/Normograma/Compilacion/docs/ley_0051_1983.htm)
e incluye el descanso trasladable del 9 de julio desde 2026, conforme a la
[Ley 2578 de 2026](https://www.alcaldiabogota.gov.co/sisjur/normas/Norma1.jsp?i=193309).
Los cambios normativos requieren actualizar estas reglas.

## Consulta, permisos y limitaciones

`EmpleadoController::show` entrega `saldoVacaciones` al expediente; Vue solo
formatea los valores. La ruta exige `auth`, `verified` y `gestionar-nomina`.
El binding de empleados aplica `visiblesPara`: Administrador y Auxiliar de Nómina
acceden a su sede; Superadmin tiene alcance global. Los empleados fuera del ámbito
autorizado no se resuelven y responden 404.

El saldo depende del historial registrado y de la referencia histórica informada;
no incluye compensaciones anteriores ni certifica el saldo histórico completo.
Sin intervalos válidos la causación es cero y la fecha de ingreso es nula; un
consumo histórico informado se conserva y puede producir un disponible negativo.
El calendario es global, sin jornadas particulares por empleado o sede. La consulta
del expediente conserva el corte a hoy.

## Referencia histórica de vacaciones

La referencia histórica registra el total de días hábiles de vacaciones
disfrutados por un empleado hasta una fecha de corte inclusiva, incluyendo los
días que también estén representados por novedades en SICOP. Esta información
permite incorporar consumos anteriores al uso del sistema sin duplicar
descuentos ni alterar la causación acumulada.

Se incorporan dos columnas opcionales a `empleados` mediante una migración
aditiva:

- `dias_vacaciones_historicos`: entero no negativo que representa los días
  hábiles disfrutados hasta el corte.
- `fecha_corte_vacaciones`: fecha inclusiva que delimita el consumo histórico.

Ambos campos deben tener valor o permanecer nulos. Un total de cero con fecha
representa una referencia explícita. PostgreSQL garantiza esta integridad y
el formulario valida que la fecha no sea posterior al día actual en
`America/Bogota`. La migración conserva los registros existentes.

Al registrar una referencia histórica por primera vez, el formulario propone
automáticamente la fecha actual como corte histórico, permitiendo modificarla
manualmente. Al corregir una referencia existente, se conserva la fecha
previamente registrada. El corte es inclusivo y determina hasta qué día están
comprendidas las vacaciones históricas informadas; las novedades posteriores
se descuentan adicionalmente, evitando contabilizar dos veces los días
ya incluidos.

El saldo se calcula sumando el consumo histórico y los días hábiles de las
novedades posteriores al corte, unificando los intervalos solapados. Una novedad
que atraviesa la fecha histórica solo aporta los días posteriores, limitados al
servicio válido y a la fecha de consulta. La causación mantiene todos los
periodos contractuales y su proporcionalidad por aniversarios; el corte
histórico no reinicia la antigüedad.

Por ejemplo, con 30 días causados y 12 disfrutados hasta el corte, el disponible
es de 18 días si no existen consumos posteriores. Las novedades incluidas
completamente en el histórico no generan descuentos adicionales. Para corregir
el total disfrutado debe actualizarse la referencia, no las novedades que ya
están comprendidas en ella.

En consultas anteriores al corte histórico, el total registrado no se aplica
ni se prorratea. Se utilizan las novedades disponibles para esa fecha y se
advierte que no es posible reconstruir completamente el consumo anterior.
Los empleados sin referencia conservan el comportamiento habitual del cálculo.

El expediente permite registrar y corregir ambos valores mediante una
operación restringida por `gestionar-nomina` y la visibilidad por sede. La
actualización utiliza una transacción con bloqueo de la fila del empleado y
reutiliza `Empleado::Auditable` para conservar valores anteriores y nuevos,
usuario, fecha y sede. Si falla la auditoría, se revierte la operación; guardar
valores idénticos no genera eventos adicionales.

Las correcciones sustituyen la referencia vigente y actualizan las consultas
posteriores sin eliminar novedades ni reservas. Pueden producir saldos
negativos, que permanecen visibles y sujetos a las validaciones existentes.
La referencia no modifica los recibos, importes, días calendario de
liquidación ni las restricciones de cierre y reapertura de periodos.

`SaldoInicialVacacionesTest` verifica el registro, las validaciones, el corte
inclusivo, los solapamientos, las correcciones, la auditoría, los permisos por
sede, la ausencia de historial y la compatibilidad con las novedades y los
cálculos monetarios.

## Validación en novedades

Al seleccionar Vacaciones, el formulario consulta el saldo del empleado y muestra
los días hábiles del período y el consumo adicional. El servidor recalcula y
valida el saldo al guardar, sin depender de la consulta previa del formulario.

Para validar la programación de vacaciones, se utiliza la causación acumulada hasta la fecha actual y se reserva el consumo de todas las vacaciones registradas, incluidas las futuras, sin anticipar causación. Este criterio corresponde a una decisión técnica pendiente de validación con el cliente.

Al editar se excluye la novedad original antes de evaluar su reemplazo. Solo se
descuentan días no cubiertos por otras vacaciones; editar o eliminar devuelve
únicamente los días que dejan de estar cubiertos.

La consulta y las operaciones respetan los permisos por sede del expediente y
los bloqueos de novedades liquidadas. Las altas y los aumentos de consumo exigen
saldo suficiente.
Una edición de vacaciones del mismo empleado puede conservar o reducir el
consumo total aunque exista saldo negativo previo. La comparación incluye la
novedad original y su reemplazo al mismo horizonte temporal, todas las reservas
futuras y la unión de solapamientos. Esta excepción no se aplica a altas, cambios
de empleado ni conversiones desde otro tipo de novedad.
Las escrituras concurrentes no pueden comprometer el mismo saldo disponible.

Esta validación de días hábiles es independiente de los días calendario y los
importes de liquidación de nómina.
