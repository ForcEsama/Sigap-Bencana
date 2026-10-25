import 'package:flutter/material.dart';

enum AppDestination { home, map, sos, notifications, profile }

class AppBottomNavigation extends StatelessWidget {
  const AppBottomNavigation({
    required this.selected,
    required this.onSelect,
    super.key,
  });

  final AppDestination selected;
  final ValueChanged<AppDestination> onSelect;

  static const Color _red = Color(0xFFD50032);

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 72,
      padding: const EdgeInsets.symmetric(horizontal: 6),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFF1DDE1))),
      ),
      child: Row(
        children: [
          _item('Home', Icons.home, AppDestination.home),
          _item('Map', Icons.map, AppDestination.map),
          Expanded(
            child: Center(
              child: Transform.translate(
                offset: const Offset(0, -12),
                child: InkWell(
                  onTap: () => onSelect(AppDestination.sos),
                  customBorder: const CircleBorder(),
                  child: Container(
                    width: 60,
                    height: 60,
                    decoration: const BoxDecoration(
                      color: _red,
                      shape: BoxShape.circle,
                    ),
                    alignment: Alignment.center,
                    child: const Text(
                      'SOS',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          _item(
            'Notification',
            Icons.notifications,
            AppDestination.notifications,
          ),
          _item('Profile', Icons.person, AppDestination.profile),
        ],
      ),
    );
  }

  Widget _item(String label, IconData icon, AppDestination destination) {
    final isSelected = selected == destination;

    return Expanded(
      child: InkWell(
        onTap: () => onSelect(destination),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 22, color: isSelected ? _red : Colors.black),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                color: isSelected ? _red : Colors.black,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
