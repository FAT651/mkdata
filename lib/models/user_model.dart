class User {
  final String id;
  final String fullname;
  final String email;
  final String mobile;
  final String? referralCode;
  final DateTime created;

  User({
    required this.id,
    required this.fullname,
    required this.email,
    required this.mobile,
    this.referralCode,
    required this.created,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'].toString(),
      fullname: json['fullname'] ?? '',
      email: json['email'] ?? '',
      mobile:
          json['mobile'] ??
          '', // Default empty string if mobile is not in response
      referralCode: json['referral_code'],
      created: json['created'] != null
          ? DateTime.parse(json['created'])
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'fullname': fullname,
      'email': email,
      'mobile': mobile,
      'referral_code': referralCode,
      'created': created.toIso8601String(),
    };
  }
}
