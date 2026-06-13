import { useState, useRef, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, apiErrorMessage } from '@/lib/api';
import { date } from '@/lib/format';
import { useAuth } from '@/stores/auth';
import Loader from '@/components/Loader';
import Alert from '@/components/Alert';
import type { SupportTicket } from '@/types';

const statusLabels: Record<string, string> = {
    open: 'Ouvert',
    pending: 'En attente',
    resolved: 'Résolu',
    closed: 'Fermé',
    escalated: 'Escaladé',
};

export default function TicketDetail() {
    const { id } = useParams<{ id: string }>();
    const { user } = useAuth();
    const qc = useQueryClient();
    const [reply, setReply] = useState('');
    const [replyErr, setReplyErr] = useState('');
    const bottomRef = useRef<HTMLDivElement>(null);

    const { data: ticket, isLoading } = useQuery<SupportTicket>({
        queryKey: ['ticket', id],
        queryFn: async () => (await api.get<SupportTicket>(`/tickets/${id}`)).data,
    });

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [ticket?.messages?.length]);

    const sendReply = useMutation({
        mutationFn: () => api.post(`/tickets/${id}/messages`, { message: reply }),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['ticket', id] });
            setReply('');
        },
        onError: (err) => setReplyErr(apiErrorMessage(err)),
    });

    const handleSend = () => {
        setReplyErr('');
        if (!reply.trim()) return;
        sendReply.mutate();
    };

    if (isLoading) return <Loader />;
    if (!ticket) return <p className="text-gray-500">Ticket introuvable.</p>;

    const isClosed = ticket.status === 'resolved' || ticket.status === 'closed';

    return (
        <div className="mx-auto max-w-2xl">
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <h1 className="text-xl font-bold">{ticket.subject}</h1>
                    {ticket.order && (
                        <p className="text-sm text-gray-500">Commande #{ticket.order.order_number}</p>
                    )}
                </div>
                <span className="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                    {statusLabels[ticket.status] ?? ticket.status}
                </span>
            </div>

            {/* Fil de messages */}
            <div className="mb-4 space-y-3 rounded-lg border bg-white p-4">
                {ticket.messages?.map((msg) => {
                    const isMe = msg.user_id === user?.id;
                    return (
                        <div key={msg.id} className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}>
                            <div className={`max-w-[80%] rounded-lg px-4 py-2.5 text-sm ${
                                isMe ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-800'
                            }`}>
                                {!isMe && (
                                    <p className="mb-1 text-xs font-semibold opacity-70">
                                        {msg.user?.name ?? 'Support'}
                                    </p>
                                )}
                                <p className="whitespace-pre-wrap">{msg.message}</p>
                                <p className={`mt-1 text-right text-xs opacity-60`}>{date(msg.created_at)}</p>
                            </div>
                        </div>
                    );
                })}
                <div ref={bottomRef} />
            </div>

            {/* Zone de réponse */}
            {!isClosed ? (
                <div className="rounded-lg border bg-white p-4">
                    {replyErr && <div className="mb-3"><Alert>{replyErr}</Alert></div>}
                    <textarea
                        value={reply}
                        onChange={(e) => setReply(e.target.value)}
                        rows={3}
                        placeholder="Votre réponse…"
                        className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                        onKeyDown={(e) => { if (e.key === 'Enter' && e.ctrlKey) handleSend(); }}
                    />
                    <div className="mt-2 flex items-center justify-between">
                        <p className="text-xs text-gray-400">Ctrl+Entrée pour envoyer</p>
                        <button
                            onClick={handleSend}
                            disabled={sendReply.isPending || !reply.trim()}
                            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            {sendReply.isPending ? 'Envoi…' : 'Répondre'}
                        </button>
                    </div>
                </div>
            ) : (
                <p className="rounded-lg border bg-gray-50 p-3 text-center text-sm text-gray-500">
                    Ce ticket est {statusLabels[ticket.status]?.toLowerCase()}. Créez un nouveau ticket si besoin.
                </p>
            )}
        </div>
    );
}
