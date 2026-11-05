<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Lokasi SOS - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']},colors:{sigap:'#D2002F',blush:'#FFECED'}}}};</script>
    <style>
        body{font-family:Poppins,sans-serif}.nav-item{display:flex;align-items:center;gap:14px;border-radius:8px;padding:10px 12px;color:#191919}.nav-item.active{color:#c9002c;background:#ffb8bd}#sos-map{min-height:320px;height:min(55vh,520px)}
        .sos-pulse-ring{position:absolute;border-radius:9999px;animation:sos-pulse 2.8s ease-in-out infinite}.sos-pulse-ring:nth-child(2){animation-delay:.25s}.sos-pulse-ring:nth-child(3){animation-delay:.5s}.sos-pulse-ring:nth-child(4){animation:none}
        @keyframes sos-pulse{0%,100%{transform:scale(.96);opacity:.82}50%{transform:scale(1.04);opacity:1}}
        @media(prefers-reduced-motion:reduce){.sos-pulse-ring{animation:none}}
        @media(max-width:1023px){#sidebar{display:none}#sidebar.open{display:flex;position:fixed;inset:0 auto 0 0;z-index:50;width:min(270px,86vw);overflow-y:auto;box-shadow:10px 0 30px #0002}}
    </style>
</head>
<body class="min-h-screen bg-blush text-[#181818]"><div class="min-h-screen lg:flex">
    @include('sos._sidebar')
    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-5 flex items-start justify-between gap-4"><div class="flex gap-3"><button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button><div><h1 class="text-2xl font-semibold sm:text-3xl">Konfirmasi Lokasi</h1><p class="mt-1 text-sm text-gray-600">Pastikan titik lokasi sesuai sebelum menyimpan permintaan.</p></div></div><div class="flex shrink-0 items-center gap-2"><x-notification-bell /><span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden sm:inline">{{ auth()->user()->name }}</span></div></header>
        <section id="sos-loading-screen" class="hidden min-h-[65vh] flex-col items-center justify-center px-2 py-10 text-center" role="status" aria-live="polite" aria-busy="true">
            <h2 class="text-xl font-semibold sm:text-2xl">Mengirim Permintaan Bantuan...</h2>
            <div class="relative mt-8 flex h-72 w-72 items-center justify-center sm:h-80 sm:w-80" aria-hidden="true">
                <div class="sos-pulse-ring h-full w-full bg-rose-100"></div>
                <div class="sos-pulse-ring h-[82%] w-[82%] bg-rose-200"></div>
                <div class="sos-pulse-ring h-[68%] w-[68%] bg-rose-400"></div>
                <div class="sos-pulse-ring flex h-[52%] w-[52%] items-center justify-center bg-rose-600 text-3xl font-semibold text-white sm:text-4xl">SOS</div>
            </div>
            <p class="mt-7 max-w-md text-sm text-gray-600">Permintaan sedang diproses. Halaman akan berlanjut setelah hasil penyimpanan diterima.</p>
        </section>
        <div id="sos-confirm-content">
        <p id="sos-submit-error" class="mb-4 hidden rounded-lg border border-rose-300 bg-white p-3 text-sm text-rose-800" role="alert" tabindex="-1"></p>
        @if (session('error'))<p role="alert" class="mb-4 rounded-lg border border-rose-300 bg-white p-3 text-sm text-rose-800">{{ session('error') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mb-4 rounded-lg border border-rose-300 bg-white p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg bg-white p-4 text-sm"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-sigap font-semibold text-white">2</span><span class="font-semibold text-sigap">{{ $disasterLabel }}</span><a href="{{ route('sos.create') }}" class="ml-auto font-medium text-sigap underline">Ubah jenis darurat</a><span class="hidden text-gray-500 sm:inline">Langkah 2 dari 2</span></div>
        <section class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:px-6"><h2 class="text-lg font-semibold">Pilih dan konfirmasi titik lokasi</h2><button id="locate-button" type="button" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-sigap hover:bg-rose-50"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i><span id="locate-button-label">Gunakan lokasi saya</span></button></div>
            <div class="grid min-w-0 lg:grid-cols-[1.35fr_1fr]">
                <div class="min-w-0"><div id="sos-map" role="application" aria-label="Peta pemilihan lokasi bantuan"></div></div>
                <div class="flex min-w-0 flex-col gap-4 border-t border-gray-200 p-4 sm:p-6 lg:border-l lg:border-t-0">
                    <div><h2 class="text-lg font-semibold">Lokasi permintaan</h2><p id="location-message" class="mt-2 text-sm text-gray-600" role="status" aria-live="polite">Tekan “Gunakan lokasi saya” atau pilih titik pada peta. Tanpa titik yang dipilih, permintaan tidak dapat disimpan.</p></div>
                    <div id="accuracy-display" class="hidden rounded-md bg-blue-50 p-3 text-sm text-blue-900"></div>
                    <label class="text-sm font-medium" for="address">Alamat atau patokan (opsional)
                        <input id="address" name="address" form="sos-submit-form" type="text" maxlength="500" value="{{ old('address') }}" placeholder="Isi jika Anda mengetahui alamatnya" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                    </label>
                    <fieldset class="grid gap-3 sm:grid-cols-2"><legend class="mb-1 text-sm font-medium">Koordinat lokasi (dapat disesuaikan)</legend>
                        <label class="text-xs text-gray-600" for="latitude">Latitude
                            <input id="latitude" name="latitude" form="sos-submit-form" type="number" step="any" min="-90" max="90" required value="{{ old('latitude') }}" placeholder="Pilih titik pada peta" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                        </label>
                        <label class="text-xs text-gray-600" for="longitude">Longitude
                            <input id="longitude" name="longitude" form="sos-submit-form" type="number" step="any" min="-180" max="180" required value="{{ old('longitude') }}" placeholder="Pilih titik pada peta" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                        </label>
                    </fieldset>
                    <p class="text-xs leading-relaxed text-gray-500">Anda dapat mengeklik peta, menggeser penanda, atau mengetik koordinat. Pastikan titik benar. Alamat tidak diisi otomatis karena tidak ada layanan geocoding yang terhubung.</p>
                </div>
            </div>
        </section>
        <p class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Permintaan akan tersimpan di aplikasi saja. Sistem belum meneruskannya ke layanan darurat atau petugas, sehingga penyimpanan bukan bukti bahwa bantuan telah diterima.</p>
        <form id="sos-submit-form" method="POST" action="{{ route('sos.store') }}" class="mt-4 flex flex-col-reverse justify-between gap-3 sm:flex-row">
            @csrf<input type="hidden" name="submission_key" value="{{ $submissionKey }}">
            <a href="{{ route('home') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-center text-sm font-medium hover:bg-gray-50">Batal</a>
            <button id="submit-button" type="submit" class="rounded-lg bg-sigap px-7 py-3 text-sm font-semibold text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid fa-paper-plane mr-2" aria-hidden="true"></i>Kirim Permintaan Bantuan</button>
        </form>
        </div>
    </main>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function toggleMenu(){document.getElementById('sidebar').classList.toggle('open')}
    (()=>{
        const map=L.map('sos-map').setView([-0.9333,100.325],12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
        const latInput=document.getElementById('latitude'), lonInput=document.getElementById('longitude'), message=document.getElementById('location-message'), locateButton=document.getElementById('locate-button'), locateButtonLabel=document.getElementById('locate-button-label'), accuracyDisplay=document.getElementById('accuracy-display');
        let marker=null, accuracyCircle=null;
        const initialLat=Number(latInput.value), initialLon=Number(lonInput.value);
        function setPoint(lat,lon,accuracy=null,origin='Titik dipilih pada peta'){
            if(!Number.isFinite(lat)||!Number.isFinite(lon)||lat < -90||lat > 90||lon < -180||lon > 180)return;
            latInput.value=lat.toFixed(7);lonInput.value=lon.toFixed(7);
            const point=[lat,lon];
            if(marker){marker.setLatLng(point)}else{marker=L.marker(point,{draggable:true}).addTo(map);marker.on('dragend',()=>{const p=marker.getLatLng();setPoint(p.lat,p.lng,null,'Lokasi disesuaikan dengan menggeser penanda')})}
            if(accuracyCircle){map.removeLayer(accuracyCircle);accuracyCircle=null}
            if(Number.isFinite(accuracy)&&accuracy>0){accuracyCircle=L.circle(point,{radius:accuracy,color:'#ef4444',fillColor:'#fb7185',fillOpacity:.14,weight:2}).addTo(map);accuracyDisplay.textContent=`Perkiraan akurasi yang dilaporkan browser: ±${Math.round(accuracy)} meter.`;accuracyDisplay.classList.remove('hidden')}else{accuracyDisplay.classList.add('hidden')}
            message.textContent=origin;locateButtonLabel.textContent='Perbarui Lokasi';map.setView(point,16);
        }
        if(latInput.value!==''&&lonInput.value!==''&&Number.isFinite(initialLat)&&Number.isFinite(initialLon))setPoint(initialLat,initialLon,null,'Koordinat yang dikirim sebelumnya; periksa kembali sebelum mengirim.');
        map.on('click',event=>setPoint(event.latlng.lat,event.latlng.lng,null,'Titik lokasi dipilih manual pada peta.'));
        const syncInputs=()=>{const lat=Number(latInput.value),lon=Number(lonInput.value);if(latInput.value!==''&&lonInput.value!=='')setPoint(lat,lon,null,'Koordinat dimasukkan secara manual.');};
        latInput.addEventListener('change',syncInputs);lonInput.addEventListener('change',syncInputs);
        locateButton.addEventListener('click',()=>{
            if(!window.isSecureContext){message.textContent='Lokasi perangkat memerlukan koneksi aman (HTTPS) atau localhost. Anda masih dapat memilih titik secara manual pada peta.';return}
            if(!navigator.geolocation){message.textContent='Browser ini tidak mendukung lokasi perangkat. Pilih titik secara manual pada peta.';return}
            locateButton.disabled=true;message.textContent='Meminta lokasi perangkat…';
            navigator.geolocation.getCurrentPosition(position=>{setPoint(position.coords.latitude,position.coords.longitude,position.coords.accuracy,'Lokasi perangkat ditemukan. Periksa titik dan akurasinya sebelum melanjutkan.');locateButton.disabled=false;},error=>{
                const errors={1:'Izin lokasi ditolak. Aktifkan izin atau pilih titik pada peta.',2:'Perangkat tidak dapat menentukan lokasi. Coba lagi atau pilih titik pada peta.',3:'Waktu pencarian lokasi habis. Coba lagi atau pilih titik pada peta.'};
                message.textContent=errors[error.code]||'Lokasi tidak dapat diperoleh. Pilih titik pada peta.';locateButton.disabled=false;
            },{enableHighAccuracy:true,timeout:15000,maximumAge:0});
        });
        const submitForm=document.getElementById('sos-submit-form'), submitButton=document.getElementById('submit-button'), confirmContent=document.getElementById('sos-confirm-content'), loadingScreen=document.getElementById('sos-loading-screen'), submitError=document.getElementById('sos-submit-error');
        let submissionInProgress=false;
        function showSubmitError(text){
            loadingScreen.classList.add('hidden');loadingScreen.classList.remove('flex');confirmContent.classList.remove('hidden');
            submitError.textContent=text;submitError.classList.remove('hidden');submitError.focus();submitButton.disabled=false;submitButton.innerHTML='<i class="fa-solid fa-paper-plane mr-2" aria-hidden="true"></i>Kirim Permintaan Bantuan';submissionInProgress=false;
        }
        submitForm.addEventListener('submit',async event=>{
            const lat=Number(latInput.value),lon=Number(lonInput.value);
            if(latInput.value===''||lonInput.value===''||!Number.isFinite(lat)||!Number.isFinite(lon)||lat < -90||lat > 90||lon < -180||lon > 180){event.preventDefault();message.textContent='Pilih atau masukkan koordinat yang valid sebelum mengirim.';return}
            if(submissionInProgress){event.preventDefault();return}
            event.preventDefault();submissionInProgress=true;submitButton.disabled=true;submitError.classList.add('hidden');confirmContent.classList.add('hidden');loadingScreen.classList.remove('hidden');loadingScreen.classList.add('flex');
            try{
                const response=await fetch(submitForm.action,{method:'POST',body:new FormData(submitForm),credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                const payload=await response.json().catch(()=>null);
                if(response.ok&&payload&&typeof payload.redirect_url==='string'){
                    const redirectUrl=new URL(payload.redirect_url,window.location.origin);
                    if(redirectUrl.origin===window.location.origin){window.location.assign(redirectUrl.href);return}
                }
                const validationMessage=payload&&payload.errors?Object.values(payload.errors).flat()[0]:null;
                showSubmitError((payload&&payload.message)||validationMessage||'Permintaan belum tersimpan. Periksa koneksi lalu coba kirim kembali.');
            }catch(error){
                showSubmitError('Koneksi terputus sebelum hasil diterima. Permintaan mungkin sudah tercatat; coba kirim kembali dengan tombol yang sama untuk memeriksa hasilnya.');
            }
        });
        window.addEventListener('pageshow',event=>{if(event.persisted&&submissionInProgress)showSubmitError('Halaman konfirmasi dibuka kembali. Periksa informasi lokasi, lalu kirim jika masih diperlukan.');});
    })();
</script>
</body>
</html>
