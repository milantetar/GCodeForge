import { Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import type { PropsWithChildren } from 'react';
import type { PaginationLink, Project } from '@/types/gcode';

export const fieldClass =
    'mt-2 w-full rounded-xl border border-white/10 bg-[#111116] p-3 text-sm text-zinc-200 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-500/20';

export function Panel({ children }: PropsWithChildren) {
    return (
        <motion.section
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            className="forge-card rounded-2xl border border-white/10 p-5 sm:p-7"
        >
            {children}
        </motion.section>
    );
}
export function FormErrors({ errors }: { errors: Record<string, string> }) {
    return (
        Object.keys(errors).length > 0 && (
            <div
                role="alert"
                className="my-4 rounded-xl border border-red-400/20 bg-red-400/5 p-4 text-sm text-red-300"
            >
                {Object.entries(errors).map(([key, message]) => (
                    <p key={key}>{message}</p>
                ))}
            </div>
        )
    );
}
export function Pagination({ links }: { links: PaginationLink[] }) {
    return (
        <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2">
            {links.map((link, i) => {
                const label = link.label
                    .replace(/&laquo;/g, '←')
                    .replace(/&raquo;/g, '→');

                return link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-violet-500/20 text-violet-200' : 'text-zinc-400 hover:bg-white/5'}`}
                    >
                        {label}
                    </Link>
                ) : (
                    <span key={i} className="px-3 py-2 text-sm text-zinc-700">
                        {label}
                    </span>
                );
            })}
        </nav>
    );
}
export function ProjectCard({ project }: { project: Project }) {
    return (
        <Link
            href={`/projects/${project.id}`}
            className="group forge-card block rounded-2xl border border-white/10 p-6 transition hover:-translate-y-1 hover:border-violet-400/40 motion-reduce:transform-none"
        >
            <div className="flex items-start justify-between gap-3">
                <h3 className="font-medium text-zinc-100 group-hover:text-violet-200">
                    {project.name}
                </h3>
                <span className="rounded-full bg-white/5 px-2 py-1 text-xs text-zinc-400">
                    {project.status}
                </span>
            </div>
            <p className="mt-3 line-clamp-2 min-h-10 text-sm text-zinc-500">
                {project.description || 'Your next pattern starts here.'}
            </p>
            <div className="mt-6 flex justify-between text-xs text-zinc-500">
                <span>{project.conversions_count ?? 0} conversions</span>
                <span>
                    {project.machine_profile?.name ?? 'No machine profile'}
                </span>
            </div>
        </Link>
    );
}
