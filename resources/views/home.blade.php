
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - SIGAP BENCANA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] },
                    colors: { sigap: '#D50032', softpink: '#FFECEE' }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-[#FFECEE] font-poppins text-gray-900">

    <!-- Navbar -->
    <nav class="relative z-10 flex min-h-[68px] items-center justify-between bg-white px-5 py-3 shadow-sm md:px-9">
    <a href="{{ url('/') }}" class="flex items-center">
        <img
            src="{{ asset('images/SigapBencanaLogo.png') }}"
            alt="SIGAP BENCANA"
            class="h-10 w-auto object-contain"
        >
    </a>

        <div class="hidden items-center gap-7 text-sm md:flex">
            <a href="{{ url('/') }}" class="font-medium text-sigap">Home</a>
            <a href="#peta" class="transition hover:text-sigap">Map</a>
            <a href="#berita" class="transition hover:text-sigap">News</a>

            <div class="group relative">
                <button class="flex items-center gap-2 hover:text-sigap">
                    Menu <i class="fa-solid fa-chevron-down text-xs"></i>
                </button>

                <div class="invisible absolute left-0 top-full mt-3 w-48 rounded-lg bg-white py-2 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100">
                    <a href="#lapor" class="block px-4 py-2 hover:bg-gray-50">Lapor Kejadian</a>
                    <a href="#evakuasi" class="block px-4 py-2 hover:bg-gray-50">Tempat Evakuasi</a>
                    <a href="#kontak" class="block px-4 py-2 hover:bg-gray-50">Kontak Darurat</a>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 text-sm">
            @auth
                <button type="button" aria-label="Notifikasi" class="relative p-1 text-xl">
                    <i class="fa-regular fa-bell"></i>
                    <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-sigap text-[9px] text-white">3</span>
                </button>

                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-500 text-xs text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs transition hover:border-sigap hover:text-sigap">
                        Keluar
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sigap hover:underline">Masuk</a>
                <a href="{{ route('register') }}" class="rounded bg-sigap px-3 py-2 text-xs text-white hover:bg-red-700">Daftar</a>
            @endauth
        </div>
    </nav>

    <!-- Hero -->
    <section class="relative flex h-[1000vh] min-h-[350px] max-h-[700px] items-center overflow-hidden bg-gray-800">
        <div class="absolute inset-0">
            <img src="{{ asset('images/home-hero1.png') }}"
                 alt="Kondisi wilayah terdampak bencana"
                 class="h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-[#BC0013]/95 via-[#BC0013]/70 to-transparent"></div>
        </div>

        <div class="relative z-[1] max-w-5xl px-6 py-14 text-white md:px-12">
            <h1 class="text-3xl font-medium leading-tight sm:text-6xl md:text-[80px]">
                Bersama Lebih Siap,<br>
                Lebih Aman
            </h1>

            <p class="mt-2 max-w-3x1 text-sm leading-relaxed sm:text-3xl">
                Platform informasi, pemantauan, dan pelaporan bencana
                demi masyarakat yang lebih tangguh
            </p>

            <a href="#peta"
               class="mt-6 inline-flex min-h-16 items-center rounded-md bg-white px-7 py-2 text-2xl font-medium text-sigap transition hover:bg-gray-100">
                Lihat Peta Risiko
            </a>
        </div>
    </section>

    <!-- Layanan utama -->
    <section class="relative z-[2] mx-4 -mt-12 min-h-[200px] rounded-t-lg rounded-b-lg bg-white px-4 py-8 shadow-sm sm:mx-12 sm:px-8 md:-mt-20 md:px-10 md:py-10">
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">

            <a href="#peringatan" class="group flex flex-col items-center text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-red-200 text-3xl text-red-700 transition group-hover:scale-105">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <span class="mt-3 text-sm font-medium sm:text-base">Peringatan Bencana</span>
            </a>

            <a href="#lapor" class="group flex flex-col items-center text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-amber-100 text-3xl text-amber-600 transition group-hover:scale-105">
                    <i class="fa-solid fa-house-medical"></i>
                </div>
                <span class="mt-3 text-sm font-medium sm:text-base">Lapor Kejadian</span>
            </a>

            <a href="#evakuasi" class="group flex flex-col items-center text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-emerald-100 text-3xl text-emerald-500 transition group-hover:scale-105">
                    <i class="fa-solid fa-tent"></i>
                </div>
                <span class="mt-3 text-sm font-medium sm:text-base">Tempat Evakuasi</span>
            </a>

            <a href="#kontak" class="group flex flex-col items-center text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-fuchsia-200 text-3xl text-fuchsia-600 transition group-hover:scale-105">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <span class="mt-3 text-sm font-medium sm:text-base">Kontak Darurat</span>
            </a>

        </div>
    </section>
</body>
</html>
