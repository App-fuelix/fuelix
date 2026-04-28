import 'dart:convert';
import 'dart:math' as math;
import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart' as gmaps;
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'google_route_screen.dart';

class StationsScreen extends StatefulWidget {
  const StationsScreen({super.key});
  @override
  State<StationsScreen> createState() => _StationsScreenState();
}

class _StationsScreenState extends State<StationsScreen> {
  final MapController _mapController = MapController();
  static const LatLng _tunisiaDefaultCenter = LatLng(36.8065, 10.1815);
  StreamSubscription<Position>? _positionStreamSubscription;
  
  bool _isLoading = true;
  bool _isRouteLoading = false;
  List<Map<String, dynamic>> _stations = [];
  LatLng? _userLocation;
  String _locationStatus = 'Recherche de votre position...';
  String _selectedService = 'All';
  List<LatLng> _routePoints = [];
  Map<String, dynamic>? _activeRouteStation;
  String _routeSummary = '';

  final List<Map<String, dynamic>> _services = [
    {'key': 'All', 'label': 'All', 'icon': Icons.apps},
    {'key': 'fuel', 'label': 'Fuel', 'icon': Icons.local_gas_station},
    {'key': 'carwash', 'label': 'Car Wash', 'icon': Icons.local_car_wash},
    {'key': 'lubricants', 'label': 'Lubricants', 'icon': Icons.oil_barrel},
    {'key': 'shop', 'label': 'Shop', 'icon': Icons.storefront},
  ];

  @override
  void initState() {
    super.initState();
    _initMapAndData();
  }

  @override
  void dispose() {
    _positionStreamSubscription?.cancel();
    super.dispose();
  }

  Future<void> _initMapAndData() async {
    setState(() => _isLoading = true);
    
    // 1. Get Location
    await _getUserLocation();
    _startAutoLocationTracking();
    
    // 2. Fetch Stations
    await _loadStations();
  }

