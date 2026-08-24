import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldX } from 'lucide-react';

import { ForgeLogo } from '@/components/forge-logo';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export default function Forbidden() {
    return (
        <>
            <Head title="Access denied" />
            <main className="relative grid min-h-screen place-items-center overflow-hidden bg-[#07070a] px-5">
                <div className="forge-grid absolute inset-0" />
                <div className="relative w-full max-w-md text-center">
                    <ForgeLogo className="mb-8 justify-center" />
                    <Card className="forge-glow">
                        <CardContent className="py-10">
                            <div className="mx-auto grid size-14 place-items-center rounded-2xl border border-red-400/15 bg-red-400/[0.07]">
                                <ShieldX className="size-6 text-red-300" />
                            </div>
                            <p className="mt-6 text-xs font-semibold tracking-[0.16em] text-red-400 uppercase">
                                403 · Restricted
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-white">
                                Access denied
                            </h1>
                            <p className="mt-3 text-sm leading-6 text-zinc-600">
                                Your current role does not have permission to
                                view this area. Ask a workspace administrator if
                                you need access.
                            </p>
                            <Button asChild className="mt-7">
                                <Link href="/dashboard">
                                    <ArrowLeft className="size-4" /> Return to
                                    workspace
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
