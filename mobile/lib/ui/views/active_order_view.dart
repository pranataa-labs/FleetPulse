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
          // Order Header Card
          Container(
            padding: const EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.primaryBlue, width: 2),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        'PESANAN AKTIF DITUGASKAN',
                        style: TextStyle(
                          color: AppTheme.primaryBlue,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ),
                    Text(
                      '#${order.orderNumber}',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontFamily: 'monospace',
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Ongkir: Rp ${order.totalFee.toStringAsFixed(0)}',
                      style: const TextStyle(fontSize: 12, color: AppTheme.slate700),
                    ),
                    Text(
                      'Status: ${order.deliveryStatus}',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primaryBlue,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Direct Contact Card: SENDER (Penjual)
          _buildContactCard(
            title: 'Pengirim (Toko/Penjual)',
            name: order.senderName,
            phone: order.senderPhone,
            address: order.pickupAddress,
            icon: Icons.storefront_outlined,
            onWhatsApp: () => _launchUrl(order.senderWaLink),
            onCall: () => _launchUrl('tel:${order.senderPhone}'),
          ),
          const SizedBox(height: 12),

          // Direct Contact Card: RECIPIENT (Pembeli)
          _buildContactCard(
            title: 'Penerima (Pembeli/Customer)',
            name: order.recipientName,
            phone: order.recipientPhone,
            address: order.dropoffAddress,
            icon: Icons.person_pin_circle_outlined,
            onWhatsApp: () => _launchUrl(order.recipientWaLink),
            onCall: () => _launchUrl('tel:${order.recipientPhone}'),
          ),
          const SizedBox(height: 16),

          // Progressive Workflow Action Button
          _buildProgressiveButton(context, vm),
        ],
      ),
    );
  }

  Widget _buildContactCard({
    required String title,
    required String name,
    required String phone,
    required String address,
    required IconData icon,
    required VoidCallback onWhatsApp,
    required VoidCallback onCall,
  }) {
    return Container(
      padding: const EdgeInsets.all(14.0),
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
              Row(
                children: [
                  Icon(icon, size: 16, color: AppTheme.slate500),
                  const SizedBox(width: 6),
                  Text(
                    title,
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.slate700),
                  ),
                ],
              ),
              Text(
                phone,
                style: const TextStyle(fontSize: 11, fontFamily: 'monospace', color: AppTheme.slate500),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(name, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
          Text(address, style: const TextStyle(fontSize: 12, color: AppTheme.slate500)),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: onWhatsApp,
                  icon: const Icon(Icons.chat_bubble_outline, size: 16),
                  label: const Text('WhatsApp'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.successGreen,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton.icon(
                onPressed: onCall,
                icon: const Icon(Icons.phone_outlined, size: 16),
                label: const Text('Telp'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.slate700,
                  side: const BorderSide(color: AppTheme.slate200),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProgressiveButton(BuildContext context, DriverViewModel vm) {
    if (order.deliveryStatus == 'ACCEPTED') {
      return ElevatedButton.icon(
        onPressed: () => vm.advanceActiveOrderStatus('PICKED_UP'),
        icon: const Icon(Icons.inventory_2_outlined),
        label: const Text('1. Konfirmasi Ambil Fisik Paket'),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.indigo.shade600,
          padding: const EdgeInsets.symmetric(vertical: 14),
        ),
      );
    } else if (order.deliveryStatus == 'PICKED_UP') {
      return ElevatedButton.icon(
        onPressed: () => vm.advanceActiveOrderStatus('IN_TRANSIT'),
        icon: const Icon(Icons.navigation_outlined),
        label: const Text('2. Mulai Jalan Antar ke Penerima'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.primaryBlue,
          padding: const EdgeInsets.symmetric(vertical: 14),
        ),
      );
    } else if (order.deliveryStatus == 'IN_TRANSIT') {
      return ElevatedButton.icon(
        onPressed: () => vm.advanceActiveOrderStatus('DELIVERED'),
        icon: const Icon(Icons.check_circle_outline),
        label: const Text('3. Selesai Antar (Serahkan Paket)'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.successGreen,
          padding: const EdgeInsets.symmetric(vertical: 14),
        ),
      );
    } else {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.emerald.shade50,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          'Pesanan telah selesai (${order.deliveryStatus}).',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.emerald.shade800, fontWeight: FontWeight.bold),
        ),
      );
    }
  }
}
