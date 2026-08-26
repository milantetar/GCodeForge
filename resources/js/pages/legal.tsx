import { Head } from '@inertiajs/react';

import { PublicPageRenderer } from '@/components/cms/public-page-renderer';
import type { Auth, CmsPage } from '@/types';

type LegalProps = {
    auth: Auth;
    document: 'terms' | 'privacy';
    cmsPage: CmsPage | null;
};

const titles = {
    terms: 'Terms of Service',
    privacy: 'Privacy Policy',
} as const;

export default function Legal({ auth, document, cmsPage }: LegalProps) {
    const title = titles[document];
    const page: CmsPage = cmsPage ?? {
        id: 0,
        title,
        slug: document,
        type: 'legal',
        status: 'published',
        excerpt: null,
        seo: { title, description: null },
        settings: {},
        published_at: null,
        created_at: '',
        updated_at: '',
        sections: [
            {
                key: 'legal-content',
                type: 'rich_text',
                name: title,
                content: {
                    eyebrow: 'Legal',
                    title,
                    intro: 'Content is being prepared. Please check back soon.',
                    blocks: [],
                },
                settings: {},
                sort_order: 0,
                is_visible: true,
            },
        ],
    };

    return (
        <>
            <Head title={page.seo.title ?? page.title} />
            <PublicPageRenderer page={page} auth={auth} />
        </>
    );
}
