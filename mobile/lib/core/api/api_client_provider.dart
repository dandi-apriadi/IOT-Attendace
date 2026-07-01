import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/secure_storage_provider.dart';
import '../../features/auth/providers/auth_provider.dart';
import 'api_client.dart';

final Provider<ApiClient> apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(secureStorageProvider);

  return ApiClient(
    storage,
    onUnauthorized: () async {
      await ref.read(authProvider.notifier).handleUnauthorized();
    },
  );
});
