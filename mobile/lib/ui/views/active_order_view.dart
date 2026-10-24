import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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

  void _showAddressDetailModal(BuildContext context, {required bool isPickup}) {
    final title = isPickup ? order.senderName : order.recipientName;
    final phone = isPickup ? order.senderPhone : order.recipientPhone;
    final address = isPickup ? order.pickupAddress : order.dropoffAddress;
    final lat = isPickup ? order.pickupLat : order.dropoffLat;
    final lng = isPickup ? order.pickupLng : order.dropoffLng;
    final waLink = isPickup ? order.senderWaLink : order.recipientWaLink;
    final badgeLabel = isPickup ? 'TITIK JEMPUT (PENJUAL)' : 'TUJUAN ANTAR (PEMBELI)';
    final badgeColor = isPickup ? AppTheme.successGreen : AppTheme.dangerRose;
    final notes = isPickup
        ? 'Toko atau pihak pengirim barang. Pastikan paket siap dipickup dan kemasan fisik dalam kondisi baik.'
        : 'Penerima langsung paket. Hubungi penerima sebelum sampai di lokasi jika diperlukan konfirmasi pagar/pos satpam.';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 12,
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Drag handle
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(
                  color: AppTheme.slate200,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Header row with Badge & Close button
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.between,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: badgeColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              decoration: BoxDecoration(color: badgeColor, shape: BoxShape.circle),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              badgeLabel,
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                color: badgeColor,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        title,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.slate900),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Kontak Person: $phone',
                        style: const TextStyle(fontSize: 12, color: AppTheme.slate500),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20, color: AppTheme.slate500),
                  onPressed: () => Navigator.pop(ctx),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Full Address Card
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppTheme.slate50,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppTheme.slate100),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.location_on_outlined, size: 16, color: AppTheme.slate500),
                      SizedBox(width: 6),
                      Text(
                        'ALAMAT LENGKAP LOKASI',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.slate500, letterSpacing: 0.5),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    address,
                    style: const TextStyle(fontSize: 13, height: 1.45, fontWeight: FontWeight.w500, color: AppTheme.slate900),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Notes / Patokan Card
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.info_outline, size: 16, color: Color(0xFFD97706)),
                      SizedBox(width: 6),
                      Text(
                        'PATOKAN & CATATAN KURIR',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFFD97706), letterSpacing: 0.5),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    notes,
                    style: const TextStyle(fontSize: 12, height: 1.4, color: Color(0xFF92400E)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Action Buttons: WhatsApp & Call
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.pop(ctx);
                      _launchUrl(waLink);
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.successGreen,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    icon: const Icon(Icons.chat, size: 16),
                    label: const Text('WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.pop(ctx);
                      _launchUrl('tel:$phone');
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.slate900,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    icon: const Icon(Icons.phone, size: 16),
                    label: const Text('Telepon', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // Secondary Actions: Salin Alamat & Buka di Maps
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      Clipboard.setData(ClipboardData(text: address));
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Alamat lengkap berhasil disalin ke clipboard!'),
                          duration: Duration(seconds: 2),
                        ),
                      );
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.slate700,
                      side: const BorderSide(color: AppTheme.slate200),
                      padding: const EdgeInsets.symmetric(vertical: 11),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    icon: const Icon(Icons.copy, size: 15),
                    label: const Text('Salin Alamat', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      Navigator.pop(ctx);
                      final query = (lat != 0.0 && lng != 0.0) ? '$lat,$lng' : Uri.encodeComponent(address);
                      _launchUrl('https://www.google.com/maps/search/?api=1&query=$query');
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.primaryBlue,
                      side: const BorderSide(color: AppTheme.slate200),
                      padding: const EdgeInsets.symmetric(vertical: 11),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    icon: const Icon(Icons.map_outlined, size: 15),
                    label: const Text('Buka Maps', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
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
                        const SizedBox(height: 2),
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
                _buildConnectedRoute(context),
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

  Widget _buildConnectedRoute(BuildContext context) {
    return Stack(
      children: [
        // Connecting Line
        Positioned(
          left: 7,
          top: 16,
          bottom: 16,
          child: Container(
            width: 2,
            color: AppTheme.slate200,
          ),
        ),
        Column(
          children: [
            // Origin Point (Penjual) - Clickable Area with Comfortable Typography
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 3),
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
                  child: InkWell(
                    onTap: () => _showAddressDetailModal(context, isPickup: true),
                    borderRadius: BorderRadius.circular(10),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 4),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'TITIK JEMPUT (PENJUAL)',
                                style: TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.slate500,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(width: 4),
                              Icon(Icons.chevron_right, size: 13, color: AppTheme.slate400),
                            ],
                          ),
                          const SizedBox(height: 5),
                          Text(
                            order.senderName,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.slate900,
                              height: 1.25,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            order.pickupAddress,
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppTheme.slate500,
                              height: 1.35,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                // Circular 1-Tap Action Icons
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Row(
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
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Destination Point (Pembeli) - Clickable Area with Comfortable Typography
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 3),
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
                  child: InkWell(
                    onTap: () => _showAddressDetailModal(context, isPickup: false),
                    borderRadius: BorderRadius.circular(10),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 4),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'TUJUAN ANTAR (PEMBELI)',
                                style: TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.slate500,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(width: 4),
                              Icon(Icons.chevron_right, size: 13, color: AppTheme.slate400),
                            ],
                          ),
                          const SizedBox(height: 5),
                          Text(
                            order.recipientName,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.slate900,
                              height: 1.25,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            order.dropoffAddress,
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppTheme.slate500,
                              height: 1.35,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                // Circular 1-Tap Action Icons
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Row(
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
