import { Head, Link, usePoll } from '@inertiajs/react';
import { Download, LoaderCircle } from 'lucide-react';
import { useEffect } from 'react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Panel } from '@/components/workspace';
import type { Conversion } from '@/types/gcode';

export default function ConversionPage({
    conversion,
    outputPreview,
}: {
    conversion: Conversion;
    outputPreview: string | null;
}) {
    const pending =
        conversion.status === 'pending' || conversion.status === 'processing';
    const { start, stop } = usePoll(
        2500,
        { only: ['conversion', 'outputPreview'] },
        { autoStart: false },
    );
    useEffect(() => {
        if (pending) {
start();
} else {
stop();
}

        return stop;
    }, [pending, start, stop]);

    return (
        <>
            <Head title="Conversion result" />
            <AppShell
                title="Conversion result"
                actions={
                    conversion.download_url && (
                        <Button asChild size="sm">
                            <a href={conversion.download_url}>
                                <Download className="size-4" /> Download .nc
                            </a>
                        </Button>
                    )
                }
            >
                {conversion.project && (
                    <Link
                        className="text-sm text-violet-300"
                        href={`/projects/${conversion.project.id}`}
                    >
                        ← {conversion.project.name}
                    </Link>
                )}
                <div className="my-7 flex items-center gap-4">
                    {pending && (
                        <LoaderCircle className="size-6 animate-spin text-violet-400 motion-reduce:animate-none" />
                    )}
                    <div>
                        <h2 className="text-2xl text-white capitalize">
                            {conversion.status === 'pending'
                                ? 'Waiting for a worker'
                                : conversion.status === 'processing'
                                  ? 'Transforming your G-code'
                                  : `Conversion ${conversion.status}`}
                        </h2>
                        <p className="mt-2 font-mono text-xs text-zinc-600">
                            {conversion.id}
                        </p>
                    </div>
                </div>
                {pending && (
                    <p role="status" className="mb-6 text-sm text-zinc-500">
                        This page refreshes automatically. You can leave and
                        return from the project history. If it stays pending,
                        ensure the conversions queue worker is running.
                    </p>
                )}
                {conversion.error_message && (
                    <p
                        role="alert"
                        className="mb-6 rounded-xl border border-red-400/20 bg-red-400/5 p-5 text-sm text-red-300"
                    >
                        {conversion.error_message}
                    </p>
                )}
                <div className="mb-6 grid gap-4 sm:grid-cols-3">
                    {Object.entries(conversion.metrics).map(([key, value]) => (
                        <Panel key={key}>
                            <p className="text-xs text-zinc-500 capitalize">
                                {key.replaceAll('_', ' ')}
                            </p>
                            <p className="mt-3 text-2xl text-white">
                                {value ?? '—'}
                            </p>
                        </Panel>
                    ))}
                </div>
                {conversion.warnings.map((warning, index) => (
                    <p key={index} className="mb-4 text-sm text-amber-200">
                        {warning}
                    </p>
                ))}
                <Panel>
                    <h3 className="text-zinc-200">Run configuration</h3>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        {Object.entries(conversion.options).map(
                            ([key, value]) => (
                                <div
                                    key={key}
                                    className="flex justify-between gap-4 text-xs"
                                >
                                    <dt className="text-zinc-500">{key}</dt>
                                    <dd className="font-mono text-zinc-300">
                                        {value}
                                    </dd>
                                </div>
                            ),
                        )}
                    </dl>
                </Panel>
                {outputPreview !== null && (
                    <div className="mt-6">
                        <Panel>
                            <h3 className="text-zinc-200">Output preview</h3>
                            <p className="mt-2 text-xs text-zinc-500">
                                First 64 KiB. Download the full file for
                                inspection and simulation.
                            </p>
                            <pre className="mt-5 max-h-[36rem] overflow-auto rounded-xl bg-black/30 p-5 font-mono text-xs leading-6 text-violet-200">
                                {outputPreview}
                            </pre>
                        </Panel>
                    </div>
                )}
            </AppShell>
        </>
    );
}
