<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'replies', 'assignee'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => SupportTicket::count(),
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
            'closed'      => SupportTicket::where('status', 'closed')->count(),
        ];

        return view('admin.support.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['user', 'replies.user', 'assignee']);

        // Get all staff for assignment dropdown
        $staff = User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get();

        return view('admin.support.show', compact('ticket', 'staff'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message'     => 'required|string|max:5000',
            'attachment'  => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
            'is_internal' => 'nullable|boolean',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support', 'public');
        }

        $isInternal = $request->boolean('is_internal');

        SupportTicketReply::create([
            'ticket_id'       => $ticket->id,
            'user_id'         => auth()->id(),
            'message'         => $validated['message'],
            'is_staff_reply'  => true,
            'is_internal'     => $isInternal,
            'attachment_path' => $attachmentPath,
        ]);

        // Update ticket status
        if (!$isInternal && in_array($ticket->status, ['open', 'in_progress'])) {
            $ticket->update([
                'status'      => 'awaiting_response',
                'assigned_to' => $ticket->assigned_to ?? auth()->id(),
            ]);
        }

        // Notify the ticket owner
        if (!$isInternal) {
            Notification::create([
                'user_id'    => $ticket->user_id,
                'title'      => 'Staff Reply on Ticket 💬',
                'message'    => "Staff responded to your ticket {$ticket->ticket_number}: {$ticket->subject}",
                'type'       => 'info',
                'icon'       => '💬',
                'action_url' => route('support.show', $ticket),
            ]);
        }

        return back()->with('success', $isInternal ? 'Internal note added.' : 'Reply sent to customer.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,awaiting_response,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        // Notify the ticket owner
        Notification::create([
            'user_id'    => $ticket->user_id,
            'title'      => 'Ticket Status Updated',
            'message'    => "Your ticket {$ticket->ticket_number} status changed from " . ucwords(str_replace('_', ' ', $oldStatus)) . " to " . ucwords(str_replace('_', ' ', $validated['status'])) . ".",
            'type'       => $validated['status'] === 'resolved' ? 'success' : 'info',
            'icon'       => $validated['status'] === 'resolved' ? '✅' : '🔄',
            'action_url' => route('support.show', $ticket),
        ]);

        AuditLog::log('ticket_status_updated', [
            'model_type' => 'SupportTicket',
            'model_id'   => $ticket->id,
            'old_values'  => ['status' => $oldStatus],
            'new_values'  => ['status' => $validated['status']],
        ]);

        return back()->with('success', "Ticket status updated to {$validated['status']}.");
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $assignee = User::findOrFail($validated['assigned_to']);

        $ticket->update(['assigned_to' => $assignee->id]);

        Notification::create([
            'user_id'    => $ticket->user_id,
            'title'      => 'Ticket Assigned',
            'message'    => "Your ticket {$ticket->ticket_number} has been assigned to {$assignee->name}.",
            'type'       => 'info',
            'icon'       => '👤',
            'action_url' => route('support.show', $ticket),
        ]);

        AuditLog::log('ticket_assigned', [
            'model_type'  => 'SupportTicket',
            'model_id'    => $ticket->id,
            'new_values'  => ['assigned_to' => $assignee->name],
        ]);

        return back()->with('success', "Ticket assigned to {$assignee->name}.");
    }
}
