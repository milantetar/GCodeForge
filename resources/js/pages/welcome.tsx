import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Bot,
    Check,
    ChevronRight,
    Code2,
    Gauge,
    Layers3,
    Play,
    ShieldCheck,
    Sparkles,
    WandSparkles,
    Zap,
} from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';

import { ForgeLogo } from '@/components/forge-logo';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types';

type WelcomeProps = { auth: Auth };

const features = [
    {
        icon: Bot,
        title: 'AI toolpath copilot',
        description:
            'Turn intent into precise machine instructions with context-aware assistance.',
        gradient: 'from-violet-500/20 to-fuchsia-500/5',
    },
    {
        icon: Gauge,
        title: 'Instant validation',
        description:
            'Catch unsafe moves, collisions, and inefficient paths before they reach your machine.',
        gradient: 'from-blue-500/20 to-cyan-500/5',
    },
    {
        icon: ShieldCheck,
        title: 'Team-grade control',
        description:
            'Role-based access, secure workspaces, and an audit-ready activity trail.',
        gradient: 'from-emerald-500/15 to-teal-500/5',
    },
];

const codeLines = [
    ['N10', 'G21 G90 G94', 'Set metric · absolute'],
    ['N20', 'G00 X0 Y0 Z5', 'Safe rapid position'],
    ['N30', 'G01 Z-1.2 F180', 'Begin precision cut'],
    ['N40', 'G03 X42 Y18 I21 J0', 'Arc interpolation'],
    ['N50', 'G00 Z8', 'Retract to safety'],
];

