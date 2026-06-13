import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { money, date } from '@/lib/format';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import type { Payout, PendingAmount } from '@/types';

interface PayoutsData {
    payouts: { data: Payout[]; meta?: { current_page: number; last_page: number; total: number } };
    pending_amount: PendingAmount;
}

const statusLabels: Record<string, string> = {
    pending: 'En attente',
    processing: 'En cours',
    paid: 'Payé',
    failed: 'Échoué',
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-50 text-yellow-700',
    processing: 'bg-blue-50 text-blue-700',
    paid: 'bg-green-50 text-green-700',
    failed: 'bg-red-50 text-red-700',
};

export default function Payouts() {
    const qc = useQueryClient();

    const { data, isLoading } = useQuery<PayoutsData>({
        queryKey: ['seller-payouts'],
        queryFn: async () => (await api.get<PayoutsData>('/seller/payouts')).data,
    });

    const request = useMutation({
        mutationFn: () => api.post('/seller/payouts/request'),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['seller-payouts'] }),
    });

    if (isLoading) return <Loader />;

    const pending = data?.pending_amount;

    return (
        <div>
            <h1 className="mb-6 text-2xl font-bold">Mes virements</h1>

            {/* Solde disponible */}
            <div className="mb-6 rounded-lg border bg-white p-5">
                <h2 className="mb-3 font-semibold text-gray-700">Solde disponible</h2>
                {request.isError && (
                    <div className="mb-3"><Alert>{apiErrorMessage(request.error)}</Alert></div>
                )}
                {request.isSuccess && (
                    <div className="mb-3"><Alert type="success">Demande de virement enregistrée.</Alert></div>
                )}
                <div className="flex items-end gap-6">
                    <div>
                        <p className="text-3xl font-bold text-brand-700">
                            {money(pending?.net_amount ?? 0, 'EUR')}
                        </p>
                        <p className="mt-1 text-sm text-gray-500">
                            Brut&nbsp;: {money(pending?.total_amount ?? 0, 'EUR')} —
                            Commission&nbsp;{((pending?.fee_rate ?? 0.05) * 100).toFixed(0)}&nbsp;%&nbsp;:
                            -{money(pending?.fees_amount ?? 0, 'EUR')}
                        </p>
                    </div>
                    <button
                        onClick={() => request.mutate()}
                        disabled={request.isPending || (pending?.net_amount ?? 0) <= 0}
                        className="ml-auto rounded-md bg-brand-600 px-5 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                    >
                        {request.isPending ? 'Envoi…' : 'Demander un virement'}
                    </button>
                </div>
            </div>

            {/* Historique */}
            <h2 className="mb-3 font-semibold text-gray-700">Historique</h2>
            {data?.payouts.data.length === 0 && (
                <p className="text-sm text-gray-500">Aucun virement pour l'instant.</p>
            )}
            <div className="space-y-3">
                {data?.payouts.data.map((p) => (
                    <div key={p.id} className="flex items-center justify-between rounded-lg border bg-white p-4">
                        <div>
                            <p className="font-medium">{money(p.net_amount, 'EUR')}</p>
                            <p className="text-sm text-gray-500">
                                {date(p.period_start)} → {date(p.period_end)}
                                {p.stripe_payout_id && (
                                    <span className="ml-2 text-xs text-gray-400">#{p.stripe_payout_id}</span>
                                )}
                            </p>
                        </div>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[p.status] ?? 'bg-gray-100 text-gray-600'}`}>
                            {statusLabels[p.status] ?? p.status}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
