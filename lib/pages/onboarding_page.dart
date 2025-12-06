import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'login_page.dart';

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key});

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  late PageController _pageController;
  int _currentPage = 0;

  final List<OnboardingItem> onboardingItems = [
    OnboardingItem(
      title: 'Airtime Solution',
      description:
          'Experience unparalleled support in the VTU and data reselling industry with our top-notch customer service. Resolve any issues promptly through our Live chat feature.',
      icon: Icons.support_agent,
      iconColor: Color(0xFFFF9800),
      imageAsset: 'assets/images/idee.png',
    ),
    OnboardingItem(
      title: 'Customer Support',
      description:
          'We provide a solution for purchasing airtime in Nigeria. Simply enter the desired amount you wish to purchase.',
      icon: Icons.bolt,
      iconColor: Color(0xFFFFC107),
      imageAsset: 'assets/images/laptop.png',
    ),
    OnboardingItem(
      title: 'Automated Delivery',
      description:
          'Our range of products, including affordable MTN data, GOtv, Startimes, DSTV subscriptions, and even electricity bills, are all handled through automated processes.',
      icon: Icons.local_shipping,
      iconColor: Color(0xFFE91E63),
      imageAsset: 'assets/images/sem.png',
    ),
  ];

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _completeOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboarding_completed', true);

    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const LoginPage()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          Column(
            children: [
              Expanded(
                child: PageView.builder(
                  controller: _pageController,
                  onPageChanged: (index) {
                    setState(() {
                      _currentPage = index;
                    });
                  },
                  itemCount: onboardingItems.length,
                  itemBuilder: (context, index) {
                    return OnboardingScreen(item: onboardingItems[index]);
                  },
                ),
              ),
            ],
          ),
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              padding: EdgeInsets.only(
                left: 20.0,
                right: 20.0,
                top: 16.0,
                bottom: MediaQuery.of(context).padding.bottom + 16.0,
              ),
              color: Colors.white,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      onboardingItems.length,
                      (index) => AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        margin: const EdgeInsets.symmetric(horizontal: 6),
                        width: _currentPage == index ? 12 : 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _currentPage == index
                              ? const Color(0xFFce4323)
                              : Colors.grey.shade300,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      TextButton(
                        onPressed: () async {
                          final prefs = await SharedPreferences.getInstance();
                          await prefs.setBool('onboarding_completed', true);
                          if (mounted) {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(
                                builder: (context) => const LoginPage(),
                              ),
                            );
                          }
                        },
                        child: const Text(
                          'Skip',
                          style: TextStyle(
                            color: Color(0xFFce4323),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      SizedBox(
                        width: 56,
                        height: 56,
                        child: FloatingActionButton(
                          backgroundColor: const Color(0xFFce4323),
                          elevation: 6,
                          onPressed: () {
                            if (_currentPage == onboardingItems.length - 1) {
                              _completeOnboarding();
                            } else {
                              _pageController.nextPage(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeInOut,
                              );
                            }
                          },
                          child: Icon(
                            _currentPage == onboardingItems.length - 1
                                ? Icons.check
                                : Icons.arrow_forward,
                            color: Colors.white,
                            size: 24,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class OnboardingItem {
  final String title;
  final String description;
  final IconData icon;
  final Color iconColor;
  final String? imageAsset;

  OnboardingItem({
    required this.title,
    required this.description,
    required this.icon,
    required this.iconColor,
    this.imageAsset,
  });
}

class OnboardingScreen extends StatelessWidget {
  final OnboardingItem item;

  const OnboardingScreen({super.key, required this.item});

  @override
  Widget build(BuildContext context) {
    final baseWidth = MediaQuery.of(context).size.width;
    final titleSize = (baseWidth * 0.065).clamp(20.0, 30.0);
    final descSize = (baseWidth * 0.043).clamp(13.0, 18.0);

    return Stack(
      children: [
        // Blue gradient background at top
        Container(
          height: MediaQuery.of(context).size.height * 0.55,
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color(0xFFce4323), Color(0xFFce4323)],
            ),
          ),
        ),
        // White content area
        Container(
          margin: EdgeInsets.only(
            top: MediaQuery.of(context).size.height * 0.5,
          ),
          color: Colors.white,
        ),
        // Main content
        SafeArea(
          child: Column(
            children: [
              // Top section with icon - responsive sizing
              // Top section with large illustration (use image asset similar to screenshot)
              Expanded(
                child: Center(
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final width = MediaQuery.of(context).size.width;
                      final circleSize = (width * 0.65).clamp(160.0, 320.0);
                      final imgSize = circleSize * 0.9;
                      return Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          // Large illustration that extends slightly into the white area
                          SizedBox(
                            height: circleSize,
                            width: circleSize,
                            child: Image.asset(
                              item.imageAsset ?? 'assets/images/idee.png',
                              width: imgSize,
                              height: imgSize,
                              fit: BoxFit.contain,
                              alignment: Alignment.bottomCenter,
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),
              // Wave divider
              ClipPath(
                clipper: WaveClipper(),
                child: Container(color: Colors.white, height: 80),
              ),
              // Bottom white section with text
              Expanded(
                child: Container(
                  color: Colors.white,
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.start,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Text(
                        item.title,
                        style: TextStyle(
                          fontSize: titleSize,
                          fontWeight: FontWeight.bold,
                          color: Colors.black,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        item.description,
                        style: TextStyle(
                          fontSize: descSize,
                          color: const Color(0xFF666666),
                          height: 1.6,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class WaveClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    Path path = Path();
    path.lineTo(0, 40);
    path.quadraticBezierTo(size.width / 4, 0, size.width / 2, 40);
    path.quadraticBezierTo(size.width * 3 / 4, 80, size.width, 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
