import { Head, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useMemo, useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
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

type UserRecord = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: 'super_admin' | 'admin' | 'operator' | 'viewer';
    subdit_id: number | null;
    subdit_name: string | null;
    created_at: string | null;
    can_edit: boolean;
    can_delete: boolean;
};

type SubditOption = {
    id: number;
    name: string;
};

type RoleOption = {
    value: UserRecord['role'];
    label: string;
};

type Props = {
    users: UserRecord[];
    subdits: SubditOption[];
    roles: RoleOption[];
};

type UserForm = {
    name: string;
    username: string;
    email: string;
    role: UserRecord['role'];
    subdit_id: string;
    password: string;
    password_confirmation: string;
    _method?: 'put';
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen User',
        href: '/admin/users',
    },
];

const defaultRole: UserRecord['role'] = 'viewer';

export default function UserManagementPage({ users, subdits, roles }: Props) {
    const [editingUser, setEditingUser] = useState<UserRecord | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [userToDelete, setUserToDelete] = useState<UserRecord | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const form = useForm<UserForm>({
        name: '',
        username: '',
        email: '',
        role: defaultRole,
        subdit_id: '',
        password: '',
        password_confirmation: '',
    });

    const selectedRole = useMemo(() => form.data.role, [form.data.role]);
    const roleNeedsSubdit = selectedRole === 'operator';

    const openCreate = () => {
        setEditingUser(null);
        form.clearErrors();
        form.setData({
            name: '',
            username: '',
            email: '',
            role: defaultRole,
            subdit_id: '',
            password: '',
            password_confirmation: '',
        });
        setIsModalOpen(true);
    };

    const openEdit = (user: UserRecord) => {
        setEditingUser(user);
        form.clearErrors();
        form.setData({
            name: user.name,
            username: user.username,
            email: user.email ?? '',
            role: user.role,
            subdit_id: user.subdit_id ? String(user.subdit_id) : '',
            password: '',
            password_confirmation: '',
            _method: 'put',
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingUser(null);
    };

    const submitForm: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        const endpoint = editingUser
            ? `/admin/users/${editingUser.id}`
            : '/admin/users';

        form.transform((data) => ({
            ...data,
            subdit_id: data.subdit_id || null,
            password: data.password || undefined,
            password_confirmation: data.password_confirmation || undefined,
            _method: editingUser ? 'put' : undefined,
        }));

        form.post(endpoint, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    };

    const deleteUser = (user: UserRecord) => {
        setUserToDelete(user);
    };

    const confirmDeleteUser = () => {
        if (!userToDelete) {
            return;
        }

        const targetUser = userToDelete;
        setIsDeleting(true);
        setUserToDelete(null);

        router.delete(`/admin/users/${targetUser.id}`, {
            preserveScroll: true,
            onFinish: () => setIsDeleting(false),
        });
    };

    const roleBadgeLabel = (role: UserRecord['role']) =>
        role.replace('_', ' ').toUpperCase();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen User" />
            <div className="mx-auto w-full max-w-7xl space-y-4 p-4">
                <div className="flex items-center justify-between rounded-xl border bg-card p-4">
                    <div>
                        <h1 className="font-semibold">Daftar User</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola akun operator, admin, dan pimpinan.
                        </p>
                    </div>
                    <Button onClick={openCreate}>Tambah User</Button>
                </div>

                <div className="overflow-x-auto rounded-xl border bg-card">
                    <table className="w-full border-collapse text-sm">
                        <thead>
                            <tr className="bg-muted/60">
                                <th className="border px-3 py-2 text-left">
                                    Nama
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Username
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Email
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Role
                                </th>
                                <th className="border px-3 py-2 text-left">
                                    Subdit
                                </th>
                                <th className="border px-3 py-2 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map((user) => (
                                <tr key={user.id}>
                                    <td className="border px-3 py-2">
                                        {user.name}
                                    </td>
                                    <td className="border px-3 py-2">
                                        {user.username}
                                    </td>
                                    <td className="border px-3 py-2">
                                        {user.email ?? '-'}
                                    </td>
                                    <td className="border px-3 py-2">
                                        <Badge variant="secondary">
                                            {roleBadgeLabel(user.role)}
                                        </Badge>
                                    </td>
                                    <td className="border px-3 py-2">
                                        {user.subdit_name ?? '-'}
                                    </td>
                                    <td className="border px-3 py-2 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                disabled={!user.can_edit}
                                                onClick={() => openEdit(user)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                disabled={!user.can_delete}
                                                onClick={() => deleteUser(user)}
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
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingUser ? 'Ubah User' : 'Tambah User'}
                        </DialogTitle>
                        <DialogDescription>
                            Role operator wajib memilih subdit.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={submitForm}>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="user-name">Nama</Label>
                                <Input
                                    id="user-name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="user-username">Username</Label>
                                <Input
                                    id="user-username"
                                    value={form.data.username}
                                    onChange={(event) =>
                                        form.setData(
                                            'username',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError message={form.errors.username} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="user-email">
                                    Email (Opsional)
                                </Label>
                                <Input
                                    id="user-email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.email} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="user-role">Role</Label>
                                <select
                                    id="user-role"
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    value={form.data.role}
                                    onChange={(event) =>
                                        form.setData(
                                            'role',
                                            event.target
                                                .value as UserRecord['role'],
                                        )
                                    }
                                    required
                                >
                                    {roles.map((role) => (
                                        <option
                                            key={role.value}
                                            value={role.value}
                                        >
                                            {role.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.role} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="user-subdit">Subdit</Label>
                                <select
                                    id="user-subdit"
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    value={form.data.subdit_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'subdit_id',
                                            event.target.value,
                                        )
                                    }
                                    disabled={!roleNeedsSubdit}
                                >
                                    <option value="">-</option>
                                    {subdits.map((subdit) => (
                                        <option
                                            key={subdit.id}
                                            value={subdit.id}
                                        >
                                            {subdit.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.subdit_id} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="user-password">
                                    Password {editingUser ? '(Opsional)' : ''}
                                </Label>
                                <Input
                                    id="user-password"
                                    type="password"
                                    value={form.data.password}
                                    onChange={(event) =>
                                        form.setData(
                                            'password',
                                            event.target.value,
                                        )
                                    }
                                    required={!editingUser}
                                />
                                <InputError message={form.errors.password} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="user-password-confirmation">
                                    Konfirmasi Password
                                </Label>
                                <Input
                                    id="user-password-confirmation"
                                    type="password"
                                    value={form.data.password_confirmation}
                                    onChange={(event) =>
                                        form.setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    required={!editingUser}
                                />
                            </div>
                        </div>

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

            <ConfirmDialog
                open={userToDelete !== null}
                title="Hapus user?"
                description={`Akun ${
                    userToDelete?.name ?? ''
                } akan dihapus permanen.`}
                confirmLabel="Ya, Hapus"
                processing={isDeleting}
                onOpenChange={(open) => {
                    if (!open) {
                        setUserToDelete(null);
                    }
                }}
                onConfirm={confirmDeleteUser}
            />
        </AppLayout>
    );
}
