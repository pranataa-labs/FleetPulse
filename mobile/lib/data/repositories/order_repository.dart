import '../models/order_model.dart';
import '../services/api_service.dart';

class OrderRepository {
  final ApiService _apiService;

  OrderRepository({ApiService? apiService}) : _apiService = apiService ?? ApiService();

  Future<List<OrderModel>> fetchAvailablePool(int driverId) async {
    return await _apiService.getAvailableOrders(driverId);
  }

  Future<OrderModel?> fetchActiveOrder(int driverId) async {
    return await _apiService.getActiveOrder(driverId);
  }

  Future<bool> claimOrder(int orderId, int driverId) async {
    return await _apiService.claimOrder(orderId, driverId);
  }

  Future<bool> updateStatus(int orderId, int driverId, String nextStatus) async {
    return await _apiService.updateOrderStatus(orderId, driverId, nextStatus);
  }

  Future<bool> sendTelemetry(int driverId, double lat, double lng, {double? heading, double? speed}) async {
    return await _apiService.sendLocationPing(
      driverId: driverId,
      lat: lat,
      lng: lng,
      heading: heading,
      speed: speed,
    );
  }
}
