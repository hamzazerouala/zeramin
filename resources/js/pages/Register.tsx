import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api, apiErrorMessage } from '@/lib/api';
import { useAuth } from '@/stores/auth';
import Alert from '@/components/Alert';
import type { User } from '@/types';

export default function Register() {
    const setSession = useAuth((s) => s.setSession);
    const navigate = useNavigate();
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        user_type: 'buyer',
        shop_name: '',
    });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const set = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }));

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const { data } = await api.post<{ user: User; token: string }>('/auth/register', form);
            setSession(data.user, data.token);
            navigate(form.user_type === 'seller' ? '/seller' : '/');
        } catch (err) {
            setError(apiErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-sm">
            <h1 className="mb-6 text-2xl font-bold">Créer un compte</h1>
            <form onSubmit={submit} className="space-y-4">
                {error && <Alert>{error}</Alert>}

                <div className="flex gap-2 text-sm">
                    {(['buyer', 'seller'] as const).map((t) => (
                        <button
                            key={t}
                            type="button"
                            onClick={() => set('user_type', t)}
                            className={`flex-1 rounded-md border px-3 py-2 ${form.user_type === t ? 'border-brand-600 bg-brand-50 text-brand-700' : ''}`}
                        >
                            {t === 'buyer' ? 'Client' : 'Vendeur'}
                        </button>
                    ))}
                </div>

                <input placeholder="Nom" value={form.name} onChange={(e) => set('name', e.target.value)} required className="w-full rounded-md border px-3 py-2" />
                <input type="email" placeholder="Email" value={form.email} onChange={(e) => set('email', e.target.value)} required className="w-full rounded-md border px-3 py-2" />
                {form.user_type === 'seller' && (
                    <input placeholder="Nom de la boutique" value={form.shop_name} onChange={(e) => set('shop_name', e.target.value)} required className="w-full rounded-md border px-3 py-2" />
                )}
                <input type="password" placeholder="Mot de passe (12+ caractères)" value={form.password} onChange={(e) => set('password', e.target.value)} required className="w-full rounded-md border px-3 py-2" />
                <input type="password" placeholder="Confirmer le mot de passe" value={form.password_confirmation} onChange={(e) => set('password_confirmation', e.target.value)} required className="w-full rounded-md border px-3 py-2" />

                <button disabled={loading} className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                    Créer mon compte
                </button>
            </form>
            <p className="mt-4 text-center text-sm text-gray-500">
                Déjà inscrit ?{' '}
                <Link to="/login" className="text-brand-600 hover:underline">
                    Se connecter
                </Link>
            </p>
        </div>
    );
}
