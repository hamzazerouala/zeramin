import { useWishlist } from '@/hooks/useWishlist';
import ProductCard from '@/components/ProductCard';
import Loader from '@/components/Loader';

export default function Wishlist() {
    const { data: products, isLoading } = useWishlist();

    if (isLoading) return <Loader />;

    return (
        <div>
            <h1 className="mb-6 text-2xl font-bold">Mes favoris</h1>
            {products && products.length === 0 && <p className="text-gray-500">Aucun favori.</p>}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {products?.map((p) => (
                    <ProductCard key={p.id} product={p} />
                ))}
            </div>
        </div>
    );
}
