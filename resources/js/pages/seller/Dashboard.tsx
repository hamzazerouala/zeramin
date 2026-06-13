import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import type { Order } from '@/types';

interface DashboardData {
    revenue_month: number;
    orders_total: number;
    avg_basket: number;
    orders_by_status: Record<string, number>;
    low_stock_products: { id: number; title: string; stock_platform: number }[];
    recent_orders: Order[];
}

function Kpi({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border bg-white p-4">
            <p className="text-sm text-gray-500">{label}</p>
            <p className="mt-1 text-2xl font-bold">{value}</p>
        </div>
    );
}

export default function SellerDashboard() {
    const { data, isLoading } = useQuery({
        queryKey: ['seller-dashboard'],
        queryFn: async () => (await api.get<DashboardData>('/seller/dashboard')).data,
    });

    if (isLoading) return <Loader />;
    if (!data) return null;

    return (
        <div>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold">Tableau de bord</h1>
                <nav className="flex gap-3 text-sm">
                    <Link to="/seller/products" className="text-brand-600 hover:underline">Produits</Link>
                    <Link to="/seller/products/import" className="text-brand-600 hover:underline">Importer</Link>
                    <Link to="/seller/orders" className="text-brand-600 hover:underline">Commandes</Link>
                    <Link to="/seller/settings" className="text-brand-600 hover:underline">Paramètres</Link>
                </nav>
            </div>

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <Kpi label="CA du mois" value={money(data.revenue_month)} />
                <Kpi label="Commandes" value={String(data.orders_total)} />
                <Kpi label="Panier moyen" value={money(data.avg_basket)} />
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <div>
                    <h2 className="mb-3 font-semibold">Commandes récentes</h2>
                    <div className="space-y-2">
                        {data.recent_orders.length === 0 && <p className="text-sm text-gray-500">Aucune commande.</p>}
                        {data.recent_orders.map((o) => (
                            <div key={o.id} className="flex justify-between rounded-md border bg-white p-3 text-sm">
                                <span>{o.order_number}</span>
                                <span className="text-gray-500">{date(o.created_at)}</span>
                                <span className="font-medium">{money(o.total_amount, o.currency)}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">Stock bas</h2>
                    <div className="space-y-2">
                        {data.low_stock_products.length === 0 && <p className="text-sm text-gray-500">Tout est en stock.</p>}
                        {data.low_stock_products.map((p) => (
                            <div key={p.id} className="flex justify-between rounded-md border bg-white p-3 text-sm">
                                <span className="truncate">{p.title}</span>
                                <span className="font-medium text-red-500">{p.stock_platform}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
