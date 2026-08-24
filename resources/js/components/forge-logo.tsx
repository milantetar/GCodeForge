import { Link } from '@inertiajs/react';
import { Braces } from 'lucide-react';

import { cn } from '@/lib/utils';

type ForgeLogoProps = {
    compact?: boolean;
    className?: string;
};

export function ForgeLogo({ compact = false, className }: ForgeLogoProps) {
    return (
        <Link
            href="/"
            className={cn('group inline-flex items-center gap-2.5', className)}
        >
            <span className="relative grid size-9 place-items-center overflow-hidden rounded-xl border border-forge-400/25 bg-gradient-to-br from-forge-500/25 to-blue-500/10 shadow-[inset_0_1px_0_rgba(255,255,255,.14),0_8px_24px_rgba(109,40,217,.18)]">
                <span className="absolute inset-0 -translate-x-full animate-shimmer bg-gradient-to-r from-transparent via-white/10 to-transparent" />
                <Braces className="size-5 text-forge-300" strokeWidth={2.1} />
            </span>
            {!compact && (
                <span className="text-[15px] font-semibold tracking-tight text-white">
                    GCode <span className="text-zinc-500">Forge</span>
                </span>
            )}
        </Link>
    );
}
