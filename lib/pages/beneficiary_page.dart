import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../utils/network_utils.dart';

class BeneficiaryPage extends StatefulWidget {
  const BeneficiaryPage({super.key});

  @override
  State<BeneficiaryPage> createState() => _BeneficiaryPageState();
}

class _BeneficiaryPageState extends State<BeneficiaryPage> {
  List<Map<String, dynamic>> _beneficiaries = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadBeneficiaries();
  }

  Future<void> _loadBeneficiaries() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('user_id');
      if (userId == null) throw Exception('Not logged in');
      final resp = await http.get(
        Uri.parse('${ApiService.baseUrl}/api/beneficiaries?user_id=$userId'),
      );
      if (resp.statusCode == 200) {
        final data = json.decode(resp.body);
        if (data['status'] == 'success') {
          final list = List<Map<String, dynamic>>.from(data['data']);
          setState(() {
            _beneficiaries = list;
          });
        } else {
          throw Exception(data['message'] ?? 'Failed to load');
        }
      } else {
        throw Exception('Failed to fetch beneficiaries');
      }
    } catch (e) {
      if (mounted) showNetworkErrorSnackBar(context, e);
      setState(() {
        _beneficiaries = [];
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveBeneficiaries() async {
    // No-op: server persists beneficiaries. Keep local cache in SharedPreferences if needed.
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('beneficiaries', json.encode(_beneficiaries));
  }

  Future<void> _showAddEditDialog({int? index}) async {
    final nameController = TextEditingController(
      text: index != null ? (_beneficiaries[index]['name'] ?? '') : '',
    );
    final phoneController = TextEditingController(
      text: index != null ? (_beneficiaries[index]['phone'] ?? '') : '',
    );

    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(index == null ? 'Add Beneficiary' : 'Edit Beneficiary'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            TextField(
              controller: phoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Phone'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final name = nameController.text.trim();
              final phone = phoneController.text.trim();
              if (name.isEmpty || phone.isEmpty) return;
              setState(() => _isLoading = true);
              try {
                final prefs = await SharedPreferences.getInstance();
                final userId = prefs.getString('user_id');
                if (userId == null) throw Exception('Not logged in');

                if (index == null) {
                  final resp = await http.post(
                    Uri.parse('${ApiService.baseUrl}/api/beneficiary'),
                    headers: {'Content-Type': 'application/json'},
                    body: json.encode({
                      'user_id': userId,
                      'name': name,
                      'phone': phone,
                    }),
                  );
                  if (resp.statusCode == 200) {
                    final d = json.decode(resp.body);
                    if (d['status'] == 'success') {
                      // append the returned id
                      _beneficiaries.add({
                        'id': d['data']['id'],
                        'name': name,
                        'phone': phone,
                      });
                    } else {
                      throw Exception(d['message'] ?? 'Failed to add');
                    }
                  } else {
                    throw Exception('Failed to add beneficiary');
                  }
                } else {
                  final id = _beneficiaries[index]['id'];
                  final resp = await http.put(
                    Uri.parse('${ApiService.baseUrl}/api/beneficiary'),
                    headers: {'Content-Type': 'application/json'},
                    body: json.encode({
                      'id': id,
                      'user_id': userId,
                      'name': name,
                      'phone': phone,
                    }),
                  );
                  if (resp.statusCode == 200) {
                    final d = json.decode(resp.body);
                    if (d['status'] == 'success') {
                      _beneficiaries[index] = {
                        'id': id,
                        'name': name,
                        'phone': phone,
                      };
                    } else {
                      throw Exception(d['message'] ?? 'Failed to update');
                    }
                  } else {
                    throw Exception('Failed to update beneficiary');
                  }
                }

                await _saveBeneficiaries();
                Navigator.of(context).pop(true);
              } catch (e) {
                if (mounted) showNetworkErrorSnackBar(context, e);
                Navigator.of(context).pop(false);
              } finally {
                if (mounted) setState(() => _isLoading = false);
              }
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );

    if (result == true) {
      setState(() {});
    }
  }

  Future<void> _deleteBeneficiary(int index) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Beneficiary'),
        content: const Text(
          'Are you sure you want to delete this beneficiary?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      setState(() => _isLoading = true);
      try {
        final prefs = await SharedPreferences.getInstance();
        final userId = prefs.getString('user_id');
        if (userId == null) throw Exception('Not logged in');
        final id = _beneficiaries[index]['id'];
        final resp = await http.delete(
          Uri.parse('${ApiService.baseUrl}/api/beneficiary'),
          headers: {'Content-Type': 'application/json'},
          body: json.encode({'id': id, 'user_id': userId}),
        );
        if (resp.statusCode == 200) {
          final d = json.decode(resp.body);
          if (d['status'] == 'success') {
            _beneficiaries.removeAt(index);
            await _saveBeneficiaries();
            setState(() {});
          } else {
            throw Exception(d['message'] ?? 'Delete failed');
          }
        } else {
          throw Exception('Failed to delete beneficiary');
        }
      } catch (e) {
        if (mounted) showNetworkErrorSnackBar(context, e);
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: const Color(0xFF0A2463),
        elevation: 0,
        title: const Text(
          'Beneficiaries',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add, color: Colors.white),
            onPressed: () => _showAddEditDialog(),
            tooltip: 'Add beneficiary',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox(
                    width: 40,
                    height: 40,
                    child: CircularProgressIndicator(
                      color: Color(0xFF0A2463),
                      strokeWidth: 2.5,
                    ),
                  ),
                  SizedBox(height: 16),
                  Text(
                    'Loading beneficiaries...',
                    style: TextStyle(
                      color: Color(0xFF0A2463),
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            )
          : _beneficiaries.isEmpty
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.contacts_outlined,
                    size: 64,
                    color: Colors.grey.shade300,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No beneficiaries yet',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey.shade600,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: 160,
                    height: 44,
                    child: ElevatedButton(
                      onPressed: () => _showAddEditDialog(),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0A2463),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.add, color: Colors.white, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'Add Beneficiary',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            )
          : ListView.builder(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: _beneficiaries.length,
              itemBuilder: (context, index) {
                final b = _beneficiaries[index];
                final name = (b['name'] ?? '').toString();
                final phone = (b['phone'] ?? '').toString();
                return Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  child: Card(
                    elevation: 2,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      leading: CircleAvatar(
                        backgroundColor: const Color(0xFF0A2463),
                        child: Text(
                          name.isNotEmpty ? name[0].toUpperCase() : '?',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      title: Text(
                        name,
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                        ),
                      ),
                      subtitle: Text(
                        phone,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 13,
                        ),
                      ),
                      onTap: () {
                        Navigator.of(context).pop(phone);
                      },
                      trailing: PopupMenuButton(
                        itemBuilder: (context) => [
                          PopupMenuItem(
                            child: const Row(
                              children: [
                                Icon(Icons.edit, size: 18),
                                SizedBox(width: 8),
                                Text('Edit'),
                              ],
                            ),
                            onTap: () => _showAddEditDialog(index: index),
                          ),
                          PopupMenuItem(
                            child: const Row(
                              children: [
                                Icon(Icons.delete, size: 18, color: Colors.red),
                                SizedBox(width: 8),
                                Text(
                                  'Delete',
                                  style: TextStyle(color: Colors.red),
                                ),
                              ],
                            ),
                            onTap: () => _deleteBeneficiary(index),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}
