import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app_lifecycle_provider.dart';

class PollingState<T> {
  const PollingState({this.data, this.error, this.loading = true});

  final T? data;
  final String? error;
  final bool loading;

  bool get hasData => data != null;

  PollingState<T> copyWith({T? data, String? error, bool? loading, bool clearError = false}) {
    return PollingState<T>(
      data: data ?? this.data,
      error: clearError ? null : (error ?? this.error),
      loading: loading ?? this.loading,
    );
  }
}

/// Base class for screens that poll an endpoint on a fixed interval.
///
/// - Keeps the last good [data] visible when a refresh fails (stale-while
///   -revalidate) instead of flashing a full-screen error.
/// - Pauses the timer while the app is backgrounded via [appLifecycleProvider].
/// - Cancels the timer automatically when the provider is disposed
///   (screen closed), via `ref.onDispose` in the subclass provider.
abstract class PollingNotifier<T> extends StateNotifier<PollingState<T>> {
  PollingNotifier(this.ref, {required this.interval}) : super(const PollingState()) {
    _bootstrap();
  }

  final Ref ref;
  final Duration interval;

  Timer? _timer;
  bool _disposed = false;

  Future<T> fetch();

  void _bootstrap() {
    _tick();
    _startTimer();

    ref.listen<AppLifecycleState>(appLifecycleProvider, (previous, next) {
      if (next == AppLifecycleState.resumed) {
        _startTimer();
        _tick();
      } else {
        _stopTimer();
      }
    });

    ref.onDispose(() {
      _disposed = true;
      _stopTimer();
    });
  }

  void _startTimer() {
    _stopTimer();
    _timer = Timer.periodic(interval, (_) => _tick());
  }

  void _stopTimer() {
    _timer?.cancel();
    _timer = null;
  }

  Future<void> _tick() async {
    if (_disposed) return;

    try {
      final result = await fetch();
      if (_disposed) return;
      state = PollingState<T>(data: result, loading: false);
    } catch (e) {
      if (_disposed) return;
      state = state.copyWith(error: _describeError(e), loading: false);
    }
  }

  String _describeError(Object e) => 'Gagal memuat data terbaru. Menampilkan data terakhir.';

  Future<void> refreshNow() => _tick();
}
