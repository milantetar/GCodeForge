import { Button } from '@/components/ui/button';

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-4" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.32 2.98-7.4Z"
            />
            <path
                fill="#34A853"
                d="M12 22c2.7 0 4.98-.9 6.63-2.43l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"
            />
            <path
                fill="#FBBC05"
                d="M6.39 13.86A6.02 6.02 0 0 1 6.07 12c0-.65.11-1.28.32-1.86V7.52H3.04A10 10 0 0 0 2 12c0 1.61.39 3.14 1.04 4.48l3.35-2.62Z"
            />
            <path
                fill="#EA4335"
                d="M12 6.01c1.47 0 2.79.51 3.83 1.5l2.87-2.88A9.64 9.64 0 0 0 12 2a10 10 0 0 0-8.96 5.52l3.35 2.62C7.18 7.77 9.39 6.01 12 6.01Z"
            />
        </svg>
    );
}

function GithubIcon() {
    return (
        <svg
            viewBox="0 0 24 24"
            className="size-4 fill-current"
            aria-hidden="true"
        >
            <path d="M12 .7a11.5 11.5 0 0 0-3.64 22.4c.58.1.79-.25.79-.56v-2.23c-3.22.7-3.9-1.36-3.9-1.36-.52-1.34-1.28-1.69-1.28-1.69-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.57-.3-5.27-1.29-5.27-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.16 1.18a10.98 10.98 0 0 1 5.75 0c2.2-1.49 3.16-1.18 3.16-1.18.63 1.59.23 2.76.11 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.71 5.39-5.29 5.68.42.36.79 1.06.79 2.14v3.25c0 .31.21.67.79.56A11.5 11.5 0 0 0 12 .7Z" />
        </svg>
    );
}

export function SocialButtons() {
    return (
        <div className="grid grid-cols-2 gap-3">
            <Button asChild variant="secondary">
                <a href="/auth/google/redirect">
                    <GoogleIcon /> Google
                </a>
            </Button>
            <Button asChild variant="secondary">
                <a href="/auth/github/redirect">
                    <GithubIcon /> GitHub
                </a>
            </Button>
        </div>
    );
}

export function OrDivider() {
    return (
        <div className="relative my-5">
            <div className="absolute inset-0 flex items-center">
                <span className="w-full border-t border-white/[0.07]" />
            </div>
            <div className="relative flex justify-center text-[11px] tracking-[0.12em] uppercase">
                <span className="bg-[#111116] px-3 text-zinc-700">
                    or continue with email
                </span>
            </div>
        </div>
    );
}
