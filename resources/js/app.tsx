import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { MotionConfig } from 'motion/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { AppErrorBoundary } from '@/components/app-error-boundary';

const appName = import.meta.env.VITE_APP_NAME || 'GCode Forge Studio';
const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx');

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        pages[`./pages/${name}.tsx`]().then((module) => module.default),
    setup({ el, App, props }) {
        createRoot(el).render(
            <AppErrorBoundary>
                <MotionConfig reducedMotion="user">
                    <App {...props} />
                    <Toaster
                        position="top-right"
                        theme="dark"
                        toastOptions={{ className: 'forge-toast' }}
                    />
                </MotionConfig>
            </AppErrorBoundary>,
        );
    },
    progress: {
        color: '#8b5cf6',
        showSpinner: false,
    },
});