  Future<void> _getUserLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      if (mounted) {
        setState(() {
          _locationStatus = 'Activez la localisation pour voir les stations proches.';
        });
      }
      return;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        if (mounted) {
          setState(() {
            _locationStatus = 'Autorisez l’accès à la localisation pour afficher votre position.';
          });
        }
        return;
      }
    }
    
    if (permission == LocationPermission.deniedForever) {
      if (mounted) {
        setState(() {
          _locationStatus = 'Les permissions de localisation sont bloquées.';
        });
      }
      return;
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 8),
        ),
      );
      final userLocation = LatLng(position.latitude, position.longitude);

      if (!mounted) return;

      _applyDetectedLocation(userLocation, status: 'Position détectée automatiquement');
      return;
    } on TimeoutException {
      final lastKnown = await Geolocator.getLastKnownPosition();
      if (lastKnown != null) {
        final userLocation = LatLng(lastKnown.latitude, lastKnown.longitude);
        if (!mounted) return;

        _applyDetectedLocation(userLocation, status: 'Dernière position connue utilisée');
        return;
      }

      if (mounted) {
        setState(() {
          _locationStatus = 'Impossible d’obtenir un fix GPS. Sur l’émulateur, définis une position simulée.';
        });
      }
    } on LocationServiceDisabledException {
      if (mounted) {
        setState(() {
          _locationStatus = 'Le service de localisation a été coupé pendant la requête.';
        });
      }
    } catch (_) {
      final lastKnown = await Geolocator.getLastKnownPosition();
      if (lastKnown != null) {
        final userLocation = LatLng(lastKnown.latitude, lastKnown.longitude);
        if (!mounted) return;

        _applyDetectedLocation(userLocation, status: 'Dernière position connue utilisée');
        return;
      }

      if (mounted) {
        setState(() {
          _locationStatus = 'La localisation est autorisée, mais aucun signal GPS n’a été reçu.';
        });
      }
    }
  }

  void _startAutoLocationTracking() {
    _positionStreamSubscription?.cancel();

    _positionStreamSubscription = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 20,
      ),
    ).listen(
      (position) {
        if (!mounted) return;

        final userLocation = LatLng(position.latitude, position.longitude);
        final hadNoLocation = _userLocation == null;

        _applyDetectedLocation(
          userLocation,
          status: 'Position mise à jour automatiquement',
          centerMap: hadNoLocation,
        );
      },
      onError: (_) {
        if (!mounted) return;
        setState(() {
          _locationStatus = 'Mise à jour automatique indisponible temporairement.';
        });
      },
    );
  }

  void _applyDetectedLocation(
    LatLng userLocation, {
    required String status,
    bool centerMap = true,
  }) {
    if (!mounted) return;

    setState(() {
      _userLocation = userLocation;
      _locationStatus = status;
    });

    if (centerMap) {
      _mapController.move(userLocation, 13.0);
    }
  }

  Future<void> _loadStations() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token == null) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      final result = await ApiService.getStations(
        token,
        service: _selectedService,
        latitude: _userLocation?.latitude,
        longitude: _userLocation?.longitude,
      );
      if (result['status'] == 200) {
        final body = result['body'] as Map<String, dynamic>;
        final payload = body['body'] is Map<String, dynamic>
            ? body['body'] as Map<String, dynamic>
            : body;
        final stationsList = payload['stations'] as List? ?? [];
        final stations = stationsList
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList();

        stations.sort((a, b) => _stationDistanceKm(a).compareTo(_stationDistanceKm(b)));
        
        setState(() {
          _stations = stations;
        });
      }
    } catch (_) {}
    finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _onServiceChanged(String serviceKey) {
    if (_selectedService == serviceKey) return;
    setState(() => _selectedService = serviceKey);
    _loadStations();
  }

  double _stationDistanceKm(Map<String, dynamic> station) {
    final distanceFromApi = (station['distance_km'] as num?)?.toDouble();
    final lat = (station['latitude'] as num?)?.toDouble();
    final lng = (station['longitude'] as num?)?.toDouble();

    if (_userLocation == null || lat == null || lng == null) {
      return distanceFromApi ?? double.infinity;
    }

    const earthRadiusKm = 6371.0;
    final dLat = _degreesToRadians(lat - _userLocation!.latitude);
    final dLng = _degreesToRadians(lng - _userLocation!.longitude);
    final a = math.sin(dLat / 2) * math.sin(dLat / 2) +
        math.cos(_degreesToRadians(_userLocation!.latitude)) *
        math.cos(_degreesToRadians(lat)) *
        math.sin(dLng / 2) * math.sin(dLng / 2);
    final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
    return earthRadiusKm * c;
  }

  double _degreesToRadians(double degrees) => degrees * math.pi / 180.0;

  Future<void> _navigateToStation(Map<String, dynamic> station) async {
    final origin = _userLocation;
    final stationLat = (station['latitude'] as num?)?.toDouble();
    final stationLng = (station['longitude'] as num?)?.toDouble();

    if (origin == null || stationLat == null || stationLng == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Position ou coordonnées de station introuvables.')),
      );
      return;
    }

    // Validate / correct station coordinates (simple heuristic for swapped lat/lng)
    var destLat = stationLat;
    var destLng = stationLng;

    const tunisiaLatMin = 30.0;
    const tunisiaLatMax = 38.8;
    const tunisiaLngMin = 7.0;
    const tunisiaLngMax = 12.0;

    bool inTunisia(double lat, double lng) {
      return lat >= tunisiaLatMin && lat <= tunisiaLatMax && lng >= tunisiaLngMin && lng <= tunisiaLngMax;
    }

    // If coordinates look swapped, fix them
    if (!inTunisia(destLat, destLng) && inTunisia(destLng, destLat)) {
      final tmp = destLat;
      destLat = destLng;
      destLng = tmp;
    }

    final destination = LatLng(destLat, destLng);

    setState(() {
      _isRouteLoading = true;
      _activeRouteStation = station;
      _routeSummary = 'Calcul de l’itinéraire...';
      _routePoints = [origin, destination];
    });

    try {
      final routeUrl = Uri.parse(
        'https://router.project-osrm.org/route/v1/driving/'
        '${origin.longitude.toStringAsFixed(6)},${origin.latitude.toStringAsFixed(6)};'
        '${destination.longitude.toStringAsFixed(6)},${destination.latitude.toStringAsFixed(6)}'
        '?overview=full&geometries=geojson&steps=false',
      );

      final response = await http.get(routeUrl);
      if (response.statusCode != 200) {
        throw Exception('Route service unavailable');
      }

      final data = jsonDecode(response.body) as Map<String, dynamic>;
      final routes = data['routes'] as List?;
      final firstRoute = routes != null && routes.isNotEmpty ? Map<String, dynamic>.from(routes.first as Map) : null;

      if (firstRoute == null) {
        throw Exception('No route found');
      }

      final geometry = firstRoute['geometry'] as Map<String, dynamic>?;
      final coordinates = geometry?['coordinates'] as List? ?? [];
      final routePoints = coordinates
          .map((point) {
            final pair = point as List;
            return LatLng((pair[1] as num).toDouble(), (pair[0] as num).toDouble());
          })
          .toList();

      final distanceMeters = (firstRoute['distance'] as num?)?.toDouble() ?? 0;
      final durationSeconds = (firstRoute['duration'] as num?)?.toDouble() ?? 0;

      if (!mounted) return;

      final usedPoints = routePoints.isNotEmpty ? routePoints : [origin, destination];

      setState(() {
        _routePoints = usedPoints;
        _routeSummary = 'Itinéraire: ${_formatDistance(distanceMeters)} • ${_formatDuration(durationSeconds)}';
      });

      _fitRouteOnMap(_routePoints);

      try {
        final gOrigin = gmaps.LatLng(origin.latitude, origin.longitude);
        final gDest = gmaps.LatLng(destination.latitude, destination.longitude);
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => GoogleRouteScreen(
              routePoints: usedPoints,
              origin: gOrigin,
              destination: gDest,
            ),
          ),
        );
      } catch (_) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Itinéraire affiché dans Fuelix.')),
        );
      }
    } catch (_) {
      if (!mounted) return;

      // Only draw a straight fallback route when both points are within Tunisia.
      if (inTunisia(origin.latitude, origin.longitude) && inTunisia(destination.latitude, destination.longitude)) {
        setState(() {
          _routePoints = [origin, destination];
          _routeSummary = 'Itinéraire approximatif affiché';
        });

        _fitRouteOnMap(_routePoints);

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Itinéraire approximatif affiché dans l’app.')),
        );
      } else {
        setState(() {
          _routePoints = [];
          _routeSummary = 'Itinéraire indisponible pour ces coordonnées';
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible de calculer un itinéraire valide pour cette station.')),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isRouteLoading = false;
        });
      }
    }
  }

  void _fitRouteOnMap(List<LatLng> points) {
    if (points.isEmpty) return;

    final latitudes = points.map((point) => point.latitude).toList();
    final longitudes = points.map((point) => point.longitude).toList();
    final southWest = LatLng(latitudes.reduce(math.min), longitudes.reduce(math.min));
    final northEast = LatLng(latitudes.reduce(math.max), longitudes.reduce(math.max));

    _mapController.fitCamera(
      CameraFit.bounds(
        bounds: LatLngBounds(southWest, northEast),
        padding: const EdgeInsets.all(64),
      ),
    );
  }

  String _formatDistance(double meters) {
    if (meters >= 1000) {
      return '${(meters / 1000).toStringAsFixed(1)} km';
    }
    return '${meters.toStringAsFixed(0)} m';
  }

  String _formatDuration(double seconds) {
    final minutes = (seconds / 60).round();
    if (minutes >= 60) {
      final hours = minutes ~/ 60;
      final remainingMinutes = minutes % 60;
      return remainingMinutes == 0 ? '$hours h' : '$hours h $remainingMinutes min';
    }
    return '$minutes min';
  }

  List<Map<String, dynamic>> get _nearbyStations {
    if (_stations.isEmpty) return [];

    final stations = [..._stations];
    stations.sort((a, b) => _stationDistanceKm(a).compareTo(_stationDistanceKm(b)));

    return stations.take(5).toList();
  }

  void _showStationDetails(Map<String, dynamic> station) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final primary = const Color(0xFFF2A945);
    final navy = const Color(0xFF0F2A44);
    
    showModalBottomSheet(
      context: context,
      backgroundColor: isDark ? const Color(0xFF1B3B5A) : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    station['name'] ?? 'Station',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: isDark ? Colors.white : navy),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: station['is_open'] ? Colors.green.withValues(alpha: 0.1) : Colors.red.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    station['is_open'] ? 'Open' : 'Closed',
                    style: TextStyle(color: station['is_open'] ? Colors.green : Colors.red, fontWeight: FontWeight.bold, fontSize: 12),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.location_on, color: Colors.grey, size: 16),
                const SizedBox(width: 4),
                Text('${station['distance_km']} km away', style: const TextStyle(color: Colors.grey, fontSize: 14)),
              ],
            ),
            const SizedBox(height: 20),
            const Text('Available Services', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: (station['services'] as List? ?? []).map((s) {
                final serviceStr = s.toString();
                IconData icon = Icons.local_gas_station;
                String label = 'Fuel';
                if (serviceStr == 'carwash') { icon = Icons.local_car_wash; label = 'Car Wash'; }
                if (serviceStr == 'lubricants') { icon = Icons.oil_barrel; label = 'Lubricants'; }
                if (serviceStr == 'shop') { icon = Icons.storefront; label = 'Shop'; }
                if (serviceStr == 'cafe') { icon = Icons.local_cafe; label = 'Cafe'; }
                
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: primary.withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(icon, color: primary, size: 16),
                      const SizedBox(width: 6),
                      Text(label, style: TextStyle(color: primary, fontWeight: FontWeight.w600, fontSize: 13)),
                    ],
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton.icon(
                onPressed: () {
                  Navigator.pop(ctx);
                  _navigateToStation(station);
                },
                icon: const Icon(Icons.navigation, color: Colors.white),
                label: const Text('Voir l’itinéraire', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primary,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final navy = const Color(0xFF0F2A44);
    final primary = const Color(0xFFF2A945);
    final bg = isDark ? navy : const Color(0xFFF4F6F8);

    return Scaffold(
      backgroundColor: bg,
      body: Stack(
        children: [
          // Flutter Map
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              // Default to Tunis if location not yet loaded
              initialCenter: _userLocation ?? _tunisiaDefaultCenter,
              initialZoom: 12.0,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.example.fuelix_project',
              ),
              if (_routePoints.isNotEmpty)
                PolylineLayer(
                  polylines: [
                    Polyline(
                      points: _routePoints,
                      strokeWidth: 5,
                      color: const Color(0xFFF2A945),
                    ),
                  ],
                ),
              MarkerLayer(
                markers: [
                  // User Location Marker
                  if (_userLocation != null)
                    Marker(
                      point: _userLocation!,
                      width: 40,
                      height: 40,
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.blue.withValues(alpha: 0.3),
                          shape: BoxShape.circle,
                        ),
                        child: Center(
                          child: Container(
                            width: 16, height: 16,
                            decoration: const BoxDecoration(
                              color: Colors.blue,
                              shape: BoxShape.circle,
                            ),
                          ),
                        ),
                      ),
                    ),
                  
                  // Station Markers
                  ..._stations.map((s) {
                    final lat = s['latitude'] as double?;
                    final lng = s['longitude'] as double?;
                    if (lat == null || lng == null) return null;
                    
                    return Marker(
                      point: LatLng(lat, lng),
                      width: 44,
                      height: 44,
                      child: GestureDetector(
                        onTap: () => _showStationDetails(s),
                        child: Container(
                          decoration: BoxDecoration(
                            color: isDark ? navy : Colors.white,
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 8)],
                            border: Border.all(color: primary, width: 2),
                          ),
                          child: Icon(Icons.local_gas_station, color: primary, size: 24),
                        ),
                      ),
                    );
                  }).whereType<Marker>(),
                ],
              ),
            ],
          ),

          // Top UI Overlay
          Positioned(
            top: 0, left: 0, right: 0,
            child: Container(
              padding: const EdgeInsets.only(top: 50, bottom: 16),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [navy, navy.withValues(alpha: 0.0)],
                ),
              ),
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Nearby Stations', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.white, shadows: [Shadow(color: Colors.black.withValues(alpha: 0.5), blurRadius: 4)])),
                        if (_isLoading) const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: _services.map((s) {
                        final isSelected = _selectedService == s['key'];
                        return Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: GestureDetector(
                            onTap: () => _onServiceChanged(s['key'] as String),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              decoration: BoxDecoration(
                                color: isSelected ? primary : (isDark ? navy.withValues(alpha: 0.8) : Colors.white),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(color: isSelected ? primary : Colors.grey.withValues(alpha: 0.3)),
                                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 4)],
                              ),
                              child: Row(
                                children: [
                                  Icon(s['icon'] as IconData, size: 16, color: isSelected ? Colors.white : (isDark ? Colors.white70 : navy)),
                                  const SizedBox(width: 6),
                                  Text(s['label'] as String, style: TextStyle(color: isSelected ? Colors.white : (isDark ? Colors.white70 : navy), fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
                                ],
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ],
              ),
            ),
          ),

          Positioned(
            left: 16,
            right: 16,
            bottom: 86,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_isRouteLoading)
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF1B3B5A) : Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 12)],
                    ),
                    child: Row(
                      children: [
                        SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2, color: primary),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'Calcul de l’itinéraire dans Fuelix...',
                            style: TextStyle(color: isDark ? Colors.white : navy, fontWeight: FontWeight.w600),
                          ),
                        ),
                      ],
                    ),
                  ),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1B3B5A) : Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 18, offset: const Offset(0, 8))],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: primary.withValues(alpha: 0.12),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(Icons.my_location, color: primary, size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              _userLocation != null ? 'Votre position actuelle' : 'Position non disponible',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: isDark ? Colors.white : navy),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _userLocation != null
                                  ? 'Latitude ${_userLocation!.latitude.toStringAsFixed(4)} • Longitude ${_userLocation!.longitude.toStringAsFixed(4)}'
                                  : _locationStatus,
                              style: TextStyle(fontSize: 12.5, color: isDark ? Colors.white70 : Colors.grey[700]),
                            ),
                            if (_activeRouteStation != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Itinéraire vers ${_activeRouteStation!['name'] ?? 'la station'}',
                                style: TextStyle(fontSize: 11.8, color: isDark ? Colors.white60 : Colors.grey[600]),
                              ),
                            ],
                            if (_routeSummary.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                _routeSummary,
                                style: TextStyle(fontSize: 11.8, color: isDark ? Colors.white60 : Colors.grey[600]),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      TextButton(
                        onPressed: _getUserLocation,
                        child: Text('Actualiser', style: TextStyle(color: primary, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1B3B5A) : Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 18, offset: const Offset(0, 8))],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Stations à proximité', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? Colors.white : navy)),
                          Text('${_nearbyStations.length} trouvée(s)', style: TextStyle(fontSize: 12, color: isDark ? Colors.white70 : Colors.grey[700])),
                        ],
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 140,
                        child: _stations.isEmpty
                            ? Center(child: Text('Aucune station disponible', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey)))
                            : ListView.separated(
                                scrollDirection: Axis.horizontal,
                                itemCount: (_nearbyStations.isEmpty ? _stations : _nearbyStations).take(5).length,
                                separatorBuilder: (context, separatorIndex) => const SizedBox(width: 12),
                                itemBuilder: (context, index) {
                                  final visibleStations = _nearbyStations.isEmpty ? _stations : _nearbyStations;
                                  final station = visibleStations.take(5).toList()[index];
                                  final distance = _stationDistanceKm(station);
                                  final isOpen = station['is_open'] == true;

                                  return GestureDetector(
                                    onTap: () {
                                      final lat = (station['latitude'] as num?)?.toDouble();
                                      final lng = (station['longitude'] as num?)?.toDouble();
                                      if (lat != null && lng != null) {
                                        _mapController.move(LatLng(lat, lng), 14.5);
                                      }
                                      _showStationDetails(station);
                                    },
                                    child: Container(
                                      width: 220,
                                      padding: const EdgeInsets.all(14),
                                      decoration: BoxDecoration(
                                        color: isDark ? navy.withValues(alpha: 0.55) : const Color(0xFFF9FAFC),
                                        borderRadius: BorderRadius.circular(18),
                                        border: Border.all(color: primary.withValues(alpha: 0.18)),
                                      ),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Expanded(
                                                child: Text(
                                                  station['name'] ?? 'Station',
                                                  maxLines: 1,
                                                  overflow: TextOverflow.ellipsis,
                                                  style: TextStyle(fontWeight: FontWeight.bold, color: isDark ? Colors.white : navy),
                                                ),
                                              ),
                                              const SizedBox(width: 8),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(
                                                  color: isOpen ? Colors.green.withValues(alpha: 0.12) : Colors.red.withValues(alpha: 0.12),
                                                  borderRadius: BorderRadius.circular(20),
                                                ),
                                                child: Text(isOpen ? 'Ouverte' : 'Fermée', style: TextStyle(fontSize: 11, color: isOpen ? Colors.green : Colors.red, fontWeight: FontWeight.bold)),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(height: 8),
                                          Text('${distance.toStringAsFixed(1)} km de vous', style: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700], fontSize: 12.5)),
                                          const SizedBox(height: 8),
                                          Wrap(
                                            spacing: 6,
                                            runSpacing: 6,
                                            children: (station['services'] as List? ?? []).take(3).map((service) {
                                              return Chip(
                                                label: Text(service.toString(), style: const TextStyle(fontSize: 11)),
                                                padding: EdgeInsets.zero,
                                                visualDensity: VisualDensity.compact,
                                                backgroundColor: primary.withValues(alpha: 0.12),
                                                side: BorderSide.none,
                                              );
                                            }).toList(),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          // User Location Button
          Positioned(
            right: 16,
            bottom: 24,
            child: FloatingActionButton(
              onPressed: _getUserLocation,
              backgroundColor: isDark ? navy : Colors.white,
              child: Icon(Icons.my_location, color: primary),
            ),
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(isDark, primary, navy),
    );
  }

  Widget _buildBottomNav(bool isDark, Color primary, Color navy) {
    return BottomNavigationBar(
      currentIndex: 0,
      onTap: (i) {
        if (i == 0) Navigator.pushReplacementNamed(context, '/home');
        if (i == 1) Navigator.pushReplacementNamed(context, '/card');
        if (i == 2) Navigator.pushReplacementNamed(context, '/history');
        if (i == 3) Navigator.pushReplacementNamed(context, '/profile');
      },
      type: BottomNavigationBarType.fixed,
      backgroundColor: isDark ? navy : Colors.white,
      selectedItemColor: primary,
      unselectedItemColor: Colors.grey,
      items: const [
        BottomNavigationBarItem(icon: Icon(Icons.home_filled), label: 'Home'),
        BottomNavigationBarItem(icon: Icon(Icons.credit_card), label: 'Card'),
        BottomNavigationBarItem(icon: Icon(Icons.history), label: 'History'),
        BottomNavigationBarItem(icon: Icon(Icons.person_outline), label: 'Profile'),
      ],
    );
  }
}
