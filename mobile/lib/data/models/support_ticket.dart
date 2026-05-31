class TicketReply {
  final int id;
  final int ticketId;
  final String message;
  final String sender;
  final String? createdAt;

  const TicketReply({
    required this.id,
    required this.ticketId,
    required this.message,
    required this.sender,
    this.createdAt,
  });

  factory TicketReply.fromJson(Map<String, dynamic> json) {
    return TicketReply(
      id: json['id'] as int,
      ticketId: json['ticket_id'] as int,
      message: json['message'] as String,
      sender: json['sender'] as String,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'ticket_id': ticketId,
      'message': message,
      'sender': sender,
      'created_at': createdAt,
    };
  }
}

class SupportTicket {
  final int id;
  final String ticketNumber;
  final String subject;
  final String message;
  final String category;
  final String priority;
  final String status;
  final String? assignedTo;
  final String? resolvedAt;
  final String? createdAt;
  final List<TicketReply>? replies;

  const SupportTicket({
    required this.id,
    required this.ticketNumber,
    required this.subject,
    required this.message,
    required this.category,
    required this.priority,
    required this.status,
    this.assignedTo,
    this.resolvedAt,
    this.createdAt,
    this.replies,
  });

  factory SupportTicket.fromJson(Map<String, dynamic> json) {
    return SupportTicket(
      id: json['id'] as int,
      ticketNumber: json['ticket_number'] as String,
      subject: json['subject'] as String,
      message: json['message'] as String,
      category: json['category'] as String,
      priority: json['priority'] as String,
      status: json['status'] as String,
      assignedTo: json['assigned_to'] as String?,
      resolvedAt: json['resolved_at'] as String?,
      createdAt: json['created_at'] as String?,
      replies: json['replies'] != null
          ? (json['replies'] as List)
              .map((e) => TicketReply.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList()
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'ticket_number': ticketNumber,
      'subject': subject,
      'message': message,
      'category': category,
      'priority': priority,
      'status': status,
      'assigned_to': assignedTo,
      'resolved_at': resolvedAt,
      'created_at': createdAt,
      'replies': replies?.map((e) => e.toJson()).toList(),
    };
  }
}
