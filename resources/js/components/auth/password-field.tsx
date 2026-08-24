import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import type { InputHTMLAttributes } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PasswordFieldProps = Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'type'
> & {
    label: string;
    error?: string;
    hint?: string;
};

export function PasswordField({
    label,
    error,
    hint,
    id,
    ...props
}: PasswordFieldProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <Label htmlFor={id}>{label}</Label>
                {hint && (
                    <span className="text-[11px] text-zinc-600">{hint}</span>
                )}
            </div>
            <div className="relative">
                <Input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={error ? 'border-red-400/45 pr-11' : 'pr-11'}
                    {...props}
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute top-0 right-0 h-11 text-zinc-600 hover:bg-transparent hover:text-zinc-300"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                >
                    {visible ? (
                        <EyeOff className="size-4" />
                    ) : (
                        <Eye className="size-4" />
                    )}
                </Button>
            </div>
            {error && (
                <p id={`${id}-error`} className="text-xs text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
