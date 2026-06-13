import { useEffect, useRef, useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { api, apiErrorMessage } from '@/lib/api';
import { useAuth } from '@/stores/auth';
import Alert from '@/components/Alert';
import type { User } from '@/types';

type LoginResponse =
    | { user: User; token: string; two_factor_required?: false }
    | { two_factor_required: true; message: string };

export default function Login() {
    const setSession = useAuth((s) => s.setSession);
    const navigate = useNavigate();
    const location = useLocation() as { state?: { from?: { pathname: string } } };

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [needs2fa, setNeeds2fa] = useState(false);
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const otpRefs = useRef<(HTMLInputElement | null)[]>([]);

    const [error, setError] = useState('');
    const [info, setInfo] = useState('');
    const [loading, setLoading] = useState(false);

    /* focus automatique sur le premier champ OTP quand le step 2FA s'affiche */
    useEffect(() => {
        if (needs2fa) {
            setTimeout(() => otpRefs.current[0]?.focus(), 50);
        }
    }, [needs2fa]);

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

    const submitCredentials = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const { data } = await api.post<LoginResponse>('/auth/login', { email, password });
            if ('two_factor_required' in data && data.two_factor_required) {
                setNeeds2fa(true);
                setInfo('Saisissez le code à 6 chiffres de votre application d\'authentification.');
            } else {
                const d = data as { user: User; token: string };
                setSession(d.user, d.token);
                navigate(location.state?.from?.pathname ?? '/');
            }
        } catch (err: unknown) {
            const e2 = err as { response?: { status?: number } };
            /* Certaines implémentations retournent HTTP 423 au lieu d'un flag JSON */
            if (e2.response?.status === 423) {
                setNeeds2fa(true);
                setInfo('Saisissez le code à 6 chiffres de votre application d\'authentification.');
            } else {
                setError(apiErrorMessage(err));
            }
        } finally {
            setLoading(false);
        }
    };

    const submitOtp = async (e: React.FormEvent) => {
        e.preventDefault();
        if (otpValue.length < 6) { setError('Veuillez saisir les 6 chiffres.'); return; }
        setError('');
        setLoading(true);
        try {
            const { data } = await api.post<{ user: User; token: string }>('/auth/login', {
                email,
                password,
                code: otpValue,
            });
            setSession(data.user, data.token);
            navigate(location.state?.from?.pathname ?? '/');
        } catch (err: unknown) {
            setError(apiErrorMessage(err));
            setOtp(['', '', '', '', '', '']);
            setTimeout(() => otpRefs.current[0]?.focus(), 50);
        } finally {
            setLoading(false);
        }
    };

    /* ——— Step 2 : saisie OTP ——— */
    if (needs2fa) {
        return (
            <div className="mx-auto max-w-sm">
                <div className="mb-6 flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => { setNeeds2fa(false); setError(''); setInfo(''); setOtp(['', '', '', '', '', '']); }}
                        className="rounded p-1 text-gray-400 hover:text-gray-700"
                        aria-label="Retour"
                    >
                        ←
                    </button>
                    <h1 className="text-2xl font-bold">Vérification 2FA</h1>
                </div>

                {info && (
                    <p className="mb-4 rounded-md bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        {info}
                    </p>
                )}
                {error && <Alert>{error}</Alert>}

                <form onSubmit={submitOtp} className="space-y-6">
                    {/* Boîtes OTP */}
                    <div className="flex justify-center gap-2">
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
                                className="h-12 w-10 rounded-md border-2 text-center text-lg font-semibold focus:border-brand-600 focus:outline-none"
                            />
                        ))}
                    </div>

                    <button
                        disabled={loading || otpValue.length < 6}
                        className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                    >
                        {loading ? 'Vérification…' : 'Valider'}
                    </button>
                </form>

                <p className="mt-4 text-center text-xs text-gray-400">
                    Code à 6 chiffres généré par votre application (Google Authenticator, Authy…)
                </p>
            </div>
        );
    }

    /* ——— Step 1 : identifiants ——— */
    return (
        <div className="mx-auto max-w-sm">
            <h1 className="mb-6 text-2xl font-bold">Connexion</h1>
            <form onSubmit={submitCredentials} className="space-y-4">
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
                <button
                    disabled={loading}
                    className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                >
                    {loading ? 'Connexion…' : 'Se connecter'}
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
