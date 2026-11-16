# Saldos de vacaciones

La causación es de 15 días hábiles por año de servicio, con proporcionalidad.
La jornada es de lunes a sábado, excluyendo domingos y festivos nacionales
colombianos.

## Cálculo y decisiones técnicas

`CalcularSaldoVacaciones::ejecutar(Empleado, ?CarbonInterface)` consulta los contratos
y las novedades de vacaciones en cada llamada. No persiste saldos ni depende de
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
  duplicados.
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

El saldo depende del historial registrado: no incluye saldos iniciales ni
compensaciones anteriores y no certifica el saldo histórico completo. Sin
intervalos válidos devuelve cero días y fecha de ingreso nula. El calendario es
global, sin jornadas particulares por empleado o sede. La consulta del expediente conserva el corte a hoy. La validación de novedades
se describe a continuación.

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
