import { useState } from 'react';
import { useReviews, useAddReview } from '@/hooks/useReviews';
import { useAuth } from '@/stores/auth';
import { date } from '@/lib/format';
import { apiErrorMessage } from '@/lib/api';
import Alert from '@/components/Alert';

function Stars({ value }: { value: number }) {
    return <span className="text-amber-500">{'★'.repeat(value)}{'☆'.repeat(5 - value)}</span>;
}

export default function Reviews({ productId }: { productId: number }) {
    const { data: reviews } = useReviews(productId);
    const { user } = useAuth();
    const addReview = useAddReview(productId);
    const [rating, setRating] = useState(5);
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        addReview.mutate(
            { rating, title, content },
            { onSuccess: () => { setTitle(''); setContent(''); setRating(5); } },
        );
    };

    return (
        <section className="mt-10">
            <h2 className="mb-4 text-lg font-semibold">Avis clients</h2>

            <div className="space-y-4">
                {reviews?.length === 0 && <p className="text-sm text-gray-500">Aucun avis pour le moment.</p>}
                {reviews?.map((r) => (
                    <div key={r.id} className="rounded-lg border bg-white p-4">
                        <div className="flex items-center justify-between">
                            <Stars value={r.rating} />
                            {r.verified_purchase && <span className="text-xs text-green-600">Achat vérifié</span>}
                        </div>
                        {r.title && <p className="mt-1 font-medium">{r.title}</p>}
                        {r.content && <p className="mt-1 text-sm text-gray-700">{r.content}</p>}
                        <p className="mt-2 text-xs text-gray-400">{r.author} · {date(r.created_at)}</p>
                    </div>
                ))}
            </div>

            {user ? (
                <form onSubmit={submit} className="mt-6 rounded-lg border bg-white p-4">
                    <h3 className="mb-3 font-medium">Laisser un avis</h3>
                    {addReview.isError && <div className="mb-3"><Alert>{apiErrorMessage(addReview.error)}</Alert></div>}
                    <select value={rating} onChange={(e) => setRating(Number(e.target.value))} className="mb-3 rounded-md border px-3 py-2">
                        {[5, 4, 3, 2, 1].map((n) => (
                            <option key={n} value={n}>{n} étoile{n > 1 ? 's' : ''}</option>
                        ))}
                    </select>
                    <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Titre (optionnel)" className="mb-3 w-full rounded-md border px-3 py-2" />
                    <textarea value={content} onChange={(e) => setContent(e.target.value)} placeholder="Votre avis" rows={3} className="mb-3 w-full rounded-md border px-3 py-2" />
                    <button disabled={addReview.isPending} className="rounded-md bg-brand-600 px-4 py-2 text-white hover:bg-brand-700 disabled:opacity-50">
                        Publier
                    </button>
                </form>
            ) : (
                <p className="mt-6 text-sm text-gray-500">Connectez-vous pour laisser un avis.</p>
            )}
        </section>
    );
}
