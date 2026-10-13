import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Panel, ProjectCard } from '@/components/workspace';
import type { Auth } from '@/types';
import type { Project } from '@/types/gcode';

export default function Dashboard({
    stats,
    recentProjects,
}: {
    stats: Record<
        'projects' | 'conversions' | 'completed' | 'profiles',
        number
    >;
    recentProjects: Project[];
}) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Workspace" />
            <AppShell
                title="Workspace"
                actions={
                    <Button asChild size="sm">
                        <Link href="/projects">
                            <Plus className="size-4" /> New project
                        </Link>
                    </Button>
                }
            >
                <p className="text-xs font-semibold tracking-widest text-violet-400 uppercase">
                    Your private studio
                </p>
                <h2 className="mt-3 text-3xl font-semibold tracking-tight text-white">
                    Ready to build, {auth.user?.name.split(' ')[0]}?
                </h2>
                <p className="mt-3 text-zinc-500">
                    From source instructions to repeatable patterns. Every
                    conversion, in one place.
                </p>
                <div className="my-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {Object.entries(stats).map(([label, value]) => (
                        <Panel key={label}>
                            <p className="text-3xl font-semibold text-zinc-100">
                                {value}
                            </p>
                            <p className="mt-2 text-sm text-zinc-500 capitalize">
                                {label}
                            </p>
                        </Panel>
                    ))}
                </div>
                <div className="mb-5 flex justify-between">
                    <h3 className="text-lg text-zinc-200">Recent projects</h3>
                    <Link href="/projects" className="text-sm text-violet-300">
                        View all →
                    </Link>
                </div>
                {recentProjects.length ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {recentProjects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                ) : (
                    <Panel>
                        <h3 className="text-xl text-zinc-100">
                            Create your first project
                        </h3>
                        <p className="mt-3 max-w-xl text-sm leading-6 text-zinc-500">
                            Choose a machine profile, upload or paste your
                            G-code, and run Pattern Repeater, Hexagonal Flat, or
                            Two Axis.
                        </p>
                        <Button asChild className="mt-5">
                            <Link href="/projects">Create project</Link>
                        </Button>
                    </Panel>
                )}
                <p className="mt-8 text-xs leading-6 text-amber-200/60">
                    Generated G-code is a transformation, not a machining safety
                    certification. Simulate, inspect units and work offsets,
                    then dry-run on your controller.
                </p>
            </AppShell>
        </>
    );
}
