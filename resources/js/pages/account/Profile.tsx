import { useEffect, useRef, useState } from 'react';
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
/*  Section 2FA                                                         */
/* ------------------------------------------------------------------ */
function TwoFactorSection() {
    const { user, loadProfile } = useAuth();
    const [step, setStep] = useState<'idle' | 'setup' | 'disable'>('idle');
    const [qrUrl, setQrUrl] = useState('');
    const [secret, setSecret] = useState('');
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const otpRefs = useRef<(HTMLInputElement | null)[]>([]);
    const [msg, setMsg] = useState('');
    const [err, setErr] = useState('');
    const [loading, setLoading] = useState(false);

    const otpValue = otp.join('');

    const handleOtpChange = (idx: number, val: string) => {
        const digit = val.replace(/\D/g, '').slice(-1);
        const next = [...otp];
        next[idx] = digit;
        setOtp(next);
        if (digit && idx < 5) otpRefs.current[idx + 1]?.focus();
    };

    const handleOtpKeyDown = (idx: number, e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Backspace' && !otp[idx] && idx > 0) {
            otpRefs.current[idx - 1]?.focus();
        }
    };

    const handleOtpPaste = (e: React.ClipboardEvent<HTMLInputElement>) => {
        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        if (pasted.length === 6) {
            setOtp(pasted.split(''));
            otpRefs.current[5]?.focus();
        }
        e.preventDefault();
    };

    const startSetup = async () => {
        setErr(''); setMsg(''); setLoading(true);
        try {
            const { data } = await api.post<{ qr_code_url: string; secret: string }>('/auth/2fa/setup');
            setQrUrl(data.qr_code_url);
            setSecret(data.secret);
            setStep('setup');
            setTimeout(() => otpRefs.current[0]?.focus(), 100);
        } catch (e) { setErr(apiErrorMessage(e)); }
        finally { setLoading(false); }
    };

    const confirmSetup = async () => {
        if (otpValue.length < 6) { setErr('Saisissez les 6 chiffres.'); return; }
        setErr(''); setLoading(true);
        try {
            await api.post('/auth/2fa/verify', { code: otpValue });
            await loadProfile();
            setStep('idle'); setMsg('Authentification à deux facteurs activée.');
            setOtp(['', '', '', '', '', '']);
        } catch (e) { setErr(apiErrorMessage(e)); setOtp(['', '', '', '', '', '']); setTimeout(() => otpRefs.current[0]?.focus(), 50); }
        finally { setLoading(false); }
    };

    const startDisable = () => {
        setStep('disable'); setErr(''); setMsg('');
        setTimeout(() => otpRefs.current[0]?.focus(), 100);
    };

    const confirmDisable = async () => {
        if (otpValue.length < 6) { setErr('Saisissez les 6 chiffres.'); return; }
        setErr(''); setLoading(true);
        try {
            await api.post('/auth/2fa/disable', { code: otpValue });
            await loadProfile();
            setStep('idle'); setMsg('Authentification à deux facteurs désactivée.');
            setOtp(['', '', '', '', '', '']);
        } catch (e) { setErr(apiErrorMessage(e)); setOtp(['', '', '', '', '', '']); setTimeout(() => otpRefs.current[0]?.focus(), 50); }
        finally { setLoading(false); }
    };

    const OtpBoxes = () => (
        <div className="flex gap-2">
            {otp.map((digit, idx) => (
                <input
                    key={idx}
                    ref={(el) => { otpRefs.current[idx] = el; }}
                    type="text"
                    inputMode="numeric"
                    maxLength={1}
                    value={digit}
                    onChange={(e) => handleOtpChange(idx, e.target.value)}
                    onKeyDown={(e) => handleOtpKeyDown(idx, e)}
                    onPaste={handleOtpPaste}
                    className="h-10 w-9 rounded-md border-2 text-center text-base font-semibold focus:border-brand-600 focus:outline-none"
                />
            ))}
        </div>
    );

    return (
        <section className="rounded-lg border bg-white p-5">
            <h2 className="mb-1 font-semibold text-gray-800">Sécurité — Authentification 2FA</h2>
            <p className="mb-4 text-sm text-gray-500">
                {user?.two_factor_enabled
                    ? 'La double authentification est activée sur votre compte.'
                    : 'Protégez votre compte avec une application d\'authentification (Google Authenticator, Authy…).'}
            </p>

            {msg && <div className="mb-3"><Alert type="success">{msg}</Alert></div>}
            {err && <div className="mb-3"><Alert>{err}</Alert></div>}

            {step === 'idle' && (
                user?.two_factor_enabled ? (
                    <button onClick={startDisable}
                        className="rounded-md border border-red-300 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        Désactiver la 2FA
                    </button>
                ) : (
                    <button onClick={startSetup} disabled={loading}
                        className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                        {loading ? 'Chargement…' : 'Activer la 2FA'}
                    </button>
                )
            )}

            {step === 'setup' && (
                <div className="space-y-4">
                    <div className="flex flex-col items-start gap-4 sm:flex-row">
                        <img src={qrUrl} alt="QR code 2FA" className="h-36 w-36 rounded-lg border p-1" />
                        <div className="text-sm">
                            <p className="mb-2 font-medium text-gray-700">1. Scannez ce QR code avec votre application d'authentification.</p>
                            <p className="mb-1 text-gray-500">Ou entrez le code manuellement :</p>
                            <code className="rounded bg-gray-100 px-2 py-1 font-mono text-xs tracking-widest">{secret}</code>
                        </div>
                    </div>
                    <div>
                        <p className="mb-2 text-sm font-medium text-gray-700">2. Entrez le code généré pour confirmer :</p>
                        <OtpBoxes />
                    </div>
                    <div className="flex gap-2">
                        <button onClick={confirmSetup} disabled={loading || otpValue.length < 6}
                            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                            {loading ? 'Vérification…' : 'Confirmer l\'activation'}
                        </button>
                        <button onClick={() => { setStep('idle'); setErr(''); setOtp(['', '', '', '', '', '']); }}
                            className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Annuler
                        </button>
                    </div>
                </div>
            )}

            {step === 'disable' && (
                <div className="space-y-4">
                    <p className="text-sm text-gray-600">Entrez votre code 2FA actuel pour confirmer la désactivation :</p>
                    <OtpBoxes />
                    <div className="flex gap-2">
                        <button onClick={confirmDisable} disabled={loading || otpValue.length < 6}
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                            {loading ? 'Vérification…' : 'Confirmer la désactivation'}
                        </button>
                        <button onClick={() => { setStep('idle'); setErr(''); setOtp(['', '', '', '', '', '']); }}
                            className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Annuler
                        </button>
                    </div>
                </div>
            )}
        </section>
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

            {/* --- Sécurité 2FA --- */}
            <TwoFactorSection />
        </div>
    );
}
