import { Link, useNavigate } from 'react-router-dom';
import { useCart, useUpdateCartItem, useRemoveCartItem, useApplyCoupon } from '@/hooks/useCart';
import { money } from '@/lib/format';
import { apiErrorMessage } from '@/lib/api';
import { useState } from 'react';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';

export default function CartPage() {
    const { data: cart, isLoading } = useCart();
    const updateItem = useUpdateCartItem();
    const removeItem = useRemoveCartItem();
    const applyCoupon = useApplyCoupon();
    const [coupon, setCoupon] = useState('');
    const navigate = useNavigate();

    if (isLoading) return <Loader />;

    if (!cart || cart.items.length === 0) {
        return (
            <div className="py-16 text-center">
                <p className="text-gray-500">Votre panier est vide.</p>
                <Link to="/" className="mt-3 inline-block text-brand-600 hover:underline">
                    Parcourir le catalogue
                </Link>
            </div>
        );
    }

    return (
        <div className="grid gap-8 lg:grid-cols-3">
            <div className="space-y-3 lg:col-span-2">
                <h1 className="text-2xl font-bold">Mon panier</h1>
                {cart.items.map((item) => (
                    <div key={item.id} className="flex items-center gap-4 rounded-lg border bg-white p-3">
                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded bg-gray-100">
                            {item.thumbnail && <img src={item.thumbnail} alt="" className="h-full w-full object-cover" />}
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-medium">{item.title}</p>
                            <p className="text-sm text-gray-500">{money(item.unit_price, cart.currency)}</p>
                        </div>
                        <input
                            type="number"
                            min={1}
                            value={item.quantity}
                            onChange={(e) => updateItem.mutate({ id: item.id, quantity: Math.max(1, Number(e.target.value)) })}
                            className="w-16 rounded-md border px-2 py-1"
                        />
                        <div className="w-20 text-right font-medium">{money(item.subtotal, cart.currency)}</div>
                        <button onClick={() => removeItem.mutate(item.id)} className="text-gray-400 hover:text-red-500">
                            ✕
                        </button>
                    </div>
                ))}
            </div>

            <div className="h-fit rounded-lg border bg-white p-5">
                <h2 className="text-lg font-semibold">Résumé</h2>
                <div className="mt-4 flex justify-between">
                    <span>Sous-total</span>
                    <span className="font-medium">{money(cart.subtotal ?? 0, cart.currency)}</span>
                </div>

                <div className="mt-4">
                    <div className="flex gap-2">
                        <input
                            value={coupon}
                            onChange={(e) => setCoupon(e.target.value)}
                            placeholder="Code promo"
                            className="flex-1 rounded-md border px-3 py-1.5 text-sm"
                        />
                        <button
                            onClick={() => applyCoupon.mutate(coupon)}
                            className="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50"
                        >
                            Appliquer
                        </button>
                    </div>
                    {cart.coupon_code && <p className="mt-2 text-sm text-green-600">Code appliqué : {cart.coupon_code}</p>}
                    {applyCoupon.isError && <div className="mt-2"><Alert>{apiErrorMessage(applyCoupon.error)}</Alert></div>}
                </div>

                <button
                    onClick={() => navigate('/checkout')}
                    className="mt-5 w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700"
                >
                    Passer la commande
                </button>
            </div>
        </div>
    );
}
