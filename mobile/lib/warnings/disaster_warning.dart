class DisasterWarning {
  const DisasterWarning({
    required this.id,
    required this.title,
    required this.disasterType,
    required this.description,
    required this.severity,
    required this.urgency,
    required this.affectedArea,
    required this.source,
    required this.sourceUrl,
    required this.status,
    required this.expiresAt,
    this.updatedAt,
    this.publishedAt,
    this.startsAt,
  });

  final int id;
  final String title;
  final String disasterType;
  final String description;
  final String severity;
  final String? urgency;
  final String affectedArea;
  final String source;
  final String sourceUrl;
  final String status;
  final DateTime? publishedAt;
  final DateTime? startsAt;
  final DateTime expiresAt;
  final DateTime? updatedAt;

  factory DisasterWarning.fromJson(Map<String, dynamic> json) {
    return DisasterWarning(
      id: json['id'] as int,
      title: json['title'] as String,
      disasterType: json['disaster_type'] as String,
      description: json['description'] as String,
      severity: json['severity'] as String,
      urgency: json['urgency'] as String?,
      affectedArea: json['affected_area'] as String,
      source: json['source'] as String,
      sourceUrl: json['source_url'] as String,
      status: json['status'] as String,
      publishedAt: _date(json['published_at']),
      startsAt: _date(json['starts_at']),
      expiresAt: DateTime.parse(json['expires_at'] as String),
      updatedAt: _date(json['updated_at']),
    );
  }

  static DateTime? _date(Object? value) =>
      value is String ? DateTime.tryParse(value) : null;

  String get formattedPublishedAt => _format(publishedAt);
  String get formattedStartsAt => _format(startsAt);
  String get formattedExpiresAt => _format(expiresAt);
  String get formattedUpdatedAt => _format(updatedAt);

  int get severityPriority {
    final value = severity.toLowerCase().replaceAll('_', ' ').trim();

    if (value == 'extreme' || value == 'awas' || value == 'level iv') {
      return 5;
    }
    if (value == 'severe' || value == 'siaga' || value == 'level iii') {
      return 4;
    }
    if (value == 'moderate' || value == 'waspada' || value == 'level ii') {
      return 3;
    }
    if (value == 'minor' || value == 'normal' || value == 'level i') {
      return 2;
    }

    return 1;
  }

  int get urgencyPriority => switch (urgency?.toLowerCase().trim()) {
    'immediate' => 4,
    'expected' => 3,
    'future' => 2,
    'past' => 0,
    _ => 1,
  };

  bool affectsAdministrativeAddress(String? address) {
    if (address == null || address.trim().isEmpty) return false;

    final locationAreas = _administrativeAreas(address);
    final warningAreas = _administrativeAreas(affectedArea);

    return locationAreas.any(warningAreas.contains);
  }

  static Set<String> _administrativeAreas(String value) {
    return value
        .split(RegExp(r'[,;|]'))
        .map((area) => area.toLowerCase().replaceAll('.', '').trim())
        .map(
          (area) => area.replaceFirst(
            RegExp(
              r'^(provinsi|prov|kabupaten|kab|kota administrasi|kota|kecamatan|kec|kelurahan|kel|desa)\s+',
            ),
            '',
          ),
        )
        .map((area) => area.replaceAll(RegExp(r'\s+'), ' ').trim())
        .where((area) => area.length >= 3 && area != 'indonesia')
        .toSet();
  }

  static int comparePriority(DisasterWarning first, DisasterWarning second) {
    final severityOrder = second.severityPriority.compareTo(
      first.severityPriority,
    );
    if (severityOrder != 0) return severityOrder;

    final urgencyOrder = second.urgencyPriority.compareTo(
      first.urgencyPriority,
    );
    if (urgencyOrder != 0) return urgencyOrder;

    return (second.publishedAt ?? second.updatedAt ?? DateTime(0)).compareTo(
      first.publishedAt ?? first.updatedAt ?? DateTime(0),
    );
  }

  static String _format(DateTime? value) {
    if (value == null) return 'Tidak tersedia';
    final date = value.toLocal();
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '${date.day}/${date.month}/${date.year} $hour:$minute';
  }
}
