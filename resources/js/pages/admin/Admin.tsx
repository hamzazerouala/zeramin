import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import Pagination from '@/components/Pagination';
import type { Order, Paginated, User } from '@/types';

export default function Admin() {
    const [tab, setTab] = useState<'users' | 'disputes'>('users');
    const [usersPage, setUsersPage] = useState(1);
    const [disputesPage, setDisputesPage] = useState(1);
    const qc = useQueryClient();

    const users = useQuery({
        queryKey: ['admin-users', usersPage],
        queryFn: async () => (await api.get<Paginated<User>>(`/admin/users?page=${usersPage}`)).data,
        enabled: tab === 'users',
    });

    const disputes = useQuery({
        queryKey: ['admin-disputes', disputesPage],
        queryFn: async () => (await api.get<Paginated<Order>>(`/admin/disputes?page=${disputesPage}`)).data,
        enabled: tab === 'disputes',
    });

    const verify = useMutation({
        mutationFn: (id: number) => api.put(`/admin/users/${id}/verify`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });

    const resolve = useMutation({
        mutationFn: ({ id, resolution }: { id: number; resolution: string }) =>
            api.put(`/admin/disputes/${id}/resolve`, { resolution }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-disputes'] }),
    });

    return (
        <div>
            <h1 className="mb-6 text-2xl font-bold">Administration</h1>
            <div className="mb-4 flex gap-2 text-sm">
                {(['users', 'disputes'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={`rounded-md border px-3 py-1.5 ${tab === t ? 'border-brand-600 bg-brand-50 text-brand-700' : ''}`}
                    >
                        {t === 'users' ? 'Utilisateurs' : 'Litiges'}
                    </button>
                ))}
            </div>

            {tab === 'users' && (
                users.isLoading ? <Loader /> : (
                    <>
                        <div className="overflow-hidden rounded-lg border bg-white">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr><th className="p-3">Nom</th><th className="p-3">Email</th><th className="p-3">Type</th><th className="p-3"></th></tr>
                                </thead>
                                <tbody>
                                    {users.data?.data.map((u) => (
                                        <tr key={u.id} className="border-t">
                                            <td className="p-3">{u.name}</td>
                                            <td className="p-3">{u.email}</td>
                                            <td className="p-3">{u.user_type}</td>
                                            <td className="p-3 text-right">
                                                {u.user_type === 'seller' && (
                                                    <button onClick={() => verify.mutate(u.id)} className="text-brand-600 hover:underline">
                                                        Vérifier KYC
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {users.data?.meta && (
                            <Pagination
                                currentPage={users.data.meta.current_page}
                                lastPage={users.data.meta.last_page}
                                onPage={setUsersPage}
                            />
                        )}
                    </>
                )
            )}

            {tab === 'disputes' && (
                disputes.isLoading ? <Loader /> : (
                    <>
                        <div className="space-y-3">
                            {disputes.data?.data.length === 0 && <p className="text-gray-500">Aucun litige.</p>}
                            {disputes.data?.data.map((o) => (
                                <div key={o.id} className="flex items-center justify-between rounded-lg border bg-white p-4">
                                    <div>
                                        <p className="font-medium">{o.order_number}</p>
                                        <p className="text-sm text-gray-500">{date(o.created_at)} · {money(o.total_amount, o.currency)}</p>
                                    </div>
                                    <select
                                        defaultValue=""
                                        onChange={(e) => e.target.value && resolve.mutate({ id: o.id, resolution: e.target.value })}
                                        className="rounded-md border px-2 py-1 text-sm"
                                    >
                                        <option value="">Résoudre…</option>
                                        <option value="refunded">Rembourser</option>
                                        <option value="delivered">Marquer livré</option>
                                        <option value="cancelled">Annuler</option>
                                    </select>
                                </div>
                            ))}
                        </div>
                        {disputes.data?.meta && (
                            <Pagination
                                currentPage={disputes.data.meta.current_page}
                                lastPage={disputes.data.meta.last_page}
                                onPage={setDisputesPage}
                            />
                        )}
                    </>
                )
            )}
        </div>
    );
}
