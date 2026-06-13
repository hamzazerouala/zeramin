import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/stores/auth';
import { useCart } from '@/hooks/useCart';

export default function Navbar() {
    const { user, logout } = useAuth();
    const { data: cart } = useCart();
    const navigate = useNavigate();
    const count = cart?.item_count ?? 0;

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    return (
        <header className="sticky top-0 z-10 border-b bg-white">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <Link to="/" className="text-xl font-bold text-brand-700">DropShop</Link>

                <nav className="flex items-center gap-4 text-sm">
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
            </div>
        </header>
    );
}
