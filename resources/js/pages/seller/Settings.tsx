import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
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

export default function SellerSettings() {
    const { data, isLoading } = useQuery({
        queryKey: ['seller-settings'],
        queryFn: async () => (await api.get('/seller/settings')).data,
    });

    const [zones, setZones] = useState<ShippingZone[]>([]);
    const [aliexpressAccount, setAliexpressAccount] = useState('');
    const [aliexpressPassword, setAliexpressPassword] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (data) {
            setZones(data.shipping_zones ?? []);
            setAliexpressAccount(data.integrations?.aliexpress_account ?? '');
        }
    }, [data]);

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

            <button onClick={save} disabled={saving} className="mt-6 rounded-md bg-brand-600 px-5 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                {saving ? 'Enregistrement…' : 'Enregistrer'}
            </button>
        </div>
    );
}
