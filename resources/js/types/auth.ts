export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Permisos = {
    gestionarNomina: boolean;
};

export type Auth = {
    user: User;
    permisos: Permisos;
};
