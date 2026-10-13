export type MachineProfile = {
    id: number;
    name: string;
    description: string | null;
    units: 'mm' | 'inch';
    is_default: boolean;
    settings: Record<string, string | number>;
};
export type Project = {
    id: number;
    name: string;
    description: string | null;
    status: 'active' | 'archived';
    machine_profile?: MachineProfile | null;
    conversions_count?: number;
    last_activity_at: string | null;
};
export type ConverterType = 'pattern-repeater' | 'hexagonal-flat' | 'two-axis';
export type Conversion = {
    id: string;
    type: ConverterType;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    options: Record<string, string | number>;
    warnings: string[];
    error_message: string | null;
    metrics: {
        input_lines: number;
        output_lines: number | null;
        duration_ms: number | null;
    };
    project?: Project;
    download_url: string | null;
    created_at: string;
};
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
export type Paginated<T> = { data: T[]; links: PaginationLink[] };
