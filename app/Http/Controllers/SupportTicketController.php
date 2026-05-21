<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends Controller
{
    /**
     * List authenticated user's tickets with optional status filter, paginated.
     */
    public function index(Request $request)
    {
        $query = auth()->user()->supportTickets()->with('replies')->latest();

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

        $tickets = $query->paginate(10)->withQueryString();

        // Stats for the current user
        $stats = [
            'total'       => auth()->user()->supportTickets()->count(),
            'open'        => auth()->user()->supportTickets()->where('status', 'open')->count(),
            'in_progress' => auth()->user()->supportTickets()->where('status', 'in_progress')->count(),
            'resolved'    => auth()->user()->supportTickets()->where('status', 'resolved')->count(),
            'closed'      => auth()->user()->supportTickets()->where('status', 'closed')->count(),
        ];

        return view('support.index', compact('tickets', 'stats'));
    }

    /**
     * Show the create ticket form.
     */
    public function create()
    {
        return view('support.create');
    }

    /**
     * Validate and store a new support ticket, notify admins.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'   => 'required|in:account,transaction,card,loan,technical,complaint,general',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string|max:5000',
            'priority'   => 'required|in:low,medium,high,urgent',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
        ]);

        $ticket = SupportTicket::create([
            'user_id'       => auth()->id(),
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject'       => $validated['subject'],
            'message'       => $validated['message'],
            'category'      => $validated['category'],
            'priority'      => $validated['priority'],
            'status'        => 'open',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('support', 'public');
            SupportTicketReply::create([
                'ticket_id'       => $ticket->id,
                'user_id'         => auth()->id(),
                'message'         => '[Attachment uploaded]',
                'is_staff_reply'  => false,
                'attachment_path' => $path,
            ]);
        }

        // Notify admin users about the new ticket
        $admins = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id'    => $admin->id,
                'title'      => 'New Support Ticket 🎫',
                'message'    => "Ticket {$ticket->ticket_number}: {$ticket->subject} (Priority: {$ticket->priority})",
                'type'       => 'info',
                'icon'       => '🎫',
                'action_url' => route('admin.support.show', $ticket),
            ]);
        }

        return redirect()->route('support.show', $ticket)
            ->with('success', __('Your support ticket has been submitted successfully.'));
    }

    /**
     * Show a ticket with its replies (only if user owns it).
     */
    public function show(SupportTicket $ticket)
    {
        // Authorize: user must own the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load(['publicReplies.user', 'user']);

        return view('support.show', compact('ticket'));
    }

    /**
     * Add a reply to a ticket (only if user owns it and ticket is not closed).
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            return back()->with('error', __('This ticket is closed and cannot receive new replies.'));
        }

        $validated = $request->validate([
            'message'     => 'required|string|max:5000',
            'attachment'  => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support', 'public');
        }

        SupportTicketReply::create([
            'ticket_id'       => $ticket->id,
            'user_id'         => auth()->id(),
            'message'         => $validated['message'],
            'is_staff_reply'  => false,
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
                'user_id'    => $admin->id,
                'title'      => 'Ticket Reply 💬',
                'message'    => "Customer replied to ticket {$ticket->ticket_number}: {$ticket->subject}",
                'type'       => 'info',
                'icon'       => '💬',
                'action_url' => route('admin.support.show', $ticket),
            ]);
        }

        return back()->with('success', __('Your reply has been sent.'));
    }

    /**
     * Close a ticket (customer).
     */
    public function close(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            return back()->with('error', __('This ticket is already closed.'));
        }

        $ticket->update([
            'status'      => 'closed',
            'resolved_at' => $ticket->resolved_at ?? now(),
        ]);

        Notification::create([
            'user_id'    => $ticket->user_id,
            'title'      => 'Ticket Closed',
            'message'    => "Your ticket {$ticket->ticket_number} has been closed.",
            'type'       => 'info',
            'icon'       => '🔒',
            'action_url' => route('support.index'),
        ]);

        return redirect()->route('support.index')->with('success', __('Ticket closed successfully.'));
    }
}
