<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard {{ $role->label() }} - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .nav-item { display: flex; align-items: center; gap: 14px; border-radius: 8px; padding: 10px 12px; color: #191919; }
        .nav-item.active { color: #c9002c; background: #ffb8bd; }
        #sidebar { display: none; }
        #sidebar.open { display: flex; position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(270px, 86vw); overflow-y: auto; box-shadow: 10px 0 30px #0002; }
        @media (min-width: 1024px) { #sidebar { display: flex; position: static; width: 238px; box-shadow: none; } }
    </style>
</head>
<body class="min-h-screen bg-[#fff3f4] font-['Poppins'] text-gray-900">
<div class="min-h-screen lg:flex">
    @include('components.information-sidebar', ['active' => 'dashboard'])

    <div class="min-w-0 flex-1">
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-8">
        <div class="lg:hidden">
            <button type="button" onclick="toggleMenu()" aria-label="Buka menu" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm">
                <i class="fa-solid fa-bars" aria-hidden="true"></i> Menu
            </button>
        </div>
        @if (session('success'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-rose-200 bg-white px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <section class="rounded-2xl bg-gradient-to-r from-[#c9002c] to-[#f04b5e] p-6 text-white shadow-sm sm:p-8">
            <p class="text-sm font-medium text-white/80">SIGAP BENCANA · ruang kerja {{ $role->label() }}</p>
            <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">Halo, {{ auth()->user()->name }}</h1>
            <p class="mt-3 max-w-3xl leading-relaxed text-white/90">{{ $summary }}</p>
        </section>

        @if ($role->value === 'admin')
            <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan operasional">
                @foreach ([['Laporan menunggu verifikasi', $pendingReportCount, 'fa-file-circle-exclamation'], ['SOS dalam penanganan', $openSosCount, 'fa-tower-broadcast'], ['Petugas aktif', $fieldOfficerCount, 'fa-user-shield']] as [$label, $count, $icon])
                    <article class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-gray-600">{{ $label }}</span>
                            <i class="fa-solid {{ $icon }} text-lg text-[#d50032]" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-3xl font-semibold">{{ $count }}</p>
                    </article>
                @endforeach
            </section>

            <section class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Laporan kejadian</h2>
                    <p class="mt-1 text-sm text-gray-600">Laporan pending perlu diverifikasi. Laporan terverifikasi dapat ditugaskan dan diperbarui progresnya.</p>
                </div>
                @forelse ($reports as $report)
                    <article class="border-t border-gray-100 py-4 first:border-0 first:pt-0">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="font-semibold">{{ $report->title }}</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ ucfirst($report->disaster_type) }} · {{ $report->user->name }} · {{ $report->address ?: 'Alamat tidak dicantumkan' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Verifikasi: {{ $report->verification_status }} · Penanganan: {{ $report->handling_status }} · Ditugaskan: {{ $report->assignedOfficer?->name ?? 'Belum ditugaskan' }}</p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs text-amber-900">{{ $report->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($report->verification_status === 'pending')
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <form method="POST" action="{{ route('operational.reports.verify', $report->id) }}" class="flex flex-wrap gap-2">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="verified">
                                    <input name="notes" maxlength="2000" placeholder="Catatan verifikasi (opsional)" class="min-w-48 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-medium text-white">Verifikasi</button>
                                </form>
                                <form method="POST" action="{{ route('operational.reports.verify', $report->id) }}" class="flex flex-wrap gap-2">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="rejected">
                                    <input required name="notes" maxlength="2000" placeholder="Alasan penolakan" class="min-w-48 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700">Tolak</button>
                                </form>
                            </div>
                        @elseif ($report->verification_status === 'verified')
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <form method="POST" action="{{ route('operational.reports.assign', $report->id) }}" class="flex gap-2">
                                    @csrf @method('PATCH')
                                    <select required name="assigned_to" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <option value="">Pilih petugas lapangan</option>
                                        @foreach ($fieldOfficers as $officer)
                                            <option value="{{ $officer->id }}" @selected($report->assignment?->assigned_to === $officer->id)>{{ $officer->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-[#d50032] px-3 py-2 text-sm font-medium text-white">Tugaskan</button>
                                </form>
                                @if ($report->assignment)
                                    <form method="POST" action="{{ route('operational.reports.handling', $report->id) }}" class="flex gap-2">
                                        @csrf @method('PATCH')
                                        <select name="status" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                            @if ($report->handling_status === 'unhandled') <option value="in_progress">Mulai ditangani</option> @endif
                                            @if ($report->handling_status === 'in_progress') <option value="resolved">Selesai</option> @endif
                                        </select>
                                        <input name="notes" maxlength="2000" placeholder="Catatan / hasil" class="min-w-28 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm">Simpan</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">Belum ada laporan yang memerlukan tindakan.</p>
                @endforelse
            </section>

            <section class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Pengguna dan akses</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola role dari tiga jenis akun. Kata sandi tidak ditampilkan atau dapat diedit dari daftar ini.</p>
                </div>
                <div class="space-y-3">
                    @foreach ($managedUsers as $managedUser)
                        <form method="POST" action="{{ route('operational.users.access', $managedUser->id) }}" class="grid gap-2 rounded-lg border border-gray-100 p-3 sm:grid-cols-[1fr_12rem_9rem_auto] sm:items-center">
                            @csrf @method('PATCH')
                            <div>
                                <p class="font-medium">{{ $managedUser->name }}</p>
                                <p class="text-xs text-gray-600">{{ $managedUser->email }}</p>
                            </div>
                            <select name="role" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" @disabled($managedUser->id === auth()->id())>
                                @foreach (\App\UserRole::cases() as $userRole)
                                    <option value="{{ $userRole->value }}" @selected($managedUser->role === $userRole->value)>{{ $userRole->label() }}</option>
                                @endforeach
                            </select>
                            <select name="is_active" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" @disabled($managedUser->id === auth()->id())>
                                <option value="1" @selected($managedUser->is_active)>Aktif</option>
                                <option value="0" @selected(! $managedUser->is_active)>Nonaktif</option>
                            </select>
                            <button type="submit" @disabled($managedUser->id === auth()->id()) class="rounded-lg bg-[#d50032] px-3 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-gray-300">Simpan akses</button>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Permintaan SOS</h2>
                    <p class="mt-1 text-sm text-gray-600">SOS tersimpan di aplikasi. Penugasan di sini tidak mengirim permintaan langsung ke instansi eksternal.</p>
                </div>
                @forelse ($sosRequests as $sos)
                    <article class="border-t border-gray-100 py-4 first:border-0 first:pt-0">
                        <div class="flex flex-wrap justify-between gap-2">
                            <div>
                                <h3 class="font-semibold">{{ $sos->reference() }} · {{ ucfirst($sos->disaster_type) }}</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $sos->user->name }} · {{ $sos->address ?: 'Alamat tidak dicantumkan' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Status: {{ $sos->status }} · Petugas: {{ $sos->assignedOfficer?->name ?? 'Belum ditugaskan' }}</p>
                            </div>
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs text-rose-800">Urgensi {{ $sos->urgency_level }}</span>
                        </div>
                        @if (in_array($sos->status, ['sent', 'assigned'], true))
                            <div class="mt-3 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('operational.sos.update', $sos->id) }}" class="flex flex-1 gap-2">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="assigned">
                                    <select required name="assigned_to" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <option value="">Pilih petugas lapangan</option>
                                        @foreach ($fieldOfficers as $officer)
                                            <option value="{{ $officer->id }}" @selected($sos->assigned_to === $officer->id)>{{ $officer->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-[#d50032] px-3 py-2 text-sm font-medium text-white">Tugaskan</button>
                                </form>
                                @if ($sos->status === 'sent')
                                    <form method="POST" action="{{ route('operational.sos.update', $sos->id) }}" class="flex flex-1 gap-2">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <input required name="notes" maxlength="2000" placeholder="Alasan tidak dapat ditindaklanjuti" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                        <button class="rounded-lg border border-rose-300 px-3 py-2 text-sm text-rose-700">Tolak</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">Belum ada SOS aktif.</p>
                @endforelse
            </section>
        @else
            <section class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">Laporan ditugaskan</h2>
                    <p class="mt-1 text-3xl font-semibold">{{ $reports->count() }}</p>
                </article>
                <article class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">Permintaan SOS ditugaskan</h2>
                    <p class="mt-1 text-3xl font-semibold">{{ $sosRequests->count() }}</p>
                </article>
            </section>
            <section class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="mb-3 text-xl font-semibold">Laporan tugas saya</h2>
                @forelse ($reports as $report)
                    <article class="border-t border-gray-100 py-4 first:border-0">
                        <h3 class="font-semibold">{{ $report->title }} · {{ ucfirst($report->disaster_type) }}</h3>
                        <p class="mt-1 text-sm text-gray-600">Pelapor: {{ $report->user->name }} · {{ $report->address ?: 'Alamat tidak dicantumkan' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Status penanganan: {{ $report->handling_status }}</p>
                        @if ($report->handling_status !== 'resolved')
                            <form method="POST" action="{{ route('operational.reports.handling', $report->id) }}" class="mt-3 flex flex-wrap gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    @if ($report->handling_status === 'unhandled') <option value="in_progress">Mulai ditangani</option> @endif
                                    @if ($report->handling_status === 'in_progress') <option value="resolved">Selesai</option> @endif
                                </select>
                                <input name="notes" maxlength="2000" @required($report->handling_status === 'in_progress') placeholder="Catatan / hasil penanganan" class="min-w-48 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <button class="rounded-lg bg-[#d50032] px-4 py-2 text-sm font-medium text-white">Simpan progres</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">Belum ada laporan yang ditugaskan kepada Anda.</p>
                @endforelse
            </section>
            <section class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="mb-3 text-xl font-semibold">Tugas SOS saya</h2>
                @forelse ($sosRequests as $sos)
                    <article class="border-t border-gray-100 py-4 first:border-0">
                        <h3 class="font-semibold">{{ $sos->reference() }} · {{ ucfirst($sos->disaster_type) }}</h3>
                        <p class="mt-1 text-sm text-gray-600">Pemohon: {{ $sos->user->name }} · {{ $sos->address ?: 'Alamat tidak dicantumkan' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Status: {{ $sos->status }} · Koordinat: {{ $sos->latitude }}, {{ $sos->longitude }}</p>
                        <form method="POST" action="{{ route('operational.sos.progress', $sos->id) }}" class="mt-3 flex flex-wrap gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @if ($sos->status === 'assigned') <option value="en_route">Menuju lokasi</option> @endif
                                @if ($sos->status === 'en_route') <option value="arrived">Tiba di lokasi</option> @endif
                                @if ($sos->status === 'arrived') <option value="completed">Selesai</option> @endif
                            </select>
                            <input name="notes" maxlength="2000" @required($sos->status === 'arrived') placeholder="Catatan / hasil penanganan" class="min-w-48 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <button class="rounded-lg bg-[#d50032] px-4 py-2 text-sm font-medium text-white">Perbarui status</button>
                        </form>
                    </article>
                @empty
                    <p class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">Belum ada permintaan SOS yang ditugaskan kepada Anda.</p>
                @endforelse
            </section>
        @endif

        <aside class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950">
            Penugasan dan status yang ditampilkan adalah catatan di SIGAP BENCANA. Sistem belum meneruskan permintaan ke instansi eksternal; ikuti SOP dan hubungi layanan darurat setempat bila bantuan segera diperlukan.
        </aside>
    
    </main>
    </div>
</div>
<script>
    function toggleMenu() {
        document.getElementById('sidebar')?.classList.toggle('open');
    }
</script>
</body>
</html>
