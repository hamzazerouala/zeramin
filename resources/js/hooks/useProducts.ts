import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { Paginated, Product } from '@/types';

export interface ProductFilters {
    category?: string;
    min_price?: number;
    max_price?: number;
    min_rating?: number;
    featured?: boolean;
    sort?: string;
    per_page?: number;
}

export function useProducts(filters: ProductFilters = {}) {
    return useQuery({
        queryKey: ['products', filters],
        queryFn: async () =>
            (await api.get<Paginated<Product>>('/products', { params: filters })).data,
    });
}

export function useProduct(slug: string) {
    return useQuery({
        queryKey: ['product', slug],
        queryFn: async () => (await api.get<{ data: Product }>(`/products/${slug}`)).data.data,
        enabled: !!slug,
    });
}

export function useProductSearch(q: string) {
    return useQuery({
        queryKey: ['product-search', q],
        queryFn: async () =>
            (await api.get<Paginated<Product>>('/products/search', { params: { q } })).data,
        enabled: q.length >= 2,
    });
}
