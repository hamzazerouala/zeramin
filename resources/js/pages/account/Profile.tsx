import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { useAuth } from '@/stores/auth';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import type { Address } from '@/types';

/* ------------------------------------------------------------------ */
/*  Sous-formulaire adresse                                             */
/* ------------------------------------------------------------------ */
const EMPTY_ADDR: Omit<Address, 'id'> = {
    recipient_name: '',
    phone: '',
    address: '',
    address_complement: '',
    postal_code: '',
    city: '',
    province: '',
    country: 'FR',
    type: 'shipping',
    is_default: false,
};

function AddressForm({
    initial,
    onSave,
    onCancel,
}: {
    initial: Omit<Address, 'id'>;
    onSave: (data: Omit<Address, 'id'>) => void;
    onCancel: () => void;
}) {
    const [form, setForm] = useState<Omit<Address, 'id'>>(initial);
    const f = <K extends keyof Omit<Address, 'id'>>(k: K, v: Omit<Address, 'id'>[K]) =>
        setForm((p: Omit<Address, 'id'>) => ({ ...p, [k]: v }));

    return (
        <div className="space-y-3 rounded-lg border bg-gray-50 p-4 text-sm">
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="mb-1 block text-gray-600">Destinataire *</label>
                    <input value={form.recipient_name} onChange={(e) => f('recipient_name', e.target.value)}
                        className="w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label className="mb-1 block text-gray-600">Téléphone</label>
                    <input value={form.phone ?? ''} onChange={(e) => f('phone', e.target.value)}
                        className="w-full rounded-md border px-3 py-2" />
                </div>
            </div>
            <div>
                <label className="mb-1 block text-gray-600">Adresse *</label>
                <input value={form.address} onChange={(e) => f('address', e.target.value)}
                    className="w-full rounded-md border px-3 py-2" />
            </div>
            <div>
                <label className="mb-1 block text-gray-600">Complément</label>
                <input value={form.address_complement ?? ''} onChange={(e) => f('address_complement', e.target.value)}
                    className="w-full rounded-md border px-3 py-2" />
            </div>
            <div className="grid grid-cols-3 gap-3">
                <div>
                    <label className="mb-1 block text-gray-600">Code postal *</label>
                    <input value={form.postal_code} onChange={(e) => f('postal_code', e.target.value)}
                        className="w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label className="mb-1 block text-gray-600">Ville *</label>
                    <input value={form.city} onChange={(e) => f('city', e.target.value)}
                        className="w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label className="mb-1 block text-gray-600">Pays *</label>
                    <input value={form.country} onChange={(e) => f('country', e.target.value.toUpperCase().slice(0, 2))}
                        className="w-full rounded-md border px-3 py-2 uppercase" maxLength={2} />
                </div>
            </div>
            <div className="flex items-center gap-4">
                <label className="flex items-center gap-2 text-gray-600">
                    <input type="checkbox" checked={form.is_default} onChange={(e) => f('is_default', e.target.checked)} />
                    Adresse par défaut
                </label>
                <select value={form.type ?? 'shipping'} onChange={(e) => f('type', e.target.value)}
                    className="rounded-md border px-2 py-1 text-sm">
                    <option value="shipping">Livraison</option>
                    <option value="billing">Facturation</option>
                    <option value="both">Les deux</option>
                </select>
            </div>
            <div className="flex gap-2 pt-1">
                <button onClick={() => onSave(form)}
                    className="rounded-md bg-brand-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                    Enregistrer
                </button>
                <button onClick={onCancel} className="rounded-md border px-4 py-1.5 text-sm text-gray-600 hover:bg-gray-100">
                    Annuler
                </button>
            </div>
        </div>
    );
}

