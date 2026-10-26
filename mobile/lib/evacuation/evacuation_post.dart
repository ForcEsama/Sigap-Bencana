class EvacuationPost {
  const EvacuationPost({
    required this.name,
    required this.address,
    required this.status,
    this.recordId,
    this.isPlanningCandidate = false,
    this.locationCode,
    this.description,
    this.latitude,
    this.longitude,
    this.capacity,
    this.recommendedCapacity,
    this.currentOccupancy,
    this.facilities,
    this.contactPerson,
    this.contactPhone,
    this.sourceDocument,
    this.sourcePage,
    this.manualReviewNote,
    this.imagePath,
  });

  final int? recordId;
  final bool isPlanningCandidate;
  final String? locationCode;
  final String name;
  final String address;
  final String status;
  final String? description;
  final double? latitude;
  final double? longitude;
  final int? capacity;
  final int? recommendedCapacity;
  final int? currentOccupancy;
  final String? facilities;
  final String? contactPerson;
  final String? contactPhone;
  final String? sourceDocument;
  final String? sourcePage;
  final String? manualReviewNote;
  final String? imagePath;

  bool get hasValidCoordinates =>
      latitude != null &&
      longitude != null &&
      latitude! >= -90 &&
      latitude! <= 90 &&
      longitude! >= -180 &&
      longitude! <= 180;

  String get statusLabel {
    if (isPlanningCandidate) {
      return 'Kandidat TEA · belum diverifikasi';
    }

    switch (status.toLowerCase().replaceAll(' ', '_')) {
      case 'available':
        return 'Tersedia';
      case 'almost_full':
        return 'Hampir penuh';
      case 'full':
        return 'Penuh';
      case 'inactive':
        return 'Tidak aktif';
      case 'active':
        return 'Aktif; ketersediaan belum diketahui';
      default:
        return 'Status belum dikonfirmasi';
    }
  }

  factory EvacuationPost.fromJson(Map<String, dynamic> json) {
    final isPlanningCandidate = json['is_planning_candidate'] == true;
    final latitude = _number(json['latitude']);
    final longitude = _number(json['longitude']);
    final hasMapCoordinates =
        (isPlanningCandidate
            ? json['has_candidate_coordinates'] == true
            : json['has_valid_coordinates'] == true) &&
        latitude != null &&
        longitude != null;

    return EvacuationPost(
      recordId: json['id'] is num ? (json['id'] as num).toInt() : null,
      isPlanningCandidate: isPlanningCandidate,
      locationCode: _string(json['location_code']),
      name: json['name']?.toString() ?? 'Tempat evakuasi',
      address: json['address']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      description: _string(json['description']),
      latitude: hasMapCoordinates ? latitude : null,
      longitude: hasMapCoordinates ? longitude : null,
      capacity: !isPlanningCandidate && json['capacity'] is num
          ? (json['capacity'] as num).toInt()
          : null,
      recommendedCapacity: json['recommended_capacity'] is num
          ? (json['recommended_capacity'] as num).toInt()
          : int.tryParse(json['recommended_capacity']?.toString() ?? ''),
      currentOccupancy: json['current_occupancy'] is num
          ? (json['current_occupancy'] as num).toInt()
          : null,
      facilities: _string(json['facilities']),
      contactPerson: _string(json['contact_person']),
      contactPhone: _string(json['contact_phone']),
      sourceDocument: _string(json['source_document']),
      sourcePage: _string(json['source_page']),
      manualReviewNote: _string(json['manual_review_note']),
      imagePath: _string(json['image_path']),
    );
  }

  static double? _number(Object? value) => value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '');

  static String? _string(Object? value) {
    final text = value?.toString().trim();
    return text == null || text.isEmpty ? null : text;
  }
}
