import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { PasswordField } from '@/components/auth/password-field';
import { SubmitButton } from '@/components/auth/submit-button';
import { CardContent } from '@/components/ui/card';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/user/confirm-password', { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Confirm password" />
            <AuthShell
                title="Confirm it’s you"
                description="This is a protected area. Confirm your password to continue."
                eyebrow="Security checkpoint"
            >
                <CardContent>
                    <div className="mb-5 grid size-10 place-items-center rounded-xl border border-forge-400/15 bg-forge-500/10">
                        <ShieldCheck className="size-5 text-forge-300" />
                    </div>
                    <form onSubmit={submit} className="space-y-5">
                        <PasswordField
                            id="password"
                            label="Password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            error={errors.password}
                            autoComplete="current-password"
                            autoFocus
                            required
                        />
                        <SubmitButton processing={processing}>
                            Confirm and continue
                        </SubmitButton>
                    </form>
                </CardContent>
            </AuthShell>
        </>
    );
}
