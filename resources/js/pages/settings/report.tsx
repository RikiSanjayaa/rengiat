import { Transition } from '@headlessui/react';
import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ReportSettingData = {
    atas_nama: string;
    jabatan: string;
    nama_penandatangan: string;
    pangkat_nrp: string;
};

type PageProps = {
    reportSetting: ReportSettingData;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Laporan',
        href: '/settings/report',
    },
];

export default function ReportSettings({ reportSetting }: PageProps) {
    const [atasNama, setAtasNama] = useState(reportSetting.atas_nama);
    const [jabatan, setJabatan] = useState(reportSetting.jabatan);
    const [namaPenandatangan, setNamaPenandatangan] = useState(
        reportSetting.nama_penandatangan,
    );
    const [pangkatNrp, setPangkatNrp] = useState(reportSetting.pangkat_nrp);
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.put(
            '/settings/report',
            {
                atas_nama: atasNama,
                jabatan: jabatan,
                nama_penandatangan: namaPenandatangan,
                pangkat_nrp: pangkatNrp,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRecentlySuccessful(true);
                    setTimeout(() => setRecentlySuccessful(false), 3000);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const hasContent = atasNama || jabatan || namaPenandatangan || pangkatNrp;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan Laporan" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                {/* Header */}
                <div className="rounded-xl border bg-card p-4">
                    <h1 className="font-semibold">Pengaturan Laporan</h1>
                    <p className="text-sm text-muted-foreground">
                        Atur format tanda tangan digital (TDD) yang akan muncul
                        pada PDF laporan yang Anda ekspor.
                    </p>
                </div>

                {/* Form */}
                <form
                    onSubmit={handleSubmit}
                    className="space-y-5 rounded-xl border bg-card p-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="atas_nama">Atas Nama</Label>
                        <Input
                            id="atas_nama"
                            value={atasNama}
                            onChange={(e) => setAtasNama(e.target.value)}
                            placeholder="Contoh: KEPALA KEPOLISIAN DAERAH NUSA TENGGARA BARAT"
                            required
                        />
                        <p className="text-xs text-muted-foreground">
                            Baris pertama setelah "An."
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="jabatan">Jabatan</Label>
                        <Input
                            id="jabatan"
                            value={jabatan}
                            onChange={(e) => setJabatan(e.target.value)}
                            placeholder="Contoh: DIRRES PPA DAN PPO POLDA NTB"
                            required
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nama_penandatangan">
                            Nama Penandatangan
                        </Label>
                        <Input
                            id="nama_penandatangan"
                            value={namaPenandatangan}
                            onChange={(e) =>
                                setNamaPenandatangan(e.target.value)
                            }
                            placeholder="Contoh: BAMBANG PAMUNGKAS,S.I.K.,M.M."
                            required
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pangkat_nrp">Pangkat & NRP</Label>
                        <Input
                            id="pangkat_nrp"
                            value={pangkatNrp}
                            onChange={(e) => setPangkatNrp(e.target.value)}
                            placeholder="Contoh: KOMBESPOL NRP 12345678"
                            required
                        />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={processing}>Simpan</Button>

                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-green-600">Tersimpan</p>
                        </Transition>
                    </div>
                </form>

                {/* Preview */}
                {hasContent && (
                    <div className="rounded-xl border bg-card p-4">
                        <p className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                            Preview TDD pada PDF
                        </p>
                        <div className="rounded-lg border bg-white p-6 dark:bg-neutral-900">
                            <div className="flex justify-end">
                                <div className="text-center">
                                    <div className="text-sm text-neutral-800 dark:text-neutral-200">
                                        An. {atasNama || '...'}
                                    </div>
                                    <div className="text-sm text-neutral-800 dark:text-neutral-200">
                                        {jabatan || '...'}
                                    </div>
                                    <div className="mt-10 text-sm font-bold text-blue-800 underline dark:text-blue-400">
                                        {namaPenandatangan || '...'}
                                    </div>
                                    <div className="text-sm text-neutral-800 dark:text-neutral-200">
                                        {pangkatNrp || '...'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
