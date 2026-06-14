<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Notifications\TicketMessageReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /** Client : liste ses propres tickets. */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with(['order:id,order_number'])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    /** Client : crée un ticket. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'message'  => ['required', 'string', 'max:5000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $ticket = SupportTicket::create([
            'user_id'  => $request->user()->id,
            'order_id' => $data['order_id'] ?? null,
            'subject'  => $data['subject'],
            'priority' => $data['priority'] ?? 'normal',
            'status'   => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'message'   => $data['message'],
        ]);

        return response()->json($ticket->load('messages.user:id,name'), 201);
    }

    /** Client + Admin : détail d'un ticket avec ses messages. */
    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();

        // Un client ne peut voir que ses propres tickets (sauf admin).
        if ($user->user_type !== 'admin' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        return response()->json($ticket->load(['messages.user:id,name', 'order:id,order_number']));
    }

    /** Client + Admin : ajouter un message à un ticket. */
    public function addMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'admin' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'message'   => $data['message'],
        ]);

        // Rouvrir si le ticket était résolu/fermé et que c'est le client qui répond.
        if ($user->user_type !== 'admin' && in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->update(['status' => 'open']);
        }

        // Notifier l'autre partie.
        $message->load('user:id,name');
        $ticket->load('user');
        $recipientIsAdmin = $user->user_type === 'admin';
        // Si admin répond → notifier le client. Si client répond → notifier l'admin (user_type admin).
        $recipient = $recipientIsAdmin
            ? $ticket->user
            : \App\Models\User::where('user_type', 'admin')->first();

        if ($recipient) {
            $recipient->notify(new TicketMessageReceived($ticket, $message));
        }

        return response()->json($message->load('user:id,name'), 201);
    }

    /* ------------------------------------------------------------------ */
    /*  Routes admin uniquement                                             */
    /* ------------------------------------------------------------------ */

    /** Admin : liste tous les tickets avec filtres optionnels. */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user:id,name,email', 'order:id,order_number'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        return response()->json($query->paginate(25));
    }

    /** Admin : changer le statut d'un ticket. */
    public function updateStatus(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,pending,resolved,closed,escalated'],
        ]);

        $ticket->update(['status' => $data['status']]);

        return response()->json(['message' => 'Statut mis à jour.', 'status' => $ticket->status]);
    }
}
