
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | SIGAP BENCANA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        sigap: '#D50032',
                        softpink: '#FFF7FF',
                    },
                },
            },
        };
    </script>
</head>

<body class="min-h-screen bg-[#FFECEE] font-poppins text-[#17131b] md:flex md:items-center md:justify-center md:p-6">

    <main class="grid min-h-screen w-full overflow-hidden bg-softpink md:min-h-[650px] md:max-w-[1050px] md:grid-cols-[1.15fr_.85fr] md:rounded-[22px] md:border-[2px] md:border-[#d2cbd7]">

        <!-- Form Registrasi -->
        <section class="flex flex-col justify-center px-6 py-8 sm:px-10 md:px-9 md:py-7">

            <a href="{{ url('/') }}"
               class="mb-3 inline-flex w-fit items-center gap-3 text-sm text-gray-400 transition hover:text-sigap">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Beranda
            </a>

            <h1 class="text-2xl font-bold sm:text-[27px]">Buat Akun</h1>

            <p class="mb-4 mt-1 text-sm leading-relaxed text-gray-400">
                Daftar untuk mengakses semua fitur SIGAP Bencana
            </p>

            @if (session('success'))
                <div class="mb-3 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    Terdapat kesalahan. Silakan periksa kembali data kamu.
                </div>
            @endif

            <!-- Form belum terhubung ke database -->
            <form action="{{ route('register.store') }}" method="POST" class="space-y-3">
                @csrf

                <!-- Nama Lengkap -->
                <div>
                    <div class="flex min-h-[43px] items-center gap-3 rounded border border-[#bcb6bf] bg-white px-3 transition focus-within:border-sigap">
                        <i class="fa-regular fa-user w-4 text-center text-gray-600"></i>
                        <input
                            type="text"
                            name="name"
                            placeholder="Nama Lengkap"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                            class="w-full min-w-0 bg-transparent py-2 text-xs outline-none placeholder:text-gray-500"
                        >
                    </div>
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <div class="flex min-h-[43px] items-center gap-3 rounded border border-[#bcb6bf] bg-white px-3 transition focus-within:border-sigap">
                        <i class="fa-regular fa-envelope w-4 text-center text-gray-600"></i>
                        <input
                            type="email"
                            name="email"
                            placeholder="Email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            class="w-full min-w-0 bg-transparent py-2 text-xs outline-none placeholder:text-gray-500"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <div class="flex min-h-[43px] items-center gap-3 rounded border border-[#bcb6bf] bg-white px-3 transition focus-within:border-sigap">
                        <i class="fa-solid fa-phone w-4 text-center text-gray-600"></i>
                        <input
                            type="tel"
                            name="phone"
                            placeholder="Nomor Telepon"
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                            class="w-full min-w-0 bg-transparent py-2 text-xs outline-none placeholder:text-gray-500"
                        >
                    </div>
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kata Sandi -->
                <div>
                    <div class="flex min-h-[43px] items-center gap-3 rounded border border-[#bcb6bf] bg-white px-3 transition focus-within:border-sigap">
                        <i class="fa-solid fa-lock w-4 text-center text-gray-600"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Kata Sandi"
                            autocomplete="new-password"
                            required
                            class="w-full min-w-0 bg-transparent py-2 text-xs outline-none placeholder:text-gray-500"
                        >
                        <button type="button" onclick="togglePassword('password', this)" class="text-gray-600 hover:text-sigap" aria-label="Tampilkan kata sandi">
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Konfirmasi Kata Sandi -->
                <div>
                    <div class="flex min-h-[43px] items-center gap-3 rounded border border-[#bcb6bf] bg-white px-3 transition focus-within:border-sigap">
                        <i class="fa-solid fa-lock w-4 text-center text-gray-600"></i>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Konfirmasi Kata Sandi"
                            autocomplete="new-password"
                            required
                            class="w-full min-w-0 bg-transparent py-2 text-xs outline-none placeholder:text-gray-500"
                        >
                        <button type="button" onclick="togglePassword('password_confirmation', this)" class="text-gray-600 hover:text-sigap" aria-label="Tampilkan konfirmasi kata sandi">
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <!-- Persetujuan -->
                <label class="flex cursor-pointer items-start gap-2 text-[10px] leading-relaxed">
                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        required
                        class="mt-0.5 h-4 w-4 shrink-0 accent-[#D50032]"
                    >
                    <span>
                        Saya setuju dengan Syarat dan Ketentuan dan Kebijakan Privasi
                    </span>
                </label>

                <!-- Tombol Daftar -->
                <button
                    type="submit"
                    class="flex min-h-[44px] w-full items-center justify-center rounded bg-sigap px-4 text-sm font-medium text-white transition hover:bg-[#b9002b] focus:outline-none focus:ring-2 focus:ring-sigap focus:ring-offset-2"
                >
                    Daftar Akun
                </button>
            </form>

            <p class="mt-4 text-center text-xs">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="ml-1 font-semibold text-sigap hover:underline">
                    Masuk
                </a>
            </p>
        </section>

        <!-- Gambar Sisi Kanan: Desktop -->
        <aside class="relative hidden overflow-hidden bg-slate-200 md:block">
            <img
                src="{{ asset('images/registration-background-w.png') }}"
                alt="Pemandangan alam Indonesia"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/20"></div>
            <p class="absolute left-7 top-10 max-w-[280px] text-lg font-medium leading-relaxed text-white">
                Informasi yang tepat hari ini, keselamatan yang lebih baik untuk esok.
            </p>
        </aside>

    </main>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');
            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';
            icon.className = show
                ? 'fa-regular fa-eye'
                : 'fa-regular fa-eye-slash';
        }
    </script>

</body>
</html>
