import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import { useEffect, useState } from 'react';

export function RotatingText({ phrases }: { phrases: string[] }) {
    const reduced = useReducedMotion();
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    useEffect(() => {
        if (reduced || paused || phrases.length < 2) {
return;
}

        const timer = window.setInterval(() => {
            if (!document.hidden) {
setIndex((current) => (current + 1) % phrases.length);
}
        }, 4000);

        return () => window.clearInterval(timer);
    }, [reduced, paused, phrases.length]);

    return (
        <span className="relative mt-2 block">
            <span className="sr-only">{phrases.join(' ')}</span>
            <span aria-hidden="true" className="grid">
                {phrases.map((phrase, i) => (
                    <span key={i} className="invisible col-start-1 row-start-1">
                        {phrase}
                    </span>
                ))}
                <AnimatePresence mode="wait" initial={false}>
                    <motion.span
                        key={reduced ? 0 : index}
                        className="col-start-1 row-start-1 bg-gradient-to-r from-forge-300 via-violet-400 to-blue-400 bg-clip-text text-transparent"
                        initial={{ opacity: 0, y: reduced ? 0 : 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: reduced ? 0 : -12 }}
                        transition={{ duration: reduced ? 0 : 0.3 }}
                    >
                        {phrases[(reduced ? 0 : index) % phrases.length]}
                    </motion.span>
                </AnimatePresence>
            </span>
            {!reduced && phrases.length > 1 && (
                <button
                    type="button"
                    onClick={() => setPaused(!paused)}
                    aria-pressed={paused}
                    className="mt-4 block w-full text-center text-xs font-normal tracking-normal text-zinc-500 hover:text-zinc-200 focus-visible:outline focus-visible:outline-forge-400"
                >
                    {paused
                        ? 'Resume headline animation'
                        : 'Pause headline animation'}
                </button>
            )}
        </span>
    );
}
