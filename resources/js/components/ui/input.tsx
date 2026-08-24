import type { InputHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

function Input({ className, type, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            type={type}
            className={cn(
                'h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3.5 text-sm text-zinc-100 shadow-[inset_0_1px_0_rgba(255,255,255,.025)] outline-none transition placeholder:text-zinc-600 hover:border-white/15 focus:border-forge-400/60 focus:ring-4 focus:ring-forge-500/10 disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}

export { Input };
