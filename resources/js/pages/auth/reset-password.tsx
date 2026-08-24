import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { FormField } from '@/components/auth/form-field';
import { PasswordField } from '@/components/auth/password-field';
import { SubmitButton } from '@/components/auth/submit-button';
import { CardContent } from '@/components/ui/card';

type ResetPasswordProps = { email: string; token: string };

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Choose a new password" />
            <AuthShell
                title="Choose a new password"
                description="Use a strong, unique password to protect your workspace."
                eyebrow="Secure your account"
            >
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
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
                        <PasswordField
                            id="password"
                            label="New password"
                            hint="At least 8 characters"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            error={errors.password}
                            autoComplete="new-password"
                            autoFocus
                            required
                        />
                        <PasswordField
                            id="password_confirmation"
                            label="Confirm new password"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            autoComplete="new-password"
                            required
                        />
                        <SubmitButton processing={processing}>
                            Update password
                        </SubmitButton>
                    </form>
                </CardContent>
            </AuthShell>
        </>
    );
}
