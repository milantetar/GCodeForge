import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import {
    fieldClass,
    FormErrors,
    Pagination,
    Panel,
} from '@/components/workspace';
import type { MachineProfile, Paginated } from '@/types/gcode';

function ProfileEditor({
    profile,
    onSaved,
}: {
    profile: MachineProfile | null;
    onSaved: () => void;
}) {
    const form = useForm({
        name: profile?.name ?? '',
        description: profile?.description ?? '',
        units: profile?.units ?? 'mm',
        is_default: profile?.is_default ?? false,
        settings: {
            repetitions: profile?.settings.repetitions ?? 1,
            y_distance: profile?.settings.y_distance ?? '0',
            b_increment: profile?.settings.b_increment ?? '0',
            rapid_feed_rate: profile?.settings.rapid_feed_rate ?? 3000,
            engraving_feed_rate: profile?.settings.engraving_feed_rate ?? 300,
        },
    });

    return (
        <Panel>
            <h2 className="text-lg text-white">
                {profile ? 'Edit profile' : 'New machine profile'}
            </h2>
            <form
                className="mt-5 space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();

                    if (profile) {
form.put(`/machine-profiles/${profile.id}`, {
                            onSuccess: onSaved,
                        });
} else {
form.post('/machine-profiles', {
                            onSuccess: () => {
                                form.reset();
                                onSaved();
                            },
                        });
}
                }}
            >
                <label className="block text-xs text-zinc-400">
                    Profile name
                    <input
                        className={fieldClass}
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        required
                        maxLength={160}
                    />
                </label>
                <label className="block text-xs text-zinc-400">
                    Description
                    <textarea
                        className={fieldClass}
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                    />
                </label>
                <label className="block text-xs text-zinc-400">
                    Units
                    <select
                        className={fieldClass}
                        value={form.data.units}
                        onChange={(event) =>
                            form.setData(
                                'units',
                                event.target.value === 'inch' ? 'inch' : 'mm',
                            )
                        }
                    >
                        <option value="mm">Millimeters</option>
                        <option value="inch">Inches</option>
                    </select>
                </label>
                {Object.entries(form.data.settings).map(([key, value]) => (
                    <label
                        className="block text-xs text-zinc-400 capitalize"
                        key={key}
                    >
                        {key.replaceAll('_', ' ')}
                        <input
                            className={fieldClass}
                            type="number"
                            required
                            step={
                                key.includes('distance') ||
                                key.includes('increment')
                                    ? 'any'
                                    : '1'
                            }
                            value={value}
                            onChange={(event) =>
                                form.setData('settings', {
                                    ...form.data.settings,
                                    [key]: event.target.value,
                                })
                            }
                        />
                    </label>
                ))}
                <label className="flex gap-3 text-sm text-zinc-400">
                    <input
                        type="checkbox"
                        checked={form.data.is_default}
                        onChange={(event) =>
                            form.setData('is_default', event.target.checked)
                        }
                    />
                    Default profile for new projects
                </label>
                <FormErrors errors={form.errors} />
                <Button disabled={form.processing} className="w-full">
                    {form.processing ? 'Saving…' : 'Save profile'}
                </Button>
                {profile && (
                    <Button type="button" variant="ghost" onClick={onSaved}>
                        Cancel editing
                    </Button>
                )}
            </form>
        </Panel>
    );
}

export default function Profiles({
    profiles,
}: {
    profiles: Paginated<MachineProfile>;
}) {
    const [editing, setEditing] = useState<MachineProfile | null>(null);

    return (
        <>
            <Head title="Machine profiles" />
            <AppShell title="Machine profiles">
                <div className="grid gap-7 xl:grid-cols-[1fr_24rem]">
                    <div>
                        <h2 className="text-2xl font-semibold text-white">
                            Configure once. Reuse every run.
                        </h2>
                        <p className="mt-3 mb-6 text-sm text-zinc-500">
                            Profiles provide defaults. Each conversion stores
                            its final options independently.
                        </p>
                        <div className="grid gap-4 md:grid-cols-2">
                            {profiles.data.map((profile) => (
                                <Panel key={profile.id}>
                                    <h3 className="text-lg text-zinc-100">
                                        {profile.name}
                                    </h3>
                                    <p className="mt-2 text-xs text-violet-300">
                                        {profile.units}{' '}
                                        {profile.is_default && '· Default'}
                                    </p>
                                    <p className="mt-3 text-sm text-zinc-500">
                                        {profile.description}
                                    </p>
                                    <div className="mt-5 flex gap-3">
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => setEditing(profile)}
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                window.confirm(
                                                    'Remove this profile? Project links will be cleared; conversion history is preserved.',
                                                ) &&
                                                router.delete(
                                                    `/machine-profiles/${profile.id}`,
                                                )
                                            }
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </Panel>
                            ))}
                        </div>
                        {!profiles.data.length && (
                            <Panel>
                                <p className="text-zinc-500">
                                    No profiles yet. Add your machine defaults
                                    here.
                                </p>
                            </Panel>
                        )}
                        <Pagination links={profiles.links} />
                    </div>
                    <ProfileEditor
                        key={editing?.id ?? 'new'}
                        profile={editing}
                        onSaved={() => setEditing(null)}
                    />
                </div>
            </AppShell>
        </>
    );
}
