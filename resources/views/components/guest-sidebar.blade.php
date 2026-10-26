@props(['active' => ''])

<aside id="sidebar" class="hidden w-[238px] shrink-0 flex-col bg-white px-5 py-6 lg:flex">
    <div class="mb-7 flex items-center justify-between px-2">
        <a href="{{ route('landing') }}"><img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="SIGAP BENCANA" class="h-12 w-auto"></a>
        <button type="button" class="lg:hidden" onclick="toggleMenu()" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <nav class="flex flex-col gap-1 text-[15px]" aria-label="Navigasi informasi publik">
        <a @class(['nav-item', 'active' => $active === 'home']) href="{{ route('landing') }}"><i class="fa-solid fa-house w-5 text-center"></i>Beranda</a>
        <a @class(['nav-item', 'active' => $active === 'warnings']) href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
        <a @class(['nav-item', 'active' => $active === 'risk-map']) href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
        <a @class(['nav-item', 'active' => $active === 'evacuation']) href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
        <a @class(['nav-item', 'active' => $active === 'news']) href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
        <a @class(['nav-item', 'active' => $active === 'contacts']) href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
        <a @class(['nav-item', 'active' => $active === 'about']) href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
        <a @class(['nav-item', 'active' => $active === 'help']) href="{{ route('help.index') }}"><i class="fa-regular fa-circle-question w-5 text-center"></i>Bantuan</a>
        <a class="nav-item mt-2" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket w-5 text-center"></i>Masuk</a>
        <a class="nav-item" href="{{ route('register') }}"><i class="fa-solid fa-user-plus w-5 text-center"></i>Daftar</a>
    </nav>
</aside>
