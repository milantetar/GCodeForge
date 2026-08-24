import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { FormField } from '@/components/auth/form-field';
import { PasswordField } from '@/components/auth/password-field';
import { SocialButtons, OrDivider } from '@/components/auth/social-buttons';
import { StatusMessage } from '@/components/auth/status-message';
import { SubmitButton } from '@/components/auth/submit-button';
import { CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type LoginProps = {
    canResetPassword: boolean;
    status?: string;
};

export default function Login({ canResetPassword, status }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Sign in" />
            <AuthShell
                title="Welcome back"
                description="Sign in to continue building smarter machine workflows."
                eyebrow="Your workshop awaits"
                footer={
                    <>
                        New to the forge?{' '}
                        <Link
                            href="/register"
                            className="font-medium text-forge-300 hover:text-forge-100"
                        >
                            Create an account
                        </Link>
                    </>
                }
            >
                <CardContent>
                    <StatusMessage message={status} />
                    <SocialButtons />
                    <OrDivider />

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
                            placeholder="you@company.com"
                            autoComplete="email"
                            autoFocus
                            required
                        />
                        <PasswordField
                            id="password"
                            label="Password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            error={errors.password}
                            placeholder="Enter your password"
                            autoComplete="current-password"
                            required
                        />

                        <div className="flex items-center justify-between py-1">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData('remember', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="remember"
                                    className="cursor-pointer text-xs text-zinc-500"
                                >
                                    Keep me signed in
                                </Label>
                            </div>
                            {canResetPassword && (
                                <Link
                                    href="/forgot-password"
                                    className="text-xs font-medium text-forge-300 transition hover:text-forge-100"
                                >
                                    Forgot password?
                                </Link>
                            )}
                        </div>

                        <SubmitButton processing={processing}>
                            Sign in <ArrowRight className="size-4" />
                        </SubmitButton>
                    </form>
                </CardContent>
            </AuthShell>
        </>
    );
}
