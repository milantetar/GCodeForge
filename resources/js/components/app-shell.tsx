import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    FileText,
    LayoutDashboard,
    LogOut,
    Menu,
    Settings,
    Shield,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { PropsWithChildren, ReactNode } from 'react';

import { ForgeLogo } from '@/components/forge-logo';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

type AppShellProps = PropsWithChildren<{ title: string; actions?: ReactNode }>;

const baseLinks = [
    { href: '/dashboard', label: 'Workspace', icon: LayoutDashboard },
    { href: '/settings/profile', label: 'Profile', icon: Settings },
    { href: '/settings/security', label: 'Security', icon: Shield },
];

export function AppShell({ title, actions, children }: AppShellProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const links = auth.permissions.includes('cms.view')
        ? [
              ...baseLinks,
              {
                  href: '/admin/cms/pages',
                  label: 'Content',
                  icon: FileText,
              },
          ]
        : baseLinks;

    const nav = (
        <>
            {links.map(({ href, label, icon: Icon }) => {
                const active = window.location.pathname === href;

                return (
                    <Link
                        key={href}
                        href={href}
                        onClick={() => setMobileOpen(false)}
                        className={cn(
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
                            active
                                ? 'text-forge-200 border border-forge-400/15 bg-forge-500/10'
                                : 'text-zinc-500 hover:bg-white/[0.04] hover:text-zinc-200',
                        )}
                    >
                        <Icon className="size-4" /> {label}
                    </Link>
                );
            })}
        </>
    );

    return (
        <main className="min-h-screen bg-[#07070a]">
            <div className="forge-grid pointer-events-none fixed inset-0" />
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-white/[0.06] bg-[#09090d]/90 p-5 backdrop-blur-xl lg:flex lg:flex-col">
                <ForgeLogo className="px-1" />
                <nav className="mt-10 space-y-1">{nav}</nav>
                <div className="mt-auto rounded-xl border border-white/[0.07] bg-white/[0.025] p-3">
                    <div className="flex items-center gap-3">
                        <div className="grid size-9 place-items-center rounded-lg bg-forge-500/15 text-xs font-semibold text-forge-300">
                            {auth.user?.name.slice(0, 2).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-zinc-200">
                                {auth.user?.name}
                            </p>
                            <p className="truncate text-[11px] text-zinc-600">
                                {auth.roles[0] ?? 'user'}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 text-zinc-600"
                            onClick={() => router.post('/logout')}
                            aria-label="Sign out"
                        >
                            <LogOut className="size-4" />
                        </Button>
                    </div>
                </div>
            </aside>

            {mobileOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
                    onClick={() => setMobileOpen(false)}
                >
                    <aside
                        className="h-full w-72 border-r border-white/10 bg-[#0b0b0f] p-5"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="flex items-center justify-between">
                            <ForgeLogo />
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setMobileOpen(false)}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                        <nav className="mt-10 space-y-1">{nav}</nav>
                    </aside>
                </div>
            )}

            <div className="relative lg:pl-64">
                <header className="sticky top-0 z-20 flex h-18 items-center justify-between border-b border-white/[0.06] bg-[#07070a]/80 px-5 backdrop-blur-xl sm:px-8">
                    <div className="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="size-5" />
                        </Button>
                        <h1 className="text-base font-semibold text-zinc-100">
                            {title}
                        </h1>
                    </div>
                    <div className="flex items-center gap-2">
                        {actions}
                        <Button
                            variant="ghost"
                            size="icon"
                            className="text-zinc-600"
                            aria-label="Notifications"
                        >
                            <Bell className="size-4" />
                        </Button>
                    </div>
                </header>
                <div className="mx-auto max-w-7xl p-5 sm:p-8">{children}</div>
            </div>
        </main>
    );
}
