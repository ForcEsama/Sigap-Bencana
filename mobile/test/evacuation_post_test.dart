import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/evacuation/evacuation_post.dart';

void main() {
  test('parses verified map coordinates and operational details', () {
    final post = EvacuationPost.fromJson({
      'id': 12,
      'name': 'Gedung Evakuasi',
      'address': 'Padang Utara',
      'latitude': '-0.896402',
      'longitude': '100.355539',
      'has_valid_coordinates': true,
      'capacity': 120,
      'current_occupancy': 25,
      'status': 'available',
    });

    expect(post.recordId, 12);
    expect(post.hasValidCoordinates, isTrue);
    expect(post.capacity, 120);
    expect(post.currentOccupancy, 25);
    expect(post.statusLabel, 'Tersedia');
  });

  test('does not expose coordinates unless the API marks them valid', () {
    final post = EvacuationPost.fromJson({
      'id': 13,
      'name': 'Lokasi tanpa koordinat',
      'address': 'Padang',
      'latitude': 0,
      'longitude': 0,
      'has_valid_coordinates': false,
      'status': 'active',
    });

    expect(post.hasValidCoordinates, isFalse);
    expect(post.latitude, isNull);
    expect(post.longitude, isNull);
    expect(post.statusLabel, 'Aktif; ketersediaan belum diketahui');
  });

  test('keeps planning coordinates visible but labels them unverified', () {
    final post = EvacuationPost.fromJson({
      'id': 'TEA-A1',
      'location_code': 'A1',
      'name': 'TEA A1 - Lahan Kosong di Kelurahan Batipuh Panjang',
      'address': 'Batipuh Panjang',
      'latitude': -0.8215,
      'longitude': 100.3365,
      'has_candidate_coordinates': true,
      'has_verified_coordinates': false,
      'is_planning_candidate': true,
      'recommended_capacity': '22919',
      'image_path': 'images/evacuation/tea-a1.jpg',
      'status': '',
    });

    expect(post.hasValidCoordinates, isTrue);
    expect(post.isPlanningCandidate, isTrue);
    expect(post.locationCode, 'A1');
    expect(post.capacity, isNull);
    expect(post.recommendedCapacity, 22919);
    expect(post.imagePath, 'images/evacuation/tea-a1.jpg');
    expect(post.statusLabel, 'Kandidat TEA · belum diverifikasi');
  });
}
