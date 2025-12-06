import '../models/user_model.dart';
import 'api_service.dart';

class AuthService {
  final ApiService _apiService = ApiService();

  Future<User> login(String email, String password) async {
    try {
      final response = await _apiService.post('auth/login.php', {
        'email': email,
        'password': password,
      });

      if (response['message'] == 'Login successful.') {
        await _apiService.saveUserData(response);
        return User.fromJson(response);
      } else {
        throw Exception(response['message']);
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<bool> register({
    required String fullname,
    required String email,
    required String mobile,
    required String password,
    String? referralCode,
  }) async {
    try {
      final response = await _apiService.post('auth/register.php', {
        'fullname': fullname,
        'email': email,
        'mobile': mobile,
        'password': password,
        'referral_code': referralCode,
      });

      if (response['message'] == 'User was created.') {
        return true;
      }

      // On failure, surface the server-provided message so UI can display it
      throw Exception(response['message'] ?? 'Registration failed');
    } catch (e) {
      rethrow;
    }
  }

  Future<void> forgotPassword(String email) async {
    try {
      final response = await _apiService.post(
        'auth/request_password_reset.php',
        {'email': email},
      );

      if (response['status'] == 'success') {
        return;
      }
      throw Exception(response['message'] ?? 'Failed to send reset email');
    } catch (e) {
      rethrow;
    }
  }

  Future<bool> resetPassword(
    String token,
    String password,
    String confirmPassword,
  ) async {
    try {
      final response = await _apiService.post('auth/reset_password.php', {
        'token': token,
        'password': password,
        'confirm_password': confirmPassword,
      });

      return response['message'] == 'Password was reset successfully.';
    } catch (e) {
      rethrow;
    }
  }

  Future<void> logout() async {
    // Clear local storage/session
    // Since our API doesn't have a logout endpoint
    await _apiService.clearAuth();
  }
}
