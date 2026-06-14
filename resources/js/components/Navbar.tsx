import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useAuth } from '@/stores/auth';
import { useCart } from '@/hooks/useCart';
import type { AppNotification } from '@/types';

function NotificationBell() {
    const { user } = useAuth();
    const qc = useQueryClient();
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const { data } = useQuery<{ data: AppNotification[]; unread_count: number }>({
        queryKey: ['notifications'],
        queryFn: async () => (await api.get('/notifications')).data,
        enabled: !!user,
        refetchInterval: 30_000,
    });

    const unread = data?.unread_count ?? 0;
    const items = data?.data ?? [];

    // Fermer au clic extérieur
    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const markRead = async (id: string) => {
        await api.post(`/notifications/${id}/read`);
        qc.invalidateQueries({ queryKey: ['notifications'] });
    };

    const markAllRead = async () => {
        await api.post('/notifications/read-all');
        qc.invalidateQueries({ queryKey: ['notifications'] });
    };

    if (!user) return null;

    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen((o) => !o)}
                className="relative p-1 text-gray-600 hover:text-brand-600"
                aria-label="Notifications"
            >
                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {unread > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 top-8 z-50 w-80 rounded-lg border bg-white shadow-lg">
                    <div className="flex items-center justify-between border-b px-4 py-2">
                        <span className="text-sm font-semibold">Notifications</span>
                        {unread > 0 && (
                            <button onClick={markAllRead} className="text-xs text-brand-600 hover:underline">
                                Tout marquer lu
                            </button>
                        )}
                    </div>
                    <div className="max-h-72 overflow-y-auto">
                        {items.length === 0 ? (
                            <p className="px-4 py-6 text-center text-sm text-gray-400">Aucune notification</p>
                        ) : (
                            items.map((n) => (
                                <div
                                    key={n.id}
                                    className={`flex cursor-pointer items-start gap-3 border-b px-4 py-3 last:border-0 hover:bg-gray-50 ${!n.read_at ? 'bg-blue-50' : ''}`}
                                    onClick={() => { markRead(n.id); setOpen(false); }}
                                >
                                    <div className="mt-0.5 flex-shrink-0">
                                        {n.data.type === 'ticket_message' ? (
                                            <span className="text-blue-500">💬</span>
                                        ) : (
                                            <span className="text-green-500">📦</span>
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-gray-800">
                                            {n.data.type === 'ticket_message'
                                                ? `Ticket : ${n.data.subject ?? ''}`
                                                : `Commande ${n.data.order_number ?? ''}`}
                                        </p>
                                        <p className="truncate text-xs text-gray-500">
                                            {n.data.type === 'ticket_message'
                                                ? (n.data.sender ?? 'Support')
                                                : `Statut : ${n.data.status ?? ''}`}
                                        </p>
                                    </div>
                                    {!n.read_at && (
                                        <span className="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-brand-600" />
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Navbar() {
    const { user, logout } = useAuth();
    const { data: cart } = useCart();
    const navigate = useNavigate();
    const count = cart?.item_count ?? 0;
    const [menuOpen, setMenuOpen] = useState(false);

    const handleLogout = async () => {
        await logout();
        navigate('/');
        setMenuOpen(false);
    };

    return (
        <header className="sticky top-0 z-10 border-b bg-white">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <Link to="/" className="text-xl font-bold text-brand-700">DropShop</Link>

                {/* Menu desktop */}
                <nav className="hidden items-center gap-4 text-sm md:flex">
                    <Link to="/" className="hover:text-brand-600">Catalogue</Link>

                    {user?.user_type === 'seller' && (
                        <Link to="/seller" className="font-medium text-brand-600">Espace vendeur</Link>
                    )}
                    {user?.user_type === 'admin' && (
                        <Link to="/admin" className="font-medium text-brand-600">Admin</Link>
                    )}
                    {user && (
                        <Link to="/account/wishlist" className="hover:text-brand-600">Favoris</Link>
                    )}
                    {user && (
                        <Link to="/account/tickets" className="hover:text-brand-600">Support</Link>
                    )}

                    <Link to="/cart" className="relative hover:text-brand-600">
                        Panier
                        {count > 0 && (
                            <span className="absolute -right-3 -top-2 rounded-full bg-brand-600 px-1.5 text-xs text-white">{count}</span>
                        )}
                    </Link>

                    {user ? (
                        <>
                            <NotificationBell />
                            <Link to="/account/orders" className="hover:text-brand-600">{user.name}</Link>
                            <Link to="/account/profile" className="hover:text-brand-600">Mon profil</Link>
                            <button onClick={handleLogout} className="text-gray-500 hover:text-gray-800">Déconnexion</button>
                        </>
                    ) : (
                        <Link to="/login" className="rounded-md bg-brand-600 px-3 py-1.5 text-white hover:bg-brand-700">Connexion</Link>
                    )}
                </nav>

                {/* Bouton hamburger mobile */}
                <button
                    className="flex flex-col gap-1.5 p-2 md:hidden"
                    onClick={() => setMenuOpen((o) => !o)}
                    aria-label="Menu"
                >
                    <span className={`block h-0.5 w-6 bg-gray-700 transition-transform ${menuOpen ? 'translate-y-2 rotate-45' : ''}`} />
                    <span className={`block h-0.5 w-6 bg-gray-700 transition-opacity ${menuOpen ? 'opacity-0' : ''}`} />
                    <span className={`block h-0.5 w-6 bg-gray-700 transition-transform ${menuOpen ? '-translate-y-2 -rotate-45' : ''}`} />
                </button>
            </div>

            {/* Menu mobile déroulant */}
            {menuOpen && (
                <nav className="border-t bg-white px-4 py-3 text-sm md:hidden">
                    <div className="flex flex-col gap-3">
                        <Link to="/" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">Catalogue</Link>
                        <Link to="/cart" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">
                            Panier {count > 0 && <span className="ml-1 rounded-full bg-brand-600 px-1.5 text-xs text-white">{count}</span>}
                        </Link>
                        {user?.user_type === 'seller' && (
                            <Link to="/seller" onClick={() => setMenuOpen(false)} className="font-medium text-brand-600">Espace vendeur</Link>
                        )}
                        {user?.user_type === 'admin' && (
                            <Link to="/admin" onClick={() => setMenuOpen(false)} className="font-medium text-brand-600">Admin</Link>
                        )}
                        {user && <>
                            <Link to="/account/orders" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">Mes commandes</Link>
                            <Link to="/account/profile" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">Mon profil</Link>
                            <Link to="/account/wishlist" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">Favoris</Link>
                            <Link to="/account/tickets" onClick={() => setMenuOpen(false)} className="hover:text-brand-600">Support</Link>
                            <button onClick={handleLogout} className="text-left text-gray-500 hover:text-gray-800">Déconnexion</button>
                        </>}
                        {!user && (
                            <Link to="/login" onClick={() => setMenuOpen(false)} className="rounded-md bg-brand-600 px-3 py-1.5 text-center text-white hover:bg-brand-700">Connexion</Link>
                        )}
                    </div>
                </nav>
            )}
        </header>
    );
}
