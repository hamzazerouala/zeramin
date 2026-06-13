export default function Loader({ label = 'Chargement…' }: { label?: string }) {
    return (
        <div className="flex items-center justify-center py-16 text-gray-500">
            <span className="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600" />
            <span className="ml-3">{label}</span>
        </div>
    );
}
