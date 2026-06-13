interface Props {
    currentPage: number;
    lastPage: number;
    onPage: (page: number) => void;
}

export default function Pagination({ currentPage, lastPage, onPage }: Props) {
    if (lastPage <= 1) return null;

    const pages: (number | '...')[] = [];
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || Math.abs(i - currentPage) <= 1) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }

    return (
        <div className="mt-4 flex items-center justify-center gap-1 text-sm">
            <button
                disabled={currentPage === 1}
                onClick={() => onPage(currentPage - 1)}
                className="rounded-md border px-3 py-1.5 disabled:opacity-40 hover:bg-gray-50"
            >
                &lsaquo;
            </button>

            {pages.map((p, i) =>
                p === '...' ? (
                    <span key={`e-${i}`} className="px-2 text-gray-400">…</span>
                ) : (
                    <button
                        key={p}
                        onClick={() => onPage(p)}
                        className={`rounded-md border px-3 py-1.5 ${
                            p === currentPage
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'hover:bg-gray-50'
                        }`}
                    >
                        {p}
                    </button>
                )
            )}

            <button
                disabled={currentPage === lastPage}
                onClick={() => onPage(currentPage + 1)}
                className="rounded-md border px-3 py-1.5 disabled:opacity-40 hover:bg-gray-50"
            >
                &rsaquo;
            </button>
        </div>
    );
}
