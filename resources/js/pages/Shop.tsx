import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import ProductCard from '@/components/ProductCard';
import Loader from '@/components/Loader';
import type { Paginated, Product } from '@/types';

export default function Shop() {
    const { slug = '' } = useParams();

    const { data: shop } = useQuery({
        queryKey: ['shop', slug],
        queryFn: async () => (await api.get(`/shops/${slug}`)).data,
        enabled: !!slug,
    });

    const { data: products, isLoading } = useQuery({
        queryKey: ['shop-products', slug],
        queryFn: async () =>
            (await api.get<Paginated<Product>>(`/shops/${slug}/products`)).data,
        enabled: !!slug,
    });

    return (
        <div>
            {shop && (
                <div className="mb-6 rounded-lg border bg-white p-6">
                    <h1 className="text-2xl font-bold">{shop.shop_name}</h1>
                    {shop.bio && <p className="mt-2 text-gray-600">{shop.bio}</p>}
                    <p className="mt-2 text-sm text-gray-400">{shop.products_count} produits</p>
                </div>
            )}

            {isLoading && <Loader />}
            {products && (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    {products.data.map((p) => (
                        <ProductCard key={p.id} product={p} />
                    ))}
                </div>
            )}
        </div>
    );
}
