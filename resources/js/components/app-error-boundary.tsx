import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';

export class AppErrorBoundary extends Component<
    { children: ReactNode },
    { failed: boolean }
> {
    state = { failed: false };
    static getDerivedStateFromError() {
        return { failed: true };
    }
    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('Page rendering failed', error, info.componentStack);
    }
    render() {
        if (!this.state.failed) {
return this.props.children;
}

        return (
            <main className="grid min-h-screen place-items-center bg-[#07070a] p-8 text-zinc-200">
                <section className="forge-card max-w-lg rounded-2xl p-8">
                    <h1 className="text-2xl font-semibold">
                        This page couldn’t be displayed.
                    </h1>
                    <p className="my-4 text-zinc-400">
                        Try reloading. If the problem continues, share the
                        browser console error with the developer.
                    </p>
                    <button
                        className="rounded-lg bg-violet-600 px-5 py-3"
                        onClick={() => window.location.reload()}
                    >
                        Reload page
                    </button>
                    <a href="/" className="ml-5 underline">
                        Go home
                    </a>
                </section>
            </main>
        );
    }
}
