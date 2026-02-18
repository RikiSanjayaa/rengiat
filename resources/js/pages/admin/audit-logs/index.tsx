import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Search, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type AuditActor = {
    id: number;
    name: string;
    username: string;
    role: string;
};

type AuditLogRecord = {
    id: number;
    actor_user_id: number;
    action: 'created' | 'updated' | 'deleted';
    auditable_type: string;
    auditable_id: number;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    created_at: string;
    actor: AuditActor | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedLogs = {
    data: AuditLogRecord[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
};

type Filters = {
    action: string;
    auditable_type: string;
    search: string;
    date_from: string;
    date_to: string;
};

type Props = {
    logs: PaginatedLogs;
    filters: Filters;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Log Audit',
        href: '/admin/audit-logs',
    },
];

const actionLabels: Record<
    string,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    created: { label: 'Dibuat', variant: 'default' },
    updated: { label: 'Diperbarui', variant: 'secondary' },
    deleted: { label: 'Dihapus', variant: 'destructive' },
};

const typeLabels: Record<string, string> = {
    rengiat_entry: 'Entri Rengiat',
    user: 'User',
    unit: 'Unit',
};

function formatDateTime(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function renderValues(values: Record<string, unknown> | null): React.ReactNode {
    if (!values || Object.keys(values).length === 0) {
        return <span className="text-muted-foreground italic">—</span>;
    }

    return (
        <div className="space-y-1">
            {Object.entries(values).map(([key, val]) => (
                <div key={key} className="flex gap-2 text-xs">
                    <span className="min-w-[100px] font-medium text-muted-foreground">
                        {key}:
                    </span>
                    <span className="break-all">
                        {val === null ? (
                            <em className="text-muted-foreground">null</em>
                        ) : (
                            String(val)
                        )}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function AuditLogPage({ logs, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [actionFilter, setActionFilter] = useState(filters.action);
    const [typeFilter, setTypeFilter] = useState(filters.auditable_type);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [expandedRow, setExpandedRow] = useState<number | null>(null);

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/admin/audit-logs',
            {
                search: search || undefined,
                action: actionFilter || undefined,
                auditable_type: typeFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setActionFilter('');
        setTypeFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/admin/audit-logs', {}, { preserveState: true });
    };

    const hasActiveFilters =
        search || actionFilter || typeFilter || dateFrom || dateTo;

    const toggleRow = (id: number) => {
        setExpandedRow(expandedRow === id ? null : id);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Log Audit" />
            <div className="mx-auto w-full max-w-7xl space-y-4 p-4">
                {/* Header */}
                <div className="rounded-xl border bg-card p-4">
                    <div>
                        <h1 className="font-semibold">Log Audit</h1>
                        <p className="text-sm text-muted-foreground">
                            Riwayat semua aktivitas yang terjadi di sistem.
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <form
                    onSubmit={applyFilters}
                    className="rounded-xl border bg-card p-4"
                >
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama user..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>

                        <select
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            value={actionFilter}
                            onChange={(e) => setActionFilter(e.target.value)}
                        >
                            <option value="">Semua Aksi</option>
                            <option value="created">Dibuat</option>
                            <option value="updated">Diperbarui</option>
                            <option value="deleted">Dihapus</option>
                        </select>

                        <select
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value)}
                        >
                            <option value="">Semua Tipe</option>
                            <option value="rengiat_entry">Entri Rengiat</option>
                            <option value="user">User</option>
                            <option value="unit">Unit</option>
                        </select>

                        <Input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            placeholder="Dari tanggal"
                        />

                        <Input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            placeholder="Sampai tanggal"
                        />
                    </div>

                    <div className="mt-3 flex gap-2">
                        <Button type="submit" size="sm">
                            <Search className="mr-1 h-3.5 w-3.5" />
                            Filter
                        </Button>
                        {hasActiveFilters && (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={clearFilters}
                            >
                                <X className="mr-1 h-3.5 w-3.5" />
                                Reset
                            </Button>
                        )}
                    </div>
                </form>

                {/* Table */}
                <div className="overflow-x-auto rounded-xl border bg-card">
                    <table className="w-full border-collapse text-sm">
                        <thead>
                            <tr className="bg-muted/60">
                                <th className="w-10 border px-3 py-2 text-left"></th>
                                <th className="border px-3 py-2 text-left">
                                    Waktu
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    User
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Aksi
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Tipe
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    ID Objek
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="border px-3 py-8 text-center text-muted-foreground"
                                    >
                                        Belum ada log audit.
                                    </td>
                                </tr>
                            )}
                            {logs.data.map((log) => {
                                const actionInfo = actionLabels[log.action] ?? {
                                    label: log.action,
                                    variant: 'outline' as const,
                                };
                                const isExpanded = expandedRow === log.id;

                                return (
                                    <>
                                        <tr
                                            key={log.id}
                                            className="cursor-pointer transition-colors hover:bg-muted/30"
                                            onClick={() => toggleRow(log.id)}
                                        >
                                            <td className="border px-3 py-2 text-center">
                                                {isExpanded ? (
                                                    <ChevronUp className="mx-auto h-4 w-4 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="mx-auto h-4 w-4 text-muted-foreground" />
                                                )}
                                            </td>
                                            <td className="border px-3 py-2 whitespace-nowrap">
                                                {formatDateTime(log.created_at)}
                                            </td>
                                            <td className="border px-3 py-2">
                                                <div>
                                                    <span className="font-medium">
                                                        {log.actor?.name ??
                                                            'Sistem'}
                                                    </span>
                                                    {log.actor && (
                                                        <span className="ml-1 text-xs text-muted-foreground">
                                                            @
                                                            {log.actor.username}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="border px-3 py-2">
                                                <Badge
                                                    variant={actionInfo.variant}
                                                >
                                                    {actionInfo.label}
                                                </Badge>
                                            </td>
                                            <td className="border px-3 py-2">
                                                {typeLabels[
                                                    log.auditable_type
                                                ] ?? log.auditable_type}
                                            </td>
                                            <td className="border px-3 py-2 font-mono text-xs">
                                                #{log.auditable_id}
                                            </td>
                                        </tr>
                                        {isExpanded && (
                                            <tr key={`${log.id}-detail`}>
                                                <td
                                                    colSpan={6}
                                                    className="border bg-muted/20 px-4 py-3"
                                                >
                                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                        <div>
                                                            <h4 className="mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                                Nilai Lama
                                                            </h4>
                                                            <div className="rounded-md border bg-background p-3">
                                                                {renderValues(
                                                                    log.old_values,
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h4 className="mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                                Nilai Baru
                                                            </h4>
                                                            <div className="rounded-md border bg-background p-3">
                                                                {renderValues(
                                                                    log.new_values,
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {logs.last_page > 1 && (
                    <div className="flex items-center justify-between rounded-xl border bg-card px-4 py-3">
                        <p className="text-sm text-muted-foreground">
                            Menampilkan{' '}
                            {(logs.current_page - 1) * logs.per_page + 1} -{' '}
                            {Math.min(
                                logs.current_page * logs.per_page,
                                logs.total,
                            )}{' '}
                            dari {logs.total} log
                        </p>
                        <div className="flex gap-1">
                            {logs.links.map((link, idx) => (
                                <Button
                                    key={idx}
                                    size="sm"
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    disabled={!link.url}
                                    onClick={() => {
                                        if (link.url) {
                                            router.get(
                                                link.url,
                                                {},
                                                { preserveState: true },
                                            );
                                        }
                                    }}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                    className="min-w-[36px]"
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
