export type CmsPageStatus = 'draft' | 'published' | 'archived';
export type CmsPageType = 'landing' | 'legal' | 'standard';
export type CmsJsonValue =
    string | number | boolean | null | CmsJsonValue[] | CmsJsonObject;
export type CmsJsonObject = { [key: string]: CmsJsonValue };
export type CmsSectionType =
    | 'hero'
    | 'feature_grid'
    | 'workflow'
    | 'security'
    | 'cta'
    | 'rich_text'
    | 'stats'
    | 'custom';

export type CmsSection = {
    id?: number;
    key: string;
    type: CmsSectionType;
    name: string;
    content: CmsJsonObject;
    settings: CmsJsonObject;
    sort_order: number;
    is_visible: boolean;
    updated_at?: string;
};

export type CmsRevision = {
    id: number;
    version: number;
    change_note: string | null;
    created_at: string;
    created_by: string | null;
};

export type CmsPage = {
    id: number;
    title: string;
    slug: string;
    type: CmsPageType;
    status: CmsPageStatus;
    excerpt: string | null;
    seo: { title: string | null; description: string | null };
    settings: CmsJsonObject;
    published_at: string | null;
    sections: CmsSection[];
    revisions?: CmsRevision[];
    sections_count?: number;
    created_at: string;
    updated_at: string;
};

export type CmsPaginator = {
    data: CmsPage[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
