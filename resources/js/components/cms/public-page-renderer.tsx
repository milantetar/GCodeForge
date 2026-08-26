import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Bot,
    CheckCircle2,
    Gauge,
    Layers3,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';

import { ForgeLogo } from '@/components/forge-logo';
import { Button } from '@/components/ui/button';
import type { Auth, CmsJsonObject, CmsPage, CmsSection } from '@/types';

type PublicPageRendererProps = { page: CmsPage; auth: Auth };
type ContentItem = CmsJsonObject;

const icons = {
    bot: Bot,
    gauge: Gauge,
    shield: ShieldCheck,
    layers: Layers3,
    sparkles: Sparkles,
};

function text(content: ContentItem, key: string, fallback = ''): string {
    const value = content[key];

    return typeof value === 'string' ? value : fallback;
}

function items(content: ContentItem, key: string): ContentItem[] {
    const value = content[key];

    return Array.isArray(value)
        ? value.filter(
              (item): item is ContentItem =>
                  typeof item === 'object' && item !== null,
          )
        : [];
}

function SectionHeading({
    content,
    centered = false,
}: {
    content: ContentItem;
    centered?: boolean;
}) {
    return (
        <div
            className={centered ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl'}
        >
            <p className="text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                {text(content, 'eyebrow')}
            </p>
            <h2 className="mt-3 text-3xl font-semibold tracking-[-0.035em] text-white sm:text-4xl">
                {text(content, 'title')}
            </h2>
            <p className="mt-4 leading-7 text-zinc-500">
                {text(content, 'description', text(content, 'intro'))}
            </p>
        </div>
    );
}

function HeroSection({ section, auth }: { section: CmsSection; auth: Auth }) {
    const content = section.content;
    const authenticated = Boolean(auth.user);

    return (
        <section className="relative mx-auto max-w-7xl px-5 pt-24 pb-28 text-center sm:px-8 lg:pt-36">
            <motion.div
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                className="mx-auto inline-flex items-center gap-2 rounded-full border border-forge-400/15 bg-forge-500/[0.08] px-3 py-1.5 text-xs font-medium text-forge-300"
            >
                <Sparkles className="size-3.5" /> {text(content, 'eyebrow')}
            </motion.div>
            <motion.h1
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.08 }}
                className="mx-auto mt-7 max-w-5xl text-5xl leading-[0.98] font-semibold tracking-[-0.055em] text-white sm:text-6xl lg:text-[5.4rem]"
            >
                {text(content, 'title')}
                <span className="block bg-gradient-to-r from-forge-300 via-violet-400 to-blue-400 bg-clip-text text-transparent">
                    {text(content, 'accent')}
                </span>
            </motion.h1>
            <p className="mx-auto mt-7 max-w-2xl text-base leading-7 text-zinc-500 sm:text-lg">
                {text(content, 'description')}
            </p>
            <div className="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Button asChild size="lg" className="w-full sm:w-auto">
                    <Link href={authenticated ? '/dashboard' : '/register'}>
                        {authenticated
                            ? 'Open studio'
                            : text(
                                  content,
                                  'primary_label',
                                  'Start forging',
                              )}{' '}
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
                <Button asChild variant="secondary" size="lg">
                    <a href="#workflow">
                        {text(content, 'secondary_label', 'See how it works')}
                    </a>
                </Button>
            </div>
            <div className="forge-card forge-glow relative mx-auto mt-20 max-w-5xl overflow-hidden rounded-2xl border border-white/[0.09] p-2 text-left">
                <div className="flex h-11 items-center justify-between border-b border-white/[0.07] px-4 text-[10px] text-zinc-600">
                    <div className="flex gap-2">
                        <span className="size-2.5 rounded-full bg-red-400/60" />
                        <span className="size-2.5 rounded-full bg-amber-400/60" />
                        <span className="size-2.5 rounded-full bg-emerald-400/60" />
                    </div>
                    turbine-bracket.nc{' '}
                    <span className="text-emerald-400">● Validated</span>
                </div>
                <div className="grid min-h-80 lg:grid-cols-[1.2fr_.8fr]">
                    <div className="space-y-2 bg-[#09090d] p-7 font-mono text-xs">
                        {[
                            ['N10', 'G21 G90 G94', 'Set metric · absolute'],
                            ['N20', 'G00 X0 Y0 Z5', 'Safe rapid position'],
                            ['N30', 'G01 Z-1.2 F180', 'Begin precision cut'],
                            ['N40', 'G03 X42 Y18 I21 J0', 'Arc interpolation'],
                            ['N50', 'G00 Z8', 'Retract to safety'],
                        ].map(([number, code, note]) => (
                            <div
                                key={number}
                                className="grid grid-cols-[3rem_1fr] rounded-lg px-2 py-2.5 hover:bg-white/[0.035] sm:grid-cols-[3rem_1fr_1fr]"
                            >
                                <span className="text-zinc-700">{number}</span>
                                <span className="text-forge-300">{code}</span>
                                <span className="hidden text-zinc-700 sm:block">
                                    ; {note}
                                </span>
                            </div>
                        ))}
                    </div>
                    <div className="relative grid min-h-72 place-items-center overflow-hidden border-l border-white/[0.07] bg-gradient-to-br from-[#15151d] to-[#09090d]">
                        <div className="absolute size-72 rounded-full bg-forge-600/10 blur-3xl" />
                        <div className="relative size-44 rotate-[-8deg] rounded-[2rem] border border-forge-400/25 bg-forge-500/10 shadow-[0_30px_80px_rgba(109,40,217,.2)]">
                            <div className="absolute inset-7 rounded-2xl border border-dashed border-forge-300/25" />
                            <div className="absolute top-1/2 left-1/2 size-14 -translate-1/2 rounded-full border border-forge-300/30 bg-black/30" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function FeatureGridSection({ section }: { section: CmsSection }) {
    const content = section.content;

    return (
        <section
            id={section.key}
            className="border-y border-white/[0.06] bg-white/[0.012] py-24"
        >
            <div className="mx-auto max-w-7xl px-5 sm:px-8">
                <SectionHeading content={content} />
                <div className="mt-12 grid gap-4 md:grid-cols-3">
                    {items(content, 'items').map((item, index) => {
                        const iconName = text(
                            item,
                            'icon',
                            'sparkles',
                        ) as keyof typeof icons;
                        const Icon = icons[iconName] ?? Sparkles;

                        return (
                            <motion.article
                                key={`${text(item, 'title')}-${index}`}
                                initial={{ opacity: 0, y: 16 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                className="group rounded-2xl border border-white/[0.07] bg-[#0d0d12] p-6 transition hover:-translate-y-1 hover:border-forge-400/25"
                            >
                                <span className="grid size-10 place-items-center rounded-xl border border-white/[0.08] bg-white/[0.04]">
                                    <Icon className="size-5 text-forge-300" />
                                </span>
                                <h3 className="mt-8 font-semibold text-zinc-100">
                                    {text(item, 'title')}
                                </h3>
                                <p className="mt-2 text-sm leading-6 text-zinc-600">
                                    {text(item, 'description')}
                                </p>
                            </motion.article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function WorkflowSection({ section }: { section: CmsSection }) {
    const content = section.content;

    return (
        <section
            id={section.key}
            className="mx-auto grid max-w-7xl gap-12 px-5 py-28 sm:px-8 lg:grid-cols-2 lg:items-center"
        >
            <SectionHeading content={content} />
            <div className="space-y-3">
                {items(content, 'items').map((item, index) => (
                    <div
                        key={`${text(item, 'title')}-${index}`}
                        className="flex gap-4 rounded-2xl border border-white/[0.07] bg-white/[0.025] p-5"
                    >
                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-forge-500/10 text-sm font-semibold text-forge-300">
                            0{index + 1}
                        </span>
                        <div>
                            <h3 className="font-medium text-zinc-100">
                                {text(item, 'title')}
                            </h3>
                            <p className="mt-1 text-sm leading-6 text-zinc-600">
                                {text(item, 'description')}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}

function SecuritySection({ section }: { section: CmsSection }) {
    const content = section.content;
    const badges = Array.isArray(content.badges)
        ? content.badges.filter(
              (badge): badge is string => typeof badge === 'string',
          )
        : [];

    return (
        <section
            id={section.key}
            className="mx-auto max-w-7xl px-5 pb-28 sm:px-8"
        >
            <div className="forge-card relative overflow-hidden rounded-3xl border border-white/[0.08] p-8 sm:p-12">
                <div className="absolute top-0 right-0 size-72 bg-forge-600/10 blur-[90px]" />
                <div className="relative max-w-3xl">
                    <SectionHeading content={content} />
                    <div className="mt-8 flex flex-wrap gap-3">
                        {badges.map((badge) => (
                            <span
                                key={badge}
                                className="flex items-center gap-2 rounded-full border border-emerald-400/15 bg-emerald-400/[0.06] px-3 py-1.5 text-xs text-emerald-300"
                            >
                                <CheckCircle2 className="size-3.5" /> {badge}
                            </span>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

function CtaSection({ section, auth }: { section: CmsSection; auth: Auth }) {
    const content = section.content;

    return (
        <section id={section.key} className="px-5 pb-28 sm:px-8">
            <div className="mx-auto max-w-5xl rounded-3xl border border-forge-400/15 bg-gradient-to-br from-forge-600/15 via-[#111118] to-blue-600/10 px-6 py-16 text-center shadow-[0_30px_100px_rgba(109,40,217,.16)] sm:px-12">
                <SectionHeading content={content} centered />
                <Button asChild size="lg" className="mt-8">
                    <Link href={auth.user ? '/dashboard' : '/register'}>
                        {text(content, 'button_label', 'Start forging')}{' '}
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </div>
        </section>
    );
}

function RichTextSection({ section }: { section: CmsSection }) {
    const content = section.content;

    return (
        <section
            id={section.key}
            className="mx-auto max-w-4xl px-5 py-20 sm:px-8"
        >
            <SectionHeading content={content} />
            <div className="mt-10 space-y-8">
                {items(content, 'blocks').map((block, index) => (
                    <article key={`${text(block, 'title')}-${index}`}>
                        <h3 className="font-medium text-zinc-200">
                            {text(block, 'title')}
                        </h3>
                        <p className="mt-2 text-sm leading-7 text-zinc-500">
                            {text(block, 'body')}
                        </p>
                    </article>
                ))}
            </div>
        </section>
    );
}

export function PublicPageRenderer({ page, auth }: PublicPageRendererProps) {
    const reduceMotion = useReducedMotion();
    const sections = (Array.isArray(page.sections) ? page.sections : []).filter((section) => section.is_visible);

    return (
        <main className="relative min-h-screen overflow-hidden bg-[#07070a]">
            <div className="forge-grid pointer-events-none absolute inset-x-0 top-0 h-[54rem]" />
            <div className="pointer-events-none absolute top-[-15rem] left-1/2 h-[30rem] w-[50rem] -translate-x-1/2 rounded-full bg-forge-600/[0.12] blur-[120px]" />
            <header className="relative z-20 mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
                <ForgeLogo />
                <nav className="hidden items-center gap-7 text-sm text-zinc-500 md:flex">
                    {sections.slice(1, 4).map((section) => (
                        <a
                            key={section.key}
                            href={`#${section.key}`}
                            className="transition hover:text-white"
                        >
                            {section.name}
                        </a>
                    ))}
                </nav>
                <div className="flex items-center gap-2">
                    {!auth.user && (
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/login">Sign in</Link>
                        </Button>
                    )}
                    <Button asChild size="sm">
                        <Link href={auth.user ? '/dashboard' : '/register'}>
                            {auth.user ? 'Open studio' : 'Start forging'}
                        </Link>
                    </Button>
                </div>
            </header>
            <motion.div
                initial={reduceMotion ? false : { opacity: 0 }}
                animate={{ opacity: 1 }}
            >
                {sections.map((section) => {
                    if (section.type === 'hero') {
                        return (
                            <HeroSection
                                key={section.key}
                                section={section}
                                auth={auth}
                            />
                        );
                    }

                    if (section.type === 'feature_grid') {
                        return (
                            <FeatureGridSection
                                key={section.key}
                                section={section}
                            />
                        );
                    }

                    if (section.type === 'workflow') {
                        return (
                            <WorkflowSection
                                key={section.key}
                                section={section}
                            />
                        );
                    }

                    if (section.type === 'security') {
                        return (
                            <SecuritySection
                                key={section.key}
                                section={section}
                            />
                        );
                    }

                    if (section.type === 'cta') {
                        return (
                            <CtaSection
                                key={section.key}
                                section={section}
                                auth={auth}
                            />
                        );
                    }

                    if (section.type === 'rich_text') {
                        return (
                            <RichTextSection
                                key={section.key}
                                section={section}
                            />
                        );
                    }

                    return null;
                })}
            </motion.div>
            <footer className="border-t border-white/[0.06] px-5 py-10 text-sm text-zinc-700 sm:px-8">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 sm:flex-row">
                    <ForgeLogo />
                    <p>© {new Date().getFullYear()} GCode Forge Studio</p>
                    <div className="flex gap-5">
                        <Link href="/terms">Terms</Link>
                        <Link href="/privacy">Privacy</Link>
                    </div>
                </div>
            </footer>
        </main>
    );
}
