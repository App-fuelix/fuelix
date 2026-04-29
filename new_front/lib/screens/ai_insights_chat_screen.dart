import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class AiInsightsChatScreen extends StatefulWidget {
  const AiInsightsChatScreen({super.key});

  @override
  State<AiInsightsChatScreen> createState() => _AiInsightsChatScreenState();
}

class _AiInsightsChatScreenState extends State<AiInsightsChatScreen> {
  final _questionController = TextEditingController();
  final _messages = <_ChatMessage>[
    const _ChatMessage(
      text: 'Bonjour. Je peux repondre sur votre consommation, votre budget carburant, votre solde carte et vos recommandations.',
      isUser: false,
    ),
  ];

  bool _isLoading = true;
  Map<String, dynamic>? _insights;
  Map<String, dynamic>? _card;

  @override
  void initState() {
    super.initState();
    _loadContext();
  }

  @override
  void dispose() {
    _questionController.dispose();
    super.dispose();
  }

  Future<void> _loadContext() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token == null) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _messages.add(const _ChatMessage(text: 'Connectez-vous pour utiliser le chatbot FueliX.', isUser: false));
        });
      }
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
      setState(() {
        _insights = insightsResult['status'] == 200 && insightsResult['body'] is Map
            ? Map<String, dynamic>.from(insightsResult['body'] as Map)
            : null;
        _card = cardResult['status'] == 200 && cardResult['body'] is Map
            ? Map<String, dynamic>.from(cardResult['body'] as Map)
            : null;
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _messages.add(const _ChatMessage(text: 'Les donnees FueliX sont indisponibles pour le moment.', isUser: false));
      });
    }
  }

  void _sendQuestion() {
    final question = _questionController.text.trim();
    if (question.isEmpty) return;

    final normalized = _normalizeText(question);
    final answer = _buildAnswer(normalized);
    setState(() {
      _messages.add(_ChatMessage(text: question, isUser: true));
      _messages.add(_ChatMessage(text: answer, isUser: false));
    });
    _questionController.clear();
  }

  String _buildAnswer(String question) {
    final asksBalance = _hasAny(question, [
      'solde',
      'balance',
      'carte',
      'argent',
      'credit',
      'reste',
      'restant',
      'disponible',
      'montant',
    ]);
    final asksCoverage = _hasAny(question, [
      'combien de temps',
      'couvrir',
      'couverture',
      'duree',
      'periode',
      'suffisant',
      'suffisante',
      'prochaine',
    ]);

    if (asksBalance && !asksCoverage) return _answerCurrentBalance();

    if (_insights == null) {
      if (asksBalance) return _answerCurrentBalance();
      return 'Je n ai pas encore recu vos donnees d analyse. Je peux quand meme repondre au solde si votre carte est disponible.';
    }

    final prediction = _prediction;
    final recommendations = _listOf('recommendations');
    final anomalies = _listOf('anomalies');
    final monthly = _listOf('monthly_comparison');
    final transactionCount = _asInt(_insights?['transaction_count']);

    final asksAdvice = _hasAny(question, ['conseil', 'recommand', 'optimis', 'reduire', 'econom', 'moins consommer']);
    final asksAnomaly = _hasAny(question, ['anomal', 'bizarre', 'inhabituel', 'suspect', 'erreur']);
    final asksTrend = _hasAny(question, ['tendance', 'evolution', 'augmente', 'baisse', 'diminue', 'mois']);
    final asksCost = _hasAny(question, ['cout', 'prix', 'depense', 'budget', 'payer']);
    final asksPrediction = _hasAny(question, ['consomm', 'prediction', 'prevision', 'futur', 'estimation']);

    if (asksBalance && asksCoverage) return _answerBalanceSufficiency(prediction);
    if (asksCoverage) return _answerCoverage(prediction);
    if (asksAdvice) return _answerOptimization(recommendations, monthly, anomalies);
    if (asksAnomaly) return _answerAnomalies(anomalies);
    if (asksTrend) return _answerTrend(monthly);

    if (transactionCount == 0) {
      return 'Je n ai pas encore assez de transactions pour donner une analyse fiable. Ajoutez quelques pleins pour obtenir des reponses personnalisees.';
    }

    if (asksCost && !asksPrediction) {
      final cost = _asDouble(prediction['estimated_monthly_cost_tnd']);
      final liters = _asDouble(prediction['predicted_monthly_liters']);
      return 'Votre cout mensuel estime est de ${_formatNumber(cost)} TND. Cette estimation correspond a une consommation prevue de ${_formatNumber(liters)} L.';
    }

    if (asksPrediction || asksCost) {
      final liters = _asDouble(prediction['predicted_monthly_liters']);
      final cost = _asDouble(prediction['estimated_monthly_cost_tnd']);
      return 'Votre consommation mensuelle prevue est de ${_formatNumber(liters)} L, pour un cout estime a ${_formatNumber(cost)} TND.';
    }

    return 'Je peux repondre uniquement aux questions liees a FueliX: consommation, depenses carburant, previsions, solde carte, anomalies et recommandations.';
  }

  String _answerCurrentBalance() {
    final balance = _currentBalance;
    if (balance == null) {
      return 'Je ne trouve pas encore votre carte carburant. Verifiez que vous avez une carte FueliX active ou rechargez la page.';
    }

    final status = (_card?['status'] ?? '').toString();
    final statusText = status.isEmpty ? '' : ' Statut de la carte: $status.';
    return 'Votre solde actuel est de ${_formatNumber(balance)} TND.$statusText';
  }

  String _answerBalanceSufficiency(Map<String, dynamic> prediction) {
    final balance = _currentBalance;
    if (balance == null) {
      return 'Je ne trouve pas encore votre carte carburant. Verifiez que vous avez une carte FueliX active ou rechargez la page.';
    }

    final estimatedCost = _asDouble(prediction['estimated_monthly_cost_tnd']);
    if (estimatedCost <= 0) {
      return 'Votre solde actuel est de ${_formatNumber(balance)} TND. Je n ai pas encore assez de donnees pour confirmer s il suffit pour la prochaine periode.';
    }

    final missing = estimatedCost - balance;
    if (missing <= 0) {
      return 'Oui. Votre solde est de ${_formatNumber(balance)} TND et le cout prevu est d environ ${_formatNumber(estimatedCost)} TND. Il resterait environ ${_formatNumber(balance - estimatedCost)} TND.';
    }

    return 'Pas totalement. Votre solde est de ${_formatNumber(balance)} TND, alors que le cout prevu est d environ ${_formatNumber(estimatedCost)} TND. Il manque environ ${_formatNumber(missing)} TND.';
  }

  String _answerCoverage(Map<String, dynamic> prediction) {
    final balance = _currentBalance;
    if (balance == null) {
      return 'Je ne peux pas calculer la duree de couverture sans le solde de votre carte carburant.';
    }

    final monthlyCost = _asDouble(prediction['estimated_monthly_cost_tnd']);
    if (monthlyCost <= 0) {
      return 'Votre solde est de ${_formatNumber(balance)} TND, mais il me manque une estimation fiable des depenses mensuelles.';
    }

    final months = balance / monthlyCost;
    final days = months * 30;
    final periodText = months >= 1 ? 'environ ${_formatNumber(months)} mois' : 'environ ${_formatNumber(days)} jours';
    return 'Avec ${_formatNumber(balance)} TND et un besoin estime a ${_formatNumber(monthlyCost)} TND par mois, votre solde peut couvrir $periodText.';
  }

  String _answerOptimization(List<dynamic> recommendations, List<dynamic> monthly, List<dynamic> anomalies) {
    final tips = <String>[];
    if (recommendations.isNotEmpty) {
      tips.addAll(recommendations.take(2).map((item) => item.toString()));
    }

    if (monthly.length >= 2) {
      final current = monthly.last is Map ? Map<String, dynamic>.from(monthly.last as Map) : <String, dynamic>{};
      final previousRaw = monthly[monthly.length - 2];
      final previous = previousRaw is Map ? Map<String, dynamic>.from(previousRaw) : <String, dynamic>{};
      if (_asDouble(current['total_liters']) > _asDouble(previous['total_liters'])) {
        tips.add('Votre consommation augmente. Regroupez les trajets courts et evitez les accelerations brusques.');
      }
    }

    if (anomalies.isNotEmpty) {
      tips.add('Verifiez les transactions inhabituelles: elles peuvent expliquer une hausse du budget.');
    }

    tips.add('Comparez les stations, gardez une pression de pneus correcte et planifiez les pleins avant les longs trajets.');
    return tips.take(3).join('\n');
  }

  String _answerAnomalies(List<dynamic> anomalies) {
    if (anomalies.isEmpty) {
      return 'Aucune anomalie importante n a ete detectee dans vos transactions recentes.';
    }

    final first = anomalies.first is Map ? Map<String, dynamic>.from(anomalies.first as Map) : <String, dynamic>{};
    return '${anomalies.length} transaction(s) inhabituelle(s) detectee(s). Exemple: ${first['date'] ?? '-'} avec ${first['quantity_liters'] ?? 0} L.';
  }

  String _answerTrend(List<dynamic> monthly) {
    if (monthly.length < 2) {
      return 'Il faut au moins deux mois de transactions pour comparer votre tendance.';
    }

    final current = monthly.last is Map ? Map<String, dynamic>.from(monthly.last as Map) : <String, dynamic>{};
    final previousRaw = monthly[monthly.length - 2];
    final previous = previousRaw is Map ? Map<String, dynamic>.from(previousRaw) : <String, dynamic>{};
    final currentLiters = _asDouble(current['total_liters']);
    final previousLiters = _asDouble(previous['total_liters']);
    if (previousLiters <= 0) {
      return 'Je ne peux pas calculer une variation fiable car le mois precedent ne contient pas assez de litres.';
    }

    final variation = ((currentLiters - previousLiters) / previousLiters) * 100;
    final direction = variation >= 0 ? 'augmente' : 'diminue';
    return 'Votre consommation $direction de ${_formatNumber(variation.abs())}%: ${_formatNumber(currentLiters)} L contre ${_formatNumber(previousLiters)} L le mois precedent.';
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

  bool _hasAny(String text, List<String> terms) {
    return terms.any((term) => text.contains(_normalizeText(term)));
  }

  String _normalizeText(String value) {
    return value
        .trim()
        .toLowerCase()
        .replaceAll(RegExp(r'[àáâãäå]'), 'a')
        .replaceAll(RegExp(r'[ç]'), 'c')
        .replaceAll(RegExp(r'[èéêë]'), 'e')
        .replaceAll(RegExp(r'[ìíîï]'), 'i')
        .replaceAll(RegExp(r'[òóôõö]'), 'o')
        .replaceAll(RegExp(r'[ùúûü]'), 'u')
        .replaceAll(RegExp(r'[ÿ]'), 'y')
        .replaceAll(RegExp(r'\s+'), ' ');
  }

  double _asDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString().replaceAll(',', '.') ?? '') ?? 0;
  }

  int _asInt(dynamic value) {
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _formatNumber(double value) {
    if (value == value.roundToDouble()) return value.toStringAsFixed(0);
    return value.toStringAsFixed(1);
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
        title: Text('Chatbot FueliX', style: TextStyle(color: text, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          if (_isLoading) const LinearProgressIndicator(color: Color(0xFFF2A945)),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) => _MessageBubble(
                message: _messages[index],
                primary: primary,
                panel: panel,
                text: text,
                isDark: isDark,
              ),
            ),
          ),
          SafeArea(
            top: false,
            child: Container(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
              decoration: BoxDecoration(
                color: panel,
                border: Border(top: BorderSide(color: isDark ? Colors.white10 : Colors.black12)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _questionController,
                      minLines: 1,
                      maxLines: 4,
                      style: TextStyle(color: text),
                      decoration: InputDecoration(
                        hintText: 'Posez une question FueliX...',
                        filled: true,
                        fillColor: isDark ? navy : const Color(0xFFF4F6F8),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      ),
                      onSubmitted: (_) => _sendQuestion(),
                    ),
                  ),
                  const SizedBox(width: 10),
                  SizedBox(
                    height: 48,
                    width: 48,
                    child: IconButton.filled(
                      onPressed: _sendQuestion,
                      style: IconButton.styleFrom(backgroundColor: primary),
                      icon: const Icon(Icons.send, color: Colors.white),
                    ),
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

class _ChatMessage {
  const _ChatMessage({required this.text, required this.isUser});

  final String text;
  final bool isUser;
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    required this.message,
    required this.primary,
    required this.panel,
    required this.text,
    required this.isDark,
  });

  final _ChatMessage message;
  final Color primary;
  final Color panel;
  final Color text;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final bubbleColor = message.isUser ? primary : panel;
    final bubbleText = message.isUser ? Colors.white : text;

    return Align(
      alignment: message.isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 310),
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: bubbleColor,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(14),
            topRight: const Radius.circular(14),
            bottomLeft: Radius.circular(message.isUser ? 14 : 2),
            bottomRight: Radius.circular(message.isUser ? 2 : 14),
          ),
          border: message.isUser ? null : Border.all(color: isDark ? Colors.white10 : Colors.black12),
        ),
        child: Text(message.text, style: TextStyle(color: bubbleText, height: 1.35)),
      ),
    );
  }
}
