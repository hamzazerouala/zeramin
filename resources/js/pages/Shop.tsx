import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import ProductCard from '@/components/ProductCard';
import Pagination from '@/components/Pagination';
import Loader from '@/components/Loader';
import type { Paginated, Product, SellerProfile } from '@/types';

type ShopData = SellerProfile & {
    products_count?: number;
    country?: string;
};

function StarRating({ rating }: { rating?: number }) {
    const r = Math.round((rating ?? 0) * 2) / 2;
    return (
        <div className="flex items-center gap-1">
            {[1, 2, 3, 4, 5].map((star) => (
                <span key={star} className={`text-lg ${r >= star ? 'text-yellow-400' : r >= star - 0.5 ? 'text-yellow-300' : 'text-gray-200'}`}>
                    ★
                </span>
            ))}
            {rating !== undefined && (
                <span className="ml-1 text-sm text-gray-500">{rating.toFixed(1)}</span>
            )}
        </div>
    );
}

export default function Shop() {
    const { slug = '' } = useParams();
    const [tab, setTab] = useState<'products' | 'about'>('products');
    const [page, setPage] = useState(1);

    const { data: shop, isLoading: shopLoading } = useQuery<ShopData>({
        queryKey: ['shop', slug],
        queryFn: async () => (await api.get<ShopData>(`/shops/${slug}`)).data,
        enabled: !!slug,
    });

    const { data: products, isLoading: productsLoading } = useQuery<Paginated<Product>>({
        queryKey: ['shop-products', slug, page],
        queryFn: async () =>
            (await api.get<Paginated<Product>>(`/shops/${slug}/products?page=${page}`)).data,
        enabled: !!slug,
    });

    if (shopLoading) return <Loader />;

    return (
        <div>
            {/* ── Banner & Header ── */}
            {shop && (
                <div className="mb-6 overflow-hidden rounded-xl border bg-white shadow-sm">
                    {/* Bannière */}
                    <div className="relative h-40 w-full sm:h-52">
                        {shop.banner_url ? (
                            <img
                                src={shop.banner_url}
                                alt={`Bannière ${shop.shop_name}`}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <div className="h-full w-full bg-gradient-to-br from-brand-500 to-brand-700" />
                        )}
                    </div>

                    {/* Infos boutique */}
                    <div className="px-6 pb-5 pt-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div className="flex items-end gap-4">
                                {/* Logo */}
                                <div className="-mt-10 flex-shrink-0">
                                    {shop.logo_url ? (
                                        <img
                                            src={shop.logo_url}
                                            alt={shop.shop_name}
                                            className="h-20 w-20 rounded-xl border-4 border-white object-cover shadow"
                                        />
                                    ) : (
                                        <div className="flex h-20 w-20 items-center justify-center rounded-xl border-4 border-white bg-brand-100 shadow">
                                            <span className="text-2xl font-bold text-brand-600">
                                                {shop.shop_name.slice(0, 1).toUpperCase()}
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="text-xl font-bold text-gray-900">{shop.shop_name}</h1>
                                        {shop.is_active && (
                                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                                Vendeur vérifié
                                            </span>
                                        )}
                                    </div>
                                    <StarRating rating={shop.avg_rating} />
                                </div>
                            </div>
                            <div className="text-sm text-gray-500">
                                {shop.products_count !== undefined && (
                                    <span>{shop.products_count} produit{shop.products_count !== 1 ? 's' : ''}</span>
                                )}
                                {shop.country && (
                                    <span className="ml-3">{shop.country}</span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* ── Onglets ── */}
            <div className="mb-5 flex gap-1 border-b">
                {(['products', 'about'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={`px-4 py-2 text-sm font-medium transition-colors ${
                            tab === t
                                ? 'border-b-2 border-brand-600 text-brand-600'
                                : 'text-gray-500 hover:text-gray-800'
                        }`}
                    >
                        {t === 'products' ? 'Produits' : 'À propos'}
                    </button>
                ))}
            </div>

            {/* ── Onglet Produits ── */}
            {tab === 'products' && (
                <>
                    {productsLoading && <Loader />}
                    {products && products.data.length === 0 && (
                        <p className="text-center text-gray-500 py-12">Aucun produit dans cette boutique.</p>
                    )}
                    {products && products.data.length > 0 && (
                        <>
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                {products.data.map((p) => (
                                    <ProductCard key={p.id} product={p} />
                                ))}
                            </div>
                            {products.meta && (
                                <Pagination
                                    currentPage={products.meta.current_page}
                                    lastPage={products.meta.last_page}
                                    onPage={(p) => { setPage(p); window.scrollTo({ top: 0, behavior: 'smooth' }); }}
                                />
                            )}
                        </>
                    )}
                </>
            )}

            {/* ── Onglet À propos ── */}
            {tab === 'about' && shop && (
                <div className="mx-auto max-w-2xl rounded-lg border bg-white p-6">
                    {shop.bio ? (
                        <>
                            <h2 className="mb-3 font-semibold text-gray-800">À propos de {shop.shop_name}</h2>
                            <p className="whitespace-pre-line text-sm leading-relaxed text-gray-600">{shop.bio}</p>
                        </>
                    ) : (
                        <p className="text-sm text-gray-500">Ce vendeur n'a pas encore renseigné sa description.</p>
                    )}
                    {shop.contact_email && (
                        <div className="mt-4 border-t pt-4 text-sm text-gray-500">
                            Contact : <a href={`mailto:${shop.contact_email}`} className="text-brand-600 hover:underline">{shop.contact_email}</a>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
