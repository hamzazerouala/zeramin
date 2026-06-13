import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, apiErrorMessage } from '@/lib/api';
import Alert from '@/components/Alert';

export default function ImportProduct() {
    const navigate = useNavigate();
    const [url, setUrl] = useState('');
    const [coef, setCoef] = useState('2');
    const [fixed, setFixed] = useState('2.5');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            await api.post('/seller/products/import-aliexpress', {
                url,
                markup_coefficient: Number(coef),
                markup_fixed: Number(fixed),
            });
            navigate('/seller/products');
        } catch (err) {
            setError(apiErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-lg">
            <h1 className="mb-6 text-2xl font-bold">Importer un produit AliExpress</h1>
            <form onSubmit={submit} className="space-y-4">
                {error && <Alert>{error}</Alert>}
                <input
                    required
                    type="url"
                    placeholder="https://www.aliexpress.com/item/..."
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                    className="w-full rounded-md border px-3 py-2"
                />
                <div className="flex gap-3">
                    <label className="flex-1 text-sm">
                        Coefficient
                        <input type="number" step="0.1" min="1" value={coef} onChange={(e) => setCoef(e.target.value)} className="mt-1 w-full rounded-md border px-3 py-2" />
                    </label>
                    <label className="flex-1 text-sm">
                        Marge fixe (€)
                        <input type="number" step="0.5" min="0" value={fixed} onChange={(e) => setFixed(e.target.value)} className="mt-1 w-full rounded-md border px-3 py-2" />
                    </label>
                </div>
                <button disabled={loading} className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                    {loading ? 'Import en cours…' : 'Importer'}
                </button>
            </form>
            <p className="mt-4 text-sm text-gray-400">
                Le titre, les images et le prix sont récupérés automatiquement. Le prix de vente = coût × coefficient + marge fixe.
            </p>
        </div>
    );
}
