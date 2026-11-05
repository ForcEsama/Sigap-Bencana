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
        .profile-row { display: flex; align-items: flex-start; gap: 12px; border-radius: 9px; background: #ffedef; padding: 11px 14px; }
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
            @include('components.operational-dashboard-link')
            <a class="nav-item" href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
            <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <a class="nav-item" href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
            <a class="nav-item" href="{{ route('sos.create') }}"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</a>
            <a class="nav-item" href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
            <a class="nav-item" href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
            <a class="nav-item" href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
            <a class="nav-item" href="{{ route('help.index') }}"><i class="fa-regular fa-circle-question w-5 text-center"></i>Bantuan</a>
            <a class="nav-item active" href="{{ route('profile.edit') }}" aria-current="page"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">
                @csrf
                <button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button>
            </form>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Profil Saya</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Informasi akun SIGAP BENCANA Anda</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
            <x-notification-bell />
            <button id="edit-profile-toggle" type="button" aria-expanded="{{ $errors->profile->any() ? 'true' : 'false' }}" aria-controls="profile-edit-panel" onclick="togglePanel('profile-edit-panel', 'edit-profile-toggle')" class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg bg-sigap px-4 py-2 text-sm font-medium text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Edit Profil</span>
            </button>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif

        <section class="mb-5 rounded-lg bg-white p-5 shadow-sm sm:p-7" aria-label="Ringkasan profil">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[#ffedef] text-3xl font-semibold text-sigap" aria-hidden="true">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h2 class="break-words text-xl font-semibold sm:text-2xl">{{ $user->name }}</h2>
                    <p class="mt-1 break-all text-sm text-gray-600"><i class="fa-solid fa-envelope mr-2 text-sigap" aria-hidden="true"></i>{{ $user->email }}</p>
                    <p class="mt-1 text-sm text-gray-600"><i class="fa-solid fa-phone mr-2 text-sigap" aria-hidden="true"></i>{{ $user->phone ?: 'Belum diisi' }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
            <h2 class="mb-4 text-xl font-semibold">Informasi Pribadi</h2>
            <div class="space-y-2">
                <div class="profile-row">
                    <i class="fa-regular fa-user mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                    <div class="min-w-0"><h3 class="text-sm font-semibold">Nama Lengkap</h3><p class="break-words text-sm text-gray-600">{{ $user->name }}</p></div>
                </div>
                <div class="profile-row">
                    <i class="fa-solid fa-envelope mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                    <div class="min-w-0"><h3 class="text-sm font-semibold">Email</h3><p class="break-all text-sm text-gray-600">{{ $user->email }}</p></div>
                </div>
                <div class="profile-row">
                    <i class="fa-solid fa-phone mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                    <div class="min-w-0"><h3 class="text-sm font-semibold">Nomor Telepon</h3><p class="text-sm text-gray-600">{{ $user->phone ?: 'Belum diisi' }}</p></div>
                </div>
            </div>
        </section>

        <section class="mt-5 rounded-lg bg-white p-5 shadow-sm sm:p-7">
            <h2 class="mb-4 text-xl font-semibold">Lokasi Domisili</h2>
            <div class="space-y-2">
                @foreach ([
                    ['fa-location-dot', 'Alamat', $user->domicile_address],
                    ['fa-map', 'Provinsi', $user->province],
                    ['fa-map-location-dot', 'Kabupaten/Kota', $user->regency_city],
                    ['fa-map-pin', 'Kecamatan', $user->district],
                    ['fa-house', 'Kelurahan/Desa', $user->village],
                    ['fa-envelopes-bulk', 'Kode Pos', $user->postal_code],
                ] as [$icon, $label, $value])
                    <div class="profile-row">
                        <i class="fa-solid {{ $icon }} mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                        <div class="min-w-0"><h3 class="text-sm font-semibold">{{ $label }}</h3><p class="break-words text-sm text-gray-600">{{ $value ?: 'Belum diisi' }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-5 rounded-lg bg-white p-5 shadow-sm sm:p-7">
            <h2 class="mb-4 text-xl font-semibold">Informasi Tambahan</h2>
            <div class="space-y-2">
                <div class="profile-row">
                    <i class="fa-regular fa-calendar mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                    <div class="min-w-0"><h3 class="text-sm font-semibold">Tanggal Lahir</h3><p class="text-sm text-gray-600">{{ $user->date_of_birth?->locale('id')->translatedFormat('d F Y') ?? 'Belum diisi' }}</p></div>
                </div>
                <div class="profile-row">
                    <i class="fa-solid fa-user mt-0.5 w-5 shrink-0 text-center text-sigap" aria-hidden="true"></i>
                    <div class="min-w-0"><h3 class="text-sm font-semibold">Jenis Kelamin</h3><p class="text-sm text-gray-600">{{ $user->gender ? ucfirst($user->gender) : 'Belum diisi' }}</p></div>
                </div>
            </div>
        </section>

        <section id="profile-edit-panel" @if ($errors->profile->any()) data-open-on-load="true" @endif class="{{ $errors->profile->any() ? '' : 'hidden' }} mt-5 rounded-lg bg-white p-5 shadow-sm sm:p-7" aria-labelledby="profile-edit-title">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 id="profile-edit-title" class="text-xl font-semibold">Edit Profil</h2>
                    <p class="mt-1 text-sm text-gray-500">Lengkapi informasi pribadi dan domisili Anda.</p>
                </div>
                <button type="button" onclick="togglePanel('profile-edit-panel', 'edit-profile-toggle')" class="rounded p-1 text-gray-500 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap" aria-label="Tutup formulir edit profil"><i class="fa-solid fa-xmark"></i></button>
            </div>

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

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium">Nomor Telepon</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="20" autocomplete="tel" class="field">
                    @error('phone', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="domicile_address" class="mb-1 block text-sm font-medium">Alamat Domisili</label>
                    <textarea id="domicile_address" name="domicile_address" rows="2" maxlength="255" autocomplete="street-address" class="field">{{ old('domicile_address', $user->domicile_address) }}</textarea>
                    @error('domicile_address', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="province" class="mb-1 block text-sm font-medium">Provinsi</label>
                        <input id="province" name="province" type="text" value="{{ old('province', $user->province) }}" maxlength="100" autocomplete="address-level1" class="field">
                        @error('province', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="regency_city" class="mb-1 block text-sm font-medium">Kabupaten/Kota</label>
                        <input id="regency_city" name="regency_city" type="text" value="{{ old('regency_city', $user->regency_city) }}" maxlength="100" autocomplete="address-level2" class="field">
                        @error('regency_city', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="district" class="mb-1 block text-sm font-medium">Kecamatan</label>
                        <input id="district" name="district" type="text" value="{{ old('district', $user->district) }}" maxlength="100" class="field">
                        @error('district', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="village" class="mb-1 block text-sm font-medium">Kelurahan/Desa</label>
                        <input id="village" name="village" type="text" value="{{ old('village', $user->village) }}" maxlength="100" class="field">
                        @error('village', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="postal_code" class="mb-1 block text-sm font-medium">Kode Pos</label>
                        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $user->postal_code) }}" maxlength="10" autocomplete="postal-code" class="field">
                        @error('postal_code', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="date_of_birth" class="mb-1 block text-sm font-medium">Tanggal Lahir</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" autocomplete="bday" class="field">
                        @error('date_of_birth', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="gender" class="mb-1 block text-sm font-medium">Jenis Kelamin</label>
                        <select id="gender" name="gender" class="field">
                            <option value="">Pilih jika ingin mengisi</option>
                            <option value="laki-laki" @selected(old('gender', $user->gender) === 'laki-laki')>Laki-laki</option>
                            <option value="perempuan" @selected(old('gender', $user->gender) === 'perempuan')>Perempuan</option>
                        </select>
                        @error('gender', 'profile')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-sigap px-5 py-3 font-medium text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">Simpan Profil</button>
            </form>
        </section>

        <section class="mt-5 rounded-lg bg-white p-5 shadow-sm sm:p-7" aria-labelledby="password-panel-title">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 id="password-panel-title" class="text-lg font-semibold">Keamanan Akun</h2>
                    <p class="mt-1 text-sm text-gray-500">Ganti kata sandi untuk menjaga keamanan akun Anda.</p>
                </div>
                <button id="password-toggle" type="button" aria-expanded="{{ $errors->password->any() ? 'true' : 'false' }}" aria-controls="password-edit-panel" onclick="togglePanel('password-edit-panel', 'password-toggle')" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-800 transition hover:border-sigap hover:text-sigap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>Ganti Kata Sandi
                </button>
            </div>

            <div id="password-edit-panel" @if ($errors->password->any()) data-open-on-load="true" @endif class="{{ $errors->password->any() ? '' : 'hidden' }} mt-5 border-t border-gray-100 pt-5">
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
            </div>
        </section>
    </main>
</div>
<script>
    function togglePanel(panelId, buttonId) {
        const panel = document.getElementById(panelId);
        const button = document.getElementById(buttonId);
        const willOpen = panel.classList.contains('hidden');

        panel.classList.toggle('hidden', !willOpen);
        button.setAttribute('aria-expanded', String(willOpen));
    }

    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    document.querySelectorAll('[data-open-on-load="true"]').forEach((panel) => {
        panel.classList.remove('hidden');
        const buttonId = panel.id === 'profile-edit-panel' ? 'edit-profile-toggle' : 'password-toggle';
        document.getElementById(buttonId)?.setAttribute('aria-expanded', 'true');
    });
</script>
</body>
</html>
