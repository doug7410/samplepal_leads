import { useCallback, useState } from 'react';

export interface ColumnDef {
    key: string;
    label: string;
    defaultVisible?: boolean;
}

interface UseColumnVisibilityOptions {
    storageKey: string;
    columns: ColumnDef[];
}

export function useColumnVisibility({ storageKey, columns }: UseColumnVisibilityOptions) {
    const [visibleKeys, setVisibleKeys] = useState<Set<string>>(() => {
        if (typeof window === 'undefined') {
            return new Set(columns.filter((c) => c.defaultVisible !== false).map((c) => c.key));
        }
        const stored = localStorage.getItem(storageKey);
        if (stored) {
            try {
                return new Set(JSON.parse(stored) as string[]);
            } catch {
                // fall through to defaults
            }
        }
        return new Set(columns.filter((c) => c.defaultVisible !== false).map((c) => c.key));
    });

    const isVisible = useCallback((key: string) => visibleKeys.has(key), [visibleKeys]);

    const toggle = useCallback(
        (key: string) => {
            setVisibleKeys((prev) => {
                const next = new Set(prev);
                if (next.has(key)) {
                    next.delete(key);
                } else {
                    next.add(key);
                }
                localStorage.setItem(storageKey, JSON.stringify([...next]));
                return next;
            });
        },
        [storageKey],
    );

    return { visibleKeys, isVisible, toggle, columns };
}
