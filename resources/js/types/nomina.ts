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
    contrato_vigente?: { id: number; periodicidad_pago: string } | null;
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

export type ParametroNomina = {
    id: number;
    anio: number;
    valor_salario_minimo: string;
    valor_auxilio_transporte: string;
    valor_uvt: string;
    porcentaje_salud_empleado: string;
    porcentaje_pension_empleado: string;
    horas_mensuales: number;
    porcentaje_recargo_hora_extra_diurna: string;
    porcentaje_recargo_hora_extra_nocturna: string;
    porcentaje_recargo_nocturno: string;
    porcentaje_recargo_dominical_festivo: string;
    porcentaje_incapacidad: string;
};

export type ConceptoNomina = {
    id: number;
    codigo: string;
    nombre: string;
    tipo: 'devengo' | 'deduccion';
    forma_calculo: 'valor_fijo' | 'porcentaje' | 'sistema';
    valor_base: string | null;
    porcentaje_base: string | null;
    es_constitutivo_salario: boolean;
    es_sistema: boolean;
    es_activo: boolean;
};

export type AsignacionConcepto = {
    id: number;
    concepto_nomina_id: number;
    concepto: Pick<
        ConceptoNomina,
        | 'id'
        | 'codigo'
        | 'nombre'
        | 'tipo'
        | 'forma_calculo'
        | 'valor_base'
        | 'porcentaje_base'
    >;
    valor_asignado: string | null;
    fecha_inicio: string;
    fecha_fin: string | null;
};

export type OpcionTipoNovedad = Opcion & {
    unidad: 'horas' | 'minutos' | null;
    es_por_dias: boolean;
};

export type Novedad = {
    id: number;
    empleado_id: number;
    empleado?: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>;
    tipo: string;
    tipo_etiqueta?: string;
    fecha_inicio: string;
    fecha_fin: string | null;
    cantidad: number | null;
    concepto_nomina_id: number | null;
    concepto?: Pick<ConceptoNomina, 'id' | 'nombre'> | null;
    valor_eventual: string | null;
    observacion: string | null;
    esta_liquidada?: boolean;
};

export type PeriodoNomina = {
    id: number;
    periodicidad_pago: 'semanal' | 'quincenal' | 'mensual';
    fecha_inicio: string;
    fecha_fin: string;
    estado: 'borrador' | 'liquidado' | 'cerrado';
    fecha_liquidacion: string | null;
    fecha_cierre: string | null;
    liquidador?: { id: number; name: string } | null;
    recibos_count?: number;
    recibos_sum_valor_neto?: string | null;
};

export type ReciboNomina = {
    id: number;
    periodo_nomina_id: number;
    empleado_id: number;
    empleado?: Pick<
        Empleado,
        'id' | 'nombres' | 'apellidos' | 'numero_documento'
    >;
    valor_salario_base: string;
    dias_liquidados: number;
    valor_total_devengado: string;
    valor_total_deducciones: string;
    valor_neto: string;
    fecha_envio: string | null;
};

export type DetalleReciboNomina = {
    id: number;
    concepto_nomina_id: number;
    tipo: 'devengo' | 'deduccion';
    descripcion: string;
    cantidad: number | null;
    valor_concepto: string;
};
