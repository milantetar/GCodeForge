export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_url?: string | null;
    preferred_theme?: 'light' | 'dark' | 'system';
    status?: 'active' | 'suspended' | 'disabled';
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User | null;
    roles: string[];
    permissions: string[];
    twoFactorEnabled: boolean;
    twoFactorConfirmed: boolean;
};
