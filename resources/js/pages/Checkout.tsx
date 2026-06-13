import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { loadStripe } from '@stripe/stripe-js';
import { Elements, PaymentElement, useStripe, useElements } from '@stripe/react-stripe-js';
import { useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { useCart } from '@/hooks/useCart';
import { money } from '@/lib/format';
import Alert from '@/components/Alert';
import type { CheckoutBreakdown } from '@/types';

const stripePromise = loadStripe((import.meta.env.VITE_STRIPE_PUBLIC_KEY as string) || '');

interface AddressForm {
    recipient_name: string;
    phone: string;
    address: string;
    postal_code: string;
    city: string;
    country: string;
    email: string;
}

export default function Checkout() {
    const { data: cart } = useCart();
    const [address, setAddress] = useState<AddressForm>({
        recipient_name: '',
        phone: '',
        address: '',
        postal_code: '',
        city: '',
        country: 'FR',
        email: '',
    });
    const [clientSecret, setClientSecret] = useState('');
    const [paymentIntentId, setPaymentIntentId] = useState('');
    const [breakdown, setBreakdown] = useState<CheckoutBreakdown | null>(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const set = (k: keyof AddressForm, v: string) => setAddress((a) => ({ ...a, [k]: v }));

    const startPayment = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const { data } = await api.post('/checkout/create-payment-intent', {
                shipping_address: {
                    recipient_name: address.recipient_name,
                    phone: address.phone,
                    address: address.address,
                    postal_code: address.postal_code,
                    city: address.city,
                    country: address.country,
                    email: address.email,
                },
                customer_email: address.email || undefined,
            });
            setClientSecret(data.client_secret);
            setPaymentIntentId(data.payment_intent_id);
            setBreakdown(data.breakdown);
        } catch (err) {
            setError(apiErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    if (!cart || cart.items.length === 0) {
        return <p className="py-16 text-center text-gray-500">Votre panier est vide.</p>;
    }

    return (
        <div className="mx-auto max-w-xl">
            <h1 className="mb-6 text-2xl font-bold">Paiement</h1>
            {error && <div className="mb-4"><Alert>{error}</Alert></div>}

            {!clientSecret ? (
                <form onSubmit={startPayment} className="space-y-4">
                    <h2 className="font-semibold">Adresse de livraison</h2>
                    <input required placeholder="Nom complet" value={address.recipient_name} onChange={(e) => set('recipient_name', e.target.value)} className="w-full rounded-md border px-3 py-2" />
                    <input required type="email" placeholder="Email" value={address.email} onChange={(e) => set('email', e.target.value)} className="w-full rounded-md border px-3 py-2" />
                    <input placeholder="Téléphone" value={address.phone} onChange={(e) => set('phone', e.target.value)} className="w-full rounded-md border px-3 py-2" />
                    <input required placeholder="Adresse" value={address.address} onChange={(e) => set('address', e.target.value)} className="w-full rounded-md border px-3 py-2" />
                    <div className="flex gap-3">
                        <input required placeholder="Code postal" value={address.postal_code} onChange={(e) => set('postal_code', e.target.value)} className="w-1/3 rounded-md border px-3 py-2" />
                        <input required placeholder="Ville" value={address.city} onChange={(e) => set('city', e.target.value)} className="flex-1 rounded-md border px-3 py-2" />
                    </div>
                    <select value={address.country} onChange={(e) => set('country', e.target.value)} className="w-full rounded-md border px-3 py-2">
                        {['FR', 'BE', 'DE', 'ES', 'IT', 'NL', 'GB', 'US', 'CA'].map((c) => (
                            <option key={c} value={c}>{c}</option>
                        ))}
                    </select>
                    <button disabled={loading} className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                        {loading ? 'Calcul en cours…' : 'Continuer vers le paiement'}
                    </button>
                </form>
            ) : (
                <>
                    {breakdown && (
                        <div className="mb-4 rounded-lg border bg-white p-4 text-sm">
                            <div className="flex justify-between"><span>Sous-total</span><span>{money(breakdown.subtotal, cart.currency)}</span></div>
                            {breakdown.discount > 0 && <div className="flex justify-between text-green-600"><span>Remise</span><span>-{money(breakdown.discount, cart.currency)}</span></div>}
                            <div className="flex justify-between"><span>Livraison</span><span>{money(breakdown.shipping, cart.currency)}</span></div>
                            <div className="mt-1 flex justify-between border-t pt-1 font-semibold"><span>Total</span><span>{money(breakdown.total, cart.currency)}</span></div>
                        </div>
                    )}
                    <Elements stripe={stripePromise} options={{ clientSecret }}>
                        <PaymentForm paymentIntentId={paymentIntentId} />
                    </Elements>
                </>
            )}
        </div>
    );
}

function PaymentForm({ paymentIntentId }: { paymentIntentId: string }) {
    const stripe = useStripe();
    const elements = useElements();
    const navigate = useNavigate();
    const qc = useQueryClient();
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);

    const pay = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!stripe || !elements) return;
        setProcessing(true);
        setError('');

        const { error: stripeError } = await stripe.confirmPayment({
            elements,
            redirect: 'if_required',
        });

        if (stripeError) {
            setError(stripeError.message ?? 'Le paiement a échoué.');
            setProcessing(false);
            return;
        }

        try {
            const { data } = await api.post('/payments/confirm', { payment_intent_id: paymentIntentId });
            qc.invalidateQueries({ queryKey: ['cart'] });
            const firstOrder = data.orders?.data?.[0] ?? data.orders?.[0];
            if (firstOrder?.id) {
                navigate(`/account/orders/${firstOrder.id}`);
            } else {
                navigate('/account/orders');
            }
        } catch (err) {
            setError(apiErrorMessage(err));
            setProcessing(false);
        }
    };

    return (
        <form onSubmit={pay} className="space-y-4">
            {error && <Alert>{error}</Alert>}
            <PaymentElement />
            <button disabled={!stripe || processing} className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                {processing ? 'Paiement…' : 'Payer maintenant'}
            </button>
        </form>
    );
}
