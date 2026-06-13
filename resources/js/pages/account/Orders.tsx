import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import type { Paginated, Order } from '@/types';

const statusLabels: Record<string, string> = {
    processing: 'En préparation',
    shipped: 'Expédiée',
    in_transit: 'En transit',
    delivered: 'Livrée',
    disputed: 'Litige',
    refunded: 'Remboursée',
    cancelled: 'Annulée',
};

export default function Orders() {
    const { data, isLoading } = useQuery({
        queryKey: ['my-orders'],
        queryFn: async () => (await api.get<Paginated<Order>>('/orders')).data,
    });

    if (isLoading) return <Loader />;

    return (
        <div>
            <h1 className="mb-6 text-2xl font-bold">Mes commandes</h1>
            {data && data.data.length === 0 && <p className="text-gray-500">Aucune commande.</p>}
            <div className="space-y-3">
                {data?.data.map((order) => (
                    <Link
                        key={order.id}
                        to={`/account/orders/${order.id}`}
                        className="flex items-center justify-between rounded-lg border bg-white p-4 hover:shadow-sm"
                    >
                        <div>
                            <p className="font-medium">{order.order_number}</p>
                            <p className="text-sm text-gray-500">{date(order.created_at)}</p>
                        </div>
                        <div className="text-right">
                            <p className="font-semibold">{money(order.total_amount, order.currency)}</p>
                            <span className="text-xs text-brand-600">{statusLabels[order.status] ?? order.status}</span>
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
}
