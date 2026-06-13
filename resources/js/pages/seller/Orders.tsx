import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import Pagination from '@/components/Pagination';
import type { Paginated, Order } from '@/types';

const STATUSES = ['processing', 'shipped', 'in_transit', 'delivered', 'disputed', 'cancelled'];

export default function SellerOrders() {
    const qc = useQueryClient();
    const [page, setPage] = useState(1);
    const [tracking, setTracking] = useState<Record<number, string>>({});

    const { data, isLoading } = useQuery({
        queryKey: ['seller-orders', page],
        queryFn: async () => (await api.get<Paginated<Order>>(`/seller/orders?page=${page}`)).data,
    });

    const updateStatus = useMutation({
        mutationFn: ({ id, status, trackingId }: { id: number; status: string; trackingId?: string }) =>
            api.put(`/seller/orders/${id}/status`, {
                status,
                aliexpress_tracking_id: trackingId || undefined,
            }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['seller-orders'] }),
    });

    if (isLoading) return <Loader />;

    return (
        <div>
            <h1 className="mb-6 text-2xl font-bold">Commandes</h1>
            {updateStatus.isError && <div className="mb-4"><Alert>{apiErrorMessage(updateStatus.error)}</Alert></div>}

            <div className="space-y-3">
                {data?.data.map((order) => (
                    <div key={order.id} className="rounded-lg border bg-white p-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="font-medium">{order.order_number}</p>
                                <p className="text-sm text-gray-500">{date(order.created_at)} · {money(order.total_amount, order.currency)}</p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <input
                                    placeholder="N° suivi"
                                    value={tracking[order.id] ?? order.tracking?.tracking_id ?? ''}
                                    onChange={(e) => setTracking((t) => ({ ...t, [order.id]: e.target.value }))}
                                    className="w-32 rounded-md border px-2 py-1 text-sm"
                                />
                                <select
                                    defaultValue={order.status}
                                    onChange={(e) =>
                                        updateStatus.mutate({ id: order.id, status: e.target.value, trackingId: tracking[order.id] })
                                    }
                                    className="rounded-md border px-2 py-1 text-sm"
                                >
                                    {STATUSES.map((s) => (
                                        <option key={s} value={s}>{s}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                ))}
                {data && data.data.length === 0 && <p className="text-gray-500">Aucune commande.</p>}
            </div>
            {data?.meta && (
                <Pagination
                    currentPage={data.meta.current_page}
                    lastPage={data.meta.last_page}
                    onPage={setPage}
                />
            )}
        </div>
    );
}
