import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    Eye,
    Globe2,
    GripVertical,
    History,
    Plus,
    Save,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import { AppShell } from '@/components/app-shell';
import { PageSettingsFields } from '@/components/cms/page-settings-fields';
import { SectionFields } from '@/components/cms/section-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    CmsPage,
    CmsPageStatus,
    CmsPageType,
    CmsSection,
    CmsSectionType,
} from '@/types';

type CmsEditProps = { cmsPage: CmsPage };
type JsonEditorProps = {
    value: string;
    onChange: (value: string) => void;
};

type CmsSectionForm = Omit<CmsSection, 'content' | 'settings'> & {
    content_json: string;
    settings_json: string;
};

type CmsEditForm = {
    lock_version: number;
    title: string;
    slug: string;
    type: CmsPageType;
    status: CmsPageStatus;
    excerpt: string;
    seo_title: string;
    seo_description: string;
    settings_json: string;
    change_note: string;
    sections: CmsSectionForm[];
};

const sectionTypes: CmsSectionType[] = [
    'hero',
    'feature_grid',
    'workflow',
    'security',
    'cta',
    'rich_text',
    'stats',
    'custom',
];

function JsonEditor({ value, onChange }: JsonEditorProps) {
    const [error, setError] = useState<string | null>(null);

    const commit = () => {
        try {
            const parsed: unknown = JSON.parse(value);

            if (
                typeof parsed !== 'object' ||
                parsed === null ||
                Array.isArray(parsed)
            ) {
                throw new Error('Content must be a JSON object.');
            }

            setError(null);
        } catch (exception) {
            setError(
                exception instanceof Error ? exception.message : 'Invalid JSON',
            );
        }
    };

    return (
        <div>
            <textarea
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onBlur={commit}
                spellCheck={false}
                className="min-h-64 w-full resize-y rounded-xl border border-white/10 bg-black/30 p-4 font-mono text-xs leading-6 text-zinc-300 transition outline-none focus:border-forge-400/50 focus:ring-4 focus:ring-forge-500/10"
            />
            {error && <p className="mt-2 text-xs text-red-400">{error}</p>}
        </div>
    );
}

export default function CmsEdit({ cmsPage }: CmsEditProps) {
    return (
        <CmsEditFormPage
            key={`${cmsPage.id}-${cmsPage.lock_version}`}
            cmsPage={cmsPage}
        />
    );
}

