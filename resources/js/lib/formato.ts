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

/**
 * Deja un valor digitado como número con punto decimal y a lo sumo dos decimales,
 * por ejemplo `1234.5`. Descarta todo lo que no sea dígito o separador decimal.
 */
export function normalizarDinero(
    valor: string | number | null | undefined,
): string {
    if (valor === null || valor === undefined || valor === '') {
        return '';
    }

    const texto = String(valor);
    const [entero, ...resto] = texto.replace(/[^\d.]/g, '').split('.');
    const decimales = resto.join('').slice(0, 2);
    const sinCeros = entero.replace(/^0+(?=\d)/, '');

    return resto.length > 0 || texto.endsWith('.')
        ? `${sinCeros}.${decimales}`
        : sinCeros;
}

/**
 * Pone el punto de miles a un valor ya normalizado: `1234567.5` se muestra `1.234.567,5`.
 */
export function formatearMiles(valor: string): string {
    if (valor === '') {
        return '';
    }

    const [entero, decimales] = valor.split('.');
    const conPuntos = entero.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return decimales === undefined ? conPuntos : `${conPuntos},${decimales}`;
}
