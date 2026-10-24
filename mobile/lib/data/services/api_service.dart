import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/order_model.dart';
import '../models/driver_model.dart';

class ApiService {
  // Use 10.0.2.2 for Android Studio Emulator, or 127.0.0.1 / device IP for local testing
  static const String defaultBaseUrl = 'http://10.0.2.2:8000/api/v1';

  final String baseUrl;
  final http.Client _client;

  ApiService({String? baseUrl, http.Client? client})
      : baseUrl = baseUrl ?? defaultBaseUrl,
        _client = client ?? http.Client();

  /// 1. Mobile Authentication
  Future<Map<String, dynamic>> login(String email, String password) async {
    final url = Uri.parse('$baseUrl/auth/login');
    final response = await _client.post(
      url,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as Map<String, dynamic>;
    } else {
      throw Exception('Login failed: ${response.body}');
    }
  }

  /// 2. Get Available Orders Pool for Driver
  Future<List<OrderModel>> getAvailableOrders(int driverId) async {
    final url = Uri.parse('$baseUrl/driver/orders/available?driver_id=$driverId');
    final response = await _client.get(url, headers: {'Accept': 'application/json'});

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final list = data['orders'] as List<dynamic>? ?? [];
      return list.map((item) => OrderModel.fromJson(item as Map<String, dynamic>)).toList();
    }
    return [];
  }

  /// 3. Get Current Active Order for Driver
  Future<OrderModel?> getActiveOrder(int driverId) async {
    final url = Uri.parse('$baseUrl/driver/orders/active?driver_id=$driverId');
    final response = await _client.get(url, headers: {'Accept': 'application/json'});

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['has_active_order'] == true && data['order'] != null) {
        return OrderModel.fromJson(data['order'] as Map<String, dynamic>);
      }
    }
    return null;
  }

  /// 4. Claim Dispatched Order
  Future<bool> claimOrder(int orderId, int driverId) async {
    final url = Uri.parse('$baseUrl/driver/orders/$orderId/claim');
    final response = await _client.post(
      url,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'driver_id': driverId}),
    );

    return response.statusCode == 200;
  }

  /// 5. Update Order Status (PICKED_UP -> IN_TRANSIT -> DELIVERED)
  Future<bool> updateOrderStatus(int orderId, int driverId, String status) async {
    final url = Uri.parse('$baseUrl/driver/orders/$orderId/status');
    final response = await _client.post(
      url,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'driver_id': driverId,
        'status': status,
        'note': 'Status diubah via FleetPulse Mobile App ($status)',
      }),
    );

    return response.statusCode == 200;
  }

  /// 6. Stream Live GPS Telemetry
  Future<bool> sendLocationPing({
    required int driverId,
    required double lat,
    required double lng,
    double? heading,
    double? speed,
  }) async {
    final url = Uri.parse('$baseUrl/driver/location');
    final response = await _client.post(
      url,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'driver_id': driverId,
        'lat': lat,
        'lng': lng,
        'heading': heading,
        'speed': speed,
      }),
    );

    return response.statusCode == 200;
  }

  /// 7. Toggle Online/Duty Status
  Future<Map<String, dynamic>> toggleDuty(int driverId, {bool? isOnline, bool? isOnDuty}) async {
    final url = Uri.parse('$baseUrl/driver/toggle-duty');
    final response = await _client.post(
      url,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'driver_id': driverId,
        if (isOnline != null) 'is_online': isOnline,
        if (isOnDuty != null) 'is_on_duty': isOnDuty,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    throw Exception('Failed to toggle duty');
  }
}
