import { Head, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
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

type UnitRecord = {
    id: number;
    subdit_id: number;
    subdit_name: string | null;
    name: string;
    order_index: number;
    active: boolean;
    created_at: string | null;
};

type SubditOption = {
    id: number;
    name: string;
};

type Props = {
    units: UnitRecord[];
    subdits: SubditOption[];
};

type UnitForm = {
    subdit_id: string;
    name: string;
    order_index: string;
    active: boolean;
    _method?: 'put';
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Unit',
        href: '/admin/units',
    },
];

export default function UnitManagementPage({ units, subdits }: Props) {
    const [editingUnit, setEditingUnit] = useState<UnitRecord | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const form = useForm<UnitForm>({
        subdit_id: '',
        name: '',
        order_index: '1',
        active: true,
    });

    const openCreate = () => {
        setEditingUnit(null);
        form.clearErrors();
        form.setData({
            subdit_id: subdits.length > 0 ? String(subdits[0].id) : '',
            name: '',
            order_index: String(units.length + 1),
            active: true,
        });
        setIsModalOpen(true);
    };

    const openEdit = (unit: UnitRecord) => {
        setEditingUnit(unit);
        form.clearErrors();
        form.setData({
            subdit_id: String(unit.subdit_id),
            name: unit.name,
            order_index: String(unit.order_index),
            active: unit.active,
            _method: 'put',
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingUnit(null);
    };

    const submitForm: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        const endpoint = editingUnit
            ? `/admin/units/${editingUnit.id}`
            : '/admin/units';

        form.transform((data) => ({
            ...data,
            subdit_id: Number(data.subdit_id),
            order_index: Number(data.order_index),
            _method: editingUnit ? 'put' : undefined,
        }));

        form.post(endpoint, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    };

    const deleteUnit = (unit: UnitRecord) => {
        if (!window.confirm(`Hapus unit ${unit.name}?`)) {
            return;
        }

        router.delete(`/admin/units/${unit.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen Unit" />
            <div className="mx-auto w-full max-w-6xl space-y-4 p-4">
                <div className="flex items-center justify-between rounded-xl border bg-card p-4">
                    <div>
                        <h1 className="font-semibold">Daftar Unit</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola struktur unit untuk input dan laporan.
                        </p>
                    </div>
                    <Button onClick={openCreate}>Tambah Unit</Button>
                </div>

                <div className="overflow-x-auto rounded-xl border bg-card">
                    <table className="w-full border-collapse text-sm">
                        <thead>
                            <tr className="bg-muted/60">
                                <th className="border px-3 py-2 text-left">
                                    Subdit
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Nama
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Urutan
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Status
                                </th>
                                <th className="border px-3 py-2 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {units.map((unit) => (
                                <tr key={unit.id}>
                                    <td className="border px-3 py-2">
                                        {unit.subdit_name ?? '-'}
                                    </td>
                                    <td className="border px-3 py-2">
                                        {unit.name}
                                    </td>
                                    <td className="border px-3 py-2">
                                        {unit.order_index}
                                    </td>
                                    <td className="border px-3 py-2">
                                        <Badge
                                            variant={
                                                unit.active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {unit.active
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="border px-3 py-2 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => openEdit(unit)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => deleteUnit(unit)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingUnit ? 'Ubah Unit' : 'Tambah Unit'}
                        </DialogTitle>
                        <DialogDescription>
                            Tentukan nama, urutan, dan status aktif unit.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={submitForm}>
                        <div className="grid gap-2">
                            <Label htmlFor="unit-subdit">Subdit</Label>
                            <select
                                id="unit-subdit"
                                value={form.data.subdit_id}
                                onChange={(event) =>
                                    form.setData(
                                        'subdit_id',
                                        event.target.value,
                                    )
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option value="">Pilih subdit</option>
                                {subdits.map((subdit) => (
                                    <option key={subdit.id} value={subdit.id}>
                                        {subdit.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.subdit_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="unit-name">Nama Unit</Label>
                            <Input
                                id="unit-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="unit-order">Urutan</Label>
                            <Input
                                id="unit-order"
                                type="number"
                                min={1}
                                value={form.data.order_index}
                                onChange={(event) =>
                                    form.setData(
                                        'order_index',
                                        event.target.value,
                                    )
                                }
                                required
                            />
                            <InputError message={form.errors.order_index} />
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.active}
                                onChange={(event) =>
                                    form.setData('active', event.target.checked)
                                }
                            />
                            Unit aktif
                        </label>
                        <InputError message={form.errors.active} />

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeModal}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
