import { useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { api, apiErrorMessage } from '@/lib/api';
import { useAuth } from '@/stores/auth';
import Alert from '@/components/Alert';
import type { User } from '@/types';

export default function Login() {
    const setSession = useAuth((s) => s.setSession);
    const navigate = useNavigate();
    const location = useLocation() as { state?: { from?: { pathname: string } } };
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [code, setCode] = useState('');
    const [needs2fa, setNeeds2fa] = useState(false);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const { data } = await api.post<{ user: User; token: string }>('/auth/login', {
                email,
                password,
                code: code || undefined,
            });
            setSession(data.user, data.token);
            navigate(location.state?.from?.pathname ?? '/');
        } catch (err: unknown) {
            const e2 = err as { response?: { status?: number } };
            if (e2.response?.status === 423) {
                setNeeds2fa(true);
                setError('Saisissez votre code d\'authentification à deux facteurs.');
            } else {
                setError(apiErrorMessage(err));
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-sm">
            <h1 className="mb-6 text-2xl font-bold">Connexion</h1>
            <form onSubmit={submit} className="space-y-4">
                {error && <Alert>{error}</Alert>}
                <input
                    type="email"
                    placeholder="Email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    className="w-full rounded-md border px-3 py-2"
                />
                <input
                    type="password"
                    placeholder="Mot de passe"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    className="w-full rounded-md border px-3 py-2"
                />
                {needs2fa && (
                    <input
                        placeholder="Code 2FA"
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        className="w-full rounded-md border px-3 py-2"
                    />
                )}
                <button
                    disabled={loading}
                    className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                >
                    Se connecter
                </button>
            </form>
            <p className="mt-4 text-center text-sm text-gray-500">
                Pas de compte ?{' '}
                <Link to="/register" className="text-brand-600 hover:underline">
                    Créer un compte
                </Link>
            </p>
        </div>
    );
}
