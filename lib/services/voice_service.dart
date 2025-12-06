import 'package:audioplayers/audioplayers.dart';

/// Simple singleton audio manager for transaction voices.
/// Plays the bundled `assets/audios/sucess.mpeg` and allows
/// callers to stop playback. This avoids duplicated/delayed
/// sounds when navigating between pages.
class VoiceService {
  VoiceService._internal();
  static final VoiceService _instance = VoiceService._internal();
  factory VoiceService() => _instance;

  final AudioPlayer _player = AudioPlayer();
  bool _isPlaying = false;

  Future<void> playTransactionSuccess() async {
    if (_isPlaying) return;
    try {
      _isPlaying = true;
      // Play bundled asset
      await _player.play(AssetSource('audios/sucess.mpeg'));
      // Listen for completion to reset state
      _player.onPlayerComplete.listen((_) {
        _isPlaying = false;
      });
    } catch (e) {
      _isPlaying = false;
      // Keep debug-friendly print; consuming apps should handle failures silently
      // (no crash) and continue navigation.
      // ignore: avoid_print
      print('VoiceService play error: $e');
    }
  }

  Future<void> stop() async {
    try {
      await _player.stop();
    } catch (e) {
      // ignore: avoid_print
      print('VoiceService stop error: $e');
    }
    _isPlaying = false;
  }

  void dispose() {
    try {
      _player.dispose();
    } catch (_) {}
    _isPlaying = false;
  }
}
