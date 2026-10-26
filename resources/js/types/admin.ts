export type CambioAuditoria = {
    antes: string | number | boolean | null;
    despues: string | number | boolean | null;
};

export type Auditoria = {
    id: number;
    modulo: string;
    evento: 'creado' | 'modificado' | 'eliminado';
    etiqueta: string;
    descripcion: string;
    created_at: string;
    cambios: Record<string, CambioAuditoria>;
    usuario: { id: number; name: string; email: string } | null;
    sede: { id: number; nombre: string } | null;
};

export type PaginaSimple<T> = {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type FiltrosAuditoria = {
    modulo?: string;
    sede_id?: string;
    user_id?: string;
    etiqueta?: string;
    evento?: string;
    desde?: string;
    hasta?: string;
};