export default function Welcome({ auth }: WelcomeProps) {
    const reduceMotion = useReducedMotion();
    const authenticated = Boolean(auth?.user);

    return (
        <>
            <Head title="Intelligent G-code workspace" />
            <main className="relative min-h-screen overflow-hidden bg-[#07070a]">
                <div className="forge-grid pointer-events-none absolute inset-x-0 top-0 h-[54rem]" />
                <div className="pointer-events-none absolute top-[-15rem] left-1/2 h-[30rem] w-[50rem] -translate-x-1/2 rounded-full bg-forge-600/[0.12] blur-[120px]" />
                <div className="pointer-events-none absolute top-[26rem] right-[-12rem] size-[34rem] rounded-full bg-blue-700/[0.08] blur-[130px]" />

                <header className="relative z-20 mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
                    <ForgeLogo />
                    <nav className="hidden items-center gap-8 text-sm text-zinc-500 md:flex">
                        <a
                            href="#features"
                            className="transition hover:text-white"
                        >
                            Features
                        </a>
                        <a
                            href="#workflow"
                            className="transition hover:text-white"
                        >
                            Workflow
                        </a>
                        <a
                            href="#security"
                            className="transition hover:text-white"
                        >
                            Security
                        </a>
                    </nav>
                    <div className="flex items-center gap-2">
                        {authenticated ? (
                            <Button asChild size="sm">
                                <Link href="/dashboard">
                                    Open studio{' '}
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="hidden sm:inline-flex"
                                >
                                    <Link href="/login">Sign in</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href="/register">Start forging</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </header>

                <section className="relative z-10 mx-auto max-w-7xl px-5 pt-20 pb-24 text-center sm:px-8 sm:pt-28 lg:pt-36">
                    <motion.div
                        initial={reduceMotion ? false : { opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                        className="mx-auto mb-7 inline-flex items-center gap-2 rounded-full border border-forge-400/15 bg-forge-500/[0.07] px-3 py-1.5 text-xs font-medium text-forge-300 shadow-[inset_0_1px_0_rgba(255,255,255,.06)]"
                    >
                        <Sparkles className="size-3.5" />
                        The intelligent workspace for modern manufacturing
                        <ChevronRight className="size-3.5 opacity-50" />
                    </motion.div>

                    <motion.h1
                        initial={reduceMotion ? false : { opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: 0.65,
                            delay: 0.05,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                        className="mx-auto max-w-5xl text-5xl leading-[0.98] font-semibold tracking-[-0.055em] text-white sm:text-6xl lg:text-[5.4rem]"
                    >
                        Forge better G-code.
                        <span className="block bg-gradient-to-r from-forge-300 via-violet-400 to-blue-400 bg-clip-text text-transparent">
                            Ship with confidence.
                        </span>
                    </motion.h1>

                    <motion.p
                        initial={reduceMotion ? false : { opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.14 }}
                        className="mx-auto mt-7 max-w-2xl text-base leading-7 text-zinc-500 sm:text-lg"
                    >
                        Build, inspect, and optimize machine instructions with
                        AI-assisted precision, real-time validation, and a
                        workflow your entire team can trust.
                    </motion.p>

                    <motion.div
                        initial={reduceMotion ? false : { opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.22 }}
                        className="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row"
                    >
                        <Button asChild size="lg" className="w-full sm:w-auto">
                            <Link
                                href={
                                    authenticated ? '/dashboard' : '/register'
                                }
                            >
                                {authenticated
                                    ? 'Enter the studio'
                                    : 'Create free workspace'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="secondary"
                            size="lg"
                            className="w-full sm:w-auto"
                        >
                            <a href="#workflow">
                                <Play className="size-4 fill-current" /> See how
                                it works
                            </a>
                        </Button>
                    </motion.div>

                    <motion.div
                        initial={
                            reduceMotion
                                ? false
                                : { opacity: 0, y: 30, scale: 0.98 }
                        }
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        transition={{
                            duration: 0.8,
                            delay: 0.3,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                        className="forge-card forge-glow relative mx-auto mt-20 max-w-5xl overflow-hidden rounded-2xl border border-white/[0.09] p-2 text-left"
                    >
                        <div className="flex h-11 items-center justify-between border-b border-white/[0.07] px-3 sm:px-4">
                            <div className="flex items-center gap-2">
                                <span className="size-2.5 rounded-full bg-red-400/60" />
                                <span className="size-2.5 rounded-full bg-amber-400/60" />
                                <span className="size-2.5 rounded-full bg-emerald-400/60" />
                            </div>
                            <span className="rounded-md border border-white/[0.06] bg-black/20 px-2 py-1 font-mono text-[10px] text-zinc-600">
                                turbine-bracket.nc
                            </span>
                            <span className="flex items-center gap-1.5 text-[10px] text-emerald-400/70">
                                <span className="size-1.5 rounded-full bg-emerald-400" />{' '}
                                Validated
                            </span>
                        </div>

                        <div className="grid min-h-[24rem] lg:grid-cols-[1.25fr_.75fr]">
                            <div className="border-b border-white/[0.07] bg-[#09090d] p-5 font-mono text-xs sm:p-7 lg:border-r lg:border-b-0">
                                <div className="mb-6 flex items-center justify-between text-zinc-700">
                                    <span>PROGRAM / MAIN</span>
                                    <span>5 OPERATIONS</span>
                                </div>
                                <div className="space-y-1">
                                    {codeLines.map(
                                        ([number, code, comment], index) => (
                                            <motion.div
                                                key={number}
                                                initial={
                                                    reduceMotion
                                                        ? false
                                                        : { opacity: 0, x: -8 }
                                                }
                                                whileInView={{
                                                    opacity: 1,
                                                    x: 0,
                                                }}
                                                viewport={{ once: true }}
                                                transition={{
                                                    delay: index * 0.06,
                                                }}
                                                className="grid grid-cols-[3rem_1fr] rounded-lg px-2 py-2.5 transition hover:bg-white/[0.035] sm:grid-cols-[3rem_1fr_1fr]"
                                            >
                                                <span className="text-zinc-700">
                                                    {number}
                                                </span>
                                                <span className="text-forge-300">
                                                    {code}
                                                </span>
                                                <span className="hidden text-zinc-700 sm:block">
                                                    ; {comment}
                                                </span>
                                            </motion.div>
                                        ),
                                    )}
                                </div>
                            </div>

                            <div className="relative flex min-h-72 items-center justify-center overflow-hidden bg-gradient-to-br from-[#12121a] to-[#09090d]">
                                <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(139,92,246,.13),transparent_58%)]" />
                                <div className="relative size-48 rotate-[-8deg] animate-float rounded-[2rem] border border-forge-400/20 bg-gradient-to-br from-forge-500/15 to-blue-500/[0.04] shadow-[0_30px_80px_rgba(109,40,217,.16),inset_0_1px_0_rgba(255,255,255,.12)]">
                                    <div className="absolute inset-7 rounded-2xl border border-dashed border-forge-300/25" />
                                    <div className="absolute top-1/2 left-1/2 size-16 -translate-1/2 rounded-full border border-forge-300/30 bg-black/30 shadow-[inset_0_0_24px_rgba(139,92,246,.18)]" />
                                    <div className="absolute top-5 right-5 size-4 rounded-full border border-blue-300/30 bg-blue-400/10" />
                                    <div className="absolute bottom-5 left-5 size-4 rounded-full border border-blue-300/30 bg-blue-400/10" />
                                </div>
                                <div className="absolute right-4 bottom-4 rounded-lg border border-white/[0.07] bg-black/40 px-3 py-2 text-[10px] text-zinc-500 backdrop-blur">
                                    <span className="mr-2 text-forge-300">
                                        42.6s
                                    </span>{' '}
                                    estimated cycle
                                </div>
                            </div>
                        </div>
                    </motion.div>

                    <div className="mt-7 flex flex-wrap items-center justify-center gap-x-7 gap-y-2 text-xs text-zinc-700">
                        {[
                            'No credit card',
                            'Private by default',
                            'Cancel anytime',
                        ].map((item) => (
                            <span
                                key={item}
                                className="flex items-center gap-1.5"
                            >
                                <Check className="size-3.5 text-forge-500" />{' '}
                                {item}
                            </span>
                        ))}
                    </div>
                </section>

                <section
                    id="features"
                    className="relative z-10 border-y border-white/[0.06] bg-white/[0.012] py-24"
                >
                    <div className="mx-auto max-w-7xl px-5 sm:px-8">
                        <div className="max-w-2xl">
                            <p className="text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                                Everything in one studio
                            </p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-[-0.035em] text-white sm:text-4xl">
                                A calmer way to build complex things.
                            </h2>
                            <p className="mt-4 leading-7 text-zinc-500">
                                Powerful enough for serious engineering, focused
                                enough to stay out of your way.
                            </p>
                        </div>
                        <div className="mt-12 grid gap-4 md:grid-cols-3">
                            {features.map(
                                ({
                                    icon: Icon,
                                    title,
                                    description,
                                    gradient,
                                }) => (
                                    <motion.article
                                        key={title}
                                        initial={
                                            reduceMotion
                                                ? false
                                                : { opacity: 0, y: 16 }
                                        }
                                        whileInView={{ opacity: 1, y: 0 }}
                                        viewport={{
                                            once: true,
                                            margin: '-80px',
                                        }}
                                        className="group relative overflow-hidden rounded-2xl border border-white/[0.07] bg-[#0d0d12] p-6 transition duration-300 hover:-translate-y-1 hover:border-white/[0.13]"
                                    >
                                        <div
                                            className={`absolute inset-0 bg-gradient-to-br ${gradient} opacity-0 transition duration-500 group-hover:opacity-100`}
                                        />
                                        <div className="relative">
                                            <span className="grid size-10 place-items-center rounded-xl border border-white/[0.08] bg-white/[0.04]">
                                                <Icon className="size-5 text-forge-300" />
                                            </span>
                                            <h3 className="mt-8 text-base font-semibold text-zinc-100">
                                                {title}
                                            </h3>
                                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                                {description}
                                            </p>
                                        </div>
                                    </motion.article>
                                ),
                            )}
                        </div>
                    </div>
                </section>

                <section
                    id="workflow"
                    className="relative z-10 mx-auto grid max-w-7xl gap-12 px-5 py-28 sm:px-8 lg:grid-cols-2 lg:items-center"
                >
                    <div>
                        <p className="text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                            Built for flow
                        </p>
                        <h2 className="mt-3 max-w-lg text-3xl font-semibold tracking-[-0.035em] text-white sm:text-4xl">
                            Move from first draft to machine-ready without the
                            friction.
                        </h2>
                        <p className="mt-5 max-w-lg leading-7 text-zinc-500">
                            The Forge keeps analysis, collaboration, revisions,
                            and output in one traceable workflow.
                        </p>
                    </div>
                    <div className="space-y-3">
                        {[
                            [
                                WandSparkles,
                                'Describe',
                                'Set material, machine, and production intent.',
                            ],
                            [
                                Layers3,
                                'Refine',
                                'Inspect toolpaths and tune every critical parameter.',
                            ],
                            [
                                Zap,
                                'Validate & ship',
                                'Run safety checks and export with confidence.',
                            ],
                        ].map(([Icon, title, description], index) => (
                            <div
                                key={String(title)}
                                className="flex gap-4 rounded-2xl border border-white/[0.07] bg-white/[0.025] p-5"
                            >
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-forge-500/10 text-forge-300">
                                    <Icon className="size-5" />
                                </span>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs text-zinc-700">
                                            0{index + 1}
                                        </span>
                                        <h3 className="font-medium text-zinc-200">
                                            {String(title)}
                                        </h3>
                                    </div>
                                    <p className="mt-1 text-sm text-zinc-600">
                                        {String(description)}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                <section
                    id="security"
                    className="relative z-10 px-5 pb-28 sm:px-8"
                >
                    <div className="mx-auto max-w-7xl overflow-hidden rounded-3xl border border-forge-400/15 bg-gradient-to-br from-forge-600/15 via-[#111117] to-blue-600/10 px-6 py-14 text-center shadow-[inset_0_1px_0_rgba(255,255,255,.08)] sm:px-12">
                        <Code2 className="mx-auto size-7 text-forge-300" />
                        <h2 className="mt-5 text-3xl font-semibold tracking-[-0.035em] text-white sm:text-4xl">
                            Ready to fire up the Forge?
                        </h2>
                        <p className="mx-auto mt-4 max-w-xl leading-7 text-zinc-500">
                            Create your secure workspace and turn the next
                            complex toolpath into a clear, confident process.
                        </p>
                        <Button asChild size="lg" className="mt-8">
                            <Link
                                href={
                                    authenticated ? '/dashboard' : '/register'
                                }
                            >
                                {authenticated
                                    ? 'Open your workspace'
                                    : 'Start building for free'}{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>

                <footer className="relative z-10 border-t border-white/[0.06]">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-5 py-8 text-xs text-zinc-700 sm:flex-row sm:px-8">
                        <ForgeLogo />
                        <span>
                            © {new Date().getFullYear()} GCode Forge Studio.
                            Built for precise work.
                        </span>
                    </div>
                </footer>
            </main>
        </>
    );
}
