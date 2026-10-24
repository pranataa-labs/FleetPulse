import 'package:flutter/foundation.dart';
import '../../data/models/order_model.dart';
import '../../data/repositories/order_repository.dart';

class DriverViewModel extends ChangeNotifier {
  final OrderRepository _repository;

  int _driverId = 1;
  String _driverName = 'Budi Santoso';
  bool _isLoading = false;
  bool _isOnline = true;
  bool _isOnDuty = true;

  OrderModel? _activeOrder;
  List<OrderModel> _availableOrders = [];
  String? _errorMessage;

  DriverViewModel({OrderRepository? repository}) : _repository = repository ?? OrderRepository();

  int get driverId => _driverId;
  String get driverName => _driverName;
  bool get isLoading => _isLoading;
  bool get isOnline => _isOnline;
  bool get isOnDuty => _isOnDuty;
  OrderModel? get activeOrder => _activeOrder;
  List<OrderModel> get availableOrders => _availableOrders;
  String? get errorMessage => _errorMessage;

  void setDriver(int id, String name) {
    _driverId = id;
    _driverName = name;
    refreshData();
  }

  Future<void> refreshData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _activeOrder = await _repository.fetchActiveOrder(_driverId);
      _availableOrders = await _repository.fetchAvailablePool(_driverId);
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> claimOrder(int orderId) async {
    _isLoading = true;
    notifyListeners();
    try {
      final success = await _repository.claimOrder(orderId, _driverId);
      if (success) {
        await refreshData();
        return true;
      }
    } catch (e) {
      _errorMessage = 'Gagal mengklaim pesanan: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }

  Future<bool> advanceActiveOrderStatus(String nextStatus) async {
    if (_activeOrder == null) return false;
    _isLoading = true;
    notifyListeners();

    try {
      final success = await _repository.updateStatus(_activeOrder!.id, _driverId, nextStatus);
      if (success) {
        await refreshData();
        return true;
      }
    } catch (e) {
      _errorMessage = 'Gagal memperbarui status: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }

  Future<void> sendPing(double lat, double lng) async {
    try {
      await _repository.sendTelemetry(_driverId, lat, lng);
    } catch (e) {
      debugPrint('Ping error: $e');
    }
  }

  void toggleOnline() {
    _isOnline = !_isOnline;
    notifyListeners();
  }

  void toggleDuty() {
    _isOnDuty = !_isOnDuty;
    notifyListeners();
  }
}
