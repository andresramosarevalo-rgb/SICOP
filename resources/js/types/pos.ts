export type Sede = {
    id: number;
    nombre: string;
    ciudad: string;
    direccion: string;
    valor_base_caja: string;
    es_activa: boolean;
    usuario?: { id: number; email: string } | null;
};

export type Curso = {
    id: number;
    sede_id: number;
    fecha: string;
    tipo_documento: string;
    numero_documento: string;
    nombre_cliente: string;
    celular: string;
    correo: string;
    numero_certificado: string;
    numero_comparendo: string;
    secretaria_transito: string;
    hora_inicio: string;
    hora_fin: string;
    referido: string | null;
    codigo_infraccion: string;
    valor_curso: string;
    porcentaje_descuento: string;
    valor_pago_gestor?: string;
    forma_pago: string;
    valor_efectivo: string;
    valor_datafono: string;
    valor_qr: string;
    sede?: Pick<Sede, 'id' | 'nombre' | 'ciudad'>;
};

export type Soat = {
    id: number;
    sede_id: number;
    fecha: string;
    tipo_documento: string;
    numero_documento: string;
    nombre_cliente: string;
    celular: string;
    correo: string;
    placa: string;
    valor_soat: string;
    forma_pago: string;
    valor_efectivo: string;
    valor_qr: string;
    sede?: Pick<Sede, 'id' | 'nombre' | 'ciudad'>;
};

export type FilaTarifaGestor = {
    codigo: string;
    valor_base: string;
    ajuste: { id: number; valor_pago_gestor: string } | null;
};

export type ResumenCaja = {
    valor_base_caja: string;
    valor_efectivo_ventas: string;
    valor_ventas_datafono: string;
    valor_ventas_qr: string;
    valor_pago_gestores: string;
    cantidad_cursos: number;
    cantidad_soats: number;
};
