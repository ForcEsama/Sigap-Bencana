<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] },
                    colors: { sigap: '#D2002F', blush: '#FFECED' },
                },
            },
        };
    </script>
    <style>
        body { font-family: Poppins, sans-serif; }
        .nav-item { display: flex; align-items: center; gap: 14px; border-radius: 8px; padding: 10px 12px; color: #191919; }
        .nav-item.active { color: #c9002c; background: #ffb8bd; }
        .field { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; padding: 11px 13px; outline: none; }
        .field:focus { border-color: #d2002f; box-shadow: 0 0 0 2px rgb(210 0 47 / 12%); }
        @media (max-width: 1023px) {
            #sidebar { display: none; }
            #sidebar.open { display: flex; position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(270px, 86vw); overflow-y: auto; box-shadow: 10px 0 30px #0002; }
        }
    </style>
</head>
<body class="min-h-screen bg-blush text-[#181818]">
<div class="min-h-screen lg:flex">
    <aside id="sidebar" class="hidden w-[238px] shrink-0 flex-col bg-white px-5 py-6 lg:flex">
        <div class="mb-7 flex items-center justify-between px-2">
            <a href="{{ route('home') }}"><img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="SIGAP BENCANA" class="h-12 w-auto"></a>
            <button type="button" class="lg:hidden" onclick="toggleMenu()" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex flex-col gap-1 text-[15px]" aria-label="Navigasi utama">
            <a class="nav-item" href="{{ route('home') }}"><i class="fa-solid fa-house w-5 text-center"></i>Home</a>
            <span class="nav-item"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</span>
            @if (auth()->user()->role === 'masyarakat')
                <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            @else
                <span class="nav-item"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Resiko</span>
            @endif
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <span class="nav-item"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</span>
            <span class="nav-item"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</span>
            <span class="nav-item"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</span>
            <span class="nav-item"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</span>
            <span class="nav-item"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</span>
            <a class="nav-item active" href="{{ route('profile.edit') }}" aria-current="page"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">
                @csrf
                <button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button>
            </form>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Profil Saya</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Kelola informasi akun SIGAP BENCANA Anda</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <span class="text-xl" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i></span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ $user->name }}</span>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif

        <div class="grid items-start gap-5 xl:grid-cols-2">
            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="mb-2 text-xl font-semibold">Informasi Profil</h2>
                <p class="mb-5 text-sm text-gray-500">Perbarui nama dan alamat email akun Anda.</p>

                @if ($errors->profile->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->profile->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="form_type" value="profile">

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required class="field">
                        @error('name', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required class="field">
                        @error('email', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Email baru perlu diverifikasi kembali sebelum dianggap terverifikasi.</p>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-sigap px-5 py-3 font-medium text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">Simpan Profil</button>
                </form>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="mb-2 text-xl font-semibold">Ganti Kata Sandi</h2>
                <p class="mb-5 text-sm text-gray-500">Masukkan kata sandi saat ini dan pilih kata sandi baru.</p>

                @if ($errors->password->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->password->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="form_type" value="password">

                    <div>
                        <label for="current_password" class="mb-1 block text-sm font-medium">Kata sandi saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="field">
                        @error('current_password', 'password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Kata sandi baru</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="field">
                        @error('password', 'password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium">Konfirmasi kata sandi baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="field">
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-sigap px-5 py-3 font-medium text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">Perbarui Kata Sandi</button>
                </form>
            </section>
        </div>
    </main>
</div>
<script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('open');
    }
</script>
</body>
</html>