/* ------------------------------------------------------------------ */
/*  Page principale                                                     */
/* ------------------------------------------------------------------ */
export default function Profile() {
    const { user, loadProfile } = useAuth();
    const qc = useQueryClient();

    /* --- Profil --- */
    const [name, setName] = useState(user?.name ?? '');
    const [phone, setPhone] = useState(user?.phone ?? '');
    const [profileMsg, setProfileMsg] = useState('');
    const [profileErr, setProfileErr] = useState('');
    const [savingProfile, setSavingProfile] = useState(false);

    useEffect(() => {
        setName(user?.name ?? '');
        setPhone(user?.phone ?? '');
    }, [user]);

    const saveProfile = async () => {
        setSavingProfile(true);
        setProfileMsg('');
        setProfileErr('');
        try {
            await api.put('/user/profile', { name, phone: phone || undefined });
            await loadProfile();
            setProfileMsg('Profil mis à jour.');
        } catch (err) {
            setProfileErr(apiErrorMessage(err));
        } finally {
            setSavingProfile(false);
        }
    };

    /* --- Adresses --- */
    const { data: addresses, isLoading: addrLoading } = useQuery<Address[]>({
        queryKey: ['my-addresses'],
        queryFn: async () => (await api.get<{ data: Address[] }>('/user/addresses')).data.data,
    });

    const [showAddForm, setShowAddForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const addMutation = useMutation({
        mutationFn: (data: Omit<Address, 'id'>) => api.post('/user/addresses', data),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['my-addresses'] }); setShowAddForm(false); },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: number; data: Omit<Address, 'id'> }) =>
            api.put(`/user/addresses/${id}`, data),
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['my-addresses'] }); setEditingId(null); },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => api.delete(`/user/addresses/${id}`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['my-addresses'] }),
    });

    return (
        <div className="mx-auto max-w-2xl space-y-8">
            <h1 className="text-2xl font-bold">Mon profil</h1>

            {/* --- Informations personnelles --- */}
            <section className="rounded-lg border bg-white p-5">
                <h2 className="mb-4 font-semibold text-gray-800">Informations personnelles</h2>
                {profileMsg && <div className="mb-3"><Alert type="success">{profileMsg}</Alert></div>}
                {profileErr && <div className="mb-3"><Alert>{profileErr}</Alert></div>}
                <div className="space-y-3">
                    <div>
                        <label className="mb-1 block text-sm text-gray-600">Nom</label>
                        <input value={name} onChange={(e) => setName(e.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm text-gray-600">Email</label>
                        <input value={user?.email ?? ''} disabled
                            className="w-full rounded-md border bg-gray-50 px-3 py-2 text-sm text-gray-500" />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm text-gray-600">Téléphone</label>
                        <input value={phone} onChange={(e) => setPhone(e.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                </div>
                <button onClick={saveProfile} disabled={savingProfile}
                    className="mt-4 rounded-md bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                    {savingProfile ? 'Enregistrement…' : 'Enregistrer'}
                </button>
            </section>

            {/* --- Adresses --- */}
            <section className="rounded-lg border bg-white p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="font-semibold text-gray-800">Mes adresses</h2>
                    {!showAddForm && (
                        <button onClick={() => setShowAddForm(true)}
                            className="text-sm text-brand-600 hover:underline">
                            + Nouvelle adresse
                        </button>
                    )}
                </div>

                {showAddForm && (
                    <div className="mb-4">
                        <AddressForm
                            initial={EMPTY_ADDR}
                            onSave={(data) => addMutation.mutate(data)}
                            onCancel={() => setShowAddForm(false)}
                        />
                    </div>
                )}

                {addrLoading && <Loader />}

                <div className="space-y-3">
                    {addresses?.map((addr) =>
                        editingId === addr.id ? (
                            <AddressForm
                                key={addr.id}
                                initial={{ ...addr }}
                                onSave={(data) => updateMutation.mutate({ id: addr.id, data })}
                                onCancel={() => setEditingId(null)}
                            />
                        ) : (
                            <div key={addr.id}
                                className="flex items-start justify-between rounded-lg border p-3 text-sm">
                                <div>
                                    <p className="font-medium">
                                        {addr.recipient_name}
                                        {addr.is_default && (
                                            <span className="ml-2 rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">
                                                Par défaut
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-gray-500">{addr.address}{addr.address_complement ? `, ${addr.address_complement}` : ''}</p>
                                    <p className="text-gray-500">{addr.postal_code} {addr.city}, {addr.country}</p>
                                    {addr.phone && <p className="text-gray-500">{addr.phone}</p>}
                                </div>
                                <div className="flex gap-3 text-xs">
                                    <button onClick={() => setEditingId(addr.id)}
                                        className="text-brand-600 hover:underline">Modifier</button>
                                    <button onClick={() => deleteMutation.mutate(addr.id)}
                                        className="text-red-500 hover:underline">Supprimer</button>
                                </div>
                            </div>
                        )
                    )}
                    {addresses?.length === 0 && !showAddForm && (
                        <p className="text-sm text-gray-500">Aucune adresse enregistrée.</p>
                    )}
                </div>
            </section>
        </div>
    );
}
