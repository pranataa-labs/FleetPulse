import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../data/models/order_model.dart';
import '../core/theme.dart';
import '../view_models/driver_view_model.dart';

class ActiveOrderView extends StatelessWidget {
  final OrderModel order;

  const ActiveOrderView({super.key, required this.order});

  Future<void> _launchUrl(String? urlString) async {
    if (urlString == null || urlString.isEmpty) return;
    final uri = Uri.parse(urlString);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.read<DriverViewModel>();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Sleek Unified Delivery Card
          Container(
            padding: const EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppTheme.slate200.withOpacity(0.8)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.02),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top Header: Order Number & Status Pill
                Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'NOMOR PESANAN',
                          style: TextStyle(
                            fontSize: 10,
                            letterSpacing: 0.5,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.slate500,
                          ),
                        ),
                        Text(
                          '#${order.orderNumber}',
                          style: const TextStyle(
                            fontFamily: 'monospace',
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.slate900,
                          ),
                        ),
                      ],
                    ),
                    _buildStatusPill(order.deliveryStatus),
                  ],
                ),
                const SizedBox(height: 20),

                // Connected Route Journey (Apple / Grab style)
                _buildConnectedRoute(),
                const SizedBox(height: 20),

                // Summary Stats Strip
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  decoration: const BoxDecoration(
                    border: Border(top: BorderSide(color: AppTheme.slate100)),
                  ),
                  child: Row(
                    children: [
                      _buildStatColumn('Ongkos Kirim', 'Rp ${order.totalFee.toStringAsFixed(0)}'),
                      _buildVerticalDivider(),
                      _buildStatColumn('Jarak Rute', '${order.distanceKm} km'),
                      _buildVerticalDivider(),
                      _buildStatColumn('Berat Paket', '${order.weightKg ?? 1.2} kg'),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Single Clean Prominent Action Button
          _buildProgressiveButton(context, vm),
        ],
      ),
    );
  }

  Widget _buildStatusPill(String status) {
    Color bg = Colors.blue.shade50;
    Color text = Colors.blue.shade700;
    String label = 'Dalam Penjemputan';

    if (status == 'PICKED_UP') {
      bg = Colors.indigo.shade50;
      text = Colors.indigo.shade700;
      label = 'Paket Diambil';
    } else if (status == 'IN_TRANSIT') {
      bg = Colors.blue.shade50;
      text = Colors.blue.shade700;
      label = 'Dalam Perjalanan';
    } else if (status == 'DELIVERED') {
      bg = Colors.emerald.shade50;
      text = Colors.emerald.shade700;
      label = 'Terkirim';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: text),
      ),
    );
  }

  Widget _buildConnectedRoute() {
    return Stack(
      children: [
        // Connecting Line
        Positioned(
          left: 7,
          top: 14,
          bottom: 14,
          child: Container(
            width: 2,
            color: AppTheme.slate200,
          ),
        ),
        Column(
          children: [
            // Origin Point
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 2),
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: AppTheme.successGreen,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'TITIK JEMPUT (PENJUAL)',
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.slate500, letterSpacing: 0.5),
                      ),
                      Text(
                        order.senderName,
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.slate900),
                      ),
                      Text(
                        order.pickupAddress,
                        style: const TextStyle(fontSize: 12, color: AppTheme.slate500),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                // Circular 1-Tap Action Icons
                Row(
                  children: [
                    _buildCircleIconButton(
                      icon: Icons.chat_bubble_outline,
                      color: AppTheme.successGreen,
                      onTap: () => _launchUrl(order.senderWaLink),
                    ),
                    const SizedBox(width: 6),
                    _buildCircleIconButton(
                      icon: Icons.phone_outlined,
                      color: AppTheme.slate700,
                      onTap: () => _launchUrl('tel:${order.senderPhone}'),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Destination Point
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 2),
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: AppTheme.dangerRose,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'TUJUAN ANTAR (PEMBELI)',
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.slate500, letterSpacing: 0.5),
                      ),
                      Text(
                        order.recipientName,
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.slate900),
                      ),
                      Text(
                        order.dropoffAddress,
                        style: const TextStyle(fontSize: 12, color: AppTheme.slate500),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                // Circular 1-Tap Action Icons
                Row(
                  children: [
                    _buildCircleIconButton(
                      icon: Icons.chat_bubble_outline,
                      color: AppTheme.successGreen,
                      onTap: () => _launchUrl(order.recipientWaLink),
                    ),
                    const SizedBox(width: 6),
                    _buildCircleIconButton(
                      icon: Icons.phone_outlined,
                      color: AppTheme.slate700,
                      onTap: () => _launchUrl('tel:${order.recipientPhone}'),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildCircleIconButton({required IconData icon, required Color color, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
          color: color.withOpacity(0.08),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, size: 16, color: color),
      ),
    );
  }

  Widget _buildStatColumn(String label, String value) {
    return Expanded(
      child: Column(
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: AppTheme.slate500)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.slate900)),
        ],
      ),
    );
  }

  Widget _buildVerticalDivider() {
    return Container(
      width: 1,
      height: 24,
      color: AppTheme.slate100,
    );
  }

  Widget _buildProgressiveButton(BuildContext context, DriverViewModel vm) {
    if (order.deliveryStatus == 'ACCEPTED') {
      return ElevatedButton(
        onPressed: () => vm.advanceActiveOrderStatus('PICKED_UP'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.slate900,
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: const Text('Konfirmasi Ambil Fisik Paket', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
      );
    } else if (order.deliveryStatus == 'PICKED_UP') {
      return ElevatedButton(
        onPressed: () => vm.advanceActiveOrderStatus('IN_TRANSIT'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.primaryBlue,
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: const Text('Mulai Perjalanan ke Penerima', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
      );
    } else if (order.deliveryStatus == 'IN_TRANSIT') {
      return ElevatedButton(
        onPressed: () => vm.advanceActiveOrderStatus('DELIVERED'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.successGreen,
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: const Text('Konfirmasi Paket Diterima', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
      );
    } else {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.emerald.shade50,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(
          'Pengiriman Selesai (${order.deliveryStatus})',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.emerald.shade800, fontWeight: FontWeight.bold),
        ),
      );
    }
  }
}
