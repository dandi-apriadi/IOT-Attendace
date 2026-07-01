import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client_provider.dart';
import '../data/reference_repository.dart';
import '../models/kelas_model.dart';

final referenceRepositoryProvider = Provider<ReferenceRepository>((ref) {
  return ReferenceRepository(ref.watch(apiClientProvider));
});

final kelasListProvider = FutureProvider.autoDispose<List<KelasModel>>((ref) {
  return ref.watch(referenceRepositoryProvider).fetchKelas();
});
