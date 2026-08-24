import { CircleAlert, CircleCheck } from 'lucide-react';

type StatusMessageProps = {
    message?: string;
    tone?: 'success' | 'error';
};

export function StatusMessage({
    message,
    tone = 'success',
}: StatusMessageProps) {
    if (!message) {
        return null;
    }

    const Icon = tone === 'success' ? CircleCheck : CircleAlert;

    return (
        <div
            className={
                tone === 'success'
                    ? 'mb-5 flex gap-2.5 rounded-xl border border-emerald-400/15 bg-emerald-400/[0.06] p-3 text-xs leading-5 text-emerald-300'
                    : 'mb-5 flex gap-2.5 rounded-xl border border-red-400/15 bg-red-400/[0.06] p-3 text-xs leading-5 text-red-300'
            }
        >
            <Icon className="mt-0.5 size-4 shrink-0" />
            {message}
        </div>
    );
}
