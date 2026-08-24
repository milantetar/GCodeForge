import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Boxes,
    Clock3,
    FileCode2,
    Plus,
    Sparkles,
    Zap,
} from 'lucide-react';
import { motion } from 'motion/react';

import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Auth } from '@/types';

export default function Dashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user?.name.split(' ')[0] ?? 'Maker';

    return (
        <>
            <Head title="Workspace" />
            <AppShell
                title="Workspace"
                actions={
                    <Button size="sm">
                        <Plus className="size-4" /> New project
                    </Button>
                }
            >
                <motion.section
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                >
                    <p className="text-xs font-medium text-forge-400">
                        GOOD TO SEE YOU
                    </p>
                    <h2 className="mt-2 text-3xl font-semibold tracking-[-0.035em] text-white">
                        Ready to build, {firstName}?
                    </h2>
                    <p className="mt-2 text-sm text-zinc-600">
                        Your Forge workspace is online and ready.
                    </p>
                </motion.section>

                <section className="mt-8 grid gap-4 md:grid-cols-3">
                    {[
                        [FileCode2, 'Projects', '0', 'Your machine programs'],
                        [Clock3, 'Recent runs', '0', 'Validated this week'],
                        [Zap, 'Optimizations', '0', 'Minutes saved'],
                    ].map(([Icon, label, value, caption]) => (
                        <Card key={String(label)} className="bg-white/[0.02]">
                            <CardContent>
                                <div className="flex items-start justify-between">
                                    <span className="grid size-9 place-items-center rounded-xl border border-white/[0.07] bg-white/[0.035]">
                                        <Icon className="size-4 text-forge-300" />
                                    </span>
                                    <ArrowUpRight className="size-4 text-zinc-800" />
                                </div>
                                <p className="mt-5 text-2xl font-semibold text-white">
                                    {String(value)}
                                </p>
                                <p className="mt-1 text-sm text-zinc-400">
                                    {String(label)}
                                </p>
                                <p className="mt-1 text-xs text-zinc-700">
                                    {String(caption)}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="mt-4 grid gap-4 lg:grid-cols-[1.35fr_.65fr]">
                    <Card className="min-h-80">
                        <CardContent className="flex min-h-80 flex-col items-center justify-center text-center">
                            <div className="grid size-14 place-items-center rounded-2xl border border-forge-400/15 bg-forge-500/10 shadow-[0_16px_40px_rgba(109,40,217,.14)]">
                                <Boxes className="size-6 text-forge-300" />
                            </div>
                            <h3 className="mt-5 font-semibold text-zinc-100">
                                Your first project starts here
                            </h3>
                            <p className="mt-2 max-w-sm text-sm leading-6 text-zinc-600">
                                Create a project to organize source files,
                                toolpaths, validation runs, and final exports.
                            </p>
                            <Button className="mt-6">
                                <Plus className="size-4" /> Create project
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent>
                            <div className="flex items-center gap-2 text-sm font-medium text-zinc-200">
                                <Sparkles className="size-4 text-forge-300" />{' '}
                                Forge setup
                            </div>
                            <div className="mt-6 space-y-5">
                                {[
                                    ['Account created', true],
                                    [
                                        'Email verified',
                                        Boolean(auth.user?.email_verified_at),
                                    ],
                                    ['Profile completed', false],
                                    ['First project created', false],
                                ].map(([label, done]) => (
                                    <div
                                        key={String(label)}
                                        className="flex items-center gap-3"
                                    >
                                        <span
                                            className={
                                                done
                                                    ? 'size-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]'
                                                    : 'size-2 rounded-full border border-zinc-700'
                                            }
                                        />
                                        <span
                                            className={
                                                done
                                                    ? 'text-sm text-zinc-400'
                                                    : 'text-sm text-zinc-600'
                                            }
                                        >
                                            {String(label)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <Button
                                asChild
                                variant="secondary"
                                className="mt-7 w-full"
                            >
                                <Link href="/settings/profile">
                                    Complete profile
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>
            </AppShell>
        </>
    );
}
