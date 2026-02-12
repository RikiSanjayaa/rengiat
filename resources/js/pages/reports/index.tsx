import { Head, router } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type UnitFilter = {
    id: number;
    name: string;
};

type ReportEntry = {
    id: number;
    time_start: string | null;
    description: string;
    has_attachment: boolean;
};

type ReportColumn = {
    unit_id: number;
    unit_name: string;
    entries: ReportEntry[];
};

type ReportDay = {
    date: string;
    header_line: string;
    columns: ReportColumn[];
};

type ReportPayload = {
    title: string;
    units: Array<{ id: number; name: string; order_index: number }>;
    days: ReportDay[];
};

type PageProps = {
    filters: {
        start_date: string;
        end_date: string | null;
        unit_id: number | null;
        keyword: string | null;
    };
    filterUnits: UnitFilter[];
    report: ReportPayload;
    canExport: boolean;
    exportUrl: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Generator Laporan',
        href: '/reports',
    },
];

export default function ReportGeneratorPage({
    filters,
    filterUnits,
    report,
    canExport,
    exportUrl,
}: PageProps) {
    const submitFilters: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        const formData = new FormData(event.currentTarget);

        const startDate = String(formData.get('start_date') ?? '');
        const endDateValue = String(formData.get('end_date') ?? '').trim();
        const unitIdValue = String(formData.get('unit_id') ?? '').trim();
        const keywordValue = String(formData.get('keyword') ?? '').trim();

        router.get(
            '/reports',
            {
                start_date: startDate,
                end_date:
                    endDateValue !== '' && endDateValue !== startDate
                        ? endDateValue
                        : undefined,
                unit_id: unitIdValue || undefined,
                keyword: keywordValue || undefined,
            },
            {
                preserveState: false,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generator Laporan" />
            <div className="mx-auto w-full max-w-7xl space-y-4 p-4">
                <section className="rounded-xl border bg-card p-4">
                    <form
                        key={`${filters.start_date}|${filters.end_date ?? ''}|${filters.unit_id ?? ''}|${filters.keyword ?? ''}`}
                        className="grid gap-4 md:grid-cols-5 md:items-end"
                        onSubmit={submitFilters}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="start-date">Tanggal Mulai</Label>
                            <Input
                                id="start-date"
                                name="start_date"
                                type="date"
                                defaultValue={filters.start_date}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end-date">Tanggal Akhir</Label>
                            <Input
                                id="end-date"
                                name="end_date"
                                type="date"
                                defaultValue={filters.end_date ?? ''}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit-id">Filter Unit</Label>
                            <select
                                id="unit-id"
                                name="unit_id"
                                defaultValue={
                                    filters.unit_id ? String(filters.unit_id) : ''
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
                                defaultValue={filters.keyword ?? ''}
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit">Preview</Button>
                            {canExport && (
                                <Button type="button" variant="outline" asChild>
                                    <a href={exportUrl}>Export PDF</a>
                                </Button>
                            )}
                        </div>
                    </form>
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-4 text-center text-sm font-semibold uppercase tracking-wide">
                        {report.title}
                    </h2>

                    <div className="space-y-8">
                        {report.days.map((day, dayIndex) => (
                            <article
                                key={day.date}
                                className={`space-y-3 ${dayIndex < report.days.length - 1 ? 'print:break-after-page' : ''}`}
                            >
                                <div className="border-y py-2 text-sm font-semibold">
                                    {day.header_line}
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[900px] border-collapse border text-sm">
                                        <thead>
                                            <tr>
                                                {day.columns.map((column) => (
                                                    <th
                                                        key={column.unit_id}
                                                        className="border bg-muted px-3 py-2 text-center text-xs uppercase"
                                                    >
                                                        {column.unit_name}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                {day.columns.map((column) => (
                                                    <td
                                                        key={column.unit_id}
                                                        className="h-28 border px-3 py-2 align-top"
                                                    >
                                                        {column.entries.length ===
                                                        0 ? (
                                                            <div className="text-center text-muted-foreground">
                                                                -
                                                            </div>
                                                        ) : (
                                                            <ol className="list-decimal space-y-1 pl-5">
                                                                {column.entries.map(
                                                                    (entry) => (
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
                                        </tbody>
                                    </table>
                                </div>
                                {dayIndex < report.days.length - 1 && (
                                    <div className="my-6 border-b border-dashed"></div>
                                )}
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
