
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAP BENCANA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }
        header {
            background: #123b5d;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        header h2 { margin: 0; }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .hero {
            padding: 75px 7%;
            background: linear-gradient(120deg, #123b5d, #187b91);
            color: white;
        }
        .hero h1 { font-size: clamp(32px, 5vw, 48px); }
        .hero p { max-width: 600px; line-height: 1.8; }
        .content { padding: 35px 7%; }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #0000000d;
        }
        .card h3 { color: #123b5d; }
        footer {
            text-align: center;
            padding: 24px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <h2>SIGAP BENCANA</h2>
        <nav>
            <a href="/">Beranda</a>
            <a href="#layanan">Layanan</a>
            <a href="#tentang">Tentang</a>
        </nav>
    </header>

    <section class="hero">
        <h1>Siaga Menghadapi Bencana</h1>
        <p>
            Platform informasi kebencanaan untuk membantu masyarakat
            memperoleh informasi, mengenali risiko, dan mempersiapkan
            langkah keselamatan.
        </p>
    </section>

    <main class="content" id="layanan">
        <h2>Layanan SIGAP BENCANA</h2>
        <div class="cards">
            <article class="card">
                <h3>Peringatan Dini</h3>
                <p>Informasi peringatan dan potensi ancaman bencana.</p>
            </article>
            <article class="card">
                <h3>Peta Bencana</h3>
                <p>Informasi lokasi dan wilayah berisiko bencana.</p>
            </article>
            <article class="card">
                <h3>Lapor Kejadian</h3>
                <p>Sarana pelaporan kejadian bencana oleh masyarakat.</p>
            </article>
            <article class="card">
                <h3>Tempat Evakuasi</h3>
                <p>Informasi lokasi evakuasi dan tempat berkumpul aman.</p>
            </article>
        </div>
    </main>

    <section class="content" id="tentang">
        <h2>Tentang SIGAP BENCANA</h2>
        <p>
            SIGAP BENCANA dikembangkan sebagai platform multiplatform
            yang mengintegrasikan website dan aplikasi mobile.
        </p>
    </section>

    <footer>
        &copy; {{ date('Y') }} SIGAP BENCANA
    </footer>
</body>
</html>
