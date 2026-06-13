import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '@/stores/auth';
import Loader from '@/components/Loader';

export default function ProtectedRoute({
    requireSeller = false,
    requireAdmin = false,
}: {
    requireSeller?: boolean;
    requireAdmin?: boolean;
}) {
    const { user, token, initialized } = useAuth();
    const location = useLocation();

    if (token && !initialized) {
        return <Loader />;
    }

    if (!token || !user) {
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    if (requireSeller && user.user_type !== 'seller') {
        return <Navigate to="/" replace />;
    }

    if (requireAdmin && user.user_type !== 'admin') {
        return <Navigate to="/" replace />;
    }

    return <Outlet />;
}
