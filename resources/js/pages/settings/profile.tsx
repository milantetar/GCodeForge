import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, UserRound } from 'lucide-react';
import type { FormEvent } from 'react';

import { AppShell } from '@/components/app-shell';
import { FormField } from '@/components/auth/form-field';
import { SubmitButton } from '@/components/auth/submit-button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { Auth } from '@/types';

export default function ProfileSettings() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user!;
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put('/user/profile-information', { preserveScroll: true });
    };

    return (
        <>
            <Head title="Profile settings" />
            <AppShell title="Profile settings">
                <div className="max-w-2xl">
                    <div className="mb-7">
                        <h2 className="text-2xl font-semibold tracking-[-0.025em] text-white">
                            Your profile
                        </h2>
                        <p className="mt-2 text-sm text-zinc-600">
                            Keep your identity and contact details up to date.
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 place-items-center rounded-xl border border-forge-400/15 bg-forge-500/10">
                                    <UserRound className="size-5 text-forge-300" />
                                </span>
                                <div>
                                    <h3 className="font-medium text-zinc-100">
                                        Personal information
                                    </h3>
                                    <p className="text-xs text-zinc-600">
                                        Shown across your Forge workspace.
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-5">
                                <FormField
                                    id="name"
                                    label="Full name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    error={errors.name}
                                    autoComplete="name"
                                    required
                                />
                                <FormField
                                    id="email"
                                    label="Email address"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    error={errors.email}
                                    autoComplete="email"
                                    required
                                />
                                <div className="flex items-center gap-4 border-t border-white/[0.07] pt-5">
                                    <SubmitButton
                                        processing={processing}
                                        className="w-auto px-6"
                                    >
                                        Save changes
                                    </SubmitButton>
                                    {recentlySuccessful && (
                                        <span className="flex items-center gap-1.5 text-xs text-emerald-400">
                                            <Check className="size-3.5" /> Saved
                                        </span>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
