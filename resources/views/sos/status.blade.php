<!DOCTYPE html>
<html lang="id"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Status Bantuan SOS - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']},colors:{sigap:'#D2002F',blush:'#FFECED'}}}};</script>
    <style>body{font-family:Poppins,sans-serif}.nav-item{display:flex;align-items:center;gap:14px;border-radius:8px;padding:10px 12px;color:#191919}.nav-item.active{color:#c9002c;background:#ffb8bd}@media(max-width:1023px){#sidebar{display:none}#sidebar.open{display:flex;position:fixed;inset:0 auto 0 0;z-index:50;width:min(270px,86vw);overflow-y:auto;box-shadow:10px 0 30px #0002}}</style>
</head><body class="min-h-screen bg-blush text-[#181818]"><div class="min-h-screen lg:flex">
    @include('sos._sidebar')
    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4"><div class="flex gap-3"><button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button><div><h1 class="text-2xl font-semibold sm:text-3xl">Status Bantuan SOS</h1><p class="mt-1 text-sm text-gray-500">Informasi status yang tercatat pada aplikasi</p></div></div><div class="flex shrink-0 items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden sm:inline">{{ auth()->user()->name }}</span></div></header>
        @php
            $historyByStatus = $sosRequest->histories->keyBy('new_status');
            $steps = [
                ['key' => 'sent', 'title' => 'Permintaan Disimpan', 'description' => 'Tercatat di aplikasi'],
                ['key' => 'acknowledged', 'title' => 'Konfirmasi Petugas', 'description' => 'Belum ada konfirmasi'],
                ['key' => 'en_route', 'title' => 'Petugas Menuju Lokasi', 'description' => 'Belum ada informasi'],
                ['key' => 'completed', 'title' => 'Bantuan Tiba', 'description' => 'Belum dikonfirmasi'],
            ];
            $statusLabels = ['sent' => 'Tersimpan di aplikasi', 'acknowledged' => 'Dikonfirmasi petugas', 'en_route' => 'Petugas menuju lokasi', 'completed' => 'Bantuan tiba'];
        @endphp
        <section class="mb-4 rounded-xl bg-white p-5 shadow-sm sm:p-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center"><span class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-100 text-xl font-bold text-sigap">SOS</span><div class="min-w-0 flex-1"><h2 class="font-semibold text-sigap">{{ $statusLabels[$sosRequest->status] ?? 'Status: '.$sosRequest->status }}</h2><p class="text-sm">Nomor: <strong>{{ $requestNumber }}</strong></p><p class="text-sm">{{ $sosRequest->created_at ? ($sosRequest->created_at->copy()->timezone(config('app.display_timezone'))->locale('id')->translatedFormat('d F Y, H:i').' WIB') : 'Waktu tidak tersedia' }}</p></div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900">{{ $disasterLabel }}</span></div></section>
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7"><h2 class="text-lg font-semibold">Perkembangan Bantuan</h2><p class="mt-1 text-sm text-gray-600">Hanya perubahan status yang tercatat pada permintaan ini yang ditampilkan.</p>
            <ol class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($steps as $step)
                    @php
                        $event = $historyByStatus->get($step['key']);
                        $isRecorded = $event !== null || ($step['key'] === 'sent' && $sosRequest->created_at !== null);
                        $eventTime = $step['key'] === 'sent' ? $sosRequest->created_at : $event?->created_at;
                    @endphp
                    <li class="min-w-0 border-l-4 {{ $isRecorded ? 'border-emerald-500' : 'border-gray-200' }} pl-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $isRecorded ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-500' }}"><i class="fa-solid {{ $isRecorded ? 'fa-check' : 'fa-circle' }}" aria-hidden="true"></i></span>
                        <h3 class="mt-2 font-semibold">{{ $step['title'] }}</h3>
                        <p class="text-sm {{ $isRecorded ? 'text-gray-700' : 'text-gray-500' }}">{{ $isRecorded ? ($eventTime ? $eventTime->copy()->timezone(config('app.display_timezone'))->locale('id')->translatedFormat('d F Y, H:i').' WIB' : 'Waktu tidak tersedia') : $step['description'] }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-6 grid gap-3 border-t border-gray-100 pt-5 text-sm sm:grid-cols-2"><p><span class="text-gray-500">Lokasi tersimpan:</span> {{ $sosRequest->address ?: 'Alamat tidak dicantumkan' }}<span class="block text-xs text-gray-500">{{ $sosRequest->latitude }}, {{ $sosRequest->longitude }}</span></p><p><span class="text-gray-500">Petugas:</span> {{ $sosRequest->assignedOfficer?->name ?? 'Belum ada informasi penugasan petugas.' }}</p></div>
        </section>
        <p class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">Status ini hanya mencerminkan data yang tersimpan di aplikasi. Saat ini aplikasi tidak meneruskan permintaan kepada petugas atau layanan darurat dan tidak menerima pembaruan penanganan secara langsung. Untuk bantuan segera, hubungi layanan darurat setempat melalui <a class="font-semibold underline" href="{{ route('emergency-contacts.index') }}">Kontak Darurat</a>.</p>
        <div class="mt-4 flex flex-wrap gap-3"><a href="{{ route('home') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium hover:bg-gray-50">Kembali ke Beranda</a><a href="{{ route('sos.create') }}" class="rounded-lg bg-sigap px-5 py-3 text-sm font-semibold text-white hover:bg-red-800">Buat Permintaan Baru</a></div>
    </main>
</div><script>function toggleMenu(){document.getElementById('sidebar').classList.toggle('open')}</script></body></html>
