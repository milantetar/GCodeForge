import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth/auth-shell';
import { FormField } from '@/components/auth/form-field';
import { PasswordField } from '@/components/auth/password-field';
import { OrDivider, SocialButtons } from '@/components/auth/social-buttons';
import { SubmitButton } from '@/components/auth/submit-button';
import { CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Create account" />
            <AuthShell
                title="Create your workspace"
                description="Start forging precise, reliable machine instructions in minutes."
                eyebrow="Free to get started"
                footer={
                    <>
                        Already have an account?{' '}
                        <Link
                            href="/login"
                            className="font-medium text-forge-300 hover:text-forge-100"
                        >
                            Sign in
                        </Link>
                    </>
                }
            >
                <CardContent>
                    <SocialButtons />
                    <OrDivider />

                    <form onSubmit={submit} className="space-y-4">
                        <FormField
                            id="name"
                            label="Full name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            error={errors.name}
                            placeholder="Alex Morgan"
                            autoComplete="name"
                            autoFocus
                            required
                        />
                        <FormField
                            id="email"
                            label="Work email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            error={errors.email}
                            placeholder="alex@company.com"
                            autoComplete="email"
                            required
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <PasswordField
                                id="password"
                                label="Password"
                                value={data.password}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                error={errors.password}
                                placeholder="8+ characters"
                                autoComplete="new-password"
                                required
                            />
                            <PasswordField
                                id="password_confirmation"
                                label="Confirm"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                placeholder="Repeat password"
                                autoComplete="new-password"
                                required
                            />
                        </div>

                        <div>
                            <div className="flex items-start gap-2.5 pt-1">
                                <Checkbox
                                    id="terms"
                                    className="mt-0.5"
                                    checked={data.terms}
                                    onCheckedChange={(checked) =>
                                        setData('terms', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="terms"
                                    className="cursor-pointer text-xs leading-5 text-zinc-500"
                                >
                                    I agree to the{' '}
                                    <Link
                                        href="/terms"
                                        className="text-zinc-300 hover:text-white"
                                    >
                                        Terms
                                    </Link>{' '}
                                    and{' '}
                                    <Link
                                        href="/privacy"
                                        className="text-zinc-300 hover:text-white"
                                    >
                                        Privacy Policy
                                    </Link>
                                    .
                                </Label>
                            </div>
                            {errors.terms && (
                                <p className="mt-2 text-xs text-red-400">
                                    {errors.terms}
                                </p>
                            )}
                        </div>

                        <SubmitButton processing={processing}>
                            Create account <ArrowRight className="size-4" />
                        </SubmitButton>
                    </form>
                </CardContent>
            </AuthShell>
        </>
    );
}
