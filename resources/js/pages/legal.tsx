import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { ForgeLogo } from '@/components/forge-logo';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type LegalProps = { document: 'terms' | 'privacy' };

const copy = {
    terms: {
        title: 'Terms of Service',
        intro: 'These starter terms explain the rules for using GCode Forge Studio. Replace them with counsel-approved terms before production launch.',
        sections: [
            [
                'Using the service',
                'Use the Forge lawfully, protect your credentials, and verify generated machine instructions before operating physical equipment.',
            ],
            [
                'Your content',
                'You retain ownership of files and instructions you upload. You grant the service only the rights needed to process and store them.',
            ],
            [
                'Safety and availability',
                'Machining involves real-world risk. You remain responsible for simulation, inspection, machine setup, and safe operation.',
            ],
        ],
    },
    privacy: {
        title: 'Privacy Policy',
        intro: 'This starter policy summarizes the account data handled by GCode Forge Studio. Review it with counsel before production launch.',
        sections: [
            [
                'Information collected',
                'We store account details, authentication records, linked social identities, and workspace activity needed to operate the service.',
            ],
            [
                'How it is used',
                'Data is used to authenticate you, provide requested features, secure the platform, and improve reliability.',
            ],
            [
                'Your choices',
                'You may update profile information, secure your account with two-factor authentication, or request account deletion.',
            ],
        ],
    },
} as const;

export default function Legal({ document }: LegalProps) {
    const content = copy[document];

    return (
        <>
            <Head title={content.title} />
            <main className="relative min-h-screen overflow-hidden bg-[#07070a] px-5 py-10 sm:px-8">
                <div className="forge-grid pointer-events-none absolute inset-0" />
                <div className="relative mx-auto max-w-3xl">
                    <div className="mb-10 flex items-center justify-between">
                        <ForgeLogo />
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/">
                                <ArrowLeft className="size-3.5" /> Home
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent className="p-7 sm:p-10">
                            <p className="text-xs font-semibold tracking-[0.16em] text-forge-400 uppercase">
                                Legal
                            </p>
                            <h1 className="mt-3 text-3xl font-semibold tracking-[-0.03em] text-white">
                                {content.title}
                            </h1>
                            <p className="mt-4 leading-7 text-zinc-500">
                                {content.intro}
                            </p>
                            <div className="mt-9 space-y-8 border-t border-white/[0.07] pt-8">
                                {content.sections.map(([title, body]) => (
                                    <section key={title}>
                                        <h2 className="font-medium text-zinc-200">
                                            {title}
                                        </h2>
                                        <p className="mt-2 text-sm leading-7 text-zinc-600">
                                            {body}
                                        </p>
                                    </section>
                                ))}
                            </div>
                            <p className="mt-10 text-xs text-zinc-700">
                                Last updated: August 24, 2026
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
