import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { money } from '@/lib/format';
import Loader from '@/components/Loader';
import type { Paginated, Product } from '@/types';

export default function SellerProducts() {
    const qc = useQueryClient();
    const { data, isLoading } = useQuery({
        queryKey: ['seller-products'],
        queryFn: async () => (await api.get<Paginated<Product>>('/seller/products')).data,
    });

    const archive = useMutation({
        mutationFn: (id: number) => api.delete(`/seller/products/${id}`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['seller-products'] }),
    });

    if (isLoading) return <Loader />;

    return (
        <div>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold">Mes produits</h1>
                <Link to="/seller/products/import" className="rounded-md bg-brand-600 px-3 py-1.5 text-sm text-white hover:bg-brand-700">
                    Importer depuis AliExpress
                </Link>
            </div>

            <div className="overflow-hidden rounded-lg border bg-white">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th className="p-3">Produit</th>
                            <th className="p-3">Prix</th>
                            <th className="p-3">Stock</th>
                            <th className="p-3">Statut</th>
                            <th className="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {data?.data.map((p) => (
                            <tr key={p.id} className="border-t">
                                <td className="p-3">{p.title}</td>
                                <td className="p-3">{money(p.price, p.currency)}</td>
                                <td className="p-3">{p.stock}</td>
                                <td className="p-3">
                                    <span className={p.in_stock ? 'text-green-600' : 'text-red-500'}>
                                        {p.in_stock ? 'En stock' : 'Rupture'}
                                    </span>
                                </td>
                                <td className="p-3 text-right">
                                    <button onClick={() => archive.mutate(p.id)} className="text-gray-400 hover:text-red-500">
                                        Archiver
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {data && data.data.length === 0 && (
                            <tr><td colSpan={5} className="p-6 text-center text-gray-500">Aucun produit.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
