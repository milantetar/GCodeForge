import { Slot } from '@radix-ui/react-slot';
import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import type { ButtonHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl font-medium transition-all duration-200 focus-visible:ring-2 focus-visible:ring-forge-400/70 focus-visible:ring-offset-2 focus-visible:ring-offset-[#09090c] disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                primary:
                    'bg-gradient-to-b from-forge-500 to-forge-700 text-white shadow-[0_10px_28px_rgba(109,40,217,.3),inset_0_1px_0_rgba(255,255,255,.28)] hover:-translate-y-0.5 hover:brightness-110 active:translate-y-0',
                secondary:
                    'border border-white/10 bg-white/[0.055] text-zinc-100 shadow-[inset_0_1px_0_rgba(255,255,255,.05)] hover:border-white/20 hover:bg-white/[0.09]',
                ghost: 'text-zinc-400 hover:bg-white/[0.06] hover:text-white',
                danger:
                    'border border-red-500/20 bg-red-500/10 text-red-300 hover:bg-red-500/15',
            },
            size: {
                sm: 'h-9 px-3.5 text-sm',
                md: 'h-11 px-5 text-sm',
                lg: 'h-12 px-6 text-[15px]',
                icon: 'size-10',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'md',
        },
    },
);

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
    };

function Button({
    className,
    variant,
    size,
    asChild = false,
    ...props
}: ButtonProps) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="button"
            className={cn(buttonVariants({ variant, size }), className)}
            {...props}
        />
    );
}

export { Button, buttonVariants };
