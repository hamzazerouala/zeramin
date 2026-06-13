import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { Product } from '@/types';

export function useWishlist(enabled = true) {
    return useQuery({
        queryKey: ['wishlist'],
        queryFn: async () => (await api.get<{ data: Product[] }>('/wishlist')).data.data,
        enabled,
    });
}

export function useToggleWishlist() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ productId, inWishlist }: { productId: number; inWishlist: boolean }) =>
            inWishlist
                ? api.delete(`/wishlist/items/${productId}`)
                : api.post('/wishlist/items', { product_id: productId }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['wishlist'] }),
    });
}
