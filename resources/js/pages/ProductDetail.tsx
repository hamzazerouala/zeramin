import { useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useProduct } from '@/hooks/useProducts';
import { useAddToCart } from '@/hooks/useCart';
import { useWishlist, useToggleWishlist } from '@/hooks/useWishlist';
import { useAuth } from '@/stores/auth';
import { money } from '@/lib/format';
import { apiErrorMessage } from '@/lib/api';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import Reviews from '@/components/Reviews';

export default function ProductDetail() {
    const { slug = '' } = useParams();
    const { data: product, isLoading, isError } = useProduct(slug);
    const addToCart = useAddToCart();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { data: wishlist } = useWishlist(!!user);
    const toggleWishlist = useToggleWishlist();
    const [active, setActive] = useState(0);
    const [variantId, setVariantId] = useState<number | null>(null);
    const [qty, setQty] = useState(1);

    if (isLoading) return <Loader />;
    if (isError || !product) return <Alert>Produit introuvable.</Alert>;

    const images = product.images ?? [];
    const inWishlist = !!wishlist?.some((p) => p.id === product.id);

    const handleAdd = () => {
        addToCart.mutate(
            { product_id: product.id, variant_id: variantId, quantity: qty },
            { onSuccess: () => navigate('/cart') },
        );
    };

    return (
        <div>
            <div className="grid gap-8 md:grid-cols-2">
                <div>
                    <div className="aspect-square overflow-hidden rounded-lg border bg-gray-100">
                        {images[active] ? (
                            <img src={images[active].url} alt={product.title} className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex h-full items-center justify-center text-gray-300">Sans image</div>
                        )}
                    </div>
                    {images.length > 1 && (
                        <div className="mt-3 flex gap-2 overflow-x-auto">
                            {images.map((img, i) => (
                                <button
                                    key={img.id}
                                    onClick={() => setActive(i)}
                                    className={`h-16 w-16 shrink-0 overflow-hidden rounded border ${i === active ? 'ring-2 ring-brand-600' : ''}`}
                                >
                                    <img src={img.url} alt="" className="h-full w-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <div className="flex items-start justify-between">
                        <h1 className="text-2xl font-bold">{product.title}</h1>
                        {user && (
                            <button
                                onClick={() => toggleWishlist.mutate({ productId: product.id, inWishlist })}
                                className="ml-3 text-2xl"
                                title={inWishlist ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                            >
                                <span className={inWishlist ? 'text-red-500' : 'text-gray-300'}>♥</span>
                            </button>
                        )}
                    </div>

                    {product.seller && (
                        <Link to={`/shops/${product.seller.shop_slug}`} className="mt-1 inline-block text-sm text-brand-600">
                            {product.seller.shop_name}
                        </Link>
                    )}

                    <div className="mt-4 text-3xl font-semibold text-brand-700">{money(product.price, product.currency)}</div>

                    <div className="mt-2 text-sm text-gray-500">
                        {product.rating > 0 && <span className="text-amber-500">★ {product.rating.toFixed(1)} ({product.rating_count})</span>}
                        {product.shipping_days ? <span className="ml-3">Livraison ~{product.shipping_days} j</span> : null}
                    </div>

                    {product.variants && product.variants.length > 0 && (
                        <select
                            className="mt-4 w-full rounded-md border px-3 py-2"
                            value={variantId ?? ''}
                            onChange={(e) => setVariantId(e.target.value ? Number(e.target.value) : null)}
                        >
                            <option value="">Choisir une variante</option>
                            {product.variants.map((v) => (
                                <option key={v.id} value={v.id}>{v.name}</option>
                            ))}
                        </select>
                    )}

                    <div className="mt-4 flex items-center gap-3">
                        <input
                            type="number"
                            min={1}
                            value={qty}
                            onChange={(e) => setQty(Math.max(1, Number(e.target.value)))}
                            className="w-20 rounded-md border px-3 py-2"
                        />
                        <button
                            disabled={!product.in_stock || addToCart.isPending}
                            onClick={handleAdd}
                            className="flex-1 rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            {product.in_stock ? 'Ajouter au panier' : 'Rupture de stock'}
                        </button>
                    </div>

                    {addToCart.isError && <div className="mt-3"><Alert>{apiErrorMessage(addToCart.error)}</Alert></div>}

                    {product.description && (
                        <div className="mt-6 whitespace-pre-line text-sm leading-relaxed text-gray-700">{product.description}</div>
                    )}
                </div>
            </div>

            <Reviews productId={product.id} />
        </div>
    );
}
