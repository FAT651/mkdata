class ElectricityProvider {
  final String id;
  final String name;
  final String abbreviation;

  ElectricityProvider({
    required this.id,
    required this.name,
    required this.abbreviation,
  });

  factory ElectricityProvider.fromJson(Map<String, dynamic> json) {
    return ElectricityProvider(
      id: json['eId'].toString(),
      name: json['provider'] as String,
      abbreviation: json['abbreviation'] as String,
    );
  }

  Map<String, dynamic> toJson() => {
    'eId': id,
    'provider': name,
    'abbreviation': abbreviation,
  };
}
