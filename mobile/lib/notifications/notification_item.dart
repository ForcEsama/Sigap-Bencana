import '../sos/sos_request.dart';

class NotificationItem {
  const NotificationItem({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.createdAt,
    required this.isRead,
    this.relatedType,
    this.relatedId,
    this.relatedSosRequest,
  });

  final int id;
  final String title;
  final String message;
  final String type;
  final DateTime? createdAt;
  final bool isRead;
  final String? relatedType;
  final int? relatedId;
  final SosRequest? relatedSosRequest;

  factory NotificationItem.fromJson(Map<String, dynamic> json) {
    return NotificationItem(
      id: json['id'] as int,
      title: json['title']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
      isRead: json['read_at'] != null,
      relatedType: json['related_type']?.toString(),
      relatedId: (json['related_id'] as num?)?.toInt(),
      relatedSosRequest: json['related_sos_request'] is Map<String, dynamic>
          ? SosRequest.fromJson(
              json['related_sos_request'] as Map<String, dynamic>,
            )
          : null,
    );
  }
}
