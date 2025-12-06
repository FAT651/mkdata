class Transaction {
  final String id;
  final String userId;
  final String type;
  final double amount;
  final String description;
  final String status;
  final DateTime createdAt;
  final String? reference;

  Transaction({
    required this.id,
    required this.userId,
    required this.type,
    required this.amount,
    required this.description,
    required this.status,
    required this.createdAt,
    this.reference,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'],
      userId: json['userId'],
      type: json['type'],
      amount: json['amount'].toDouble(),
      description: json['description'],
      status: json['status'],
      createdAt: DateTime.parse(json['createdAt']),
      reference: json['reference'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'userId': userId,
      'type': type,
      'amount': amount,
      'description': description,
      'status': status,
      'createdAt': createdAt.toIso8601String(),
      'reference': reference,
    };
  }
}
