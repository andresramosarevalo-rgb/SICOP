export type Area = {
    id: number;
    nombre: string;
    es_activa: boolean;
};

export type Empleado = {
    id: number;
    tipo_documento: string;
    numero_documento: string;
    nombres: string;
    apellidos: string;
    email: string;
    telefono: string | null;
    direccion: string | null;
    fecha_nacimiento: string | null;
    area_id: number;
    area?: Pick<Area, 'id' | 'nombre'>;
    cargo: string;
    es_activo: boolean;
};

export type OpcionTipoDocumento = {
    valor: string;
    etiqueta: string;
};

export type Opcion = {
    valor: string;
    etiqueta: string;
};

export type Contrato = {
    id: number;
    empleado_id: number;
    tipo_contrato: string;
    periodicidad_pago: string;
    fecha_inicio: string;
    fecha_fin: string | null;
    valor_salario_base: string;
    es_vigente: boolean;
};