function CmsEditFormPage({ cmsPage }: CmsEditProps) {
    const form = useForm<CmsEditForm>({
        lock_version: cmsPage.lock_version ?? 0,
        title: cmsPage.title,
        slug: cmsPage.slug,
        type: cmsPage.type,
        status: cmsPage.status,
        excerpt: cmsPage.excerpt ?? '',
        seo_title: cmsPage.seo.title ?? '',
        seo_description: cmsPage.seo.description ?? '',
        settings_json: JSON.stringify(cmsPage.settings),
        change_note: '',
        sections: cmsPage.sections.map((section) => ({
            id: section.id,
            key: section.key,
            type: section.type,
            name: section.name,
            content_json: JSON.stringify(section.content, null, 2),
            settings_json: JSON.stringify(section.settings),
            sort_order: section.sort_order,
            is_visible: section.is_visible,
            updated_at: section.updated_at,
        })),
    });

    const updateSection = (index: number, patch: Partial<CmsSectionForm>) => {
        form.setData(
            'sections',
            form.data.sections.map((section, position) =>
                position === index ? { ...section, ...patch } : section,
            ),
        );
    };

    const moveSection = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= form.data.sections.length) {
            return;
        }

        const sections = [...form.data.sections];
        [sections[index], sections[target]] = [
            sections[target],
            sections[index],
        ];
        form.setData(
            'sections',
            sections.map((section, sortOrder) => ({
                ...section,
                sort_order: sortOrder,
            })),
        );
    };

    const addSection = () => {
        const number = form.data.sections.length + 1;
        form.setData('sections', [
            ...form.data.sections,
            {
                key: `section-${number}`,
                type: 'custom',
                name: `Section ${number}`,
                content_json: JSON.stringify(
                    {
                        eyebrow: 'New section',
                        title: 'Write a clear headline',
                        description: 'Add your content here.',
                    },
                    null,
                    2,
                ),
                settings_json: '{}',
                sort_order: form.data.sections.length,
                is_visible: true,
            },
        ]);
    };

    const save = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(`/admin/cms/pages/${cmsPage.id}`, { preserveScroll: true });
    };

    const publish = () =>
        router.post(
            `/admin/cms/pages/${cmsPage.id}/publish`,
            { lock_version: cmsPage.lock_version ?? 0 },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title={`Edit ${cmsPage.title}`} />
            <AppShell
                title="Page editor"
                actions={
                    <div className="flex gap-2">
                        <Button asChild variant="ghost" size="sm">
                            <a
                                href={`/admin/cms/pages/${cmsPage.id}/preview`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Eye className="size-4" /> Preview
                            </a>
                        </Button>
                        <Button
                            size="sm"
                            onClick={publish}
                            disabled={form.isDirty || form.processing}
                            title="Save your changes before publishing"
                        >
                            <Send className="size-4" /> Publish
                        </Button>
                    </div>
                }
            >
                <form onSubmit={save}>
                    <p className="mb-4 text-sm text-zinc-400">
                        Save creates a draft; Publish updates the live page.
                        Preview shows the last saved draft.
                    </p>
                    {Object.entries(form.errors).length > 0 && (
                        <div
                            role="alert"
                            className="mb-4 rounded-xl border border-red-400/30 p-4 text-sm text-red-300"
                        >
                            {Object.entries(form.errors).map(
                                ([key, message]) => (
                                    <p key={key}>
                                        {key}: {message}
                                    </p>
                                ),
                            )}
                        </div>
                    )}
                    <div className="mb-7 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <Link
                                href="/admin/cms/pages"
                                className="mb-3 flex items-center gap-2 text-xs text-zinc-600 transition hover:text-zinc-300"
                            >
                                <ArrowLeft className="size-3.5" /> Content
                                studio
                            </Link>
                            <div className="flex items-center gap-3">
                                <h2 className="text-3xl font-semibold tracking-[-0.035em] text-white">
                                    {cmsPage.title}
                                </h2>
                                <span
                                    className={`rounded-full px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase ${form.data.status === 'published' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300'}`}
                                >
                                    {form.data.status}
                                </span>
                            </div>
                            <p className="mt-2 text-sm text-zinc-600">
                                Last updated{' '}
                                {new Date(cmsPage.updated_at).toLocaleString()}
                            </p>
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            <Save className="size-4" />{' '}
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </Button>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-[1fr_21rem]">
                        <div className="space-y-5">
                            <Card>
                                <CardContent>
                                    <div className="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="page-title">
                                                Page title
                                            </Label>
                                            <Input
                                                id="page-title"
                                                className="mt-2"
                                                value={form.data.title}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'title',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="page-slug">
                                                URL slug
                                            </Label>
                                            <div className="relative mt-2">
                                                <Globe2 className="absolute top-3.5 left-3.5 size-4 text-zinc-700" />
                                                <Input
                                                    id="page-slug"
                                                    className="pl-10"
                                                    value={form.data.slug}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'slug',
                                                            event.target.value
                                                                .toLowerCase()
                                                                .replace(
                                                                    /[^a-z0-9-]/g,
                                                                    '-',
                                                                ),
                                                        )
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <Label>Page type</Label>
                                            <select
                                                className="mt-2 h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3.5 text-sm text-zinc-200"
                                                value={form.data.type}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'type',
                                                        event.target
                                                            .value as CmsPageType,
                                                    )
                                                }
                                            >
                                                <option value="landing">
                                                    Landing
                                                </option>
                                                <option value="standard">
                                                    Standard
                                                </option>
                                                <option value="legal">
                                                    Legal
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Status</Label>
                                            <select
                                                className="mt-2 h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3.5 text-sm text-zinc-200"
                                                value={form.data.status}
                                                disabled
                                                onChange={(event) =>
                                                    form.setData(
                                                        'status',
                                                        event.target
                                                            .value as CmsPageStatus,
                                                    )
                                                }
                                            >
                                                <option value="draft">
                                                    Draft
                                                </option>
                                                <option value="published">
                                                    Published
                                                </option>
                                                <option value="archived">
                                                    Archived
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="mt-5">
                                        <Label htmlFor="excerpt">Summary</Label>
                                        <textarea
                                            id="excerpt"
                                            className="mt-2 min-h-24 w-full rounded-xl border border-white/10 bg-black/25 p-3.5 text-sm text-zinc-200 outline-none"
                                            value={form.data.excerpt}
                                            onChange={(event) =>
                                                form.setData(
                                                    'excerpt',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="font-medium text-zinc-100">
                                        Page sections
                                    </h3>
                                    <p className="mt-1 text-xs text-zinc-600">
                                        Order controls how the public page is
                                        rendered.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    onClick={addSection}
                                >
                                    <Plus className="size-4" /> Add section
                                </Button>
                            </div>

                            {form.data.sections.map((section, index) => (
                                <Card key={`${section.key}-${index}`}>
                                    <CardContent>
                                        <div className="flex flex-wrap items-center gap-3 border-b border-white/[0.07] pb-5">
                                            <GripVertical className="size-4 text-zinc-700" />
                                            <Input
                                                className="h-9 min-w-40 flex-1"
                                                value={section.name}
                                                onChange={(event) =>
                                                    updateSection(index, {
                                                        name: event.target
                                                            .value,
                                                    })
                                                }
                                            />
                                            <select
                                                className="h-9 rounded-xl border border-white/10 bg-black/25 px-3 text-xs text-zinc-300"
                                                value={section.type}
                                                onChange={(event) =>
                                                    updateSection(index, {
                                                        type: event.target
                                                            .value as CmsSectionType,
                                                    })
                                                }
                                            >
                                                {sectionTypes.map((type) => (
                                                    <option
                                                        key={type}
                                                        value={type}
                                                    >
                                                        {type.replace('_', ' ')}
                                                    </option>
                                                ))}
                                            </select>
                                            <label className="flex items-center gap-2 text-xs text-zinc-500">
                                                <input
                                                    type="checkbox"
                                                    checked={section.is_visible}
                                                    onChange={(event) =>
                                                        updateSection(index, {
                                                            is_visible:
                                                                event.target
                                                                    .checked,
                                                        })
                                                    }
                                                />{' '}
                                                Visible
                                            </label>
                                            <div className="flex">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        moveSection(index, -1)
                                                    }
                                                    disabled={index === 0}
                                                >
                                                    <ArrowUp className="size-3.5" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        moveSection(index, 1)
                                                    }
                                                    disabled={
                                                        index ===
                                                        form.data.sections
                                                            .length -
                                                            1
                                                    }
                                                >
                                                    <ArrowDown className="size-3.5" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-red-400"
                                                    onClick={() =>
                                                        form.setData(
                                                            'sections',
                                                            form.data.sections.filter(
                                                                (_, position) =>
                                                                    position !==
                                                                    index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <Label>Section key</Label>
                                                <Input
                                                    className="mt-2"
                                                    value={section.key}
                                                    onChange={(event) =>
                                                        updateSection(index, {
                                                            key: event.target.value
                                                                .toLowerCase()
                                                                .replace(
                                                                    /[^a-z0-9-]/g,
                                                                    '-',
                                                                ),
                                                        })
                                                    }
                                                />
                                            </div>
                                            <div>
                                                <Label>Renderer</Label>
                                                <Input
                                                    className="mt-2"
                                                    value={section.type}
                                                    disabled
                                                />
                                            </div>
                                        </div>
                                        <div className="mt-5">
                                            <div className="mb-2 flex items-center justify-between">
                                                <Label>Content payload</Label>
                                                <span className="text-[10px] text-zinc-700">
                                                    Structured JSON · changes
                                                    validate on blur
                                                </span>
                                            </div>
                                            <SectionFields
                                                type={section.type}
                                                value={section.content_json}
                                                onChange={(content_json) =>
                                                    updateSection(index, {
                                                        content_json,
                                                    })
                                                }
                                            />
                                            <details className="mt-4">
                                                <summary className="cursor-pointer text-sm text-zinc-400">
                                                    Advanced JSON
                                                </summary>
                                                <JsonEditor
                                                    value={section.content_json}
                                                    onChange={(contentJson) =>
                                                        updateSection(index, {
                                                            content_json:
                                                                contentJson,
                                                        })
                                                    }
                                                />
                                            </details>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <aside className="space-y-5 xl:sticky xl:top-24 xl:h-fit">
                            <Card>
                                <CardContent>
                                    <h3 className="mb-5 font-medium text-zinc-100">
                                        Page appearance & navigation
                                    </h3>
                                    <PageSettingsFields
                                        value={form.data.settings_json}
                                        onChange={(value) =>
                                            form.setData('settings_json', value)
                                        }
                                    />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent>
                                    <h3 className="font-medium text-zinc-100">
                                        Search appearance
                                    </h3>
                                    <div className="mt-5 space-y-4">
                                        <div>
                                            <Label>SEO title</Label>
                                            <Input
                                                className="mt-2"
                                                value={form.data.seo_title}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'seo_title',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div>
                                            <Label>SEO description</Label>
                                            <textarea
                                                className="mt-2 min-h-28 w-full rounded-xl border border-white/10 bg-black/25 p-3.5 text-sm text-zinc-200 outline-none"
                                                value={
                                                    form.data.seo_description
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        'seo_description',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent>
                                    <h3 className="flex items-center gap-2 font-medium text-zinc-100">
                                        <History className="size-4 text-forge-300" />{' '}
                                        Revision note
                                    </h3>
                                    <textarea
                                        className="mt-4 min-h-24 w-full rounded-xl border border-white/10 bg-black/25 p-3 text-sm text-zinc-200 outline-none"
                                        value={form.data.change_note}
                                        onChange={(event) =>
                                            form.setData(
                                                'change_note',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="What changed in this version?"
                                    />
                                    <div className="mt-5 space-y-3 border-t border-white/[0.07] pt-5">
                                        {cmsPage.revisions
                                            ?.slice(0, 5)
                                            .map((revision) => (
                                                <div
                                                    key={revision.id}
                                                    className="flex gap-3"
                                                >
                                                    <span className="grid size-7 shrink-0 place-items-center rounded-lg bg-white/[0.04] text-[10px] text-zinc-500">
                                                        v{revision.version}
                                                    </span>
                                                    <div>
                                                        <p className="text-xs text-zinc-400">
                                                            {revision.change_note ??
                                                                'Saved revision'}
                                                        </p>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            disabled={
                                                                form.isDirty ||
                                                                form.processing
                                                            }
                                                            onClick={() =>
                                                                window.confirm(
                                                                    'Restore this revision as a draft?',
                                                                ) &&
                                                                router.post(
                                                                    `/admin/cms/pages/${cmsPage.id}/revisions/${revision.id}/restore`,
                                                                    {
                                                                        lock_version:
                                                                            cmsPage.lock_version ??
                                                                            0,
                                                                    },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Restore draft
                                                        </Button>
                                                        <p className="mt-1 text-[10px] text-zinc-700">
                                                            {new Date(
                                                                revision.created_at,
                                                            ).toLocaleString()}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                    </div>
                                </CardContent>
                            </Card>
                            <Button
                                type="button"
                                variant="danger"
                                className="w-full"
                                onClick={() =>
                                    window.confirm('Archive this page?') &&
                                    router.delete(
                                        `/admin/cms/pages/${cmsPage.id}`,
                                    )
                                }
                            >
                                <Trash2 className="size-4" /> Archive page
                            </Button>
                        </aside>
                    </div>
                </form>
            </AppShell>
        </>
    );
}
