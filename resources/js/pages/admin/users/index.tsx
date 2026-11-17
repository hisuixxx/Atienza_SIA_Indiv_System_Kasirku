import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyIcon, PencilIcon, PlusIcon, SearchIcon, TrashIcon, UserCheckIcon, UserXIcon } from 'lucide-react';
import { useState } from 'react';

interface Branch {
    id: number;
    name: string;
    code: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    role: 'admin' | 'staff' | 'kitchen' | 'customer';
    is_active: boolean;
    branches: Branch[];
    created_at: string;
}

interface Stats {
    total: number;
    active: number;
    inactive: number;
    admin: number;
    staff: number;
    kitchen: number;
    customer: number;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string;
    role?: string;
    status?: string;
}

interface Props {
    users: {
        data: User[];
        links: PaginationLink[];
        meta: PaginationMeta;
    };
    stats: Stats;
    branches: Branch[];
    filters: Filters;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'User Management', href: '/admin/users' },
];

const roleLabels: Record<string, string> = {
    admin: 'Admin',
    staff: 'Staff (Cashier)',
    kitchen: 'Kitchen',
    customer: 'Customer',
};

const roleBadgeVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    admin: 'default',
    staff: 'secondary',
    kitchen: 'outline',
    customer: 'outline',
};

export default function UsersIndex({ users, stats, branches, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isResetOpen, setIsResetOpen] = useState(false);
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<User | null>(null);

    const createForm = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
        password: '',
        role: 'staff',
        is_active: true,
        branch_ids: [] as number[],
    });

    const editForm = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
        password: '',
        role: 'staff',
        is_active: true,
        branch_ids: [] as number[],
    });

    const resetForm = useForm({
        password: '',
        password_confirmation: '',
    });

    const applyFilters = () => {
        router.get('/admin/users', {
            search: search || undefined,
            role: roleFilter || undefined,
            status: statusFilter || undefined,
        }, { preserveState: true, replace: true });
    };

    const openCreate = () => {
        createForm.reset();
        createForm.setData({
            name: '',
            email: '',
            phone: '',
            address: '',
            password: '',
            role: 'staff',
            is_active: true,
            branch_ids: [],
        });
        setIsCreateOpen(true);
    };

    const openEdit = (user: User) => {
        setSelectedUser(user);
        editForm.setData({
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            address: user.address || '',
            password: '',
            role: user.role,
            is_active: user.is_active,
            branch_ids: user.branches.map((b) => b.id),
        });
        setIsEditOpen(true);
    };

    const openReset = (user: User) => {
        setSelectedUser(user);
        resetForm.reset();
        setIsResetOpen(true);
    };

    const openDelete = (user: User) => {
        setSelectedUser(user);
        setIsDeleteOpen(true);
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/users', {
            onSuccess: () => setIsCreateOpen(false),
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUser) return;
        editForm.put(`/admin/users/${selectedUser.id}`, {
            onSuccess: () => setIsEditOpen(false),
        });
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUser) return;
        resetForm.post(`/admin/users/${selectedUser.id}/reset-password`, {
            onSuccess: () => setIsResetOpen(false),
        });
    };

    const toggleStatus = (user: User) => {
        router.post(`/admin/users/${user.id}/toggle-status`, {}, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!selectedUser) return;
        router.delete(`/admin/users/${selectedUser.id}`, {
            onSuccess: () => setIsDeleteOpen(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <div className="space-y-6 p-4 md:p-6">
                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Active</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.active}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Staff + Kitchen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.staff + stats.kitchen}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Customers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.customer}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters + Add Button */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div className="flex flex-1 flex-col gap-3 md:flex-row">
                                <div className="relative flex-1">
                                    <SearchIcon className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Search name, email, phone..."
                                        className="pl-9"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    />
                                </div>
                                <Select value={roleFilter} onValueChange={setRoleFilter}>
                                    <SelectTrigger className="w-full md:w-[180px]">
                                        <SelectValue placeholder="All Roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Roles</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="staff">Staff (Cashier)</SelectItem>
                                        <SelectItem value="kitchen">Kitchen</SelectItem>
                                        <SelectItem value="customer">Customer</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select value={statusFilter} onValueChange={setStatusFilter}>
                                    <SelectTrigger className="w-full md:w-[150px]">
                                        <SelectValue placeholder="All Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Status</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={applyFilters} variant="secondary">
                                    Filter
                                </Button>
                            </div>
                            <Button onClick={openCreate}>
                                <PlusIcon className="mr-2 h-4 w-4" />
                                Add User
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">Name</th>
                                        <th className="px-4 py-3 text-left font-medium">Email / Phone</th>
                                        <th className="px-4 py-3 text-left font-medium">Role</th>
                                        <th className="px-4 py-3 text-left font-medium">Branch(es)</th>
                                        <th className="px-4 py-3 text-left font-medium">Status</th>
                                        <th className="px-4 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                                No users found.
                                            </td>
                                        </tr>
                                    ) : (
                                        users.data.map((user) => (
                                            <tr key={user.id} className="border-b hover:bg-muted/30">
                                                <td className="px-4 py-3 font-medium">{user.name}</td>
                                                <td className="px-4 py-3">
                                                    <div>{user.email}</div>
                                                    {user.phone && (
                                                        <div className="text-xs text-muted-foreground">{user.phone}</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={roleBadgeVariant[user.role]}>
                                                        {roleLabels[user.role]}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {user.branches.length > 0 ? (
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.branches.map((b) => (
                                                                <Badge key={b.id} variant="outline" className="text-xs">
                                                                    {b.code}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {user.is_active ? (
                                                        <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                                                            Active
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="destructive">Inactive</Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() => openEdit(user)}
                                                            title="Edit"
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() => openReset(user)}
                                                            title="Reset Password"
                                                        >
                                                            <KeyIcon className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() => toggleStatus(user)}
                                                            title={user.is_active ? 'Deactivate' : 'Activate'}
                                                        >
                                                            {user.is_active ? (
                                                                <UserXIcon className="h-4 w-4 text-orange-600" />
                                                            ) : (
                                                                <UserCheckIcon className="h-4 w-4 text-green-600" />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() => openDelete(user)}
                                                            title="Delete"
                                                        >
                                                            <TrashIcon className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Add New User</DialogTitle>
                        <DialogDescription>Create a new account for Admin, Staff, Kitchen, or Customer.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="grid gap-4">
                            <div>
                                <Label htmlFor="create-name">Full Name *</Label>
                                <Input
                                    id="create-name"
                                    value={createForm.data.name}
                                    onChange={(e) => createForm.setData('name', e.target.value)}
                                    required
                                />
                                {createForm.errors.name && (
                                    <p className="text-sm text-red-600">{createForm.errors.name}</p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="create-email">Email *</Label>
                                <Input
                                    id="create-email"
                                    type="email"
                                    value={createForm.data.email}
                                    onChange={(e) => createForm.setData('email', e.target.value)}
                                    required
                                />
                                {createForm.errors.email && (
                                    <p className="text-sm text-red-600">{createForm.errors.email}</p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="create-phone">Phone</Label>
                                <Input
                                    id="create-phone"
                                    value={createForm.data.phone}
                                    onChange={(e) => createForm.setData('phone', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="create-password">Password *</Label>
                                <Input
                                    id="create-password"
                                    type="password"
                                    value={createForm.data.password}
                                    onChange={(e) => createForm.setData('password', e.target.value)}
                                    required
                                />
                                {createForm.errors.password && (
                                    <p className="text-sm text-red-600">{createForm.errors.password}</p>
                                )}
                            </div>
                            <div>
                                <Label>Role *</Label>
                                <Select
                                    value={createForm.data.role}
                                    onValueChange={(v) => createForm.setData('role', v)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="staff">Staff (Cashier)</SelectItem>
                                        <SelectItem value="kitchen">Kitchen</SelectItem>
                                        <SelectItem value="customer">Customer</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {['admin', 'staff', 'kitchen'].includes(createForm.data.role) && (
                                <div>
                                    <Label>Assigned Branches</Label>
                                    <div className="mt-2 space-y-2 rounded-md border p-3">
                                        {branches.map((branch) => (
                                            <label key={branch.id} className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={createForm.data.branch_ids.includes(branch.id)}
                                                    onChange={(e) => {
                                                        const ids = e.target.checked
                                                            ? [...createForm.data.branch_ids, branch.id]
                                                            : createForm.data.branch_ids.filter((id) => id !== branch.id);
                                                        createForm.setData('branch_ids', ids);
                                                    }}
                                                />
                                                <span className="text-sm">{branch.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    checked={createForm.data.is_active}
                                    onCheckedChange={(v) => createForm.setData('is_active', !!v)}
                                />
                                <Label>Active Account</Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Creating...' : 'Create User'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>Update user information and role.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid gap-4">
                            <div>
                                <Label>Full Name *</Label>
                                <Input
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <Label>Email *</Label>
                                <Input
                                    type="email"
                                    value={editForm.data.email}
                                    onChange={(e) => editForm.setData('email', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <Label>Phone</Label>
                                <Input
                                    value={editForm.data.phone}
                                    onChange={(e) => editForm.setData('phone', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>New Password (leave blank to keep current)</Label>
                                <Input
                                    type="password"
                                    value={editForm.data.password}
                                    onChange={(e) => editForm.setData('password', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>Role *</Label>
                                <Select
                                    value={editForm.data.role}
                                    onValueChange={(v) => editForm.setData('role', v)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="staff">Staff (Cashier)</SelectItem>
                                        <SelectItem value="kitchen">Kitchen</SelectItem>
                                        <SelectItem value="customer">Customer</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {['admin', 'staff', 'kitchen'].includes(editForm.data.role) && (
                                <div>
                                    <Label>Assigned Branches</Label>
                                    <div className="mt-2 space-y-2 rounded-md border p-3">
                                        {branches.map((branch) => (
                                            <label key={branch.id} className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={editForm.data.branch_ids.includes(branch.id)}
                                                    onChange={(e) => {
                                                        const ids = e.target.checked
                                                            ? [...editForm.data.branch_ids, branch.id]
                                                            : editForm.data.branch_ids.filter((id) => id !== branch.id);
                                                        editForm.setData('branch_ids', ids);
                                                    }}
                                                />
                                                <span className="text-sm">{branch.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    checked={editForm.data.is_active}
                                    onCheckedChange={(v) => editForm.setData('is_active', !!v)}
                                />
                                <Label>Active Account</Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Reset Password Dialog */}
            <Dialog open={isResetOpen} onOpenChange={setIsResetOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reset Password</DialogTitle>
                        <DialogDescription>
                            Set a new password for <strong>{selectedUser?.name}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitReset} className="space-y-4">
                        <div>
                            <Label>New Password</Label>
                            <Input
                                type="password"
                                value={resetForm.data.password}
                                onChange={(e) => resetForm.setData('password', e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <Label>Confirm Password</Label>
                            <Input
                                type="password"
                                value={resetForm.data.password_confirmation}
                                onChange={(e) => resetForm.setData('password_confirmation', e.target.value)}
                                required
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsResetOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={resetForm.processing}>
                                Reset Password
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{selectedUser?.name}</strong>?  
                            This is a soft delete — historical records will be preserved.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsDeleteOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Delete User
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}