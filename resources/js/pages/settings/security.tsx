import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, Copy, KeyRound, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';

import { AppShell } from '@/components/app-shell';
import { FormField } from '@/components/auth/form-field';
import { PasswordField } from '@/components/auth/password-field';
import { SubmitButton } from '@/components/auth/submit-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { Auth } from '@/types';

type QrResponse = { svg: string; url: string };

export default function SecuritySettings() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const confirmForm = useForm({ code: '' });
    const [qr, setQr] = useState<QrResponse | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [loadingSetup, setLoadingSetup] = useState(false);

    const updatePassword = (event: FormEvent) => {
        event.preventDefault();
        passwordForm.put('/user/password', {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
                toast.success('Password updated successfully.');
            },
        });
    };

    const loadTwoFactorSetup = async () => {
        setLoadingSetup(true);

        try {
            const [qrResponse, codesResponse] = await Promise.all([
                fetch('/user/two-factor-qr-code', {
                    headers: { Accept: 'application/json' },
                }),
                fetch('/user/two-factor-recovery-codes', {
                    headers: { Accept: 'application/json' },
                }),
            ]);

            if (!qrResponse.ok || !codesResponse.ok) {
                throw new Error('Unable to load two-factor setup.');
            }

            setQr((await qrResponse.json()) as QrResponse);
            setRecoveryCodes((await codesResponse.json()) as string[]);
        } catch {
            toast.error(
                'Could not load the authenticator setup. Please try again.',
            );
        } finally {
            setLoadingSetup(false);
        }
    };

    const enableTwoFactor = () => {
        router.post(
            '/user/two-factor-authentication',
            {},
            {
                preserveScroll: true,
                onSuccess: () => void loadTwoFactorSetup(),
                onError: () =>
                    toast.error(
                        'Please confirm your password before enabling two-factor authentication.',
                    ),
            },
        );
    };

    const confirmTwoFactor = (event: FormEvent) => {
        event.preventDefault();
        confirmForm.post('/user/confirmed-two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => {
                setQr(null);
                toast.success('Two-factor authentication is now active.');
            },
        });
    };

    const disableTwoFactor = () => {
        router.delete('/user/two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Two-factor authentication disabled.'),
        });
    };

    return (
        <>
            <Head title="Security settings" />
            <AppShell title="Security settings">
                <div className="max-w-2xl space-y-5">
                    <div className="mb-7">
                        <h2 className="text-2xl font-semibold tracking-[-0.025em] text-white">
                            Account security
                        </h2>
                        <p className="mt-2 text-sm text-zinc-600">
                            Strengthen access to your projects and machine
                            files.
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 place-items-center rounded-xl border border-white/[0.08] bg-white/[0.035]">
                                    <LockKeyhole className="size-5 text-forge-300" />
                                </span>
                                <div>
                                    <h3 className="font-medium text-zinc-100">
                                        Change password
                                    </h3>
                                    <p className="text-xs text-zinc-600">
                                        Use a unique password you do not reuse
                                        elsewhere.
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={updatePassword}
                                className="space-y-4"
                            >
                                <PasswordField
                                    id="current_password"
                                    label="Current password"
                                    value={passwordForm.data.current_password}
                                    onChange={(event) =>
                                        passwordForm.setData(
                                            'current_password',
                                            event.target.value,
                                        )
                                    }
                                    error={passwordForm.errors.current_password}
                                    autoComplete="current-password"
                                    required
                                />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <PasswordField
                                        id="new_password"
                                        label="New password"
                                        value={passwordForm.data.password}
                                        onChange={(event) =>
                                            passwordForm.setData(
                                                'password',
                                                event.target.value,
                                            )
                                        }
                                        error={passwordForm.errors.password}
                                        autoComplete="new-password"
                                        required
                                    />
                                    <PasswordField
                                        id="new_password_confirmation"
                                        label="Confirm new password"
                                        value={
                                            passwordForm.data
                                                .password_confirmation
                                        }
                                        onChange={(event) =>
                                            passwordForm.setData(
                                                'password_confirmation',
                                                event.target.value,
                                            )
                                        }
                                        autoComplete="new-password"
                                        required
                                    />
                                </div>
                                <SubmitButton
                                    processing={passwordForm.processing}
                                    className="w-auto px-6"
                                >
                                    Update password
                                </SubmitButton>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <span className="grid size-10 place-items-center rounded-xl border border-forge-400/15 bg-forge-500/10">
                                        <ShieldCheck className="size-5 text-forge-300" />
                                    </span>
                                    <div>
                                        <h3 className="font-medium text-zinc-100">
                                            Two-factor authentication
                                        </h3>
                                        <p className="text-xs text-zinc-600">
                                            Require a time-based code when
                                            signing in.
                                        </p>
                                    </div>
                                </div>
                                <span
                                    className={
                                        auth.twoFactorConfirmed
                                            ? 'rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-medium text-emerald-400'
                                            : 'rounded-full bg-zinc-500/10 px-2.5 py-1 text-[10px] font-medium text-zinc-500'
                                    }
                                >
                                    {auth.twoFactorConfirmed ? 'ACTIVE' : 'OFF'}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {qr ? (
                                <div className="space-y-5">
                                    <div className="rounded-xl border border-white/[0.07] bg-white/[0.025] p-4 text-sm leading-6 text-zinc-500">
                                        Scan this QR code with your
                                        authenticator app, then enter the
                                        six-digit code to finish.
                                    </div>
                                    <div
                                        className="mx-auto w-fit rounded-2xl bg-white p-3"
                                        dangerouslySetInnerHTML={{
                                            __html: qr.svg,
                                        }}
                                    />
                                    <form
                                        onSubmit={confirmTwoFactor}
                                        className="flex gap-3"
                                    >
                                        <FormField
                                            id="two_factor_code"
                                            label="Verification code"
                                            value={confirmForm.data.code}
                                            onChange={(event) =>
                                                confirmForm.setData(
                                                    'code',
                                                    event.target.value
                                                        .replace(/\D/g, '')
                                                        .slice(0, 6),
                                                )
                                            }
                                            error={confirmForm.errors.code}
                                            placeholder="000000"
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            className="tracking-[0.3em]"
                                        />
                                        <SubmitButton
                                            processing={confirmForm.processing}
                                            className="mt-7 w-auto"
                                        >
                                            Confirm
                                        </SubmitButton>
                                    </form>
                                    {recoveryCodes.length > 0 && (
                                        <div className="rounded-xl border border-amber-400/15 bg-amber-400/[0.04] p-4">
                                            <div className="flex items-center justify-between">
                                                <p className="text-xs font-medium text-amber-300">
                                                    Save these recovery codes
                                                </p>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => {
                                                        void navigator.clipboard.writeText(
                                                            recoveryCodes.join(
                                                                '\n',
                                                            ),
                                                        );
                                                        toast.success(
                                                            'Recovery codes copied.',
                                                        );
                                                    }}
                                                >
                                                    <Copy className="size-3.5" />{' '}
                                                    Copy
                                                </Button>
                                            </div>
                                            <div className="mt-3 grid grid-cols-2 gap-1 font-mono text-[11px] text-zinc-500">
                                                {recoveryCodes.map((code) => (
                                                    <span key={code}>
                                                        {code}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ) : auth.twoFactorConfirmed ? (
                                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                                    <p className="flex items-center gap-2 text-sm text-emerald-400">
                                        <Check className="size-4" /> Your
                                        account has extra sign-in protection.
                                    </p>
                                    <Button
                                        variant="danger"
                                        size="sm"
                                        onClick={disableTwoFactor}
                                    >
                                        Disable 2FA
                                    </Button>
                                </div>
                            ) : (
                                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                                    <p className="max-w-md text-sm leading-6 text-zinc-600">
                                        Use any TOTP authenticator such as
                                        1Password, Authy, or Google
                                        Authenticator.
                                    </p>
                                    <Button
                                        variant="secondary"
                                        onClick={
                                            auth.twoFactorEnabled
                                                ? () =>
                                                      void loadTwoFactorSetup()
                                                : enableTwoFactor
                                        }
                                        disabled={loadingSetup}
                                    >
                                        <KeyRound className="size-4" />{' '}
                                        {loadingSetup
                                            ? 'Loading…'
                                            : 'Set up 2FA'}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
