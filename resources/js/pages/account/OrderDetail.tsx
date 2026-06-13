import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import type { Order } from '@/types';

export default function OrderDetail() {
    const { id } = useParams();
    const { data: order, isLoading } = useQuery({
        queryKey: ['order', id],
        queryFn: async () => (await api.get<{ data: Order }>(`/orders/${id}`)).data.data,
        enabled: !!id,
    });

    if (isLoading) return <Loader />;
    if (!order) return <p>Commande introuvable.</p>;

    return (
        <div className="mx-auto max-w-2xl">
            <h1 className="text-2xl font-bold">{order.order_number}</h1>
            <p className="mt-1 text-sm text-gray-500">{date(order.created_at)} · {order.status}</p>

            <div className="mt-6 rounded-lg border bg-white">
                {order.items?.map((item) => (
                    <div key={item.id} className="flex justify-between border-b p-3 last:border-0">
                        <span>{item.title} × {item.quantity}</span>
                        <span className="font-medium">{money(item.subtotal, order.currency)}</span>
                    </div>
                ))}
            </div>

            <div className="mt-4 space-y-1 text-sm">
                <div className="flex justify-between"><span>Sous-total</span><span>{money(order.subtotal, order.currency)}</span></div>
                <div className="flex justify-between"><span>Livraison</span><span>{money(order.shipping_cost, order.currency)}</span></div>
                <div className="flex justify-between text-base font-semibold"><span>Total</span><span>{money(order.total_amount, order.currency)}</span></div>
            </div>

            {order.tracking?.tracking_id && (
                <div className="mt-6 rounded-lg border bg-white p-4 text-sm">
                    <p className="font-medium">Suivi</p>
                    <p className="mt-1 text-gray-600">N° {order.tracking.tracking_id}</p>
                    {order.tracking.tracking_url && (
                        <a href={order.tracking.tracking_url} target="_blank" rel="noreferrer" className="text-brand-600 hover:underline">
                            Suivre le colis
                        </a>
                    )}
                </div>
            )}
        </div>
    );
}
