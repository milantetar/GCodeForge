import type { InputHTMLAttributes } from 'react';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FormFieldProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
};

export function FormField({ label, error, id, ...props }: FormFieldProps) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                aria-invalid={Boolean(error)}
                aria-describedby={error ? `${id}-error` : undefined}
                className={
                    error
                        ? 'border-red-400/45 focus:border-red-400/70'
                        : undefined
                }
                {...props}
            />
            {error && (
                <p id={`${id}-error`} className="text-xs text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
