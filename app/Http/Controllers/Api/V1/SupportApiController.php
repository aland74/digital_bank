<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportApiController extends Controller
{
    /**
     * List tickets with optional status filter and search.
     */
    public function index(Request $request)
    {
        $query = $request->user()->supportTickets()->with('replies')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(20);

        return response()->json([
            'tickets' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'stats' => [
                'total' => $request->user()->supportTickets()->count(),
                'open' => $request->user()->supportTickets()->where('status', 'open')->count(),
                'in_progress' => $request->user()->supportTickets()->where('status', 'in_progress')->count(),
                'resolved' => $request->user()->supportTickets()->where('status', 'resolved')->count(),
                'closed' => $request->user()->supportTickets()->where('status', 'closed')->count(),
            ],
        ]);
    }

    /**
     * Create a new support ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:account,transaction,card,loan,technical,complaint,general',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('support', 'public');
            SupportTicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => '[Attachment uploaded]',
                'is_staff_reply' => false,
                'attachment_path' => $path,
            ]);
        }

        // Notify admin users about the new ticket
        $admins = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Support Ticket',
                'message' => "Ticket {$ticket->ticket_number}: {$ticket->subject} (Priority: {$ticket->priority})",
                'type' => 'info',
                'icon' => '🎫',
            ]);
        }

        return response()->json([
            'message' => 'Your support ticket has been submitted successfully.',
            'ticket' => $ticket->fresh(),
        ], 201);
    }

    /**
     * Show a ticket with its replies.
     */
    public function show(Request $request, $id)
    {
        $ticket = $request->user()->supportTickets()->with(['publicReplies.user', 'user'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        return response()->json([
            'ticket' => $ticket,
        ]);
    }

    /**
     * Add a reply to a ticket.
     */
    public function reply(Request $request, $id)
    {
        $ticket = $request->user()->supportTickets()->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'This ticket is closed and cannot receive new replies.'], 422);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support', 'public');
        }

        $reply = SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_staff_reply' => false,
            'attachment_path' => $attachmentPath,
        ]);

        // Update ticket status to open if it was awaiting response
        if ($ticket->status === 'awaiting_response') {
            $ticket->update(['status' => 'open']);
        }

        // Notify assigned admin or all admins
        $notifyUsers = $ticket->assigned_to
            ? collect([User::find($ticket->assigned_to)])
            : User::where('role', 'admin')->orWhere('role', 'super_admin')->get();

        foreach ($notifyUsers->filter() as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Ticket Reply',
                'message' => "Customer replied to ticket {$ticket->ticket_number}: {$ticket->subject}",
                'type' => 'info',
                'icon' => '💬',
            ]);
        }

        return response()->json([
            'message' => 'Your reply has been sent.',
            'reply' => $reply->fresh(),
        ]);
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, $id)
    {
        $ticket = $request->user()->supportTickets()->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'This ticket is already closed.'], 422);
        }

        $ticket->update([
            'status' => 'closed',
            'resolved_at' => $ticket->resolved_at ?? now(),
        ]);

        Notification::create([
            'user_id' => $ticket->user_id,
            'title' => 'Ticket Closed',
            'message' => "Your ticket {$ticket->ticket_number} has been closed.",
            'type' => 'info',
            'icon' => '🔒',
        ]);

        return response()->json([
            'message' => 'Ticket closed successfully.',
            'ticket' => $ticket->fresh(),
        ]);
    }
}
