import { Link } from 'react-router-dom';

export default function NotFound() {
    return (
        <div className="py-20 text-center">
            <h1 className="text-3xl font-bold">404</h1>
            <p className="mt-2 text-gray-500">Page introuvable.</p>
            <Link to="/" className="mt-4 inline-block text-brand-600 hover:underline">
                Retour au catalogue
            </Link>
        </div>
    );
}
