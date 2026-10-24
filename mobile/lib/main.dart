import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'ui/core/theme.dart';
import 'ui/view_models/driver_view_model.dart';
import 'ui/views/courier_home_view.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const FleetPulseMobileApp());
}

class FleetPulseMobileApp extends StatelessWidget {
  const FleetPulseMobileApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => DriverViewModel()),
      ],
      child: MaterialApp(
        title: 'FleetPulse Mobile',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        home: const CourierHomeView(),
      ),
    );
  }
}
