class OrderModel {
  final int id;
  final String orderNumber;
  final String? trackingToken;
  final String deliveryStatus;
  final double totalFee;
  final double distanceKm;
  final double? weightKg;
  final String? vehicleType;

  // Pickup / Sender info
  final String senderName;
  final String senderPhone;
  final String pickupAddress;
  final double pickupLat;
  final double pickupLng;
  final String? senderWaLink;

  // Dropoff / Recipient info
  final String recipientName;
  final String recipientPhone;
  final String dropoffAddress;
  final double dropoffLat;
  final double dropoffLng;
  final String? recipientWaLink;

  // Progressive Next Action
  final String? nextAction;
  final String? nextActionLabel;

  OrderModel({
    required this.id,
    required this.orderNumber,
    this.trackingToken,
    required this.deliveryStatus,
    required this.totalFee,
    required this.distanceKm,
    this.weightKg,
    this.vehicleType,
    required this.senderName,
    required this.senderPhone,
    required this.pickupAddress,
    required this.pickupLat,
    required this.pickupLng,
    this.senderWaLink,
    required this.recipientName,
    required this.recipientPhone,
    required this.dropoffAddress,
    required this.dropoffLat,
    required this.dropoffLng,
    this.recipientWaLink,
    this.nextAction,
    this.nextActionLabel,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    final pickup = json['pickup'] as Map<String, dynamic>? ?? {};
    final dropoff = json['dropoff'] as Map<String, dynamic>? ?? {};
    final nextStep = json['next_step'] as Map<String, dynamic>?;

    return OrderModel(
      id: json['id'] as int,
      orderNumber: json['order_number'] as String,
      trackingToken: json['tracking_token'] as String?,
      deliveryStatus: json['delivery_status'] as String? ?? 'DISPATCHED',
      totalFee: (json['total_fee'] as num).toDouble(),
      distanceKm: (json['distance_km'] as num).toDouble(),
      weightKg: json['weight_kg'] != null ? (json['weight_kg'] as num).toDouble() : null,
      vehicleType: json['vehicle_type'] as String?,
      senderName: pickup['name'] as String? ?? json['sender_name'] as String? ?? 'Pengirim',
      senderPhone: pickup['phone'] as String? ?? json['sender_phone'] as String? ?? '',
      pickupAddress: pickup['address'] as String? ?? json['pickup_address'] as String? ?? '',
      pickupLat: (pickup['lat'] as num?)?.toDouble() ?? (json['pickup_lat'] as num?)?.toDouble() ?? 0.0,
      pickupLng: (pickup['lng'] as num?)?.toDouble() ?? (json['pickup_lng'] as num?)?.toDouble() ?? 0.0,
      senderWaLink: pickup['wa_link'] as String?,
      recipientName: dropoff['name'] as String? ?? json['recipient_name'] as String? ?? 'Penerima',
      recipientPhone: dropoff['phone'] as String? ?? json['recipient_phone'] as String? ?? '',
      dropoffAddress: dropoff['address'] as String? ?? json['dropoff_address'] as String? ?? '',
      dropoffLat: (dropoff['lat'] as num?)?.toDouble() ?? (json['dropoff_lat'] as num?)?.toDouble() ?? 0.0,
      dropoffLng: (dropoff['lng'] as num?)?.toDouble() ?? (json['dropoff_lng'] as num?)?.toDouble() ?? 0.0,
      recipientWaLink: dropoff['wa_link'] as String?,
      nextAction: nextStep?['action'] as String?,
      nextActionLabel: nextStep?['label'] as String?,
    );
  }
}
