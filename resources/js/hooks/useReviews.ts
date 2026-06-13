import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface Review {
    id: number;
    rating: number;
    title?: string | null;
    content?: string | null;
    verified_purchase: boolean;
    helpful_count: number;
    author?: string;
    created_at?: string;
}

export function useReviews(productId: number) {
    return useQuery({
        queryKey: ['reviews', productId],
        queryFn: async () =>
            (await api.get<{ data: Review[] }>(`/products/${productId}/reviews`)).data.data,
        enabled: !!productId,
    });
}

export function useAddReview(productId: number) {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (payload: { rating: number; title?: string; content?: string }) =>
            api.post(`/products/${productId}/reviews`, payload),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['reviews', productId] });
            qc.invalidateQueries({ queryKey: ['product'] });
        },
    });
}
