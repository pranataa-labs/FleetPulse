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
            CircleAvatar(
              radius: 16,
              backgroundColor: AppTheme.slate900,
              child: const Text('BS', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(vm.driverName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.slate900)),
                const Text('Kurir Motor • B 3012 SFE', style: TextStyle(fontSize: 10, color: AppTheme.slate500)),
              ],
            ),
          ],
        ),
        actions: [
          // Sleek Minimalist Online Indicator Switch
          Padding(
            padding: const EdgeInsets.only(right: 16.0),
            child: InkWell(
              onTap: () => vm.toggleOnline(),
              borderRadius: BorderRadius.circular(20),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: vm.isOnline ? Colors.emerald.shade50 : AppTheme.slate100,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: vm.isOnline ? Colors.emerald.shade200 : AppTheme.slate200,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: vm.isOnline ? AppTheme.successGreen : AppTheme.slate500,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      vm.isOnline ? 'Online' : 'Offline',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: vm.isOnline ? Colors.emerald.shade800 : AppTheme.slate500,
                      ),
                    ),
                  ],
                ),
              ),
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
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.two_wheeler_outlined),
            selectedIcon: Icon(Icons.two_wheeler),
            label: 'Tugas',
          ),
          NavigationDestination(
            icon: Icon(Icons.inbox_outlined),
            selectedIcon: Icon(Icons.inbox),
            label: 'Pool',
          ),
          NavigationDestination(
            icon: Icon(Icons.radar_outlined),
            selectedIcon: Icon(Icons.radar),
            label: 'Radar',
          ),
        ],
      ),
    );
  }

  Widget _buildTabBody(DriverViewModel vm) {
    if (_currentIndex == 0) {
      if (vm.activeOrder != null) {
        return ActiveOrderView(order: vm.activeOrder!);
      } else {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.check_circle_outline, size: 48, color: Colors.slate.shade300),
              const SizedBox(height: 12),
              const Text('Tidak Ada Tugas Berjalan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 4),
              const Text('Silakan ambil pesanan di tab Pool.', style: TextStyle(fontSize: 11, color: AppTheme.slate500)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => setState(() => _currentIndex = 1),
                child: const Text('Buka Pool Order'),
              ),
            ],
          ),
        );
      }
    } else if (_currentIndex == 1) {
      if (vm.availableOrders.isEmpty) {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.inbox_outlined, size: 48, color: Colors.slate.shade300),
              const SizedBox(height: 12),
              const Text('Belum Ada Pesanan Masuk', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 4),
              const Text('Tarik ke bawah untuk memuat ulang.', style: TextStyle(fontSize: 11, color: AppTheme.slate500)),
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
              border: Border.all(color: AppTheme.slate200.withOpacity(0.8)),
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
                Text(
                  '${order.pickupAddress} → ${order.dropoffAddress}',
                  style: const TextStyle(fontSize: 12, color: AppTheme.slate700),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => vm.claimOrder(order.id),
                    child: const Text('Ambil Tugas Pengiriman Ini'),
                  ),
                ),
              ],
            ),
          );
        },
      );
    } else {
      return Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppTheme.slate200.withOpacity(0.8)),
              ),
              child: Column(
                children: [
                  const Icon(Icons.sensors, size: 36, color: AppTheme.slate900),
                  const SizedBox(height: 8),
                  const Text('Telemetri GPS Lapangan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  const SizedBox(height: 4),
                  const Text('Pancaran koordinat otomatis ke Reverb', style: TextStyle(fontSize: 11, color: AppTheme.slate500)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => vm.sendPing(-6.1753924, 106.8271528),
                    child: const Text('Simulasi Ping Lokasi Sekarang'),
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
