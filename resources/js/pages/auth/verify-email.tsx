import { Head, router, useForm } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';

import { AuthShell } from '@/components/auth/auth-shell';
import { StatusMessage } from '@/components/auth/status-message';
import { SubmitButton } from '@/components/auth/submit-button';
import { Button } from '@/components/ui/button';
import { CardContent } from '@/components/ui/card';

type VerifyEmailProps = { status?: string };

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { post, processing } = useForm({});

    return (
        <>
            <Head title="Verify email" />
            <AuthShell
                title="Check your inbox"
                description="Verify your email address before entering the Forge workspace."
                eyebrow="One last step"
            >
                <CardContent className="text-center">
                    <StatusMessage
                        message={
                            status === 'verification-link-sent'
                                ? 'A fresh verification link has been sent to your email address.'
                                : undefined
                        }
                    />
                    <div className="mx-auto mb-5 grid size-12 place-items-center rounded-2xl border border-forge-400/15 bg-forge-500/10">
                        <MailCheck className="size-6 text-forge-300" />
                    </div>
                    <p className="mb-6 text-sm leading-6 text-zinc-500">
                        Didn&apos;t receive it? Check your spam folder or
                        request a fresh link.
                    </p>
                    <SubmitButton
                        processing={processing}
                        onClick={() => post('/email/verification-notification')}
                    >
                        Resend verification email
                    </SubmitButton>
                    <Button
                        variant="ghost"
                        className="mt-2 w-full text-zinc-600"
                        onClick={() => router.post('/logout')}
                    >
                        Sign out
                    </Button>
                </CardContent>
            </AuthShell>
        </>
    );
}
