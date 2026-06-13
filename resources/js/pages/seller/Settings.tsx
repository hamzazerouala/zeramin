import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';

interface ShippingZone {
    country: string;
    type: 'fixed' | 'percentage' | 'free';
    cost: number;
    is_free_above: boolean;
    free_above_amount?: number | null;
    delivery_days?: number | null;
}

interface StripeStatus {
    connected: boolean;
    charges_enabled?: boolean;
    payouts_enabled?: boolean;
    details_submitted?: boolean;
    error?: string;
}

export default function SellerSettings() {
    const [searchParams] = useSearchParams();
    const qc = useQueryClient();
    const { data, isLoading } = useQuery({
        queryKey: ['seller-settings'],
        queryFn: async () => (await api.get('/seller/settings')).data,
    });

    const { data: stripeStatus } = useQuery<StripeStatus>({
        queryKey: ['seller-stripe-status'],
        queryFn: async () => (await api.get<StripeStatus>('/seller/stripe/status')).data,
    });

    const [zones, setZones] = useState<ShippingZone[]>([]);
    const [aliexpressAccount, setAliexpressAccount] = useState('');
    const [aliexpressPassword, setAliexpressPassword] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);
    const [stripeLoading, setStripeLoading] = useState(false);

    // Message succès retour Stripe
    const stripeParam = searchParams.get('stripe');

    useEffect(() => {
        if (data) {
            setZones(data.shipping_zones ?? []);
            setAliexpressAccount(data.integrations?.aliexpress_account ?? '');
        }
    }, [data]);

    useEffect(() => {
        if (stripeParam === 'success') {
            setMessage('Compte Stripe connecté avec succès !');
            qc.invalidateQueries({ queryKey: ['seller-stripe-status'] });
        }
    }, [stripeParam, qc]);

    const connectStripe = async () => {
        setStripeLoading(true);
        try {
            const { data: res } = await api.get<{ url: string }>('/seller/stripe/onboard');
            window.location.href = res.url;
        } catch (e) {
            setError(apiErrorMessage(e));
            setStripeLoading(false);
        }
    };

    const addZone = () =>
        setZones((z) => [...z, { country: 'FR', type: 'fixed', cost: 5, is_free_above: false }]);

    const updateZone = (i: number, patch: Partial<ShippingZone>) =>
        setZones((z) => z.map((zone, idx) => (idx === i ? { ...zone, ...patch } : zone)));

    const removeZone = (i: number) => setZones((z) => z.filter((_, idx) => idx !== i));

    const save = async () => {
        setSaving(true);
        setError('');
        setMessage('');
        try {
            await api.put('/seller/settings', {
                aliexpress_account: aliexpressAccount || undefined,
                aliexpress_password: aliexpressPassword || undefined,
                shipping_zones: zones,
            });
            setMessage('Paramètres enregistrés.');
            setAliexpressPassword('');
        } catch (err) {
            setError(apiErrorMessage(err));
        } finally {
            setSaving(false);
        }
    };

    if (isLoading) return <Loader />;

    return (
        <div className="mx-auto max-w-2xl">
            <h1 className="mb-6 text-2xl font-bold">Paramètres</h1>
            {message && <div className="mb-4"><Alert type="success">{message}</Alert></div>}
            {error && <div className="mb-4"><Alert>{error}</Alert></div>}

            <section className="rounded-lg border bg-white p-5">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="font-semibold">Zones de livraison</h2>
                    <button onClick={addZone} className="text-sm text-brand-600 hover:underline">+ Ajouter</button>
                </div>
                <div className="space-y-3">
                    {zones.map((zone, i) => (
                        <div key={i} className="flex flex-wrap items-center gap-2 text-sm">
                            <input
                                value={zone.country}
                                onChange={(e) => updateZone(i, { country: e.target.value.toUpperCase() })}
                                className="w-20 rounded-md border px-2 py-1"
                                placeholder="Pays"
                            />
                            <select value={zone.type} onChange={(e) => updateZone(i, { type: e.target.value as ShippingZone['type'] })} className="rounded-md border px-2 py-1">
                                <option value="fixed">Fixe</option>
                                <option value="percentage">%</option>
                                <option value="free">Gratuit</option>
                            </select>
                            <input
                                type="number"
                                value={zone.cost}
                                onChange={(e) => updateZone(i, { cost: Number(e.target.value) })}
                                className="w-24 rounded-md border px-2 py-1"
                                placeholder="Coût"
                            />
                            <label className="flex items-center gap-1">
                                <input type="checkbox" checked={zone.is_free_above} onChange={(e) => updateZone(i, { is_free_above: e.target.checked })} />
                                Gratuit dès
                            </label>
                            <input
                                type="number"
                                value={zone.free_above_amount ?? ''}
                                onChange={(e) => updateZone(i, { free_above_amount: e.target.value ? Number(e.target.value) : null })}
                                className="w-24 rounded-md border px-2 py-1"
                                placeholder="€"
                            />
                            <button onClick={() => removeZone(i)} className="text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    ))}
                    {zones.length === 0 && <p className="text-sm text-gray-500">Aucune zone (livraison gratuite par défaut).</p>}
                </div>
            </section>

            <section className="mt-6 rounded-lg border bg-white p-5">
                <h2 className="mb-3 font-semibold">Intégration AliExpress</h2>
                <input
                    value={aliexpressAccount}
                    onChange={(e) => setAliexpressAccount(e.target.value)}
                    placeholder="Compte AliExpress (email)"
                    className="mb-3 w-full rounded-md border px-3 py-2 text-sm"
                />
                <input
                    type="password"
                    value={aliexpressPassword}
                    onChange={(e) => setAliexpressPassword(e.target.value)}
                    placeholder="Mot de passe (chiffré au stockage)"
                    className="w-full rounded-md border px-3 py-2 text-sm"
                />
            </section>

            {/* ── Section Stripe Connect ── */}
            <section className="mt-6 rounded-lg border bg-white p-5">
                <h2 className="mb-1 font-semibold">Compte bancaire Stripe</h2>
                <p className="mb-4 text-sm text-gray-500">
                    Connectez votre compte Stripe pour recevoir vos virements automatiquement.
                </p>
                {stripeStatus?.connected ? (
                    <div className="space-y-3">
                        <div className="flex flex-wrap gap-3 text-sm">
                            <span className={`flex items-center gap-1 rounded-full px-3 py-1 font-medium ${stripeStatus.charges_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                {stripeStatus.charges_enabled ? '✓' : '○'} Paiements {stripeStatus.charges_enabled ? 'activés' : 'en attente'}
                            </span>
                            <span className={`flex items-center gap-1 rounded-full px-3 py-1 font-medium ${stripeStatus.payouts_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                {stripeStatus.payouts_enabled ? '✓' : '○'} Virements {stripeStatus.payouts_enabled ? 'activés' : 'en attente'}
                            </span>
                        </div>
                        {!stripeStatus.details_submitted && (
                            <button onClick={connectStripe} disabled={stripeLoading}
                                className="rounded-md border border-brand-600 px-4 py-2 text-sm text-brand-600 hover:bg-brand-50 disabled:opacity-50">
                                {stripeLoading ? 'Redirection…' : 'Compléter mon profil Stripe'}
                            </button>
                        )}
                    </div>
                ) : (
                    <button onClick={connectStripe} disabled={stripeLoading}
                        className="flex items-center gap-2 rounded-md bg-[#635BFF] px-5 py-2 text-sm font-medium text-white hover:bg-[#4f46e5] disabled:opacity-50">
                        {stripeLoading ? 'Redirection…' : (
                            <>
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/></svg>
                                Connecter Stripe
                            </>
                        )}
                    </button>
                )}
            </section>

            <button onClick={save} disabled={saving} className="mt-6 rounded-md bg-brand-600 px-5 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                {saving ? 'Enregistrement…' : 'Enregistrer'}
            </button>
        </div>
    );
}
