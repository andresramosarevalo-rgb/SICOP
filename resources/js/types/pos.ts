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
    tipo_comparendo: string;
    valor_curso: string;
    porcentaje_descuento: string;
    sede?: Pick<Sede, 'id' | 'nombre' | 'ciudad'>;
};

export type Soat = {
    id: number;
    sede_id: number;
    fecha: string;
    tipo_documento: string;
    numero_documento: string;
    nombre_cliente: string;
    valor_soat: string;
    sede?: Pick<Sede, 'id' | 'nombre' | 'ciudad'>;
};
