import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { date } from '@/lib/format';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import Pagination from '@/components/Pagination';
import type { Paginated, SupportTicket } from '@/types';

const statusLabels: Record<string, string> = {
    open: 'Ouvert',
    pending: 'En attente',
    resolved: 'Résolu',
    closed: 'Fermé',
    escalated: 'Escaladé',
};

const statusColors: Record<string, string> = {
    open: 'bg-blue-50 text-blue-700',
    pending: 'bg-yellow-50 text-yellow-700',
    resolved: 'bg-green-50 text-green-700',
    closed: 'bg-gray-100 text-gray-600',
    escalated: 'bg-red-50 text-red-700',
};

export default function Tickets() {
    const [page, setPage] = useState(1);
    const [showForm, setShowForm] = useState(false);
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [formErr, setFormErr] = useState('');
    const qc = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['my-tickets', page],
        queryFn: async () => (await api.get<Paginated<SupportTicket>>(`/tickets?page=${page}`)).data,
    });

    const create = useMutation({
        mutationFn: () => api.post('/tickets', { subject, message }),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['my-tickets'] });
            setSubject('');
            setMessage('');
            setShowForm(false);
        },
        onError: (err) => setFormErr(apiErrorMessage(err)),
    });

    const handleCreate = () => {
        setFormErr('');
        if (!subject.trim() || !message.trim()) {
            setFormErr('Le sujet et le message sont obligatoires.');
            return;
        }
        create.mutate();
    };

    if (isLoading) return <Loader />;

    return (
        <div>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold">Support</h1>
                {!showForm && (
                    <button
                        onClick={() => setShowForm(true)}
                        className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Nouveau ticket
                    </button>
                )}
            </div>

            {showForm && (
                <div className="mb-6 rounded-lg border bg-white p-5">
                    <h2 className="mb-4 font-semibold">Nouveau ticket</h2>
                    {formErr && <div className="mb-3"><Alert>{formErr}</Alert></div>}
                    <div className="space-y-3">
                        <div>
                            <label className="mb-1 block text-sm text-gray-600">Sujet *</label>
                            <input
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="Décrivez brièvement votre problème"
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm text-gray-600">Message *</label>
                            <textarea
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                rows={4}
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="Détaillez votre demande…"
                            />
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            onClick={handleCreate}
                            disabled={create.isPending}
                            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            {create.isPending ? 'Envoi…' : 'Envoyer'}
                        </button>
                        <button
                            onClick={() => setShowForm(false)}
                            className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            )}

            <div className="space-y-3">
                {data?.data.length === 0 && (
                    <p className="text-gray-500">Aucun ticket. Ouvrez-en un si vous avez besoin d'aide.</p>
                )}
                {data?.data.map((ticket) => (
                    <Link
                        key={ticket.id}
                        to={`/account/tickets/${ticket.id}`}
                        className="flex items-center justify-between rounded-lg border bg-white p-4 hover:shadow-sm"
                    >
                        <div>
                            <p className="font-medium">{ticket.subject}</p>
                            <p className="text-sm text-gray-500">{date(ticket.created_at)}</p>
                        </div>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[ticket.status] ?? 'bg-gray-100 text-gray-600'}`}>
                            {statusLabels[ticket.status] ?? ticket.status}
                        </span>
                    </Link>
                ))}
            </div>

            {data?.meta && (
                <Pagination
                    currentPage={data.meta.current_page}
                    lastPage={data.meta.last_page}
                    onPage={setPage}
                />
            )}
        </div>
    );
}
