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
