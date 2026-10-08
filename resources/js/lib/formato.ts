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
