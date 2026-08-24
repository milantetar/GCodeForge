import { Head, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { FormField } from '@/components/auth/form-field';
import { SubmitButton } from '@/components/auth/submit-button';
import { Button } from '@/components/ui/button';
import { CardContent } from '@/components/ui/card';

export default function TwoFactorChallenge() {
    const [usingRecoveryCode, setUsingRecoveryCode] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/two-factor-challenge', { onFinish: () => reset() });
    };

    return (
        <>
            <Head title="Two-factor authentication" />
            <AuthShell
                title="Two-step verification"
                description={
                    usingRecoveryCode
                        ? 'Enter one of your emergency recovery codes.'
                        : 'Enter the six-digit code from your authenticator app.'
                }
                eyebrow="Protecting your workspace"
            >
                <CardContent>
                    <div className="mb-5 grid size-10 place-items-center rounded-xl border border-forge-400/15 bg-forge-500/10">
                        <KeyRound className="size-5 text-forge-300" />
                    </div>
                    <form onSubmit={submit} className="space-y-5">
                        {usingRecoveryCode ? (
                            <FormField
                                id="recovery_code"
                                label="Recovery code"
                                value={data.recovery_code}
                                onChange={(event) =>
                                    setData('recovery_code', event.target.value)
                                }
                                error={errors.recovery_code}
                                autoComplete="one-time-code"
                                autoFocus
                            />
                        ) : (
                            <FormField
                                id="code"
                                label="Authentication code"
                                value={data.code}
                                onChange={(event) =>
                                    setData(
                                        'code',
                                        event.target.value
                                            .replace(/\D/g, '')
                                            .slice(0, 6),
                                    )
                                }
                                error={errors.code}
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                placeholder="000000"
                                className="text-center text-lg tracking-[0.45em]"
                                autoFocus
                            />
                        )}
                        <SubmitButton processing={processing}>
                            Verify identity
                        </SubmitButton>
                    </form>
                    <Button
                        variant="ghost"
                        className="mt-2 w-full text-xs text-zinc-600"
                        onClick={() => {
                            setUsingRecoveryCode((current) => !current);
                            reset();
                        }}
                    >
                        {usingRecoveryCode
                            ? 'Use an authentication code'
                            : 'Use a recovery code'}
                    </Button>
                </CardContent>
            </AuthShell>
        </>
    );
}
