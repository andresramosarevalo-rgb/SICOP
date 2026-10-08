<?php

namespace App\Enums;

enum TipoNovedad: string
{
    case Falta = 'falta';
    case Retardo = 'retardo';
    case HoraExtraDiurna = 'hora_extra_diurna';
    case HoraExtraNocturna = 'hora_extra_nocturna';
    case RecargoNocturno = 'recargo_nocturno';
    case DominicalFestivo = 'dominical_festivo';
    case Incapacidad = 'incapacidad';
    case Vacaciones = 'vacaciones';
    case ConceptoEventual = 'concepto_eventual';

    /**
     * Nombre del tipo de novedad para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Falta => 'Falta',
            self::Retardo => 'Retardo',
            self::HoraExtraDiurna => 'Horas extra diurnas',
            self::HoraExtraNocturna => 'Horas extra nocturnas',
            self::RecargoNocturno => 'Recargo nocturno',
            self::DominicalFestivo => 'Trabajo dominical o festivo',
            self::Incapacidad => 'Incapacidad',
            self::Vacaciones => 'Vacaciones',
            self::ConceptoEventual => 'Concepto eventual',
        };
    }

    /**
     * Unidad de `cantidad` para el tipo: horas, minutos o ninguna (se mide por fechas o por valor).
     */
    public function unidad(): ?string
    {
        return match ($this) {
            self::HoraExtraDiurna, self::HoraExtraNocturna,
            self::RecargoNocturno, self::DominicalFestivo => 'horas',
            self::Retardo => 'minutos',
            default => null,
        };
    }

    /**
     * Indica si la novedad abarca un rango de días (de `fecha_inicio` a `fecha_fin`).
     */
    public function esPorDias(): bool
    {
        return in_array($this, [self::Falta, self::Incapacidad, self::Vacaciones], true);
    }
}
