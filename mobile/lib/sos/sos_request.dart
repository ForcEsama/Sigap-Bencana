import 'dart:math';

class SosRequest {
  const SosRequest({
    required this.id,
    required this.reference,
    required this.disasterType,
    required this.latitude,
    required this.longitude,
    required this.status,
    required this.requestedAt,
    this.acknowledgedAt,
    this.completedAt,
    required this.address,
    required this.events,
  });

  final int id;
  final String reference;
  final String disasterType;
  final double latitude;
  final double longitude;
  final String status;
  final DateTime? requestedAt;
  final DateTime? acknowledgedAt;
  final DateTime? completedAt;
  final String? address;
  final List<SosEvent> events;

  factory SosRequest.fromJson(Map<String, dynamic> json) {
    final rawEvents = json['events'];

    return SosRequest(
      id: (json['id'] as num).toInt(),
      reference: json['reference']?.toString() ?? 'SOS-${json['id']}',
      disasterType: json['disaster_type']?.toString() ?? '',
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      status: json['status']?.toString() ?? 'sent',
      requestedAt: DateTime.tryParse(json['requested_at']?.toString() ?? ''),
      acknowledgedAt: DateTime.tryParse(
        json['acknowledged_at']?.toString() ?? '',
      ),
      completedAt: DateTime.tryParse(json['completed_at']?.toString() ?? ''),
      address: json['address']?.toString(),
      events: rawEvents is List
          ? rawEvents
                .whereType<Map<String, dynamic>>()
                .map(SosEvent.fromJson)
                .toList()
          : const [],
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'reference': reference,
    'disaster_type': disasterType,
    'latitude': latitude,
    'longitude': longitude,
    'address': address,
    'status': status,
    'requested_at': requestedAt?.toIso8601String(),
    'acknowledged_at': acknowledgedAt?.toIso8601String(),
    'completed_at': completedAt?.toIso8601String(),
    'events': events
        .map(
          (event) => {
            'status': event.status,
            'actor': event.actor,
            'occurred_at': event.occurredAt?.toIso8601String(),
            'notes': event.notes,
          },
        )
        .toList(),
  };

  String get statusLabel => switch (status) {
    'sent' => 'Menunggu Konfirmasi Petugas',
    'assigned' => 'Petugas Ditugaskan',
    'en_route' => 'Petugas Menuju Lokasi',
    'arrived' => 'Bantuan Tiba',
    'completed' => 'Penanganan Selesai',
    'rejected' => 'Belum dapat ditindaklanjuti',
    _ => 'Status belum diketahui',
  };
}

class SosEvent {
  const SosEvent({
    required this.status,
    required this.actor,
    required this.occurredAt,
    this.notes,
  });

  final String status;
  final String actor;
  final DateTime? occurredAt;
  final String? notes;

  factory SosEvent.fromJson(Map<String, dynamic> json) => SosEvent(
    status: json['status']?.toString() ?? '',
    actor: json['actor']?.toString() ?? 'Sistem',
    occurredAt: DateTime.tryParse(json['occurred_at']?.toString() ?? ''),
    notes: json['notes']?.toString(),
  );

  String get statusLabel => switch (status) {
    'sent' => 'Permintaan Dikirim',
    'assigned' => 'Petugas Ditugaskan',
    'en_route' => 'Petugas Menuju Lokasi',
    'arrived' => 'Bantuan Tiba',
    'completed' => 'Penanganan Selesai',
    'rejected' => 'Permintaan Ditolak',
    _ => 'Pembaruan status',
  };
}

String sosDisasterTypeLabel(String disasterType) => switch (disasterType) {
  'gempa' => 'Gempa Bumi',
  'banjir' => 'Banjir',
  'longsor' => 'Longsor',
  'kebakaran' => 'Kebakaran',
  'tsunami' => 'Tsunami',
  _ => 'Keadaan darurat',
};

String createSosClientRequestId() {
  final random = Random.secure();
  final bytes = List<int>.generate(16, (_) => random.nextInt(256));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  final hex = bytes.map((byte) => byte.toRadixString(16).padLeft(2, '0'));
  final value = hex.join();

  return '${value.substring(0, 8)}-${value.substring(8, 12)}-'
      '${value.substring(12, 16)}-${value.substring(16, 20)}-'
      '${value.substring(20)}';
}
