import { Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import {
    fieldClass,
    FormErrors,
    Pagination,
    Panel,
    ProjectCard,
} from '@/components/workspace';
import type { MachineProfile, Paginated, Project } from '@/types/gcode';

export default function Projects({
    projects,
    profiles,
}: {
    projects: Paginated<Project>;
    profiles: MachineProfile[];
}) {
    const form = useForm({
        name: '',
        description: '',
        machine_profile_id:
            profiles.find((profile) => profile.is_default)?.id.toString() ?? '',
    });

    return (
        <>
            <Head title="Projects" />
            <AppShell title="Projects">
                <div className="grid gap-7 xl:grid-cols-[1fr_22rem]">
                    <div>
                        <h2 className="mb-5 text-2xl font-semibold text-white">
                            Your work, organized.
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {projects.data.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                />
                            ))}
                        </div>
                        {!projects.data.length && (
                            <Panel>
                                <p className="text-zinc-400">
                                    No projects yet. Create one to open the
                                    converter workspace.
                                </p>
                            </Panel>
                        )}
                        <Pagination links={projects.links} />
                    </div>
                    <Panel>
                        <h3 className="text-lg font-medium text-zinc-100">
                            New project
                        </h3>
                        <form
                            className="mt-5 space-y-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.post('/projects');
                            }}
                        >
                            <label className="block text-sm text-zinc-400">
                                Project name
                                <input
                                    className={fieldClass}
                                    required
                                    maxLength={160}
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="Precision engraving series"
                                />
                            </label>
                            <label className="block text-sm text-zinc-400">
                                Description
                                <textarea
                                    className={fieldClass}
                                    maxLength={5000}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                            <label className="block text-sm text-zinc-400">
                                Machine profile
                                <select
                                    className={fieldClass}
                                    value={form.data.machine_profile_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'machine_profile_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">No profile</option>
                                    {profiles.map((profile) => (
                                        <option
                                            key={profile.id}
                                            value={profile.id}
                                        >
                                            {profile.name} ({profile.units})
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <FormErrors errors={form.errors} />
                            <Button
                                disabled={form.processing}
                                className="w-full"
                            >
                                <Plus className="size-4" />
                                {form.processing
                                    ? 'Creating…'
                                    : 'Create project'}
                            </Button>
                        </form>
                    </Panel>
                </div>
            </AppShell>
        </>
    );
}
