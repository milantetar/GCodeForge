import { Input } from '@/components/ui/input';

export function PageSettingsFields({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    let settings: Record<string, unknown>;

    try {
        const parsed: unknown = JSON.parse(value);

        if (!parsed || typeof parsed !== 'object') {
return null;
}

        settings = Array.isArray(parsed)
            ? {}
            : (parsed as Record<string, unknown>);
    } catch {
        return (
            <p className="text-sm text-amber-300">
                Page settings must be valid JSON.
            </p>
        );
    }

    const set = (key: string, next: unknown) =>
        onChange(JSON.stringify({ ...settings, [key]: next }));
    const links = Array.isArray(settings.navigation)
        ? settings.navigation.filter(
              (link): link is Record<string, unknown> =>
                  !!link && typeof link === 'object' && !Array.isArray(link),
          )
        : [];

    return (
        <div className="space-y-4">
            <label className="block text-xs text-zinc-400">
                Color preset
                <select
                    className="mt-2 w-full rounded-lg border border-white/10 bg-[#111116] p-3 text-sm"
                    value={
                        typeof settings.theme === 'string'
                            ? settings.theme
                            : 'forge-dark'
                    }
                    onChange={(event) => set('theme', event.target.value)}
                >
                    <option value="forge-dark">Forge violet</option>
                    <option value="midnight">Midnight blue</option>
                    <option value="forest">Forest emerald</option>
                </select>
            </label>
            <label className="block text-xs text-zinc-400">
                Footer name
                <Input
                    className="mt-2"
                    value={
                        typeof settings.footer_text === 'string'
                            ? settings.footer_text
                            : ''
                    }
                    onChange={(event) => set('footer_text', event.target.value)}
                />
            </label>
            <p className="text-xs text-zinc-500">
                Header links (internal paths or #sections). Leave empty for
                automatic section navigation.
            </p>
            {links.map((link, index) => (
                <div
                    key={index}
                    className="space-y-2 rounded-xl border border-white/10 p-3"
                >
                    {['label', 'href'].map((key) => (
                        <label
                            key={key}
                            className="block text-xs text-zinc-500"
                        >
                            {key}
                            <Input
                                value={
                                    typeof link[key] === 'string'
                                        ? link[key]
                                        : ''
                                }
                                onChange={(event) =>
                                    set(
                                        'navigation',
                                        links.map((entry, i) =>
                                            i === index
                                                ? {
                                                      ...entry,
                                                      [key]: event.target.value,
                                                  }
                                                : entry,
                                        ),
                                    )
                                }
                            />
                        </label>
                    ))}
                    <button
                        type="button"
                        className="text-xs text-red-300"
                        onClick={() =>
                            set(
                                'navigation',
                                links.filter((_, i) => i !== index),
                            )
                        }
                    >
                        Remove link
                    </button>
                </div>
            ))}
            {links.length < 6 && (
                <button
                    type="button"
                    className="text-sm text-violet-300"
                    onClick={() =>
                        set('navigation', [
                            ...links,
                            { label: 'New link', href: '/pages/about' },
                        ])
                    }
                >
                    + Add navigation link
                </button>
            )}
        </div>
    );
}
