import { Head, router } from '@inertiajs/react';
import { type ChangeEvent, useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type UnitFilter = {
    id: number;
    name: string;
};

type SubditFilter = {
    id: number;
    name: string;
};

type ReportEntry = {
    id: number;
    time_start: string | null;
    description: string;
    has_attachment: boolean;
};

type ReportCell = {
    unit_id: number;
    entries: ReportEntry[];
};

type ReportRow = {
    subdit_id: number;
    subdit_name: string;
    cells: ReportCell[];
};

type ReportDay = {
    date: string;
    header_line: string;
    rows: ReportRow[];
};

type ReportPayload = {
    title: string;
    units: Array<{
        id: number;
        name: string;
        order_index: number;
    }>;
    days: ReportDay[];
};

type PageProps = {
    filters: {
        start_date: string;
        end_date: string | null;
        subdit_id: number | null;
        unit_id: number | null;
        keyword: string | null;
    };
    filterSubdits: SubditFilter[];
    filterUnits: UnitFilter[];
    report: ReportPayload;
    canExport: boolean;
    exportUrl: string;
};

type DatePreset =
    | ''
    | 'today'
    | 'this_week'
    | 'last_week'
    | 'this_month'
    | 'last_month';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Generator Laporan',
        href: '/reports',
    },
];

const formatDateInput = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const resolvePresetRange = (
    preset: Exclude<DatePreset, ''>,
): { startDate: string; endDate: string } => {
    const today = new Date();
    const base = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    if (preset === 'today') {
        const date = formatDateInput(base);

        return {
            startDate: date,
            endDate: date,
        };
    }

    if (preset === 'this_week' || preset === 'last_week') {
        const dayOfWeek = base.getDay();
        const daysSinceMonday = (dayOfWeek + 6) % 7;

        const startOfThisWeek = new Date(base);
        startOfThisWeek.setDate(startOfThisWeek.getDate() - daysSinceMonday);

        const start = new Date(startOfThisWeek);
        if (preset === 'last_week') {
            start.setDate(start.getDate() - 7);
        }

        const end = new Date(start);
        end.setDate(end.getDate() + 6);

        return {
            startDate: formatDateInput(start),
            endDate: formatDateInput(end),
        };
    }

    if (preset === 'this_month') {
        const start = new Date(base.getFullYear(), base.getMonth(), 1);
        const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);

        return {
            startDate: formatDateInput(start),
            endDate: formatDateInput(end),
        };
    }

    const start = new Date(base.getFullYear(), base.getMonth() - 1, 1);
    const end = new Date(base.getFullYear(), base.getMonth(), 0);

    return {
        startDate: formatDateInput(start),
        endDate: formatDateInput(end),
    };
};

