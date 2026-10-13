import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Play, Upload } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import {
    fieldClass,
    FormErrors,
    Pagination,
    Panel,
} from '@/components/workspace';
import type {
    Conversion,
    ConverterType,
    MachineProfile,
    Paginated,
    Project,
} from '@/types/gcode';

const converters: {
    value: ConverterType;
    label: string;
    description: string;
}[] = [
    {
        value: 'pattern-repeater',
        label: 'Pattern Repeater',
        description:
            'Repeat the Z4 pattern with transformed coordinates and a per-copy Y offset.',
    },
    {
        value: 'hexagonal-flat',
        label: 'Hexagonal Flat',
        description: 'Flatten X, transform Y/Z, and repeat the machining body.',
    },
    {
        value: 'two-axis',
        label: 'Two Axis',
        description:
            'Repeat XY moves with incremental B rotation and configured feed rates.',
    },
];

export default function ProjectWorkspace({
    project,
    profiles,
    conversions,
    maxInputBytes,
}: {
    project: Project;
    profiles: MachineProfile[];
    conversions: Paginated<Conversion>;
    maxInputBytes: number;
}) {
    const [converter, setConverter] =
        useState<ConverterType>('pattern-repeater');
    const [sourceMode, setSourceMode] = useState<'paste' | 'upload'>('paste');
    const defaults = project.machine_profile?.settings ?? {};
    const form = useForm<{
        project_id: number;
        gcode: string;
        file: File | null;
        async: boolean;
        options: Record<string, string | number>;
    }>({
        project_id: project.id,
        gcode: '',
        file: null,
        async: true,
        options: {
            repetitions: defaults.repetitions ?? 1,
            y_distance: defaults.y_distance ?? '0',
            b_increment: defaults.b_increment ?? '0',
            rapid_feed_rate: defaults.rapid_feed_rate ?? 3000,
            engraving_feed_rate: defaults.engraving_feed_rate ?? 300,
        },
    });
    const settings = useForm({
        name: project.name,
        description: project.description ?? '',
        machine_profile_id: project.machine_profile?.id.toString() ?? '',
    });
    const archived = project.status === 'archived';
    const optionKeys =
        converter === 'two-axis'
            ? [
                  'repetitions',
                  'b_increment',
                  'rapid_feed_rate',
                  'engraving_feed_rate',
              ]
            : ['repetitions', 'y_distance'];

    return (
        <>
            <Head title={project.name} />
            <AppShell
                title={project.name}
                actions={
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={() =>
                            archived
                                ? router.patch(`/projects/${project.id}`, {
                                      name: project.name,
                                      status: 'active',
                                  })
                                : window.confirm(
                                      'Archive this project? Existing output is preserved.',
                                  ) && router.delete(`/projects/${project.id}`)
                        }
                    >
                        {archived ? 'Restore project' : 'Archive'}
                    </Button>
                }
            >
                <Link href="/projects" className="text-sm text-violet-300">
                    ← All projects
                </Link>
                <h2 className="mt-4 text-3xl font-semibold text-white">
                    Make your next move.
                </h2>
                <p className="mt-3 mb-7 text-sm text-zinc-500">
                    {project.description ||
                        'Configure a transformation and keep the result in your project history.'}
                </p>
                {archived && (
                    <p className="mb-5 rounded-xl bg-amber-500/10 p-4 text-sm text-amber-200">
                        This project is archived. Restore it to run new
                        conversions.
                    </p>
                )}
                <div className="grid gap-6 xl:grid-cols-[1fr_22rem]">
                    <Panel>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.transform((data) => ({
                                    ...data,
                                    gcode:
                                        sourceMode === 'paste'
                                            ? data.gcode
                                            : null,
                                    file:
                                        sourceMode === 'upload'
                                            ? data.file
                                            : null,
                                    options: Object.fromEntries(
                                        optionKeys.map((key) => [
                                            key,
                                            data.options[key],
                                        ]),
                                    ),
                                }));
                                form.post(`/conversions/${converter}`);
                            }}
                        >
                            <div className="grid gap-3 sm:grid-cols-3">
                                {converters.map((item) => (
                                    <button
                                        type="button"
                                        key={item.value}
                                        onClick={() => setConverter(item.value)}
                                        aria-pressed={converter === item.value}
                                        className={`rounded-xl border p-4 text-left text-sm transition ${converter === item.value ? 'border-violet-400/50 bg-violet-500/10 text-violet-200' : 'border-white/10 text-zinc-500 hover:border-white/25'}`}
                                    >
                                        {item.label}
                                    </button>
                                ))}
                            </div>
                            <p className="mt-4 text-xs leading-6 text-zinc-500">
                                {
                                    converters.find(
                                        (item) => item.value === converter,
                                    )?.description
                                }
                            </p>
                            <div className="mt-6 flex gap-4 text-sm">
                                <button
                                    type="button"
                                    className={
                                        sourceMode === 'paste'
                                            ? 'text-violet-300'
                                            : 'text-zinc-500'
                                    }
                                    onClick={() => setSourceMode('paste')}
                                >
                                    Paste source
                                </button>
                                <button
                                    type="button"
                                    className={
                                        sourceMode === 'upload'
                                            ? 'text-violet-300'
                                            : 'text-zinc-500'
                                    }
                                    onClick={() => setSourceMode('upload')}
                                >
                                    <Upload className="mr-2 inline size-4" />
                                    Upload file
                                </button>
                            </div>
                            {sourceMode === 'paste' ? (
                                <label className="mt-4 block text-xs text-zinc-500">
                                    Source G-code
                                    <textarea
                                        aria-label="Source G-code"
                                        className={`${fieldClass} min-h-80 font-mono leading-6`}
                                        spellCheck={false}
                                        required
                                        value={form.data.gcode}
                                        onChange={(event) =>
                                            form.setData(
                                                'gcode',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Paste numeric G-code commands here…"
                                    />
                                </label>
                            ) : (
                                <label className="mt-4 block rounded-xl border border-dashed border-white/20 p-8 text-sm text-zinc-400">
                                    Choose .nc, .gcode, .tap or .txt (up to{' '}
                                    {Math.round(maxInputBytes / 1024 / 1024)}{' '}
                                    MiB)
                                    <input
                                        className="mt-4 block w-full text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/15 file:p-3 file:text-violet-200"
                                        type="file"
                                        accept=".nc,.gcode,.tap,.txt"
                                        required
                                        onChange={(event) =>
                                            form.setData(
                                                'file',
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </label>
                            )}
                            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                {optionKeys.map((key) => (
                                    <label
                                        key={key}
                                        className="text-xs text-zinc-400 capitalize"
                                    >
                                        {key.replaceAll('_', ' ')}
                                        <input
                                            className={fieldClass}
                                            type="number"
                                            step={
                                                key.includes('distance') ||
                                                key.includes('increment')
                                                    ? 'any'
                                                    : '1'
                                            }
                                            min={
                                                key === 'repetitions' ||
                                                key.includes('feed')
                                                    ? 1
                                                    : undefined
                                            }
                                            required
                                            value={form.data.options[key]}
                                            onChange={(event) =>
                                                form.setData('options', {
                                                    ...form.data.options,
                                                    [key]: event.target.value,
                                                })
                                            }
                                        />
                                    </label>
                                ))}
                            </div>
                            <p className="mt-5 text-xs leading-6 text-amber-200/60">
                                Profile units:{' '}
                                {project.machine_profile?.units ??
                                    'not specified'}
                                . Units are metadata, not converted
                                automatically. Inspect and simulate output
                                before running it on machinery.
                            </p>
                            <FormErrors errors={form.errors} />
                            {form.progress && (
                                <p className="mt-3 text-xs text-zinc-400">
                                    Uploading: {form.progress.percentage}%
                                </p>
                            )}
                            <Button
                                className="mt-6 w-full"
                                disabled={archived || form.processing}
                            >
                                <Play className="size-4" />
                                {form.processing
                                    ? 'Submitting…'
                                    : 'Run conversion'}
                            </Button>
                        </form>
                    </Panel>
                    <div className="space-y-6">
                        <Panel>
                            <h3 className="text-lg text-zinc-100">
                                Project settings
                            </h3>
                            <form
                                className="mt-5 space-y-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    settings.put(`/projects/${project.id}`, {
                                        onSuccess: () =>
                                            form.setData('options', {
                                                repetitions: 1,
                                                y_distance: '0',
                                                b_increment: '0',
                                                rapid_feed_rate: 3000,
                                                engraving_feed_rate: 300,
                                                ...profiles.find(
                                                    (profile) =>
                                                        profile.id.toString() ===
                                                        settings.data
                                                            .machine_profile_id,
                                                )?.settings,
                                            }),
                                    });
                                }}
                            >
                                <label className="block text-xs text-zinc-400">
                                    Name
                                    <input
                                        required
                                        maxLength={160}
                                        className={fieldClass}
                                        value={settings.data.name}
                                        onChange={(event) =>
                                            settings.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </label>
                                <label className="block text-xs text-zinc-400">
                                    Description
                                    <textarea
                                        className={fieldClass}
                                        value={settings.data.description}
                                        onChange={(event) =>
                                            settings.setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </label>
                                <label className="block text-xs text-zinc-400">
                                    Machine profile
                                    <select
                                        className={fieldClass}
                                        value={settings.data.machine_profile_id}
                                        onChange={(event) =>
                                            settings.setData(
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
                                                {profile.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <FormErrors errors={settings.errors} />
                                <Button
                                    variant="secondary"
                                    disabled={settings.processing}
                                >
                                    Save settings
                                </Button>
                            </form>
                        </Panel>
                        <Panel>
                            <h3 className="text-zinc-200">
                                Private by default
                            </h3>
                            <p className="mt-3 text-sm leading-6 text-zinc-500">
                                Source and generated files are stored privately.
                                Downloads require an authorized account. Each
                                run keeps its own options and history.
                            </p>
                            <Link
                                className="mt-4 block text-sm text-violet-300"
                                href="/machine-profiles"
                            >
                                Manage machine profiles →
                            </Link>
                        </Panel>
                    </div>
                </div>
                <h3 className="mt-10 mb-5 text-xl text-zinc-200">
                    Conversion history
                </h3>
                <Panel>
                    {!conversions.data.length && (
                        <p className="text-sm text-zinc-500">
                            Your conversion history will appear here.
                        </p>
                    )}
                    <div className="divide-y divide-white/5">
                        {conversions.data.map((conversion) => (
                            <Link
                                key={conversion.id}
                                href={`/conversions/${conversion.id}`}
                                className="flex flex-wrap items-center justify-between gap-4 py-4 text-sm text-zinc-300"
                            >
                                <span>
                                    <span className="capitalize">
                                        {conversion.type.replaceAll('-', ' ')}
                                    </span>
                                    <span className="mt-1 block text-xs text-zinc-600">
                                        {new Date(
                                            conversion.created_at,
                                        ).toLocaleString()}
                                    </span>
                                </span>
                                <span className="flex items-center gap-4">
                                    <span className="text-xs text-violet-300">
                                        {conversion.status}
                                    </span>
                                    <ArrowRight className="size-4" />
                                </span>
                            </Link>
                        ))}
                    </div>
                    <Pagination links={conversions.links} />
                </Panel>
            </AppShell>
        </>
    );
}
