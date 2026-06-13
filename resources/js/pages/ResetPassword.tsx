import { useState } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { api, apiErrorMessage } from '@/lib/api';
import Alert from '@/components/Alert';

export default function ResetPassword() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const token = searchParams.get('token') ?? '';
    const email = searchParams.get('email') ?? '';

    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    if (!token || !email) {
        return (
            <div className="mx-auto max-w-sm">
                <Alert>Lien invalide ou expiré. Demandez un nouveau lien de réinitialisation.</Alert>
                <p className="mt-4 text-center text-sm">
                    <Link to="/login" className="text-brand-600 hover:underline">Retour à la connexion</Link>
                </p>
            </div>
        );
    }

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (password !== passwordConfirmation) {
            setError('Les mots de passe ne correspondent pas.');
            return;
        }
        if (password.length < 8) {
            setError('Le mot de passe doit contenir au moins 8 caractères.');
            return;
        }
        setError('');
        setLoading(true);
        try {
            await api.post('/auth/reset-password', {
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            navigate('/login', {
                state: { successMessage: 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.' },
            });
        } catch (err) {
            setError(apiErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-sm">
            <h1 className="mb-2 text-2xl font-bold">Nouveau mot de passe</h1>
            <p className="mb-6 text-sm text-gray-500">Pour le compte <span className="font-medium text-gray-700">{email}</span></p>

            <form onSubmit={submit} className="space-y-4">
                {error && <Alert>{error}</Alert>}

                <div>
                    <label className="mb-1 block text-sm text-gray-600">Nouveau mot de passe</label>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        required
                        minLength={8}
                        className="w-full rounded-md border px-3 py-2 text-sm"
                        placeholder="8 caractères minimum"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm text-gray-600">Confirmer le mot de passe</label>
                    <input
                        type="password"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                        required
                        className="w-full rounded-md border px-3 py-2 text-sm"
                        placeholder="Répétez le mot de passe"
                    />
                </div>

                <button
                    disabled={loading}
                    className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                >
                    {loading ? 'Réinitialisation…' : 'Réinitialiser le mot de passe'}
                </button>
            </form>

            <p className="mt-4 text-center text-sm text-gray-500">
                <Link to="/login" className="text-brand-600 hover:underline">Retour à la connexion</Link>
            </p>
        </div>
    );
}
