export type Sede = {
    id: number;
    nombre: string;
    ciudad: string;
    direccion: string;
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
