<?php

namespace App\Enums;

/**
 * Conceptos de ley que calcula el sistema. Su valor es el código del concepto en `conceptos_nomina`.
 */
enum ConceptoSistema: string
{
    case Salario = 'SALARIO';
    case AuxilioTransporte = 'AUX_TRANSPORTE';
    case HoraExtraDiurna = 'HED';
    case HoraExtraNocturna = 'HEN';
    case RecargoNocturno = 'REC_NOCTURNO';
    case RecargoDominicalFestivo = 'REC_DOMINICAL';
    case Incapacidad = 'INCAPACIDAD';
    case Vacaciones = 'VACACIONES';
    case Salud = 'SALUD';
    case Pension = 'PENSION';
    case FondoSolidaridadPensional = 'FSP';
    case RetencionFuente = 'RETENCION';
    case DescuentoRetardo = 'RETARDO';

    /**
     * Nombre del concepto tal como aparece en el recibo de pago.
     */
    public function nombre(): string
    {
        return match ($this) {
            self::Salario => 'Salario',
            self::AuxilioTransporte => 'Auxilio de transporte',
            self::HoraExtraDiurna => 'Horas extra diurnas',
            self::HoraExtraNocturna => 'Horas extra nocturnas',
            self::RecargoNocturno => 'Recargo nocturno',
            self::RecargoDominicalFestivo => 'Recargo dominical o festivo',
            self::Incapacidad => 'Incapacidad',
            self::Vacaciones => 'Vacaciones',
            self::Salud => 'Aporte a salud',
            self::Pension => 'Aporte a pensión',
            self::FondoSolidaridadPensional => 'Fondo de Solidaridad Pensional',
            self::RetencionFuente => 'Retención en la fuente',
            self::DescuentoRetardo => 'Descuento por retardos',
        };
    }

    /**
     * Indica si el concepto suma (devengo) o resta (deducción) en el recibo.
     */
    public function tipo(): TipoConcepto
    {
        return match ($this) {
            self::Salud, self::Pension, self::FondoSolidaridadPensional,
            self::RetencionFuente, self::DescuentoRetardo => TipoConcepto::Deduccion,
            default => TipoConcepto::Devengo,
        };
    }

    /**
     * Indica si el concepto hace parte del salario para calcular aportes (el auxilio de transporte no).
     */
    public function esConstitutivoSalario(): bool
    {
        return $this->tipo() === TipoConcepto::Devengo && $this !== self::AuxilioTransporte;
    }
}
