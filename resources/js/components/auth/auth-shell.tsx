import { Link } from '@inertiajs/react';
import { CheckCircle2, Code2, ShieldCheck, Sparkles } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import type { PropsWithChildren, ReactNode } from 'react';

import { ForgeLogo } from '@/components/forge-logo';
import { Card } from '@/components/ui/card';

type AuthShellProps = PropsWithChildren<{
    title: string;
    description: string;
    eyebrow?: string;
    footer?: ReactNode;
}>;

const benefits = [
    { icon: Code2, text: 'Generate production-ready G-code' },
    { icon: ShieldCheck, text: 'Secure workspace and access controls' },
    { icon: Sparkles, text: 'Repeatable G-code transformations' },
];

export function AuthShell({
    title,
    description,
    eyebrow = 'Forge your next idea',
    footer,
    children,
}: AuthShellProps) {
    const reduceMotion = useReducedMotion();

    return (
        <main className="relative min-h-screen overflow-hidden bg-[#07070a]">
            <div className="forge-grid pointer-events-none absolute inset-0" />
            <div className="pointer-events-none absolute -top-40 left-[12%] size-[30rem] rounded-full bg-forge-600/10 blur-[110px]" />
            <div className="pointer-events-none absolute right-[-10rem] bottom-[-12rem] size-[35rem] rounded-full bg-blue-600/10 blur-[130px]" />

            <div className="relative mx-auto grid min-h-screen w-full max-w-[1440px] lg:grid-cols-[1.08fr_.92fr]">
                <section className="hidden min-h-screen flex-col justify-between border-r border-white/[0.06] p-12 lg:flex xl:p-16">
                    <ForgeLogo />

                    <motion.div
                        initial={reduceMotion ? false : { opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: 0.65,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                        className="max-w-xl"
                    >
                        <div className="mb-7 inline-flex items-center gap-2 rounded-full border border-forge-400/15 bg-forge-500/[0.07] px-3 py-1.5 text-xs font-medium text-forge-300">
                            <span className="size-1.5 rounded-full bg-forge-400 shadow-[0_0_12px_#a78bfa]" />
                            The intelligent manufacturing workspace
                        </div>
                        <h2 className="max-w-lg text-4xl leading-[1.08] font-semibold tracking-[-0.035em] text-white xl:text-5xl">
                            From raw geometry to{' '}
                            <span className="bg-gradient-to-r from-forge-300 via-violet-400 to-blue-400 bg-clip-text text-transparent">
                                flawless motion.
                            </span>
                        </h2>
                        <p className="mt-5 max-w-lg text-base leading-7 text-zinc-500">
                            Design, validate, and optimize machine instructions
                            in one focused, collaborative studio.
                        </p>

                        <div className="mt-10 grid gap-4">
                            {benefits.map(({ icon: Icon, text }, index) => (
                                <motion.div
                                    key={text}
                                    initial={
                                        reduceMotion
                                            ? false
                                            : { opacity: 0, x: -12 }
                                    }
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ delay: 0.24 + index * 0.08 }}
                                    className="flex items-center gap-3 text-sm text-zinc-400"
                                >
                                    <span className="grid size-8 place-items-center rounded-lg border border-white/[0.07] bg-white/[0.035]">
                                        <Icon className="size-4 text-forge-300" />
                                    </span>
                                    {text}
                                </motion.div>
                            ))}
                        </div>
                    </motion.div>

                    <div className="flex items-center gap-2 text-xs text-zinc-700">
                        <CheckCircle2 className="size-3.5" />
                        Built for makers, engineers, and manufacturing teams
                    </div>
                </section>

                <section className="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
                    <div className="w-full max-w-md">
                        <div className="mb-8 flex items-center justify-between lg:hidden">
                            <ForgeLogo />
                            <Link
                                href="/"
                                className="text-xs text-zinc-500 hover:text-white"
                            >
                                Back home
                            </Link>
                        </div>

                        <motion.div
                            initial={
                                reduceMotion
                                    ? false
                                    : { opacity: 0, y: 18, scale: 0.985 }
                            }
                            animate={{ opacity: 1, y: 0, scale: 1 }}
                            transition={{
                                duration: 0.5,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                        >
                            <div className="mb-6">
                                <p className="mb-2 text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                                    {eyebrow}
                                </p>
                                <h1 className="text-3xl font-semibold tracking-[-0.03em] text-white">
                                    {title}
                                </h1>
                                <p className="mt-2 text-sm leading-6 text-zinc-500">
                                    {description}
                                </p>
                            </div>

                            <Card className="forge-glow">{children}</Card>
                            {footer && (
                                <div className="mt-6 text-center text-sm text-zinc-500">
                                    {footer}
                                </div>
                            )}
                        </motion.div>
                    </div>
                </section>
            </div>
        </main>
    );
}
