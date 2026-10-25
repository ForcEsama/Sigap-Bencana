const disasterTypeLabels = <String, String>{
  'gempa': 'Gempa bumi',
  'banjir': 'Banjir',
  'longsor': 'Tanah longsor',
  'kebakaran': 'Kebakaran',
  'tsunami': 'Tsunami',
  'lainnya': 'Lainnya',
};

const verificationStatusLabels = <String, String>{
  'pending': 'Menunggu verifikasi',
  'verified': 'Terverifikasi',
  'rejected': 'Ditolak',
};

const handlingStatusLabels = <String, String>{
  'unhandled': 'Belum ditangani',
  'in_progress': 'Sedang ditangani',
  'resolved': 'Selesai ditangani',
};

String reportTypeLabel(dynamic value) {
  final type = value?.toString() ?? '';
  return disasterTypeLabels[type] ?? type;
}

String verificationStatusLabel(dynamic value) {
  final status = value?.toString() ?? '';
  return verificationStatusLabels[status] ??
      (status.isEmpty ? 'Status verifikasi tidak tersedia' : status);
}

String handlingStatusLabel(dynamic value) {
  final status = value?.toString() ?? '';
  return handlingStatusLabels[status] ??
      (status.isEmpty ? 'Status penanganan tidak tersedia' : status);
}

String reportDateLabel(
  dynamic value, {
  String fallback = 'Tanggal tidak tersedia',
}) {
  final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
  if (date == null) return fallback;

  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  final hour = date.hour.toString().padLeft(2, '0');
  final minute = date.minute.toString().padLeft(2, '0');

  return '$day/$month/${date.year}, $hour:$minute';
}

String reportStatusLabel(Map<String, dynamic> report) {
  return '${verificationStatusLabel(report['verification_status'])} · '
      '${handlingStatusLabel(report['handling_status'])}';
}
