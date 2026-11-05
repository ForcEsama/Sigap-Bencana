@props(['active' => 'landing'])

<header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-5 md:px-12 lg:px-16">
    <a href="{{ route('landing') }}" class="flex shrink-0 items-center rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2" aria-label="SIGAP BENCANA — Beranda">
        <img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="Logo SIGAP BENCANA" class="h-auto w-32 object-contain sm:w-36">
    </a>

    <nav class="hidden items-center gap-8 md:flex" aria-label="Navigasi publik">
        <a href="{{ route('landing') }}" @class(['font-medium transition hover:text-red-700 focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-4', 'text-red-700' => $active === 'landing', 'text-gray-900' => $active !== 'landing']) @if ($active === 'landing') aria-current="page" @endif>Beranda</a>
        <a href="{{ route('about.index') }}" @class(['font-medium transition hover:text-red-700 focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-4', 'text-red-700' => $active === 'about', 'text-gray-900' => $active !== 'about']) @if ($active === 'about') aria-current="page" @endif>Tentang</a>
        <a href="{{ route('help.index') }}" @class(['font-medium transition hover:text-red-700 focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-4', 'text-red-700' => $active === 'help', 'text-gray-900' => $active !== 'help']) @if ($active === 'help') aria-current="page" @endif>Bantuan</a>
    </nav>

    @if ($active !== 'landing')
        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('login') }}" class="rounded px-3 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700">Masuk</a>
            <a href="{{ route('register') }}" class="rounded-md bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2">Daftar Akun</a>
        </div>
    @endif

    <button id="publicMenuButton" type="button" aria-label="Buka menu" aria-controls="publicMobileMenu" aria-expanded="false" class="rounded-lg border border-gray-300 bg-white/90 px-3 py-2 text-gray-800 shadow-sm transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 md:hidden">
        <span aria-hidden="true">☰</span>
    </button>
</header>

<nav id="publicMobileMenu" class="relative z-30 mx-6 hidden rounded-xl border border-white/70 bg-white/95 p-3 shadow-lg md:hidden" aria-label="Navigasi publik seluler">
    <a href="{{ route('landing') }}" @class(['block rounded-lg px-3 py-2 font-medium hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700', 'text-red-700' => $active === 'landing']) @if ($active === 'landing') aria-current="page" @endif>Beranda</a>
    <a href="{{ route('about.index') }}" @class(['block rounded-lg px-3 py-2 font-medium hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700', 'text-red-700' => $active === 'about']) @if ($active === 'about') aria-current="page" @endif>Tentang</a>
    <a href="{{ route('help.index') }}" @class(['block rounded-lg px-3 py-2 font-medium hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700', 'text-red-700' => $active === 'help']) @if ($active === 'help') aria-current="page" @endif>Bantuan</a>
    @if ($active !== 'landing')
        <div class="mt-2 grid grid-cols-2 gap-2 border-t border-rose-100 pt-3">
            <a href="{{ route('login') }}" class="rounded-md border border-rose-700 px-3 py-2 text-center text-sm font-semibold text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700">Masuk</a>
            <a href="{{ route('register') }}" class="rounded-md bg-rose-700 px-3 py-2 text-center text-sm font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2">Daftar Akun</a>
        </div>
    @endif
</nav>

<script>
    (() => {
        const button = document.getElementById('publicMenuButton');
        const menu = document.getElementById('publicMobileMenu');
        if (!button || !menu) return;

        button.addEventListener('click', () => {
            const isOpen = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!isOpen));
            button.setAttribute('aria-label', isOpen ? 'Buka menu' : 'Tutup menu');
            menu.classList.toggle('hidden', isOpen);
        });
    })();
</script>
