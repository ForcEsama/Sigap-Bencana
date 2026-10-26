<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SIGAP BENCANA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif']
                    },
                    colors: {
                        sigap: '#D50032',
                        softpink: '#FFF0F2'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-softpink font-poppins text-gray-900">

    <a href="{{ url('/') }}"
       class="absolute left-7 top-5 flex items-center gap-3 text-gray-500 transition hover:text-sigap">
        <i class="fa-solid fa-arrow-left"></i>
        <span class="text-sm sm:text-base">Kembali ke Beranda</span>
    </a>

    <main class="flex min-h-screen items-center justify-center px-5 py-20">

        <section class="w-full max-w-md rounded-md bg-white px-7 py-10 shadow-sm sm:px-12 sm:py-11">

            <div class="mb-1 text-xl font-semibold">Masuk</div>
            <p class="mb-1 text-sm text-gray-600">
                Masuk ke akun SIGAP Bencana Anda
            </p>

            <form action="{{ route('login.store') }}" method="POST" class="space-y-3">
                @csrf

                <div class="flex min-h-[42px] items-center gap-4 rounded border border-gray-400 px-3 focus-within:border-sigap">
                    <i class="fa-regular fa-user text-gray-600"></i>
                    <input
                        type="text"
                        name="identity"
                        placeholder="Email atau Nomor Telepon"
                        autocomplete="username"
                        class="w-full bg-transparent py-2 text-xs outline-none sm:text-sm"
                        required>
                </div>

                <div class="flex min-h-[42px] items-center gap-4 rounded border border-gray-400 px-3 focus-within:border-sigap">
                    <i class="fa-solid fa-lock text-gray-600"></i>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Kata Sandi"
                        autocomplete="current-password"
                        class="w-full bg-transparent py-2 text-xs outline-none sm:text-sm"
                        required>
                    <button
                        type="button"
                        id="togglePassword"
                        aria-label="Tampilkan kata sandi"
                        class="text-gray-600 hover:text-sigap">
                        <i id="eyeIcon" class="fa-regular fa-eye-slash"></i>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3 text-[10px] sm:text-xs">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-5 w-5 accent-sigap">
                        <span>Ingat saya</span>
                    </label>

                    <a href="#"
                       class="font-medium text-sigap hover:underline">
                        Lupa kata sandi?
                    </a>
                </div>

                @if ($errors->any())
                    <div class="rounded bg-red-50 px-3 py-2 text-xs text-red-600">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button
                    type="submit"
                    class="flex min-h-[40px] w-full items-center justify-center rounded bg-sigap px-4 py-2 text-sm text-white transition hover:bg-[#b9002b] focus:outline-none focus:ring-2 focus:ring-sigap focus:ring-offset-2">
                    Masuk
                </button>

                <div class="flex items-center gap-3 py-1 text-[10px] text-gray-500">
                    <div class="h-px flex-1 bg-gray-400"></div>
                    <span>atau masuk dengan</span>
                    <div class="h-px flex-1 bg-gray-400"></div>
                </div>

                <button
                    type="button"
                    class="flex min-h-[36px] w-full items-center justify-center gap-3 rounded border border-gray-400 text-xs font-medium transition hover:bg-gray-50">
                    <img src="{{ asset('images/Google_Favicon_2025.svg') }}" alt="" aria-hidden="true" class="h-4 w-4">
                    Masuk dengan Google
                </button>

                <p class="pt-1 text-center text-[10px] text-gray-700 sm:text-xs">
                    Belum punya akun?
                    <a href="{{ route('register') }}"
                       class="font-medium text-sigap hover:underline">
                        Daftar
                    </a>
                </p>
            </form>

        </section>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleButton.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden
                ? 'fa-regular fa-eye'
                : 'fa-regular fa-eye-slash';

            toggleButton.setAttribute(
                'aria-label',
                isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
            );
        });
    </script>

</body>
</html>
