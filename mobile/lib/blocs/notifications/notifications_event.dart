import 'package:equatable/equatable.dart';

abstract class NotificationsEvent extends Equatable {
  @override
  List<Object?> get props => [];
}

class FetchNotifications extends NotificationsEvent {}

class MarkAsRead extends NotificationsEvent {
  final int notificationId;
  MarkAsRead(this.notificationId);

  @override
  List<Object?> get props => [notificationId];
}

class MarkAllAsRead extends NotificationsEvent {}
