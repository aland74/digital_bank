class AppNotification {
  final int id;
  final String title;
  final String message;
  final String type;
  final String? icon;
  final String? actionUrl;
  final bool isRead;
  final String? readAt;
  final Map<String, dynamic>? data;
  final String? createdAt;

  const AppNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    this.icon,
    this.actionUrl,
    this.isRead = false,
    this.readAt,
    this.data,
    this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as int,
      title: json['title'] as String,
      message: json['message'] as String,
      type: json['type'] as String,
      icon: json['icon'] as String?,
      actionUrl: json['action_url'] as String?,
      isRead: json['is_read'] as bool? ?? false,
      readAt: json['read_at'] as String?,
      data: json['data'] as Map<String, dynamic>?,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'message': message,
      'type': type,
      'icon': icon,
      'action_url': actionUrl,
      'is_read': isRead,
      'read_at': readAt,
      'data': data,
      'created_at': createdAt,
    };
  }
}
