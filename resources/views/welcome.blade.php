
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIGAP BENCANA | Bersama Lebih Siap</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-white">

    <main class="relative min-h-screen overflow-hidden bg-slate-100">

        <!-- Background Hero -->
        <div
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('/images/home-hero.png');">
        </div>

        <!-- Gradasi supaya teks tetap terbaca -->
        <div class="absolute inset-0 bg-gradient-to-r
                    from-rose-50 via-rose-50/75 to-transparent">
        </div>

        <!-- Navbar -->
        <header class="relative z-10 mx-auto flex max-w-7xl
                       items-center justify-between px-6 py-6
                       md:px-12 lg:px-16">

            <!-- Logo SIGAP BENCANA -->
            <a href="/" class="flex shrink-0 items-center">
                <img
                    src="{{ asset('images/SigapBencanaLogo.png') }}"
                    alt="Logo SIGAP BENCANA"
                    class="h-auto w-32 object-contain sm:w-36"
                >
            </a>



            <!-- Navigasi Desktop -->
            <nav class="hidden items-center gap-8 md:flex">
                <a href="/" class="font-medium text-gray-900 transition hover:text-red-600">
                    Beranda
                </a>
                <a href="#tentang" class="font-medium text-gray-900 transition hover:text-red-600">
                    Tentang
                </a>
                <a href="#bantuan" class="font-medium text-gray-900 transition hover:text-red-600">
                    Bantuan
                </a>
            </nav>

            <!-- Tombol navigasi mobile -->
            <button
                id="menuButton"
                type="button"
                aria-label="Buka menu"
                aria-expanded="false"
                class="rounded-lg border border-gray-300 bg-white/80
                       px-3 py-2 text-gray-800 md:hidden">
                ☰
            </button>
        </header>

        <!-- Menu Mobile -->
        <nav id="mobileMenu"
             class="relative z-20 mx-6 hidden rounded-xl border
                    border-white/70 bg-white/95 p-4 shadow-lg md:hidden">
            <a href="/" class="block rounded-lg px-3 py-2 hover:bg-red-50">
                Beranda
            </a>
            <a href="#tentang" class="block rounded-lg px-3 py-2 hover:bg-red-50">
                Tentang
            </a>
            <a href="#bantuan" class="block rounded-lg px-3 py-2 hover:bg-red-50">
                Bantuan
            </a>
        </nav>

        <!-- Hero Content -->
        <section class="relative z-10 mx-auto flex min-h-[calc(100vh-88px)]
                        max-w-7xl items-center px-6 pb-16 pt-8
                        md:px-12 lg:px-16">

            <div class="max-w-xl">
                <p class="mb-4 font-semibold uppercase tracking-[0.2em]
                          text-red-700">
                    SIGAP BENCANA
                </p>

                <h1 class="text-4xl font-extrabold leading-tight
                           tracking-tight text-gray-950
                           sm:text-5xl lg:text-6xl">
                    Bersama<br>
                    Lebih Siap<br>
                    hadapi bencana
                </h1>

                <p id="tentang"
                   class="mt-5 max-w-md text-sm leading-7 text-gray-800
                          sm:text-base">
                    Platform informasi, pemantauan, dan pelaporan
                    bencana demi masyarakat yang lebih tangguh.
                </p>

                <div class="mt-7 flex max-w-sm flex-col gap-3 sm:flex-row
                            sm:flex-wrap">

                    <a href="/login"
                       class="flex min-h-12 items-center justify-center
                              rounded-md bg-rose-600 px-8 py-3
                              font-semibold text-white shadow-md
                              transition hover:bg-rose-700">
                        Masuk
                    </a>

                    <a href="/register"
                       class="flex min-h-12 items-center justify-center
                              rounded-md border border-rose-600 bg-white/80
                              px-8 py-3 font-semibold text-rose-600
                              transition hover:bg-rose-50">
                        Daftar Akun
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer kecil -->
        <footer id="bantuan"
                class="absolute bottom-0 left-0 z-10 w-full
                       px-6 py-3 text-center text-xs text-gray-700
                       sm:text-left sm:px-12 lg:px-16">
            SIGAP BENCANA &copy; 2026 — Siaga, Tanggap, Selamat.
        </footer>

    </main>

    <script>
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        menuButton.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.toggle('hidden');
            menuButton.setAttribute('aria-expanded', String(!isHidden));
        });
    </script>

</body>
</html>
