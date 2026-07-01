import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:mobile/core/api/api_client.dart';
import 'package:mobile/core/storage/secure_storage_service.dart';
import 'package:mobile/features/auth/data/auth_repository.dart';
import 'package:mobile/features/auth/models/user_model.dart';
import 'package:mobile/features/auth/providers/auth_provider.dart';
import 'package:mobile/main.dart';

/// Skips the real secure-storage session restore -- flutter_secure_storage's
/// Windows implementation talks to the OS Credential Manager, which is not
/// available under `flutter test` and would otherwise hang the widget test.
class _ImmediatelyUnauthenticatedRepository extends AuthRepository {
  _ImmediatelyUnauthenticatedRepository()
      : super(
          ApiClient(
            SecureStorageService(const FlutterSecureStorage()),
            onUnauthorized: () async {},
          ),
          SecureStorageService(const FlutterSecureStorage()),
        );

  @override
  Future<UserModel?> restoreSession() async => null;
}

void main() {
  testWidgets('App boots to the login screen when unauthenticated', (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authProvider.overrideWith((ref) => AuthNotifier(_ImmediatelyUnauthenticatedRepository())),
        ],
        child: const PresenSyncApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Login Monitoring'), findsOneWidget);
  });
}
