import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

class NotificationService {
  // Singleton pattern
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  FirebaseMessaging? _messaging;

  Future<void> init() async {
    try {
      _messaging = FirebaseMessaging.instance;
    } catch (e) {
      debugPrint('Could not initialize FirebaseMessaging: $e');
      return;
    }

    // Request permissions for iOS/Android 13+
    NotificationSettings settings = await _messaging!.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      debugPrint('User granted permission for notifications');
    } else if (settings.authorizationStatus == AuthorizationStatus.provisional) {
      debugPrint('User granted provisional permission for notifications');
    } else {
      debugPrint('User declined or has not decided on permissions');
    }

    // Handle foreground messages
    if (_messaging != null) {
      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        debugPrint('Received foreground message: ${message.notification?.title}');
      });
    }

    // Handle background messages when the app is opened from a notification
    if (_messaging != null) {
      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
        debugPrint('Notification opened from background: ${message.notification?.title}');
      });
    }

    // Handle termination state
    FirebaseMessaging.instance.getInitialMessage().then((RemoteMessage? message) {
      if (message != null) {
        debugPrint('Notification opened from terminated state: ${message.notification?.title}');
      }
    });
  }

  Future<String?> getToken() async {
    try {
      return await _messaging?.getToken();
    } catch (e) {
      debugPrint('Error fetching FCM token: $e');
      return null;
    }
  }
}
