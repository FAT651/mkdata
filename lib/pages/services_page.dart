import 'package:flutter/material.dart';
import 'airtime_page.dart';
import 'data_page.dart';
import 'cable_page.dart';
import 'electricity_page.dart';
import 'exam_pin_page.dart';
import 'datapin_page.dart';
import 'azcash_page.dart';
import 'card_pin_page.dart';
import 'package:url_launcher/url_launcher.dart';

class ServicesPage extends StatelessWidget {
  const ServicesPage({super.key});

  Future<void> _launchURL(String urlString) async {
    final Uri url = Uri.parse(urlString);
    if (!await launchUrl(url)) {
      throw Exception('Could not launch \$urlString');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'All Services',
          style: TextStyle(color: Colors.black),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Popular Services',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF36474F),
                ),
              ),
              const SizedBox(height: 16),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 3,
                mainAxisSpacing: 16,
                crossAxisSpacing: 16,
                childAspectRatio: 1,
                children: [
                  _buildServiceCard(
                    context,
                    'Airtime',
                    Icons.phone_android,
                    Colors.red,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const AirtimePage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Data',
                    Icons.wifi,
                    Colors.blue,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => const DataPage()),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Cable TV',
                    Icons.tv,
                    Colors.cyan,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const CablePage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Electricity',
                    Icons.flash_on,
                    Colors.green,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const ElectricityPage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Exam Pin',
                    Icons.school,
                    Colors.brown,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const ExamPinPage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Data Pin',
                    Icons.pin,
                    Colors.purple,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const DatapinPage(),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),
              const Text(
                'Other Services',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF36474F),
                ),
              ),
              const SizedBox(height: 16),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 3,
                mainAxisSpacing: 16,
                crossAxisSpacing: 16,
                childAspectRatio: 1,
                children: [
                  _buildServiceCard(
                    context,
                    'A2Cash',
                    Icons.money,
                    Colors.indigo,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const AzcashPage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Card Pin',
                    Icons.credit_card,
                    Colors.amber,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const CardPinPage(),
                      ),
                    ),
                  ),
                  _buildServiceCard(
                    context,
                    'Website',
                    Icons.language,
                    Colors.blue,
                    () => _launchURL('https://binalionedata.com.ng/'),
                  ),
                  _buildServiceCard(
                    context,
                    'NIN Verification',
                    Icons.verified_user,
                    Colors.teal,
                    () {
                      // TODO: Implement NIN verification
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Coming soon!')),
                      );
                    },
                  ),
                  _buildServiceCard(
                    context,
                    'BVN Verification',
                    Icons.account_box,
                    Colors.deepPurple,
                    () {
                      // TODO: Implement BVN verification
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Coming soon!')),
                      );
                    },
                  ),
                  _buildServiceCard(
                    context,
                    'CAC Verification',
                    Icons.business,
                    Colors.orange,
                    () {
                      // TODO: Implement CAC verification
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Coming soon!')),
                      );
                    },
                  ),
                  _buildServiceCard(
                    context,
                    'NIN Number',
                    Icons.pin,
                    Colors.teal,
                    () {
                      // TODO: Implement NIN Number verification
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('NIN Number verification coming soon!'),
                        ),
                      );
                    },
                  ),
                  _buildServiceCard(
                    context,
                    'NIN Slip',
                    Icons.document_scanner,
                    Colors.teal,
                    () {
                      // TODO: Implement NIN Slip verification
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('NIN Slip verification coming soon!'),
                        ),
                      );
                    },
                  ),
                  _buildServiceCard(
                    context,
                    'Market Signals',
                    Icons.trending_up,
                    Colors.blue,
                    () {
                      // TODO: Implement Market Signals service
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Market signals coming soon!'),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildServiceCard(
    BuildContext context,
    String title,
    IconData icon,
    Color color,
    VoidCallback onTap,
  ) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFF455A64), width: 2),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.shade200,
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 32),
            ),
            const SizedBox(height: 8),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              child: Text(
                title,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF36474F),
                ),
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
