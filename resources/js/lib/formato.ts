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
