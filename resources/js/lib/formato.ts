const formatoPesos = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 2,
});

/**
 * Formatea un valor monetario en pesos colombianos, por ejemplo `$ 1.750.905`.
 */
export function formatearPesos(valor: string | number): string {
    return formatoPesos.format(Number(valor));
}

const etiquetasFormaPago: Record<string, string> = {
    efectivo: 'Efectivo',
    datafono: 'Datáfono',
    qr: 'QR',
    mixto: 'Mixto',
};

/**
 * Nombre de una forma de pago para mostrar en pantalla, por ejemplo `Datáfono`.
 */
export function etiquetaFormaPago(forma: string): string {
    return etiquetasFormaPago[forma] ?? forma;
}

/**
 * Nombre legible de un campo guardado en la auditoría, por ejemplo `Valor curso`.
 */
export function etiquetaCampo(campo: string): string {
    const texto = campo.replace(/_id$/, '').replaceAll('_', ' ');

    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/**
 * Valor de un campo de la auditoría listo para mostrar: pesos, porcentajes y sí/no cuando aplica.
 */
export function formatearValorAuditoria(
    campo: string,
    valor: string | number | boolean | null,
): string {
    if (valor === null || valor === '') {
        return '—';
    }

    if (campo.startsWith('valor_') && !isNaN(Number(valor))) {
        return formatearPesos(Number(valor));
    }

    if (campo.startsWith('porcentaje_') && !isNaN(Number(valor))) {
        return `${Number(valor)} %`;
    }

    if (/^(es|tiene)_/.test(campo)) {
        return Number(valor) === 1 || valor === true ? 'Sí' : 'No';
    }

    return String(valor);
}
