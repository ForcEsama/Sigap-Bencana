import 'package:flutter/material.dart';

import '../home_page.dart';

Widget? signedInPageForRole(
  String? role, {
  bool offlineMode = false,
  VoidCallback? onRetryConnection,
}) {
  return switch (role) {
    'masyarakat' => HomePage(
      offlineMode: offlineMode,
      onRetryConnection: onRetryConnection,
    ),
    'admin' || 'petugas_lapangan' => HomePage(
      offlineMode: offlineMode,
      onRetryConnection: onRetryConnection,
    ),
    _ => null,
  };
}
