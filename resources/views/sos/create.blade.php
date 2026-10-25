<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOS - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] }, colors: { sigap: '#D2002F', blush: '#FFECED' } } } };</script>
    <style>body{font-family:Poppins,sans-serif}.nav-item{display:flex;align-items:center;gap:14px;border-radius:8px;padding:10px 12px;color:#191919}.nav-item.active{color:#c9002c;background:#ffb8bd}@media(max-width:1023px){#sidebar{display:none}#sidebar.open{display:flex;position:fixed;inset:0 auto 0 0;z-index:50;width:min(270px,86vw);overflow-y:auto;box-shadow:10px 0 30px #0002}}</style>
</head>
<body class="min-h-screen bg-blush text-[#181818]">
<div class="min-h-screen lg:flex">
    @include('sos._sidebar')
    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-8 flex items-start justify-between gap-4">
            <div class="flex gap-3"><button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button><div><h1 class="text-2xl font-semibold sm:text-3xl">Pilih Jenis Keadaan Darurat</h1><p class="mt-1 text-sm text-gray-600">Pilih kondisi yang sedang Anda alami.</p></div></div>
            <div class="flex shrink-0 items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden sm:inline">{{ auth()->user()->name }}</span></div>
        </header>
        @if (session('error'))<p role="alert" class="mb-4 rounded-lg border border-rose-300 bg-white p-3 text-sm text-rose-800">{{ session('error') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mb-4 rounded-lg border border-rose-300 bg-white p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
        <section class="mx-auto max-w-4xl rounded-xl bg-white p-5 shadow-sm sm:p-8">
            <div class="mb-6 flex items-center gap-3 rounded-lg bg-rose-50 p-4 text-sm"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-sigap font-semibold text-white">1</span><strong class="text-sigap">Jenis keadaan darurat</strong><span class="h-px flex-1 bg-rose-200"></span><span class="text-gray-500">2 Konfirmasi lokasi</span><span class="ml-auto hidden text-gray-500 sm:inline">Langkah 1 dari 2</span></div>
            <form method="POST" action="{{ route('sos.confirm-selection') }}" id="sos-type-form">
                @csrf
                <fieldset><legend class="sr-only">Jenis keadaan darurat</legend>
                    @php($icons = ['gempa' => 'fa-house-crack', 'banjir' => 'fa-house-flood-water', 'longsor' => 'fa-hill-rockslide', 'kebakaran' => 'fa-house-fire', 'tsunami' => 'fa-water'])
                    <div class="space-y-3">
                        @foreach ($disasterTypes as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 transition hover:border-rose-300 has-[:checked]:border-2 has-[:checked]:border-sigap has-[:checked]:bg-rose-50 focus-within:ring-2 focus-within:ring-rose-200">
                                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-rose-100 text-xl text-sigap"><i class="fa-solid {{ $icons[$value] }}" aria-hidden="true"></i></span>
                                <span class="flex-1 font-semibold">{{ $label }}</span>
                                <input class="peer order-last h-5 w-5 accent-[#D2002F]" type="radio" name="disaster_type" value="{{ $value }}" required {{ old('disaster_type') === $value ? 'checked' : '' }}>
                                <span class="hidden text-sm font-medium text-sigap peer-checked:block">Dipilih</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-7 flex flex-col-reverse justify-between gap-3 sm:flex-row"><a href="{{ route('home') }}" class="rounded-lg border border-gray-300 px-6 py-3 text-center text-sm font-medium hover:bg-gray-50">Batal</a><button type="submit" class="rounded-lg bg-sigap px-7 py-3 text-sm font-semibold text-white hover:bg-red-800">Lanjutkan</button></div>
            </form>
        </section>
        <p class="mx-auto mt-4 max-w-4xl rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Peringatan: fitur ini menyimpan permintaan di aplikasi dan belum terhubung untuk mengirim panggilan atau notifikasi kepada petugas maupun layanan darurat. Jika Anda berada dalam bahaya langsung, hubungi layanan darurat setempat melalui halaman <a class="font-semibold underline" href="{{ route('emergency-contacts.index') }}">Kontak Darurat</a>.</p>
    </main>
</div>
<script>function toggleMenu(){document.getElementById('sidebar').classList.toggle('open')}</script>
</body>
</html>
