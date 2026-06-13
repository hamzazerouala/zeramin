import { Link } from 'react-router-dom';
import { money } from '@/lib/format';
import type { Product } from '@/types';

export default function ProductCard({ product }: { product: Product }) {
    return (
        <Link
            to={`/products/${product.slug}`}
            className="group overflow-hidden rounded-lg border bg-white transition hover:shadow-md"
        >
            <div className="aspect-square overflow-hidden bg-gray-100">
                {product.thumbnail ? (
                    <img
                        src={product.thumbnail}
                        alt={product.title}
                        loading="lazy"
                        className="h-full w-full object-cover transition group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center text-gray-300">Sans image</div>
                )}
            </div>
            <div className="p-3">
                <h3 className="line-clamp-2 text-sm font-medium">{product.title}</h3>
                <div className="mt-2 flex items-center justify-between">
                    <span className="font-semibold text-brand-700">{money(product.price, product.currency)}</span>
                    {product.rating > 0 && (
                        <span className="text-xs text-amber-500">★ {product.rating.toFixed(1)}</span>
                    )}
                </div>
                {!product.in_stock && <p className="mt-1 text-xs text-red-500">Rupture de stock</p>}
            </div>
        </Link>
    );
}
