import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Mail } from 'lucide-react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { FormField } from '@/components/auth/form-field';
import { StatusMessage } from '@/components/auth/status-message';
import { SubmitButton } from '@/components/auth/submit-button';
import { CardContent } from '@/components/ui/card';

type ForgotPasswordProps = { status?: string };

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Reset password" />
            <AuthShell
                title="Reset your password"
                description="Enter your email and we'll send you a secure reset link."
                eyebrow="Account recovery"
                footer={
                    <Link
                        href="/login"
                        className="inline-flex items-center gap-2 font-medium text-forge-300 hover:text-forge-100"
                    >
                        <ArrowLeft className="size-3.5" /> Back to sign in
                    </Link>
                }
            >
                <CardContent>
                    <StatusMessage message={status} />
                    <div className="mb-5 grid size-10 place-items-center rounded-xl border border-forge-400/15 bg-forge-500/10">
                        <Mail className="size-5 text-forge-300" />
                    </div>
                    <form onSubmit={submit} className="space-y-5">
                        <FormField
                            id="email"
                            label="Email address"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            error={errors.email}
                            placeholder="you@company.com"
                            autoComplete="email"
                            autoFocus
                            required
                        />
                        <SubmitButton processing={processing}>
                            Send reset link
                        </SubmitButton>
                    </form>
                </CardContent>
            </AuthShell>
        </>
    );
}
