import { useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import Navbar from '@/components/Navbar';
import { useAuth } from '@/stores/auth';

export default function Layout() {
    const loadProfile = useAuth((s) => s.loadProfile);

    useEffect(() => {
        loadProfile();
    }, [loadProfile]);

    return (
        <div className="min-h-screen">
            <Navbar />
            <main className="mx-auto max-w-6xl px-4 py-8">
                <Outlet />
            </main>
            <footer className="border-t py-8 text-center text-sm text-gray-400">
                DropShop — propulsé par Laravel & React
            </footer>
        </div>
    );
}
