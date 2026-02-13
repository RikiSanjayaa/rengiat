export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    role: 'super_admin' | 'admin' | 'operator' | 'viewer';
    subdit_id: number | null;
    subdit_name: string | null;
    unit_id: number | null;
    unit_name: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    abilities: {
        manage_users: boolean;
        manage_units: boolean;
        export_rengiat: boolean;
    };
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
