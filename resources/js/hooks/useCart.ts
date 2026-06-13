import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { Cart } from '@/types';

export function useCart() {
    return useQuery({
        queryKey: ['cart'],
        queryFn: async () => (await api.get<{ data: Cart }>('/cart')).data.data,
    });
}

export function useAddToCart() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (payload: { product_id: number; variant_id?: number | null; quantity?: number }) =>
            api.post('/cart/items', payload),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['cart'] }),
    });
}

export function useUpdateCartItem() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ id, quantity }: { id: number; quantity: number }) =>
            api.put(`/cart/items/${id}`, { quantity }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['cart'] }),
    });
}

export function useRemoveCartItem() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => api.delete(`/cart/items/${id}`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['cart'] }),
    });
}

export function useApplyCoupon() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (code: string) => api.post('/cart/apply-coupon', { code }),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['cart'] }),
    });
}
