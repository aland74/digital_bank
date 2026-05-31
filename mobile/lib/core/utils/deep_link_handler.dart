import 'dart:async';
import 'package:flutter/material.dart';
import 'package:app_links/app_links.dart';
import '../../app.dart';

class DeepLinkHandler {
  // Singleton pattern
  static final DeepLinkHandler _instance = DeepLinkHandler._internal();
  factory DeepLinkHandler() => _instance;
  DeepLinkHandler._internal();

  final Map<String, String> _routeMapping = {
    '/transactions': '/transactions',
    '/notifications': '/notifications',
    '/dashboard': '/dashboard',
  };

  StreamSubscription? _linkSubscription;

  Future<void> init() async {
    final appLinks = AppLinks();

    // 1. Listen for incoming links while the app is running (background/foreground)
    _linkSubscription = appLinks.uriLinkStream.listen(
      (Uri uri) {
        handleDeepLink(uri);
      },
      onError: (e) {
        debugPrint('Deep link stream error: $e');
      },
    );

    // 2. Handle the initial link when the app is opened from a terminated state
    Future.delayed(const Duration(milliseconds: 500), () async {
      try {
        final initialLink = await appLinks.getInitialLink();
        if (initialLink != null) {
          handleDeepLink(initialLink);
        }
      } catch (e) {
        debugPrint('Failed to get initial deep link: $e');
      }
    });
  }

  void handleDeepLink(Uri uri) {
    debugPrint('Handling deep link: $uri');

    final path = uri.path;
    final route = _routeMapping[path];

    if (route != null) {
      // Use the App.navigatorKey to perform navigation without build context
      App.navigatorKey.currentState?.pushNamed(route);
    } else {
      debugPrint('No route mapping found for path: $path');
    }
  }

  void dispose() {
    _linkSubscription?.cancel();
  }
}
