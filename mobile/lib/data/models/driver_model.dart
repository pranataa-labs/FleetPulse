class DriverModel {
  final int id;
  final String name;
  final String email;
  final String phone;
  final bool isOnline;
  final bool isOnDuty;
  final double? lastLat;
  final double? lastLng;
  final String? vehicleType;
  final String? licensePlate;

  DriverModel({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.isOnline,
    required this.isOnDuty,
    this.lastLat,
    this.lastLng,
    this.vehicleType,
    this.licensePlate,
  });

  factory DriverModel.fromJson(Map<String, dynamic> json) {
    final driver = json['driver'] as Map<String, dynamic>? ?? {};
    final fleet = driver['fleet'] as Map<String, dynamic>?;

    return DriverModel(
      id: driver['driver_id'] as int? ?? json['id'] as int? ?? 1,
      name: json['name'] as String? ?? 'Kurir',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      isOnline: driver['is_online'] as bool? ?? false,
      isOnDuty: driver['is_on_duty'] as bool? ?? false,
      lastLat: (driver['last_lat'] as num?)?.toDouble(),
      lastLng: (driver['last_lng'] as num?)?.toDouble(),
      vehicleType: fleet?['vehicle_type'] as String?,
      licensePlate: fleet?['license_plate'] as String?,
    );
  }
}
