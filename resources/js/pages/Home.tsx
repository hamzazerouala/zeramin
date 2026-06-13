import { useState } from 'react';
import { useProducts, type ProductFilters } from '@/hooks/useProducts';
import ProductCard from '@/components/ProductCard';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';

export default function Home() {
    const [filters, setFilters] = useState<ProductFilters>({ sort: 'featured' });
    const { data, isLoading, isError } = useProducts(filters);

    return (
        <div>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-bold">Catalogue</h1>

                <div className="flex gap-2">
                    <select
                        value={filters.sort}
                        onChange={(e) => setFilters((f) => ({ ...f, sort: e.target.value }))}
                        className="rounded-md border px-3 py-1.5 text-sm"
                    >
                        <option value="featured">Mis en avant</option>
                        <option value="recent">Plus récents</option>
                        <option value="price_asc">Prix croissant</option>
                        <option value="price_desc">Prix décroissant</option>
                        <option value="rating">Mieux notés</option>
                    </select>
                    <input
                        type="number"
                        placeholder="Prix max"
                        className="w-28 rounded-md border px-3 py-1.5 text-sm"
                        onChange={(e) =>
                            setFilters((f) => ({ ...f, max_price: e.target.value ? Number(e.target.value) : undefined }))
                        }
                    />
                </div>
            </div>

            {isLoading && <Loader />}
            {isError && <Alert>Impossible de charger les produits.</Alert>}

            {data && (
                <>
                    {data.data.length === 0 ? (
                        <p className="py-16 text-center text-gray-500">Aucun produit pour le moment.</p>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            {data.data.map((p) => (
                                <ProductCard key={p.id} product={p} />
                            ))}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
