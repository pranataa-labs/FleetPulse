import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/theme.dart';
import '../view_models/driver_view_model.dart';
import 'active_order_view.dart';

class CourierHomeView extends StatefulWidget {
  const CourierHomeView({super.key});

  @override
  State<CourierHomeView> createState() => _CourierHomeViewState();
}

class _CourierHomeViewState extends State<CourierHomeView> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverViewModel>().refreshData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<DriverViewModel>();

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppTheme.slate900,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.local_shipping, size: 18, color: Colors.white),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('FleetPulse Courier', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                Text(vm.driverName, style: const TextStyle(fontSize: 11, color: AppTheme.slate500)),
              ],
            ),
          ],
        ),
        actions: [
          // Online Toggle Chip
          Padding(
            padding: const EdgeInsets.only(right: 12.0),
            child: FilterChip(
              selected: vm.isOnline,
              onSelected: (_) => vm.toggleOnline(),
              label: Text(vm.isOnline ? 'ONLINE' : 'OFFLINE'),
              labelStyle: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: vm.isOnline ? AppTheme.successGreen : AppTheme.slate500,
              ),
              backgroundColor: AppTheme.slate100,
              selectedColor: Colors.emerald.shade50,
              side: BorderSide(color: vm.isOnline ? Colors.emerald.shade200 : AppTheme.slate200),
            ),
          ),
        ],
      ),
      body: vm.isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () => vm.refreshData(),
              child: _buildTabBody(vm),
            ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (idx) => setState(() => _currentIndex = idx),
        destinations: [
          NavigationDestination(
            icon: Badge(
              isLabelVisible: vm.activeOrder != null,
              child: const Icon(Icons.two_wheeler_outlined),
            ),
            selectedIcon: const Icon(Icons.two_wheeler),
            label: 'Tugas Aktif',
          ),
          NavigationDestination(
            icon: Badge(
              label: Text('${vm.availableOrders.length}'),
              isLabelVisible: vm.availableOrders.isNotEmpty,
              child: const Icon(Icons.inbox_outlined),
            ),
            selectedIcon: const Icon(Icons.inbox),
            label: 'Pool Order',
          ),
          const NavigationDestination(
            icon: Icon(Icons.gps_fixed_outlined),
            selectedIcon: Icon(Icons.gps_fixed),
            label: 'GPS Radar',
          ),
        ],
      ),
    );
  }

  Widget _buildTabBody(DriverViewModel vm) {
    if (_currentIndex == 0) {
      // 1. Active Order Tab
      if (vm.activeOrder != null) {
        return ActiveOrderView(order: vm.activeOrder!);
      } else {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.check_circle_outline, size: 54, color: Colors.slate.shade300),
              const SizedBox(height: 12),
              const Text('Tidak Ada Tugas Berjalan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 4),
              const Text('Silakan pilih pesanan dari menu Pool Order.', style: TextStyle(fontSize: 12, color: AppTheme.slate500)),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: () => setState(() => _currentIndex = 1),
                icon: const Icon(Icons.search, size: 16),
                label: const Text('Buka Pool Order'),
              ),
            ],
          ),
        );
      }
    } else if (_currentIndex == 1) {
      // 2. Available Orders Pool
      if (vm.availableOrders.isEmpty) {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.inventory_2_outlined, size: 48, color: Colors.slate.shade300),
              const SizedBox(height: 12),
              const Text('Belum Ada Pesanan Masuk', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 4),
              const Text('Tarik ke bawah untuk memuat ulang daftar.', style: TextStyle(fontSize: 12, color: AppTheme.slate500)),
            ],
          ),
        );
      }

      return ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: vm.availableOrders.length,
        itemBuilder: (context, index) {
          final order = vm.availableOrders[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.slate200),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    Text(
                      '#${order.orderNumber}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontFamily: 'monospace', fontSize: 12),
                    ),
                    Text(
                      'Rp ${order.totalFee.toStringAsFixed(0)}',
                      style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.successGreen, fontSize: 13),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text('Jemput: ${order.pickupAddress}', style: const TextStyle(fontSize: 12, color: AppTheme.slate700)),
                Text('Tujuan: ${order.dropoffAddress}', style: const TextStyle(fontSize: 12, color: AppTheme.slate500)),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => vm.claimOrder(order.id),
                    child: const Text('Klaim & Ambil Pengiriman Ini'),
                  ),
                ),
              ],
            ),
          );
        },
      );
    } else {
      // 3. Telemetry & GPS Radar Tab
      return Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppTheme.slate200),
              ),
              child: Column(
                children: [
                  const Icon(Icons.sensors, size: 40, color: AppTheme.primaryBlue),
                  const SizedBox(height: 8),
                  const Text('GPS Telemetry Streamer', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  const Text('Mengirim koordinat real-time ke WebSocket Reverb', style: TextStyle(fontSize: 11, color: AppTheme.slate500)),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: () => vm.sendPing(-6.1753924, 106.8271528),
                    icon: const Icon(Icons.my_location, size: 16),
                    label: const Text('Simulasi Ping GPS (-6.175, 106.827)'),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }
  }
}