export default function ReportGeneratorPage({
    filters,
    filterSubdits,
    filterUnits,
    report,
    canExport,
    exportUrl,
}: PageProps) {
    const [datePreset, setDatePreset] = useState<DatePreset>('');
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date ?? '');
    const [subditId, setSubditId] = useState(
        filters.subdit_id ? String(filters.subdit_id) : '',
    );
    const [unitId, setUnitId] = useState(
        filters.unit_id ? String(filters.unit_id) : '',
    );
    const [keyword, setKeyword] = useState(filters.keyword ?? '');

    useEffect(() => {
        setDatePreset('');
        setStartDate(filters.start_date);
        setEndDate(filters.end_date ?? '');
        setSubditId(filters.subdit_id ? String(filters.subdit_id) : '');
        setUnitId(filters.unit_id ? String(filters.unit_id) : '');
        setKeyword(filters.keyword ?? '');
    }, [
        filters.end_date,
        filters.keyword,
        filters.start_date,
        filters.subdit_id,
        filters.unit_id,
    ]);

    const normalizeFilterPayload = (
        input: {
            start_date: string;
            end_date: string;
            subdit_id: string;
            unit_id: string;
            keyword: string;
        },
    ) => {
        const normalizedStartDate = input.start_date.trim();
        const normalizedEndDate = input.end_date.trim();
        const normalizedSubditId = input.subdit_id.trim();
        const normalizedUnitId = input.unit_id.trim();
        const normalizedKeyword = input.keyword.trim();

        return {
            start_date: normalizedStartDate,
            end_date:
                normalizedEndDate !== '' &&
                normalizedEndDate !== normalizedStartDate
                    ? normalizedEndDate
                    : undefined,
            subdit_id: normalizedSubditId || undefined,
            unit_id: normalizedUnitId || undefined,
            keyword: normalizedKeyword || undefined,
        };
    };

    const currentQuery = useMemo(
        () =>
            normalizeFilterPayload({
                start_date: startDate,
                end_date: endDate,
                subdit_id: subditId,
                unit_id: unitId,
                keyword,
            }),
        [endDate, keyword, startDate, subditId, unitId],
    );

    const serverQuery = useMemo(
        () =>
            normalizeFilterPayload({
                start_date: filters.start_date,
                end_date: filters.end_date ?? '',
                subdit_id: filters.subdit_id ? String(filters.subdit_id) : '',
                unit_id: filters.unit_id ? String(filters.unit_id) : '',
                keyword: filters.keyword ?? '',
            }),
        [
            filters.end_date,
            filters.keyword,
            filters.start_date,
            filters.subdit_id,
            filters.unit_id,
        ],
    );

    const currentQuerySignature = JSON.stringify(currentQuery);
    const serverQuerySignature = JSON.stringify(serverQuery);

    const applyDatePreset = (event: ChangeEvent<HTMLSelectElement>) => {
        const nextPreset = event.target.value as DatePreset;
        setDatePreset(nextPreset);

        if (nextPreset === '') {
            return;
        }

        const range = resolvePresetRange(nextPreset);
        setStartDate(range.startDate);
        setEndDate(range.endDate);
    };

    useEffect(() => {
        if (currentQuerySignature === serverQuerySignature) {
            return;
        }

        const debounceId = window.setTimeout(() => {
            router.get('/reports', currentQuery, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 250);

        return () => window.clearTimeout(debounceId);
    }, [currentQuery, currentQuerySignature, serverQuerySignature]);

    const tableMinWidth = 240 + report.units.length * 220;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generator Laporan" />
            <div className="mx-auto w-full max-w-7xl space-y-4 p-4">
                <section className="rounded-xl border bg-card p-4">
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3 md:items-end">
                            <div className="grid gap-2">
                                <Label htmlFor="date-preset">Preset Tanggal</Label>
                                <select
                                    id="date-preset"
                                    value={datePreset}
                                    onChange={applyDatePreset}
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Pilih preset</option>
                                    <option value="today">Hari Ini</option>
                                    <option value="this_week">Minggu Ini</option>
                                    <option value="last_week">Minggu Lalu</option>
                                    <option value="this_month">Bulan Ini</option>
                                    <option value="last_month">Bulan Lalu</option>
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="start-date">Tanggal Mulai</Label>
                                <Input
                                    id="start-date"
                                    name="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(event) => {
                                        setDatePreset('');
                                        setStartDate(event.target.value);
                                    }}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end-date">Tanggal Akhir</Label>
                                <Input
                                    id="end-date"
                                    name="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(event) => {
                                        setDatePreset('');
                                        setEndDate(event.target.value);
                                    }}
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-4 md:items-end">
                            <div className="grid gap-2">
                                <Label htmlFor="subdit-id">Filter Subdit</Label>
                                <select
                                    id="subdit-id"
                                    name="subdit_id"
                                    value={subditId}
                                    onChange={(event) =>
                                        setSubditId(event.target.value)
                                    }
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Semua Subdit</option>
                                    {filterSubdits.map((subdit) => (
                                        <option key={subdit.id} value={subdit.id}>
                                            {subdit.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="unit-id">Filter Unit</Label>
                                <select
                                    id="unit-id"
                                    name="unit_id"
                                    value={unitId}
                                    onChange={(event) =>
                                        setUnitId(event.target.value)
                                    }
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Semua Unit</option>
                                    {filterUnits.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="keyword">Kata Kunci</Label>
                                <Input
                                    id="keyword"
                                    name="keyword"
                                    type="text"
                                    placeholder="Cari kata pada uraian"
                                    value={keyword}
                                    onChange={(event) =>
                                        setKeyword(event.target.value)
                                    }
                                />
                            </div>
                            <div className="flex gap-2">
                                {canExport && (
                                    <Button type="button" asChild>
                                        <a href={exportUrl}>Export PDF</a>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-4 text-center text-sm font-semibold uppercase tracking-wide">
                        {report.title}
                    </h2>

                    <div className="space-y-4">
                        {report.days.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                Tidak ada data kegiatan pada rentang tanggal
                                terpilih.
                            </div>
                        ) : (
                            report.days.map((day) => (
                                <article key={day.date} className="space-y-2">
                                    <div className="border-y py-2 text-sm font-semibold">
                                        {day.header_line}
                                    </div>
                                    <div className="overflow-x-auto">
                                        <table
                                            className="w-full border-collapse border text-sm"
                                            style={{ minWidth: `${tableMinWidth}px` }}
                                        >
                                            <thead>
                                                <tr>
                                                    <th className="w-48 border bg-muted px-3 py-2 text-left text-xs uppercase">
                                                        Subdit
                                                    </th>
                                                    {report.units.map((unit) => (
                                                        <th
                                                            key={unit.id}
                                                            className="border bg-muted px-3 py-2 text-left text-xs uppercase"
                                                        >
                                                            {unit.name}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {day.rows.map((row) => (
                                                    <tr key={row.subdit_id}>
                                                        <td className="border px-3 py-2 align-top font-semibold">
                                                            {row.subdit_name}
                                                        </td>
                                                        {row.cells.map((cell) => (
                                                            <td
                                                                key={`${row.subdit_id}-${cell.unit_id}`}
                                                                className="h-24 border px-3 py-2 align-top"
                                                            >
                                                                {cell.entries
                                                                    .length ===
                                                                0 ? (
                                                                    <div className="text-center text-muted-foreground">
                                                                        -
                                                                    </div>
                                                                ) : (
                                                                    <ol className="list-decimal space-y-1 pl-5">
                                                                        {cell.entries.map(
                                                                            (
                                                                                entry,
                                                                            ) => (
                                                                                <li
                                                                                    key={
                                                                                        entry.id
                                                                                    }
                                                                                >
                                                                                    {entry.time_start
                                                                                        ? `[${entry.time_start}] `
                                                                                        : ''}
                                                                                    {
                                                                                        entry.description
                                                                                    }
                                                                                    {entry.has_attachment
                                                                                        ? ' [LAMPIRAN]'
                                                                                        : ''}
                                                                                </li>
                                                                            ),
                                                                        )}
                                                                    </ol>
                                                                )}
                                                            </td>
                                                        ))}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
