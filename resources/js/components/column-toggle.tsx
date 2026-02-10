import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ColumnDef } from '@/hooks/use-column-visibility';
import { Columns3 } from 'lucide-react';

interface ColumnToggleProps {
    columns: ColumnDef[];
    visibleKeys: Set<string>;
    onToggle: (key: string) => void;
}

export function ColumnToggle({ columns, visibleKeys, onToggle }: ColumnToggleProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="flex items-center gap-1">
                    <Columns3 size={14} />
                    <span>Columns</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {columns.map((col) => (
                    <DropdownMenuCheckboxItem key={col.key} checked={visibleKeys.has(col.key)} onCheckedChange={() => onToggle(col.key)}>
                        {col.label}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
