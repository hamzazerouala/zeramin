import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/stores/auth';
import { useCart } from '@/hooks/useCart';

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
