import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/notification_repository.dart';
import '../../data/models/notification.dart';
import 'notifications_event.dart';
import 'notifications_state.dart';

class NotificationsBloc extends Bloc<NotificationsEvent, NotificationsState> {
  final NotificationRepository _repository;

  NotificationsBloc(this._repository) : super(NotificationsLoading()) {
    on<FetchNotifications>(_onFetchNotifications);
    on<MarkAsRead>(_onMarkAsRead);
    on<MarkAllAsRead>(_onMarkAllAsRead);
  }

  Future<void> _onFetchNotifications(
    FetchNotifications event,
    Emitter<NotificationsState> emit,
  ) async {
    emit(NotificationsLoading());
    final response = await _repository.getLatest();
    if (response.success) {
      emit(NotificationsLoaded(response.data ?? []));
    } else {
      emit(NotificationsError(response.message ?? 'Failed to fetch notifications'));
    }
  }

  Future<void> _onMarkAsRead(
    MarkAsRead event,
    Emitter<NotificationsState> emit,
  ) async {
    final currentState = state;
    if (currentState is NotificationsLoaded) {
      final response = await _repository.markAsRead(event.notificationId);
      if (response.success) {
        // Update local state to reflect read status
        final updatedNotifications = currentState.notifications.map((n) {
          if (n.id == event.notificationId) {
            return AppNotification(
              id: n.id,
              title: n.title,
              message: n.message,
              type: n.type,
              icon: n.icon,
              actionUrl: n.actionUrl,
              isRead: true,
              readAt: DateTime.now().toIso8601String(),
              data: n.data,
              createdAt: n.createdAt,
            );
          }
          return n;
        }).toList();
        emit(NotificationsLoaded(updatedNotifications));
      }
    }
  }

  Future<void> _onMarkAllAsRead(
    MarkAllAsRead event,
    Emitter<NotificationsState> emit,
  ) async {
    final currentState = state;
    if (currentState is NotificationsLoaded) {
      final response = await _repository.markAllAsRead();
      if (response.success) {
        final updatedNotifications = currentState.notifications.map((n) {
          return AppNotification(
            id: n.id,
            title: n.title,
            message: n.message,
            type: n.type,
            icon: n.icon,
            actionUrl: n.actionUrl,
            isRead: true,
            readAt: DateTime.now().toIso8601String(),
            data: n.data,
            createdAt: n.createdAt,
          );
        }).toList();
        emit(NotificationsLoaded(updatedNotifications));
      }
    }
  }
}
