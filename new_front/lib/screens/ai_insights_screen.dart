import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'ai_insights_chat_screen.dart';

class AiInsightsScreen extends StatefulWidget {
  const AiInsightsScreen({super.key});

  @override
  State<AiInsightsScreen> createState() => _AiInsightsScreenState();
}

class _AiInsightsScreenState extends State<AiInsightsScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _insights;
  Map<String, dynamic>? _card;

  @override
  void initState() {
    super.initState();
    _loadInsights();
  }

  Future<void> _loadInsights() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token == null) {
      if (mounted) setState(() => _isLoading = false);
      return;
    }

    try {
      final results = await Future.wait([
        ApiService.getAiInsights(token),
        ApiService.getCard(token),
      ]);
      if (!mounted) return;

      final insightsResult = results[0];
      final cardResult = results[1];

      if (insightsResult['status'] == 200) {
        setState(() {
          _insights = Map<String, dynamic>.from(insightsResult['body'] as Map);
          _card = cardResult['status'] == 200 && cardResult['body'] is Map
              ? Map<String, dynamic>.from(cardResult['body'] as Map)
              : null;
        });
      } else {
        _showMessage('Impossible de charger les insights IA.');
      }
    } catch (_) {
      if (mounted) _showMessage('Serveur IA indisponible.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Map<String, dynamic> get _prediction {
    final raw = _insights?['prediction'];
    return raw is Map ? Map<String, dynamic>.from(raw) : <String, dynamic>{};
  }

  List<dynamic> _listOf(String key) {
    final raw = _insights?[key];
    return raw is List ? raw : [];
  }

  double? get _currentBalance {
    final raw = _card?['balance_raw'] ?? _card?['balance'];
    if (raw == null) return null;
    if (raw is num) return raw.toDouble();
    final cleaned = raw.toString().replaceAll('TND', '').replaceAll(' ', '').replaceAll(',', '.');
    return double.tryParse(cleaned);
  }

  String _formatNumber(double value) {
    if (value == value.roundToDouble()) return value.toStringAsFixed(0);
    return value.toStringAsFixed(1);
  }

  void _openChat() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const AiInsightsChatScreen()),
    );
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.redAccent),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final primary = const Color(0xFFF2A945);
    final navy = const Color(0xFF0F2A44);
    final bg = isDark ? navy : const Color(0xFFF4F6F8);
    final panel = isDark ? const Color(0xFF1B3B5A) : Colors.white;
    final text = isDark ? Colors.white : navy;

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: text),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('AI Insights', style: TextStyle(color: text, fontWeight: FontWeight.bold)),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openChat,
        backgroundColor: primary,
        foregroundColor: Colors.white,
        child: const Icon(Icons.chat_bubble_outline),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFF2A945)))
          : RefreshIndicator(
              onRefresh: _loadInsights,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 96),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _predictionCard(panel, primary, text, isDark),
                    const SizedBox(height: 16),
                    _chatLauncherCard(panel, primary, text, isDark),
                    const SizedBox(height: 16),
                    _recommendationsCard(panel, text),
                    const SizedBox(height: 16),
                    _monthlyCard(panel, text, primary),
                    if (_listOf('anomalies').isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _anomaliesCard(panel, text),
                    ],
                  ],
                ),
              ),
            ),
    );
  }

  Widget _predictionCard(Color panel, Color primary, Color text, bool isDark) {
    final prediction = _prediction;
    final transactionCount = _insights?['transaction_count'] ?? 0;
    final balance = _currentBalance;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: panel,
        borderRadius: BorderRadius.circular(16),
        border: Border(top: BorderSide(color: primary, width: 4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.psychology, color: primary),
              const SizedBox(width: 10),
              Text('Prevision personnalisee', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: text)),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            '${prediction['predicted_monthly_liters'] ?? 0} L',
            style: TextStyle(fontSize: 34, fontWeight: FontWeight.bold, color: primary),
          ),
          const SizedBox(height: 4),
          Text('Consommation mensuelle prevue', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700])),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(child: _metricTile('Cout estime', '${prediction['estimated_monthly_cost_tnd'] ?? 0} TND', isDark)),
              const SizedBox(width: 12),
              Expanded(child: _metricTile('Transactions', '$transactionCount', isDark)),
            ],
          ),
          if (balance != null) ...[
            const SizedBox(height: 12),
            _metricTile('Solde carte', '${_formatNumber(balance)} TND', isDark),
          ],
          const SizedBox(height: 12),
          const Text('Analyse calculee a partir de votre historique recent.', style: TextStyle(color: Colors.grey, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _chatLauncherCard(Color panel, Color primary, Color text, bool isDark) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: _openChat,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(color: panel, borderRadius: BorderRadius.circular(16)),
        child: Row(
          children: [
            Container(
              height: 46,
              width: 46,
              decoration: BoxDecoration(color: primary.withOpacity(0.14), shape: BoxShape.circle),
              child: Icon(Icons.smart_toy_outlined, color: primary),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Chatbot FueliX', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: text)),
                  const SizedBox(height: 4),
                  Text(
                    'Posez vos questions sur le solde, la consommation et le budget.',
                    style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700], fontSize: 12),
                  ),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios, color: isDark ? Colors.white54 : Colors.grey, size: 16),
          ],
        ),
      ),
    );
  }

  Widget _recommendationsCard(Color panel, Color text) {
    final recommendations = _listOf('recommendations');
    return _section(
      panel: panel,
      text: text,
      title: 'Recommandations',
      icon: Icons.tips_and_updates,
      children: recommendations.isEmpty
          ? [const Text('Aucune recommandation disponible.', style: TextStyle(color: Colors.grey))]
          : recommendations.map((item) => _bullet(item.toString(), text)).toList(),
    );
  }

  Widget _monthlyCard(Color panel, Color text, Color primary) {
    final months = _listOf('monthly_comparison');
    return _section(
      panel: panel,
      text: text,
      title: 'Comparaison mensuelle',
      icon: Icons.bar_chart,
      children: months.isEmpty
          ? [const Text('Pas encore assez de donnees.', style: TextStyle(color: Colors.grey))]
          : months.reversed.map((item) {
              final month = item is Map ? item : {};
              return Container(
                margin: const EdgeInsets.only(bottom: 10),
                child: Row(
                  children: [
                    Expanded(child: Text('${month['month'] ?? '-'}', style: TextStyle(color: text, fontWeight: FontWeight.w600))),
                    Text('${month['total_liters'] ?? 0} L', style: TextStyle(color: primary, fontWeight: FontWeight.bold)),
                  ],
                ),
              );
            }).toList(),
    );
  }

  Widget _anomaliesCard(Color panel, Color text) {
    final anomalies = _listOf('anomalies');
    return _section(
      panel: panel,
      text: text,
      title: 'Anomalies',
      icon: Icons.warning_amber,
      children: anomalies.map((item) {
        final anomaly = item is Map ? item : {};
        return _bullet('${anomaly['date'] ?? '-'}: ${anomaly['quantity_liters'] ?? 0} L - ${anomaly['station_name'] ?? ''}', text);
      }).toList(),
    );
  }

  Widget _section({
    required Color panel,
    required Color text,
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(color: panel, borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: const Color(0xFFF2A945)),
              const SizedBox(width: 10),
              Text(title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: text)),
            ],
          ),
          const SizedBox(height: 14),
          ...children,
        ],
      ),
    );
  }

  Widget _bullet(String value, Color text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.only(top: 6),
            child: Icon(Icons.circle, size: 7, color: Color(0xFFF2A945)),
          ),
          const SizedBox(width: 10),
          Expanded(child: Text(value, style: TextStyle(color: text))),
        ],
      ),
    );
  }

  Widget _metricTile(String label, String value, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
          const SizedBox(height: 4),
          Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF0F2A44))),
        ],
      ),
    );
  }
}
