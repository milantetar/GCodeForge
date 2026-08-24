import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { Check } from 'lucide-react';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

function Checkbox({ className, ...props }: ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            className={cn(
                'peer flex size-4.5 shrink-0 items-center justify-center rounded-[5px] border border-white/15 bg-black/25 text-white outline-none transition focus-visible:ring-2 focus-visible:ring-forge-400/60 data-[state=checked]:border-forge-500 data-[state=checked]:bg-forge-600',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator>
                <Check className="size-3.5" strokeWidth={2.5} />
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

export { Checkbox };
