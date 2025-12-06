import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/transaction.dart';
import 'api_service.dart';

class TransactionService {
  static Future<List<Transaction>> getTransactions(String userId) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiService.baseUrl}/api/transactions?user_id=$userId'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return List<Transaction>.from(
            data['data'].map((x) => Transaction.fromJson(x)),
          );
        }
        throw Exception(data['message'] ?? 'Failed to load transactions');
      }
      throw Exception('Failed to load transactions');
    } catch (e) {
      throw Exception('Error loading transactions: $e');
    }
  }
}
