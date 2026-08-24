import { LoaderCircle } from 'lucide-react';
import type { ButtonHTMLAttributes } from 'react';

import { Button } from '@/components/ui/button';

type SubmitButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    processing?: boolean;
};

export function SubmitButton({
    children,
    processing,
    ...props
}: SubmitButtonProps) {
    return (
        <Button
            type="submit"
            className="w-full"
            disabled={processing}
            {...props}
        >
            {processing && <LoaderCircle className="size-4 animate-spin" />}
            {children}
        </Button>
    );
}
