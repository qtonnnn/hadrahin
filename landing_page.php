<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <title>HADRAHIN — Manajemen Tim Hadrah Modern</title>
  <!-- Google Fonts: plus elegant serif & contemporary sans -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 (free) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- AOS scroll animation (light) -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <style>
    /* ---------- ROOT & GLOBAL –– modern islamic elegance ---------- */
    :root {
      --deep-sage: #2F4B3C;      /* islamic green, mature */
      --gold-sand: #C9A86B;      /* subtle gold, qur'anic illumination */
      --cream-parchment: #FCF8F0; /* warm white, like paper */
      --ink-charcoal: #1E2A2E;   /* almost black with softness */
      --taupe-stone: #5E5B52;    /* grey with brown undertone */
      --border-light: rgba(201, 168, 107, 0.2);
      --shadow-sm: 0 15px 35px -12px rgba(31, 47, 43, 0.08);
      --shadow-hover: 0 25px 45px -12px rgba(47, 75, 60, 0.15);
      --font-serif: 'DM Serif Display', serif;
      --font-sans: 'Plus Jakarta Sans', sans-serif;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: var(--font-sans);
      background-color: var(--cream-parchment);
      color: var(--ink-charcoal);
      line-height: 1.6;
      overflow-x: hidden;
    }

    h1, h2, h3, h4 {
      font-family: var(--font-serif);
      font-weight: 400;
      letter-spacing: -0.02em;
      line-height: 1.2;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* ---------- subtle islamic pattern overlay (modern minimalist) ---------- */
    .islamic-pattern-bg {
      position: relative;
      background-color: var(--cream-parchment);
    }
    .islamic-pattern-bg::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        radial-gradient(circle at 15% 30%, rgba(201, 168, 107, 0.03) 0px, transparent 12%),
        radial-gradient(circle at 85% 70%, rgba(47, 75, 60, 0.03) 0px, transparent 15%),
        repeating-linear-gradient(45deg, rgba(201, 168, 107, 0.02) 0px, rgba(201, 168, 107, 0.02) 1px, transparent 1px, transparent 12px);
      pointer-events: none;
    }

    /* ----- geometric divider (modern islamic arch) ----- */
    .arch-divider {
      width: 100%;
      height: 60px;
      background: linear-gradient(135deg, var(--deep-sage) 0%, #3A5E4C 100%);
      clip-path: polygon(0 40%, 100% 0, 100% 100%, 0 80%);
      opacity: 0.95;
      margin: 2rem 0 0;
    }

    /* ---------- BUTTONS ---------- */
    .btn {
      display: inline-block;
      padding: 0.85rem 2.2rem;
      border-radius: 60px;
      font-weight: 600;
      font-size: 1rem;
      letter-spacing: 0.02em;
      transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1);
      text-decoration: none;
      border: 1.5px solid transparent;
      box-shadow: 0 8px 18px -8px rgba(0,0,0,0.08);
      font-family: var(--font-sans);
    }

    .btn-primary {
      background-color: var(--deep-sage);
      color: white;
      border-color: var(--deep-sage);
    }
    .btn-primary:hover {
      background-color: #1E3528;
      border-color: #1E3528;
      box-shadow: 0 15px 25px -10px rgba(47, 75, 60, 0.35);
      transform: translateY(-3px);
    }

    .btn-outline {
      background-color: transparent;
      color: var(--deep-sage);
      border: 1.5px solid var(--deep-sage);
    }
    .btn-outline:hover {
      background-color: var(--deep-sage);
      color: white;
      transform: translateY(-3px);
    }

    .btn-gold {
      background-color: var(--gold-sand);
      color: var(--ink-charcoal);
      border-color: var(--gold-sand);
    }
    .btn-gold:hover {
      background-color: #B69152;
      border-color: #B69152;
      color: white;
      box-shadow: 0 15px 25px -10px rgba(201, 168, 107, 0.4);
    }

    /* ---------- NAVBAR ---------- */
    .navbar {
      padding: 1.5rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: rgba(252, 248, 240, 0.8);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      position: sticky;
      top: 0;
      z-index: 50;
      border-bottom: 1px solid var(--border-light);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .logo i {
      font-size: 2.1rem;
      color: var(--deep-sage);
    }
    .logo span {
      font-family: var(--font-serif);
      font-size: 1.9rem;
      font-weight: 400;
      color: var(--deep-sage);
      letter-spacing: -0.02em;
    }
    .nav-links {
      display: flex;
      gap: 2.8rem;
      align-items: center;
    }
    .nav-links a {
      text-decoration: none;
      color: var(--ink-charcoal);
      font-weight: 500;
      font-size: 1.05rem;
      transition: color 0.2s;
      border-bottom: 2px solid transparent;
      padding-bottom: 4px;
    }
    .nav-links a:hover {
      color: var(--deep-sage);
      border-bottom-color: var(--gold-sand);
    }

    /* ---------- HERO ---------- */
    .hero {
      padding: 5rem 0 6rem;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 3rem;
    }
    .hero-content {
      flex: 1 1 45%;
    }
    .hero-badge {
      display: inline-block;
      background: rgba(201, 168, 107, 0.1);
      backdrop-filter: blur(4px);
      padding: 0.4rem 1.4rem;
      border-radius: 40px;
      color: var(--deep-sage);
      font-weight: 600;
      font-size: 0.9rem;
      letter-spacing: 1px;
      border: 1px solid rgba(201,168,107,0.3);
      margin-bottom: 1.5rem;
    }
    .hero-content h1 {
      font-size: 3.9rem;
      color: var(--ink-charcoal);
      margin-bottom: 1.2rem;
      line-height: 1.1;
    }
    .hero-highlight {
      color: var(--deep-sage);
      border-bottom: 4px solid var(--gold-sand);
      display: inline-block;
      padding-bottom: 0px;
    }
    .hero-desc {
      font-size: 1.2rem;
      color: var(--taupe-stone);
      margin: 1.8rem 0 2.2rem;
      font-weight: 400;
      max-width: 90%;
    }
    .hero-buttons {
      display: flex;
      gap: 1.2rem;
      flex-wrap: wrap;
    }
    .hero-image {
      flex: 1 1 40%;
      background: radial-gradient(circle at 70% 30%, rgba(201,168,107,0.08) 0%, transparent 70%);
      padding: 1rem;
      position: relative;
    }
    .mockup-frame {
      background: white;
      border-radius: 32px;
      padding: 1.2rem 1rem;
      box-shadow: var(--shadow-sm);
      border: 1px solid rgba(201,168,107,0.2);
      backdrop-filter: blur(6px);
      transform: perspective(1000px) rotateY(-5deg) rotateX(2deg);
      transition: transform 0.4s;
    }
    .mockup-frame:hover {
      transform: perspective(1000px) rotateY(-3deg) rotateX(1deg) translateY(-6px);
      box-shadow: var(--shadow-hover);
    }
    .mockup-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #EFE9DD;
      padding-bottom: 0.8rem;
    }
    .mockup-header i {
      color: var(--deep-sage);
      font-size: 1.5rem;
    }
    .mockup-header span {
      font-weight: 600;
      color: var(--deep-sage);
    }
    .mockup-content {
      padding-top: 1.5rem;
    }
    .mockup-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1rem;
      color: var(--ink-charcoal);
    }
    .mockup-row i {
      color: var(--gold-sand);
      margin-right: 8px;
    }
    .mockup-footer {
      margin-top: 1.8rem;
      background: var(--deep-sage);
      color: white;
      padding: 0.7rem;
      border-radius: 20px;
      text-align: center;
      font-weight: 600;
      font-size: 0.95rem;
    }

    /* ---------- features / keistimewaan (modern islamic) ---------- */
    .section-title {
      text-align: center;
      margin-bottom: 3.5rem;
    }
    .section-title h2 {
      font-size: 2.8rem;
      color: var(--deep-sage);
    }
    .section-title p {
      color: var(--taupe-stone);
      font-size: 1.2rem;
      max-width: 650px;
      margin: 0.8rem auto 0;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2.5rem;
      margin: 4rem 0;
    }

    .feature-card {
      background: white;
      padding: 2.5rem 1.8rem;
      border-radius: 36px;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s ease;
      border: 1px solid white;
      position: relative;
      backdrop-filter: blur(4px);
    }
    .feature-card:hover {
      border-color: var(--gold-sand);
      box-shadow: var(--shadow-hover);
      transform: translateY(-10px);
    }
    .feature-icon {
      background: rgba(47, 75, 60, 0.07);
      width: 70px;
      height: 70px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.8rem;
    }
    .feature-icon i {
      font-size: 2.5rem;
      color: var(--deep-sage);
    }
    .feature-card h3 {
      font-size: 1.9rem;
      margin-bottom: 0.8rem;
      color: var(--ink-charcoal);
      font-weight: 500;
    }
    .feature-card p {
      color: #4A4A4A;
      font-weight: 400;
    }

    /* nilai islami / values */
    .islamic-values {
      background: linear-gradient(145deg, #FAF6ED, #F5EFE2);
      border-radius: 100px 20px 100px 20px;
      padding: 4rem 3rem;
      margin: 4rem 0;
      border: 1px solid var(--border-light);
    }
    .values-grid {
      display: flex;
      gap: 2.5rem;
      flex-wrap: wrap;
      justify-content: space-around;
    }
    .value-item {
      text-align: center;
      flex: 1 1 200px;
    }
    .value-item i {
      font-size: 2.5rem;
      color: var(--gold-sand);
      background: white;
      padding: 0.8rem;
      border-radius: 50%;
      box-shadow: var(--shadow-sm);
    }
    .value-item h4 {
      font-size: 1.7rem;
      margin: 1.2rem 0 0.4rem;
      color: var(--deep-sage);
    }

    /* ---------- TIMELINE / CARA KERJA (elegan) ---------- */
    .timeline {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      margin: 5rem 0;
      position: relative;
    }
    .timeline::before {
      content: "◈ ◈ ◈";
      position: absolute;
      top: 30%;
      left: 50%;
      transform: translateX(-50%);
      color: var(--gold-sand);
      font-size: 2rem;
      letter-spacing: 12px;
      opacity: 0.3;
    }
    .step {
      background: white;
      padding: 2rem 1.8rem;
      border-radius: 30px;
      flex: 0 1 30%;
      box-shadow: var(--shadow-sm);
      border-bottom: 5px solid var(--gold-sand);
    }
    .step-num {
      font-family: var(--font-serif);
      font-size: 3rem;
      color: var(--deep-sage);
      opacity: 0.25;
      line-height: 1;
    }

    /* ---------- TESTIMONI / KELAS ATAS ---------- */
    .testimonial {
      background-color: white;
      padding: 4rem 3rem;
      border-radius: 48px 48px 48px 12px;
      margin: 5rem 0;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-light);
      position: relative;
    }
    .testimonial i.fa-quote-right {
      position: absolute;
      bottom: 30px;
      right: 40px;
      font-size: 4rem;
      color: var(--gold-sand);
      opacity: 0.2;
    }

    /* ---------- CTA (modern islamic) ---------- */
    .cta-modern {
      background: linear-gradient(105deg, var(--deep-sage) 0%, #3F5F4E 100%);
      border-radius: 50px 50px 50px 0;
      padding: 4.5rem 4rem;
      color: white;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      margin: 6rem 0 3rem;
      box-shadow: 0 30px 40px -20px rgba(47,75,60,0.5);
    }
    .cta-modern h2 {
      font-size: 2.6rem;
      color: white;
      margin-bottom: 0.6rem;
    }
    .cta-modern p {
      opacity: 0.9;
    }

    /* ---------- FOOTER with elegant touch ---------- */
    footer {
      padding: 3.5rem 2rem 2.5rem;
      border-top: 1px solid rgba(201,168,107,0.2);
      margin-top: 3rem;
      color: var(--taupe-stone);
    }
    .footer-grid {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    .footer-logo i {
      font-size: 2rem;
      color: var(--deep-sage);
    }
    .footer-links a {
      color: var(--taupe-stone);
      text-decoration: none;
      margin-left: 2rem;
    }
    .footer-links a:hover {
      color: var(--deep-sage);
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 900px) {
      .hero, .cta-modern { flex-direction: column; }
      .nav-links { display: none; }
      .hero-content h1 { font-size: 3rem; }
      .timeline::before { display: none; }
      .step { flex: 0 1 100%; margin-bottom: 1.5rem; }
    }
  </style>
</head>
<body class="islamic-pattern-bg">

  <!-- MODERN ISLAMIC NAVBAR -->
  <nav class="navbar">
    <div class="logo">
      <i class="fas fa-mosque"></i>
      <span>HADRAHIN</span>
    </div>
    <div class="nav-links">
      <a href="#fitur">Fitur</a>
      <a href="#cara-kerja">Alur</a>
      <a href="#testimoni">Testimoni</a>
      <a href="#download" class="btn-outline" style="padding: 0.6rem 1.8rem;">Download</a>
    </div>
  </nav>

  <div class="container">
    <!-- HERO –– PROFESSIONAL & INVITING -->
    <section class="hero">
      <div class="hero-content" data-aos="fade-right" data-aos-duration="800">
        <span class="hero-badge"><i class="fas fa-star" style="color: var(--gold-sand); margin-right: 6px;"></i> Manajemen Hadrah Modern</span>
        <h1>
          Harmoni <span class="hero-highlight">Tim Hadrah</span><br> dalam Satu Genggaman
        </h1>
        <p class="hero-desc">
          HADRAHIN — platform manajemen tim hadrah yang sudah mencakup modul Jadwal Latihan, Absensi, Inventaris Alat, Acara, Dresscode, Keuangan, dan Manajemen User. Mudah digunakan oleh pengurus dan pembina.
        </p>
        <div class="hero-buttons">
          <a href="auth/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Masuk</a>
          <a href="#fitur" class="btn btn-outline"><i class="fas fa-video" style="margin-right: 8px;"></i> Lihat Modul</a>
        </div>
        <p style="margin-top: 2rem; font-size: 0.95rem; color: var(--taupe-stone);">
          <i class="fas fa-check-circle" style="color: var(--deep-sage);"></i> 30 hari uji coba, tanpa kartu kredit.
        </p>
      </div>
      <div class="hero-image" data-aos="fade-left" data-aos-duration="900">
        <div class="mockup-frame">
          <div class="mockup-header">
            <span><i class="fas fa-music"></i> HADRAHIN v2.0</span>
            <i class="fas fa-wifi" style="color: var(--gold-sand);"></i>
          </div>
          <div class="mockup-content">
            <div class="mockup-row">
              <span><i class="fas fa-calendar-alt"></i> Jadwal Latihan</span>
              <span style="font-weight: 600;">Ahad, 19.30</span>
            </div>
            <div class="mockup-row">
              <span><i class="fas fa-drum"></i> Inventaris Rebana</span>
              <span style="color: var(--deep-sage);">12/14 siap</span>
            </div>
            <div class="mockup-row">
              <span><i class="fas fa-th-large"></i> 7 Modul</span>
              <span style="color: var(--deep-sage); font-weight: 600;">Absensi · Jadwal · Alat</span>
            </div>
            <div class="mockup-footer">
              <i class="fas fa-play"></i> Qasidah - Ya Rasulallah
            </div>
          </div>
        </div>
        <!-- subtle arabesque decoration -->
        <div style="text-align: right; margin-top: 0.8rem; font-size: 1.8rem; color: var(--gold-sand); opacity: 0.2;">﷽</div>
      </div>
    </section>

    <!-- ARCH DIVIDER (islamic arch motif) -->
    <div class="arch-divider"></div>

    <!-- FEATURES SECTION dengan nuansa islami -->
    <section id="fitur" style="padding: 3rem 0;">
      <div class="section-title" data-aos="fade-up">
        <h2>Keistimewaan <span style="color: var(--gold-sand);">HADRAHIN</span></h2>
        <p>Manajemen tim hadrah profesional, dengan nilai-nilai islami dan kemudahan digital.</p>
      </div>

      <div class="features-grid">
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="100">
          <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
          <h3>Jadwal Latihan</h3>
          <p>Buat dan kelola jadwal latihan dengan pengingat untuk anggota dan penugasan setlist.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="150">
          <div class="feature-icon"><i class="fas fa-check-circle"></i></div>
          <h3>Absensi Latihan</h3>
          <p>Catat kehadiran anggota, rekap otomatis, dan lihat riwayat keaktifan tim.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="200">
          <div class="feature-icon"><i class="fas fa-box-open"></i></div>
          <h3>Inventaris Alat</h3>
          <p>Kelola rebana, sound, dan perlengkapan; catat peminjaman dan kondisi barang.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="250">
          <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
          <h3>Acara & Dokumentasi</h3>
          <p>Atur acara, kumpulkan dokumentasi, dan arsipkan hasil penampilan dengan mudah.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="300">
          <div class="feature-icon"><i class="fas fa-tshirt"></i></div>
          <h3>Dresscode</h3>
          <p>Standarisasi pakaian tampil, pantau ketersediaan, dan catat perubahan dresscode.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="350">
          <div class="feature-icon"><i class="fas fa-wallet"></i></div>
          <h3>Keuangan & User</h3>
          <p>Rekap kas, laporan sederhana, dan manajemen akun pengguna serta peran (role).</p>
        </div>
      </div>
    </section>

    <!-- ISLAMIC VALUES ELEGAN -->
    <div class="islamic-values" data-aos="fade-up">
      <div class="section-title" style="margin-bottom: 2rem;">
        <h2 style="color: var(--deep-sage);">Nilai <span style="color: var(--gold-sand);">Islami</span></h2>
      </div>
      <div class="values-grid">
        <div class="value-item">
          <i class="fas fa-hand-holding-heart"></i>
          <h4>Ta'awun</h4>
          <p style="color: var(--ink-charcoal);">Kolaborasi tim penuh keberkahan</p>
        </div>
        <div class="value-item">
          <i class="fas fa-clock"></i>
          <h4>Waktu</h4>
          <p>Ketepatan ibadah & latihan</p>
        </div>
        <div class="value-item">
          <i class="fas fa-feather"></i>
          <h4>Adab</h4>
          <p>Etika dalam setiap nada</p>
        </div>
        <div class="value-item">
          <i class="fas fa-tree"></i>
          <h4>Istiqamah</h4>
          <p>Konsisten berkarya</p>
        </div>
      </div>
    </div>

    <!-- CARA KERJA (Timeline / alur profesional) -->
    <section id="cara-kerja" style="margin: 5rem 0;" data-aos="fade-up">
      <div class="section-title">
        <h2>Alur <span style="color: var(--gold-sand);">Cerdas</span></h2>
        <p>Dari daftar hingga harmoni, semua terstruktur elegan.</p>
      </div>
      <div class="timeline">
        <div class="step" data-aos="flip-left" data-aos-delay="50">
          <div class="step-num">01</div>
          <h3 style="font-size: 1.9rem; margin: 0.5rem 0 1rem;">Undang Tim</h3>
          <p style="color: #3B4A43;">Tambah anggota via tautan atau kode masjid. Role khusus: pelatih, manajer, anggota.</p>
        </div>
        <div class="step" data-aos="flip-left" data-aos-delay="150">
          <div class="step-num">02</div>
          <h3 style="font-size: 1.9rem; margin: 0.5rem 0 1rem;">Atur & Latih</h3>
          <p>Jadwal rutin, setlist sholawat, dan penugasan alat. Semua notifikasi realtime.</p>
        </div>
        <div class="step" data-aos="flip-left" data-aos-delay="250">
          <div class="step-num">03</div>
          <h3 style="font-size: 1.9rem; margin: 0.5rem 0 1rem;">Tampil Sempurna</h3>
          <p>Monitoring kesiapan, dokumentasi, dan evaluasi pasca acara dalam satu dashboard.</p>
        </div>
      </div>
    </section>

    <!-- TESTIMONIAL (kredibilitas profesional) -->
    <section id="testimoni" class="testimonial" data-aos="fade-right">
      <i class="fas fa-quote-right"></i>
      <div style="display: flex; gap: 1.8rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 0 0 70px;">
          <div style="background: var(--deep-sage); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.2rem; font-family: var(--font-serif);">H</div>
        </div>
        <div style="flex: 1;">
          <p style="font-size: 1.35rem; font-weight: 450; color: #2E3B36;">“HADRAHIN mengubah cara kami mengelola grup hadrah. Sekarang semua rapi, dari inventaris rebana hingga jadwal iqomat. Sangat profesional dan desainnya membuat betah.”</p>
          <div style="margin-top: 1.5rem;">
            <strong style="font-size: 1.2rem;">KH. Ahmad Fauzi</strong>
            <span style="color: var(--taupe-stone);"> — Pembina Majelis Sholawat Nusantara</span>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA MODERN – Download / Akses -->
    <section id="download" class="cta-modern" data-aos="zoom-in-up">
      <div>
        <h2>Siap mengelola tim hadrah <br>dengan lebih bermartabat?</h2>
        <p style="font-size: 1.2rem; margin-top: 0.8rem;">Gabung 120+ majelis hadrah. Dapatkan diskon 40% untuk 3 bulan pertama.</p>
        <div style="display: flex; gap: 1.2rem; margin-top: 2rem;">
          <a href="#" class="btn btn-gold" style="background: white; color: var(--deep-sage); border: none; font-weight: 700;"><i class="fab fa-google-play"></i> Google Play</a>
          <a href="#" class="btn" style="background: rgba(255,255,255,0.15); color: white; border: 1.5px solid rgba(255,255,255,0.5);"><i class="fab fa-apple"></i> App Store</a>
        </div>
      </div>
      <div style="font-size: 5rem; opacity: 0.2; color: white; rotate: 10deg;">
        <i class="fas fa-mosque"></i>
      </div>
    </section>

    <!-- FOOTER elegan islami -->
    <footer>
      <div class="footer-grid">
        <div class="footer-logo">
          <i class="fas fa-mosque"></i>
          <span style="font-family: var(--font-serif); font-size: 2rem; color: var(--deep-sage); margin-left: 8px;">HADRAHIN</span>
          <p style="margin-top: 1rem; max-width: 300px;">© 2025 Hadrahin. <br>Sistem manajemen tim hadrah modern yang memberkati.</p>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
          <span style="font-weight: 700; color: var(--deep-sage);">Produk</span>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Fitur</a>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Harga</a>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Unduh</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
          <span style="font-weight: 700; color: var(--deep-sage);">Sumber</span>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Blog</a>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Panduan</a>
          <a href="#" style="text-decoration: none; color: var(--taupe-stone);">Mitra masjid</a>
        </div>
      </div>
      <div style="text-align: center; margin-top: 3rem; padding-top: 1.8rem; border-top: 1px solid rgba(201,168,107,0.2);">
        <span><i class="fas fa-star" style="color: var(--gold-sand);"></i>  “Dan tolong-menolonglah dalam kebaikan” (QS. Al-Maidah:2) </span>
      </div>
    </footer>

  </div> <!-- end container -->

  <!-- AOS init -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 800,
      once: true,
      offset: 80
    });
  </script>

  <!-- smooth additional detail: ensure all interactions elegant -->
  <script>
    (function() {
      // subtle hover effect on cards, native elegance
      console.log('HADRAHIN – landingpage islami profesional')
    })();
  </script>
</body>
</html>