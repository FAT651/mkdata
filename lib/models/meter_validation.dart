class MeterValidation {
  final bool isValid;
  final String name;
  final String address;
  final String meterNumber;
  final String provider;

  MeterValidation({
    required this.isValid,
    required this.name,
    required this.address,
    required this.meterNumber,
    required this.provider,
  });

  factory MeterValidation.fromJson(Map<String, dynamic> json) {
    // Check if the response indicates an invalid meter
    final bool isInvalid = json['invalid'] ?? false;

    return MeterValidation(
      isValid: !isInvalid,
      name: json['name'] as String? ?? '',
      address: json['address'] as String? ?? '',
      meterNumber: json['meter_number'] as String? ?? '',
      provider: json['provider'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
    'isValid': isValid,
    'name': name,
    'address': address,
    'meterNumber': meterNumber,
    'provider': provider,
  };
}
