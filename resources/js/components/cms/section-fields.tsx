import { Input } from '@/components/ui/input';
import type { CmsSectionType } from '@/types';

const common = ['eyebrow', 'title', 'description'];
const fields: Record<CmsSectionType, string[]> = {
    hero: [...common, 'accent', 'primary_label', 'secondary_label'],
    feature_grid: common,
    workflow: common,
    security: common,
    cta: [...common, 'button_label'],
    rich_text: [...common, 'body'],
    stats: common,
    custom: [...common, 'body'],
};

export function SectionFields({
    type,
    value,
    onChange,
}: {
    type: CmsSectionType;
    value: string;
    onChange: (value: string) => void;
}) {
    let content: Record<string, unknown>;

    try {
        const parsed: unknown = JSON.parse(value);

        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
return null;
}

        content = parsed as Record<string, unknown>;
    } catch {
        return (
            <p className="text-sm text-amber-300">
                Fix the JSON below to use the field editor.
            </p>
        );
    }

    const set = (key: string, next: unknown) =>
        onChange(JSON.stringify({ ...content, [key]: next }, null, 2));
    const listKey =
        type === 'rich_text' || type === 'custom' ? 'blocks' : 'items';
    const entries = Array.isArray(content[listKey])
        ? content[listKey].filter(
              (entry): entry is Record<string, unknown> =>
                  !!entry && typeof entry === 'object' && !Array.isArray(entry),
          )
        : [];
    const listFields =
        type === 'stats'
            ? ['value', 'label']
            : listKey === 'blocks'
              ? ['title', 'body']
              : [
                    'title',
                    'description',
                    ...(type === 'feature_grid' ? ['icon'] : []),
                ];

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {fields[type].map((key) => (
                <label key={key} className="space-y-2 text-xs text-zinc-400">
                    <span className="capitalize">
                        {key.replaceAll('_', ' ')}
                    </span>
                    <Input
                        value={
                            typeof content[key] === 'string' ? content[key] : ''
                        }
                        onChange={(event) => set(key, event.target.value)}
                    />
                </label>
            ))}
            {type === 'hero' && (
                <label className="space-y-2 text-xs text-zinc-400 sm:col-span-2">
                    Rotating phrases (one per line)
                    <textarea
                        className="mt-2 min-h-24 w-full rounded-xl border border-white/10 bg-black/30 p-3"
                        value={
                            Array.isArray(content.rotating_phrases)
                                ? content.rotating_phrases.join('\n')
                                : ''
                        }
                        onChange={(event) =>
                            set(
                                'rotating_phrases',
                                event.target.value.split('\n'),
                            )
                        }
                    />
                </label>
            )}
            {[
                'feature_grid',
                'workflow',
                'stats',
                'rich_text',
                'custom',
            ].includes(type) && (
                <div className="space-y-3 sm:col-span-2">
                    {entries.map((entry, index) => (
                        <div
                            key={index}
                            className="space-y-3 rounded-xl border border-white/10 p-4"
                        >
                            {listFields.map((key) => (
                                <label
                                    key={key}
                                    className="block text-xs text-zinc-500 capitalize"
                                >
                                    {key}
                                    <Input
                                        className="mt-2"
                                        value={
                                            typeof entry[key] === 'string'
                                                ? entry[key]
                                                : ''
                                        }
                                        onChange={(event) =>
                                            set(
                                                listKey,
                                                entries.map((item, i) =>
                                                    i === index
                                                        ? {
                                                              ...item,
                                                              [key]: event
                                                                  .target.value,
                                                          }
                                                        : item,
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
                                        listKey,
                                        entries.filter((_, i) => i !== index),
                                    )
                                }
                            >
                                Remove item
                            </button>
                        </div>
                    ))}
                    <button
                        type="button"
                        className="text-sm text-violet-300"
                        onClick={() =>
                            set(listKey, [
                                ...entries,
                                Object.fromEntries(
                                    listFields.map((key) => [key, '']),
                                ),
                            ])
                        }
                    >
                        + Add content item
                    </button>
                </div>
            )}
        </div>
    );
}
