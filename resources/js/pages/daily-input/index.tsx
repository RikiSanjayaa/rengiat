import { Head, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type UnitOption = {
    id: number;
    subdit_id: number | null;
    subdit_name: string | null;
    name: string;
};

type EntryAttachment = {
    id: number;
    url: string;
    mime_type: string;
};

type Entry = {
    id: number;
    unit_id: number;
    subdit_name: string | null;
    unit_name: string | null;
    entry_date: string;
    time_start: string | null;
    description: string;
    created_at: string | null;
    created_by_name: string | null;
    updated_by_name: string | null;
    can_update: boolean;
    can_delete: boolean;
    attachments: EntryAttachment[];
};

type DailyInputPageProps = {
    selectedDate: string;
    selectedUnitId: number | null;
    units: UnitOption[];
    entries: Entry[];
    canCreate: boolean;
    attachmentsEnabled: boolean;
};

type EntryForm = {
    entry_date: string;
    time_start: string;
    description: string;
    unit_id: string;
    attachment: File | null;
    _method?: 'put';
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Input Harian',
        href: '/daily-input',
    },
];

export default function DailyInputPage({
    selectedDate,
    selectedUnitId,
    units,
    entries,
    canCreate,
    attachmentsEnabled,
}: DailyInputPageProps) {
    const [dateFilter, setDateFilter] = useState(selectedDate);
    const [unitFilter, setUnitFilter] = useState<string>(
        selectedUnitId ? String(selectedUnitId) : '',
    );
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingEntry, setEditingEntry] = useState<Entry | null>(null);

    const form = useForm<EntryForm>({
        entry_date: selectedDate,
        time_start: '',
        description: '',
        unit_id: selectedUnitId ? String(selectedUnitId) : '',
        attachment: null,
    });

    const hasWriteAccess = useMemo(
        () => canCreate || entries.some((entry) => entry.can_update),
        [canCreate, entries],
    );

    const unitsBySubdit = useMemo(
        () =>
            units.reduce<Record<string, UnitOption[]>>((carry, unit) => {
                const key = unit.subdit_name ?? 'Tanpa Subdit';
                if (!carry[key]) {
                    carry[key] = [];
                }

                carry[key].push(unit);

                return carry;
            }, {}),
        [units],
    );

    useEffect(() => {
        setDateFilter(selectedDate);
        setUnitFilter(selectedUnitId ? String(selectedUnitId) : '');
    }, [selectedDate, selectedUnitId]);

    const currentQuery = useMemo(
        () => ({
            date: dateFilter,
            unit_id: unitFilter || undefined,
        }),
        [dateFilter, unitFilter],
    );

    const serverQuery = useMemo(
        () => ({
            date: selectedDate,
            unit_id: selectedUnitId ? String(selectedUnitId) : undefined,
        }),
        [selectedDate, selectedUnitId],
    );

    const currentQuerySignature = JSON.stringify(currentQuery);
    const serverQuerySignature = JSON.stringify(serverQuery);

    useEffect(() => {
        if (currentQuerySignature === serverQuerySignature) {
            return;
        }

        const debounceId = window.setTimeout(() => {
            router.get('/daily-input', currentQuery, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 200);

        return () => window.clearTimeout(debounceId);
    }, [currentQuery, currentQuerySignature, serverQuerySignature]);

    const openCreateModal = () => {
        setEditingEntry(null);
        form.reset();
        form.setData({
            entry_date: dateFilter,
            time_start: '',
            description: '',
            unit_id: unitFilter,
            attachment: null,
        });
        setIsModalOpen(true);
    };

    const openEditModal = (entry: Entry) => {
        setEditingEntry(entry);
        form.clearErrors();
        form.setData({
            entry_date: entry.entry_date,
            time_start: entry.time_start ?? '',
            description: entry.description,
            unit_id: String(entry.unit_id),
            attachment: null,
            _method: 'put',
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingEntry(null);
        form.clearErrors();
    };

    const submitForm: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        const endpoint = editingEntry ? `/entries/${editingEntry.id}` : '/entries';

        form.transform((data) => ({
            ...data,
            _method: editingEntry ? 'put' : undefined,
        }));

        form.post(endpoint, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    };

    const deleteEntry = (entry: Entry) => {
        if (!window.confirm('Hapus entri ini?')) {
            return;
        }

        router.delete(`/entries/${entry.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Input Harian" />
            <div className="mx-auto w-full max-w-6xl space-y-4 p-4">
                <div className="rounded-xl border bg-card p-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-end">
                        <div className="grid gap-2">
                            <Label htmlFor="entry-date-filter">Tanggal</Label>
                            <Input
                                id="entry-date-filter"
                                type="date"
                                value={dateFilter}
                                onChange={(event) =>
                                    setDateFilter(event.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit-filter">Unit</Label>
                            <select
                                id="unit-filter"
                                value={unitFilter}
                                onChange={(event) =>
                                    setUnitFilter(event.target.value)
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                disabled={units.length <= 1}
                            >
                                {Object.entries(unitsBySubdit).map(
                                    ([subditName, subditUnits]) => (
                                        <optgroup
                                            key={subditName}
                                            label={subditName}
                                        >
                                            {subditUnits.map((unit) => (
                                                <option
                                                    key={unit.id}
                                                    value={unit.id}
                                                >
                                                    {unit.name}
                                                </option>
                                            ))}
                                        </optgroup>
                                    ),
                                )}
                            </select>
                        </div>
                        {hasWriteAccess && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={openCreateModal}
                                disabled={!canCreate}
                            >
                                Tambah Entri
                            </Button>
                        )}
                    </div>
                </div>

                <div className="space-y-3">
                    {entries.length === 0 && (
                        <div className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Belum ada entri pada tanggal ini.
                        </div>
                    )}

                    {entries.map((entry) => (
                        <article
                            key={entry.id}
                            className="space-y-3 rounded-xl border bg-card p-4"
                        >
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <Badge variant="secondary">
                                    {entry.time_start ?? 'Tanpa Jam'}
                                </Badge>
                                <span className="text-muted-foreground">
                                    {entry.subdit_name ?? '-'}
                                </span>
                                <span className="font-medium">
                                    {entry.unit_name ?? '-'}
                                </span>
                            </div>
                            <p className="text-sm leading-6">{entry.description}</p>

                            {attachmentsEnabled && entry.attachments.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {entry.attachments.map((attachment) => (
                                        <a
                                            key={attachment.id}
                                            href={attachment.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="block overflow-hidden rounded-md border"
                                        >
                                            <img
                                                src={attachment.url}
                                                alt="Attachment"
                                                className="h-20 w-20 object-cover"
                                            />
                                        </a>
                                    ))}
                                </div>
                            )}

                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    Dibuat oleh {entry.created_by_name ?? '-'}
                                    {entry.updated_by_name
                                        ? `, diubah oleh ${entry.updated_by_name}`
                                        : ''}
                                </span>
                                <div className="flex gap-2">
                                    {entry.can_update && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openEditModal(entry)}
                                        >
                                            Edit
                                        </Button>
                                    )}
                                    {entry.can_delete && (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => deleteEntry(entry)}
                                        >
                                            Hapus
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </div>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingEntry ? 'Ubah Entri' : 'Tambah Entri'}
                        </DialogTitle>
                        <DialogDescription>
                            Isi data aktivitas harian sesuai unit kerja.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={submitForm}>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="form-entry-date">Tanggal</Label>
                                <Input
                                    id="form-entry-date"
                                    type="date"
                                    value={form.data.entry_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'entry_date',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError message={form.errors.entry_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="form-time-start">
                                    Jam Mulai (Opsional)
                                </Label>
                                <Input
                                    id="form-time-start"
                                    type="time"
                                    value={form.data.time_start}
                                    onChange={(event) =>
                                        form.setData(
                                            'time_start',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.time_start} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="form-unit-id">Unit</Label>
                            <select
                                id="form-unit-id"
                                value={form.data.unit_id}
                                onChange={(event) =>
                                    form.setData('unit_id', event.target.value)
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                required
                                disabled={units.length <= 1}
                            >
                                {Object.entries(unitsBySubdit).map(
                                    ([subditName, subditUnits]) => (
                                        <optgroup
                                            key={subditName}
                                            label={subditName}
                                        >
                                            {subditUnits.map((unit) => (
                                                <option
                                                    key={unit.id}
                                                    value={unit.id}
                                                >
                                                    {unit.name}
                                                </option>
                                            ))}
                                        </optgroup>
                                    ),
                                )}
                            </select>
                            <InputError message={form.errors.unit_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="form-description">Uraian</Label>
                            <textarea
                                id="form-description"
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                className="min-h-32 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                            <InputError message={form.errors.description} />
                        </div>

                        {attachmentsEnabled && (
                            <div className="grid gap-2">
                                <Label htmlFor="form-attachment">
                                    Lampiran Gambar (Opsional)
                                </Label>
                                <Input
                                    id="form-attachment"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    onChange={(event) =>
                                        form.setData(
                                            'attachment',
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                <InputError message={form.errors.attachment} />
                            </div>
                        )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeModal}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editingEntry ? 'Simpan Perubahan' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
