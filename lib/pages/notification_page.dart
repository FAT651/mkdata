import 'package:flutter/material.dart';

class NotificationPage extends StatefulWidget {
  const NotificationPage({super.key});

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        backgroundColor: const Color(0xFF36474F),
        foregroundColor: Colors.white,
      ),
      body: ListView.builder(
        itemCount: 0, // TODO: Replace with actual notifications
        itemBuilder: (context, index) {
          return const ListTile(
            // TODO: Implement notification items
          );
        },
      ),
    );
  }
}
