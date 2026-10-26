@props(['active' => ''])

<aside id="sidebar" class="hidden w-[238px] shrink-0 flex-col bg-white px-5 py-6 lg:flex">
    <div class="mb-7 flex items-center justify-between px-2">
        <a href="{{ route('home') }}"><img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="SIGAP BENCANA" class="h-12 w-auto"></a>
        <button type="button" class="lg:hidden" onclick="toggleMenu()" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <nav class="flex flex-col gap-1 text-[15px]" aria-label="Navigasi utama">
        <a @class(['nav-item', 'active' => $active === 'home']) href="{{ route('home') }}"><i class="fa-solid fa-house w-5 text-center"></i>Home</a>
        @include('components.operational-dashboard-link')
        <a @class(['nav-item', 'active' => $active === 'warnings']) href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
        <a @class(['nav-item', 'active' => $active === 'risk-map']) href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
        <a @class(['nav-item', 'active' => $active === 'reports-create']) href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
        <a @class(['nav-item', 'active' => $active === 'reports']) href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
        <a @class(['nav-item', 'active' => $active === 'evacuation']) href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
        <a @class(['nav-item', 'active' => $active === 'sos']) href="{{ route('sos.create') }}"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</a>
        <a @class(['nav-item', 'active' => $active === 'news']) href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
        <a @class(['nav-item', 'active' => $active === 'contacts']) href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
        <a @class(['nav-item', 'active' => $active === 'about']) href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
        <a @class(['nav-item', 'active' => $active === 'help']) href="{{ route('help.index') }}"><i class="fa-regular fa-circle-question w-5 text-center"></i>Bantuan</a>
        <a @class(['nav-item', 'active' => $active === 'profile']) href="{{ route('profile.edit') }}"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
        <a @class(['nav-item', 'active' => $active === 'notifications']) href="{{ route('notifications.index') }}"><i class="fa-regular fa-bell w-5 text-center"></i>Notifikasi</a>
        <form action="{{ route('logout') }}" method="POST" class="mt-1">
            @csrf
            <button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button>
        </form>
    </nav>
</aside>
