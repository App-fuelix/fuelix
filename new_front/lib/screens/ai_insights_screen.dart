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
  final _questionController = TextEditingController();
  bool _isLoading = true;
  Map<String, dynamic>? _insights;
  Map<String, dynamic>? _card;

  @override
  void initState() {
    super.initState();
    _loadInsights();
  }

  @override
  void dispose() {
    _questionController.dispose();
    super.dispose();
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

  double _asDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString().replaceAll(',', '.') ?? '') ?? 0;
  }

  String get _spendingTrend {
    final months = _listOf('monthly_comparison');
    if (months.length < 2) return 'Not enough data';

    final current = months.last is Map ? Map<String, dynamic>.from(months.last as Map) : <String, dynamic>{};
    final previousRaw = months[months.length - 2];
    final previous = previousRaw is Map ? Map<String, dynamic>.from(previousRaw) : <String, dynamic>{};
    final currentLiters = _asDouble(current['total_liters']);
    final previousLiters = _asDouble(previous['total_liters']);

    if (previousLiters <= 0) return 'Stable';
    final change = ((currentLiters - previousLiters) / previousLiters) * 100;
    if (change.abs() < 1) return 'Stable';
    return change > 0 ? '+${_formatNumber(change)}%' : '-${_formatNumber(change.abs())}%';
  }

  String get _balanceStatus {
    final balance = _currentBalance;
    final estimatedCost = _asDouble(_prediction['estimated_monthly_cost_tnd']);
    if (balance == null) return 'No card';
    if (estimatedCost <= 0) return '${_formatNumber(balance)} TND';
    if (balance >= estimatedCost) return 'Covered';
    return 'Needs ${_formatNumber(estimatedCost - balance)} TND';
  }

  void _openChat([String? question]) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => AiInsightsChatScreen(initialQuestion: question)),
    );
  }

  void _sendQuestion() {
    final question = _questionController.text.trim();
    if (question.isEmpty) return;
    _questionController.clear();
    _openChat(question);
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
        onPressed: () => _openChat(),
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
                    _insightMetrics(primary, panel, text, isDark),
                    const SizedBox(height: 16),
                    _recommendationsCard(panel, text, isDark),
                    const SizedBox(height: 16),
                    _chatSection(panel, primary, text, navy, isDark),
                  ],
                ),
              ),
            ),
    );
  }

  // ignore: unused_element
  Widget _predictionCard(Color panel, Color primary, Color text, bool isDark) {
    final prediction = _prediction;
    final estimatedBudget = _asDouble(prediction['estimated_monthly_cost_tnd']);
    final predictedLiters = _asDouble(prediction['predicted_monthly_liters']);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF183854) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: primary.withOpacity(0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                height: 44,
                width: 44,
                decoration: BoxDecoration(color: primary.withOpacity(0.16), shape: BoxShape.circle),
                child: Icon(Icons.auto_graph, color: primary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Next month fuel budget', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: text)),
                    const SizedBox(height: 3),
                    Text('Estimated from recent activity', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700], fontSize: 12)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            '${_formatNumber(estimatedBudget)} TND',
            style: TextStyle(fontSize: 34, fontWeight: FontWeight.bold, color: primary),
          ),
          const SizedBox(height: 4),
          Text('Predicted consumption: ${_formatNumber(predictedLiters)} L', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700])),
        ],
      ),
    );
  }

  Widget _insightMetrics(Color primary, Color panel, Color text, bool isDark) {
    final prediction = _prediction;
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      childAspectRatio: 1.35,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      children: [
        _miniInsightCard('Budget', '${_formatNumber(_asDouble(prediction['estimated_monthly_cost_tnd']))} TND', Icons.payments_outlined, panel, primary, text, isDark),
        _miniInsightCard('Consumption', '${_formatNumber(_asDouble(prediction['predicted_monthly_liters']))} L', Icons.local_gas_station_outlined, panel, primary, text, isDark),
        _miniInsightCard('Trend', _spendingTrend, Icons.trending_up, panel, primary, text, isDark),
        _miniInsightCard('Balance', _balanceStatus, Icons.account_balance_wallet_outlined, panel, primary, text, isDark),
      ],
    );
  }

  // ignore: unused_element
  Widget _chatLauncherCard(Color panel, Color primary, Color text, bool isDark) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => _openChat(),
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

  Widget _miniInsightCard(String label, String value, IconData icon, Color panel, Color primary, Color text, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: panel, borderRadius: BorderRadius.circular(14)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: primary, size: 22),
          const Spacer(),
          Text(value, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(fontWeight: FontWeight.bold, color: text, fontSize: 14)),
          const SizedBox(height: 4),
          Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: isDark ? Colors.white60 : Colors.grey[600], fontSize: 11)),
        ],
      ),
    );
  }

  Widget _chatSection(Color panel, Color primary, Color text, Color navy, bool isDark) {
    final quickQuestions = <String, String>{
      'Expenses increasing?': 'Why are my fuel expenses increasing?',
      'Reduce consumption': 'How can I reduce my fuel consumption?',
      'Normal usage?': 'Is my consumption normal compared to my history?',
      'This week plan': 'What should I do this week to control my budget?',
      'Recharge card?': 'Should I recharge my FueliX card?',
    };

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(color: panel, borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.smart_toy_outlined, color: primary),
              const SizedBox(width: 10),
              Text('Ask FueliX AI', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: text)),
            ],
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _questionController,
            minLines: 1,
            maxLines: 3,
            style: TextStyle(color: text),
            decoration: InputDecoration(
              hintText: 'Ask why it changed or what to do next...',
              hintStyle: TextStyle(color: isDark ? Colors.white54 : Colors.grey[500], fontSize: 13),
              filled: true,
              fillColor: isDark ? navy : const Color(0xFFF4F6F8),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              suffixIcon: IconButton(
                onPressed: _sendQuestion,
                icon: Icon(Icons.send, color: primary),
              ),
            ),
            onSubmitted: (_) => _sendQuestion(),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: quickQuestions.entries.map((entry) {
              return ActionChip(
                label: Text(entry.key, style: TextStyle(color: text, fontSize: 12)),
                avatar: Icon(Icons.bolt, color: primary, size: 16),
                backgroundColor: isDark ? const Color(0xFF0F2A44) : const Color(0xFFFFF6E8),
                side: BorderSide(color: primary.withOpacity(0.24)),
                onPressed: () => _openChat(entry.value),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _recommendationsCard(Color panel, Color text, bool isDark) {
    final recommendations = _listOf('recommendations');
    return _section(
      panel: panel,
      text: text,
      title: 'Smart recommendation',
      icon: Icons.lightbulb_outline,
      children: recommendations.isEmpty
          ? [Text('Add more transactions to unlock personalized advice.', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700]))]
          : recommendations.map((item) => _bullet(_englishRecommendation(item.toString()), text)).toList(),
    );
  }

  String _englishRecommendation(String value) {
    final normalized = value.toLowerCase();
    if (normalized.contains('ajoutez des transactions')) {
      return 'Add more transactions to unlock personalized recommendations.';
    }
    if (normalized.contains('recommandations seront plus')) {
      return 'Your recommendations will become more accurate after a full analysis of your history.';
    }
    if (normalized.contains('consommation augmente')) {
      return 'Your consumption is increasing this month. Check repeated trips and tire pressure.';
    }
    if (normalized.contains('bonne tendance')) {
      return 'Good trend: your consumption is lower than the previous month.';
    }
    if (normalized.contains('prix/litre') || normalized.contains('prix moyen')) {
      return 'Monitor your price per liter and compare stations before refueling.';
    }
    if (normalized.contains('transactions semblent inhabituelles')) {
      return 'One or more transactions look unusual. Review the details to confirm them.';
    }
    if (normalized.contains('hausse est possible')) {
      return 'An increase may be coming soon. Plan your card top-ups before long trips.';
    }
    if (normalized.contains('profil de consommation est stable')) {
      return 'Your consumption profile is stable for now.';
    }
    return value;
  }

  // ignore: unused_element
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

  // ignore: unused_element
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

  // ignore: unused_element
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
