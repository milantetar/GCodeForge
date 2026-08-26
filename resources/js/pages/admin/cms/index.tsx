import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, FilePlus2, FileText, Globe2, Layers3 } from 'lucide-react';

import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CmsPageType, CmsPaginator } from '@/types';

type CmsIndexProps = { pages: CmsPaginator };

export default function CmsIndex({ pages }: CmsIndexProps) {
    const form = useForm({
        title: '',
        slug: '',
        type: 'standard' as CmsPageType,
        status: 'draft' as const,
        excerpt: '',
        seo_title: '',
        seo_description: '',
        settings: {},
        change_note: 'Initial page',
        sections: [],
    });

    const createPage = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/admin/cms/pages');
    };

    return (
        <>
            <Head title="Content studio" />
            <AppShell title="Content studio">
                <section className="grid gap-5 xl:grid-cols-[1fr_22rem]">
                    <div>
                        <div className="mb-6 flex items-end justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                                    CMS
                                </p>
                                <h2 className="mt-2 text-3xl font-semibold tracking-[-0.035em] text-white">
                                    Pages under your control.
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
                                    Edit content, reorder sections, manage
                                    search metadata, and publish without
                                    touching React.
                                </p>
                            </div>
                            <span className="hidden rounded-full border border-white/[0.08] bg-white/[0.03] px-3 py-1.5 text-xs text-zinc-500 sm:block">
                                {pages.total} pages
                            </span>
                        </div>

                        <div className="space-y-3">
                            {pages.data.map((page) => (
                                <Link
                                    key={page.id}
                                    href={`/admin/cms/pages/${page.id}/edit`}
                                    className="group block rounded-2xl border border-white/[0.07] bg-[#0d0d12] p-5 transition hover:-translate-y-0.5 hover:border-forge-400/20 hover:bg-white/[0.035]"
                                >
                                    <div className="flex items-center gap-4">
                                        <span className="grid size-11 shrink-0 place-items-center rounded-xl border border-white/[0.08] bg-white/[0.04]">
                                            <FileText className="size-5 text-forge-300" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="truncate font-medium text-zinc-100">
                                                    {page.title}
                                                </h3>
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-[10px] font-medium uppercase ${page.status === 'published' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300'}`}
                                                >
                                                    {page.status}
                                                </span>
                                            </div>
                                            <div className="mt-1 flex items-center gap-4 text-xs text-zinc-600">
                                                <span>/{page.slug}</span>
                                                <span className="flex items-center gap-1">
                                                    <Layers3 className="size-3" />{' '}
                                                    {page.sections_count ?? 0}{' '}
                                                    sections
                                                </span>
                                            </div>
                                        </div>
                                        <ArrowRight className="size-4 text-zinc-700 transition group-hover:translate-x-1 group-hover:text-forge-300" />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>

                    <Card className="h-fit xl:sticky xl:top-24">
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <span className="grid size-9 place-items-center rounded-xl bg-forge-500/10">
                                    <FilePlus2 className="size-4 text-forge-300" />
                                </span>
                                <div>
                                    <h3 className="font-medium text-zinc-100">
                                        New page
                                    </h3>
                                    <p className="text-xs text-zinc-600">
                                        Start as a private draft
                                    </p>
                                </div>
                            </div>
                            <form
                                onSubmit={createPage}
                                className="mt-6 space-y-4"
                            >
                                <div>
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        className="mt-2"
                                        value={form.data.title}
                                        onChange={(event) =>
                                            form.setData(
                                                'title',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="About the Forge"
                                    />
                                    {form.errors.title && (
                                        <p className="mt-1 text-xs text-red-400">
                                            {form.errors.title}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="slug">URL slug</Label>
                                    <div className="relative mt-2">
                                        <Globe2 className="absolute top-3.5 left-3.5 size-4 text-zinc-700" />
                                        <Input
                                            id="slug"
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
                                            placeholder="about"
                                        />
                                    </div>
                                    {form.errors.slug && (
                                        <p className="mt-1 text-xs text-red-400">
                                            {form.errors.slug}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="type">Page type</Label>
                                    <select
                                        id="type"
                                        className="mt-2 h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3.5 text-sm text-zinc-200 outline-none"
                                        value={form.data.type}
                                        onChange={(event) =>
                                            form.setData(
                                                'type',
                                                event.target
                                                    .value as CmsPageType,
                                            )
                                        }
                                    >
                                        <option value="standard">
                                            Standard
                                        </option>
                                        <option value="landing">
                                            Landing page
                                        </option>
                                        <option value="legal">
                                            Legal page
                                        </option>
                                    </select>
                                </div>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={form.processing}
                                >
                                    Create draft{' '}
                                    <ArrowRight className="size-4" />
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </section>
            </AppShell>
        </>
    );
}
