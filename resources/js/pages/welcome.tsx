import { Head } from '@inertiajs/react';

import { PublicPageRenderer } from '@/components/cms/public-page-renderer';
import type { Auth, CmsPage } from '@/types';

type WelcomeProps = { auth: Auth; cmsPage: CmsPage | null };

const fallbackPage: CmsPage = {
    id: 0,
    title: 'GCode Forge Studio',
    slug: 'home',
    type: 'landing',
    status: 'published',
    excerpt: null,
    seo: { title: 'Intelligent G-code workspace', description: null },
    settings: {},
    published_at: null,
    created_at: '',
    updated_at: '',
    sections: [
        {
            key: 'hero',
            type: 'hero',
            name: 'Hero',
            content: {
                eyebrow: 'The intelligent workspace for modern manufacturing',
                title: 'Forge better G-code.',
                accent: 'Ship with confidence.',
                description:
                    'Build, inspect, and optimize machine instructions with AI-assisted precision and real-time validation.',
                primary_label: 'Create free workspace',
                secondary_label: 'See how it works',
            },
            settings: {},
            sort_order: 0,
            is_visible: true,
        },
    ],
};

export default function Welcome({ auth, cmsPage }: WelcomeProps) {
    const page = cmsPage ?? fallbackPage;

    return (
        <>
            <Head title={page.seo.title ?? page.title}>
                {page.seo.description && (
                    <meta name="description" content={page.seo.description} />
                )}
            </Head>
            <PublicPageRenderer page={page} auth={auth} />
        </>
    );
}
