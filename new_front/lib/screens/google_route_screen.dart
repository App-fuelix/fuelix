import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' as gmaps;
import 'package:latlong2/latlong.dart' as ll;

class GoogleRouteScreen extends StatefulWidget {
  final List<ll.LatLng> routePoints;
  final ll.LatLng origin;
  final ll.LatLng destination;

  const GoogleRouteScreen({super.key, required this.routePoints, required this.origin, required this.destination});

  @override
  State<GoogleRouteScreen> createState() => _GoogleRouteScreenState();
}

class _GoogleRouteScreenState extends State<GoogleRouteScreen> {
  gmaps.GoogleMapController? _controller;
  final String _polyId = 'route_poly';
  

  List<gmaps.LatLng> _toGpoints(List<ll.LatLng> pts) => pts.map((p) => gmaps.LatLng(p.latitude, p.longitude)).toList();

  @override
  Widget build(BuildContext context) {
    final gRoute = _toGpoints(widget.routePoints);
    final gOrigin = gmaps.LatLng(widget.origin.latitude, widget.origin.longitude);
    final gDest = gmaps.LatLng(widget.destination.latitude, widget.destination.longitude);

    final initial = gRoute.isNotEmpty ? gRoute.first : gOrigin;

    final polyline = gmaps.Polyline(
      polylineId: gmaps.PolylineId(_polyId),
      points: gRoute,
      width: 6,
      color: Colors.blueAccent,
    );

    final markers = {
      gmaps.Marker(
        markerId: const gmaps.MarkerId('origin'),
        position: gOrigin,
        infoWindow: const gmaps.InfoWindow(title: 'Votre position'),
      ),
      gmaps.Marker(
        markerId: const gmaps.MarkerId('destination'),
        position: gDest,
        infoWindow: const gmaps.InfoWindow(title: 'Destination'),
      ),
    };

    return Scaffold(
      appBar: AppBar(title: const Text('Itinéraire (Google Maps)')),
      body: gmaps.GoogleMap(
        initialCameraPosition: gmaps.CameraPosition(target: initial, zoom: 13.0),
        myLocationEnabled: true,
        markers: markers,
        polylines: {polyline},
        onMapCreated: (c) => _controller = c,
      ),
    );
  }
}
