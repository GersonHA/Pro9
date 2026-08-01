<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  {{-- ─── SEO BÁSICO (por página) ──────────────────────────────────── --}}
  <title>@yield('title', 'Sistema de Facturación Electrónica para Perú | Facturoom')</title>
  <meta name="description" content="@yield('description', 'Sistema de facturación electrónica para pymes en Perú. Emite facturas, boletas y guías GRE a SUNAT en segundos. POS, inventario y 30+ módulos. 60 días gratis.')">
  <meta name="keywords" content="@yield('keywords', 'facturación electrónica Perú, sistema de facturación electrónica, facturar por internet Perú, sistema ERP pequeñas empresas, boletas electrónicas Perú, guía de remisión electrónica')">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{{ url()->current() }}">

  {{-- ─── OPEN GRAPH (WhatsApp, Facebook, LinkedIn) ─────────────────── --}}
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:title" content="@yield('og_title', 'Facturoom — Sistema de Facturación Electrónica para Perú')">
  <meta property="og:description" content="@yield('og_description', 'Emite facturas, boletas y guías GRE a SUNAT en segundos. POS, inventario, compras y 30+ módulos. Prueba gratis 60 días sin tarjeta de crédito.')">
  <meta property="og:image" content="{{ asset('images/facturoom-og-banner.png') }}?v=3">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:alt" content="Facturoom — Facturación electrónica a SUNAT en segundos">
  <meta property="og:locale" content="es_PE">
  <meta property="og:site_name" content="Facturoom">

  {{-- ─── TWITTER CARD ──────────────────────────────────────────────── --}}
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="@yield('og_title', 'Facturoom — Sistema de Facturación Electrónica para Perú')">
  <meta name="twitter:description" content="@yield('og_description', 'Emite facturas y boletas a SUNAT en segundos. 30+ módulos, 60 días gratis.')">
  <meta name="twitter:image" content="{{ asset('images/facturoom-og-banner.png') }}?v=3">
  <meta name="twitter:image:alt" content="Facturoom — Facturación electrónica a SUNAT en segundos">

  @stack('head')

  {{-- ─── FAVICON ───────────────────────────────────────────────────── --}}
  <link rel="icon" type="image/png" href="{{ asset('images/facturoom-logo.png') }}">

  {{-- ─── FUENTES ───────────────────────────────────────────────────── --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap&font-display=swap" rel="stylesheet">
<style>
/* ─── TOKENS (paleta original de la landing) ─────────────────────── */
:root{
  --navy:   #0F172A;
  --navy-2: #1E293B;
  --navy-3: #334155;
  --amber:  #F59E0B;
  --amber-d:#D97706;
  --amber-p:#FFFBEB;
  --amber-l:#FDE68A;
  --paper:  #F8FAFC;
  --paper-2:#F1F5F9;
  --carbon: #0F172A;
  --body:   #475569;
  --border: #E2E8F0;
  --mid:    #94A3B8;
  --green:  #059669;
  --radius: 14px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Outfit',sans-serif;background:var(--paper);color:var(--carbon);-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
::selection{background:var(--amber);color:#fff}

/* ─── KEYFRAMES ───────────────────────────────────────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeRight{from{opacity:0;transform:translateX(-28px)}to{opacity:1;transform:translateX(0)}}
@keyframes pulseRing{0%{transform:scale(1);opacity:.6}70%{transform:scale(1.55);opacity:0}100%{transform:scale(1.55);opacity:0}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}

/* ─── UTILITY ─────────────────────────────────────────────────────── */
.container{max-width:1200px;margin:0 auto;padding:0 24px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:9px;font-family:'Outfit',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;border:none;transition:transform .22s cubic-bezier(.16,1,.3,1),background .22s,border-color .22s,color .22s;letter-spacing:.01em;line-height:1}
.btn:active{transform:scale(.97)}
.btn-amber{background:var(--amber);color:#fff}
.btn-amber:hover{background:var(--amber-d);transform:translateY(-2px)}
.btn-ghost{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.22)}
.btn-ghost:hover{border-color:var(--amber);color:var(--amber);transform:translateY(-2px)}
.btn-ghost-dark{background:transparent;color:var(--carbon);border:1.5px solid var(--border)}
.btn-ghost-dark:hover{border-color:var(--amber);color:var(--amber)}
.btn-navy{background:var(--navy);color:#fff;border:2px solid var(--navy)}
.btn-navy:hover{background:var(--navy-2);transform:translateY(-2px)}

.section-eye{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--amber);margin-bottom:14px}
.section-eye .dot{width:7px;height:7px;border-radius:50%;background:var(--amber);position:relative}
.section-eye .dot::after{content:'';position:absolute;inset:-3px;border-radius:50%;border:1.5px solid var(--amber);animation:pulseRing 2s infinite}
.section-title{font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:900;color:var(--carbon);line-height:1.1;letter-spacing:-.02em;margin-bottom:12px}
.section-sub{font-size:1rem;color:var(--body);max-width:60ch;line-height:1.7}
.section-header{margin-bottom:56px}

/* reveal */
.reveal{opacity:0}
.reveal.in-view{animation:fadeUp .6s cubic-bezier(.16,1,.3,1) both}

/* ─── NAV ─────────────────────────────────────────────────────────── */
#nav{position:fixed;top:0;left:0;right:0;z-index:100;background:#fff;border-bottom:1px solid var(--border);transition:background .3s,box-shadow .3s}
#nav.scrolled{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);box-shadow:0 2px 16px rgba(15,23,42,.08)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px}
.nav-logo{display:flex;align-items:center;gap:10px}
.nav-logo img{height:40px;width:auto}
.nav-logo-text{display:flex;align-items:baseline;font-size:1.35rem;font-weight:800;line-height:1}
.nav-logo-text .factu{color:var(--carbon)}
.nav-logo-text .room{color:var(--amber)}
.nav-links{display:flex;align-items:center;gap:28px}
.nav-links a{color:var(--body);font-size:.9rem;font-weight:600;transition:color .2s;position:relative}
.nav-links a:hover,.nav-links a.active{color:var(--amber)}
.nav-links a.active::after{content:'';position:absolute;left:0;right:0;bottom:-21px;height:2px;background:var(--amber)}
.nav-cta{display:flex;align-items:center;gap:12px}
.hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:6px;background:none;border:none}
.hamburger span{display:block;width:22px;height:2px;background:var(--carbon);border-radius:2px;transition:.3s}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* mobile menu — z-index por encima del WhatsApp flotante (200), si no el botón
   verde queda sobre el menú abierto y los taps al fondo caen en WhatsApp. */
#mobile-menu{position:fixed;inset:64px 0 0;z-index:201;background:#fff;transform:translateY(-12px);opacity:0;pointer-events:none;transition:opacity .28s,transform .28s;display:flex;flex-direction:column;padding:28px 24px;gap:4px}
#mobile-menu.open{opacity:1;transform:translateY(0);pointer-events:auto}
#mobile-menu a{font-size:1.12rem;font-weight:700;color:var(--carbon);padding:16px 4px;border-bottom:1px solid var(--border)}
#mobile-menu a.active{color:var(--amber)}
#mobile-menu .btn{margin-top:20px;justify-content:center}

main{padding-top:64px}

/* ─── HELPERS REUTILIZABLES ───────────────────────────────────────── */
/* tarjeta clara estándar */
.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius)}
/* "mockup" oscuro (mismo look que el dash-card del home original) */
.mock{background:var(--navy-2);border:1px solid var(--navy-3);border-radius:18px;color:#fff;position:relative;overflow:hidden;box-shadow:0 24px 50px -24px rgba(15,23,42,.45)}
.mock::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(245,158,11,.4),transparent)}

/* ─── INNER PAGE HERO / SECTIONS (compartido) ─────────────────────── */
.page-hero{position:relative;overflow:hidden;padding:88px 0 72px;background:var(--navy)}
.page-hero-glow{position:absolute;top:-120px;right:-90px;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,.13) 0%,transparent 68%);pointer-events:none}
.page-hero-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(245,158,11,.08) 1px,transparent 1px);background-size:28px 28px;mask-image:radial-gradient(ellipse 70% 80% at 50% 0%,#000 30%,transparent 100%);-webkit-mask-image:radial-gradient(ellipse 70% 80% at 50% 0%,#000 30%,transparent 100%);pointer-events:none}
.page-hero .inner{position:relative;max-width:720px}
.page-hero .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.76rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--amber);margin-bottom:18px}
.page-hero h1{font-size:clamp(2.2rem,4.8vw,3.4rem);font-weight:900;color:#fff;letter-spacing:-.02em;line-height:1.06;margin-bottom:18px}
.page-hero h1 .accent{color:var(--amber)}
.page-hero .lead{font-size:1.08rem;color:var(--mid);max-width:62ch;line-height:1.7}
.page-section{padding:88px 0;background:var(--paper)}
.page-section.alt{background:var(--paper-2)}
.cta-band{background:var(--navy);border-top:1px solid var(--navy-3);padding:80px 0;text-align:center}
.cta-band h2{font-size:clamp(1.7rem,3.2vw,2.4rem);font-weight:900;color:#fff;letter-spacing:-.02em;margin-bottom:14px}
.cta-band p{font-size:1.02rem;color:var(--mid);max-width:52ch;margin:0 auto 28px;line-height:1.6}
.cta-band .actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}

/* ─── FOOTER ──────────────────────────────────────────────────────── */
#footer{background:var(--navy);border-top:1px solid var(--navy-3);padding:56px 0 32px}
.footer-top{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:40px;margin-bottom:40px}
.footer-brand .nav-logo-text .factu{color:#fff}
.footer-brand p{font-size:.9rem;color:var(--mid);line-height:1.7;max-width:38ch;margin-top:16px}
.footer-stats{display:flex;gap:0;margin-top:22px}
.footer-stat-item{padding-right:28px;margin-right:28px;border-right:1px solid var(--navy-3)}
.footer-stat-item:last-child{border-right:none;margin-right:0;padding-right:0}
.footer-stat-val{font-size:1.5rem;font-weight:900;color:var(--amber);letter-spacing:-.03em;line-height:1;margin-bottom:4px;font-variant-numeric:tabular-nums}
.footer-stat-label{font-size:.72rem;color:rgba(255,255,255,.45);font-weight:600;text-transform:uppercase;letter-spacing:.07em}
.footer-col h4{font-size:.78rem;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:.1em;margin-bottom:18px}
.footer-link{display:flex;align-items:center;gap:9px;font-size:.9rem;color:var(--mid);font-weight:600;transition:color .2s;margin-bottom:12px}
.footer-link:hover{color:var(--amber)}
.footer-link svg{color:var(--amber);flex-shrink:0}
.footer-bottom{border-top:1px solid var(--navy-3);padding-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.footer-copy{font-size:.78rem;color:rgba(255,255,255,.32)}

/* ─── FLOATING WA ─────────────────────────────────────────────────── */
#wa-float{position:fixed;bottom:24px;right:24px;z-index:200;width:56px;height:56px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(37,211,102,.35);transition:transform .22s cubic-bezier(.16,1,.3,1),box-shadow .22s}
#wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:pulseRing 2.2s infinite;z-index:-1}
#wa-float:hover{transform:scale(1.1);box-shadow:0 8px 28px rgba(37,211,102,.45)}
/* Con el menú móvil abierto se oculta el flotante para que no intercepte los taps. */
body.menu-open #wa-float{opacity:0;visibility:hidden;pointer-events:none}

@stack('styles')

/* ─── MOBILE BASE ─────────────────────────────────────────────────── */
@media(max-width:860px){
  .nav-links,.nav-cta .btn{display:none}
  .hamburger{display:flex}
  .footer-top{grid-template-columns:1fr;gap:32px}
}
</style>
</head>
<body>

{{-- ─── FLOATING WA ─────────────────────────────────────────────── --}}
<a id="wa-float" href="https://wa.me/51981524571?text=Hola%2C%20quiero%20información%20sobre%20Facturoom" target="_blank" rel="noopener" aria-label="Contactar por WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.8 9.8 0 0 1-5.012-1.374l-.36-.213-3.76.89.952-3.659-.234-.376A9.787 9.787 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
  </svg>
</a>

{{-- ─── NAV ─────────────────────────────────────────────────────── --}}
@php $nav = $nav ?? ''; @endphp
<nav id="nav">
  <div class="container nav-inner">
    <a href="{{ route('landing') }}" class="nav-logo">
      <img src="{{ asset('images/facturoom-logo.png') }}" alt="Facturoom — Sistema de facturación electrónica">
      <div class="nav-logo-text"><span class="factu">Factu</span><span class="room">room</span></div>
    </a>
    <div class="nav-links">
      <a href="{{ route('landing') }}" class="{{ $nav === 'home' ? 'active' : '' }}">Inicio</a>
      <a href="{{ route('landing.funcionalidades') }}" class="{{ $nav === 'funcionalidades' ? 'active' : '' }}">Funcionalidades</a>
      <a href="{{ route('landing.precios') }}" class="{{ $nav === 'precios' ? 'active' : '' }}">Precios</a>
      <a href="{{ route('landing.nosotros') }}" class="{{ $nav === 'nosotros' ? 'active' : '' }}">Nosotros</a>
      <a href="{{ route('landing.contacto') }}" class="{{ $nav === 'contacto' ? 'active' : '' }}">Contacto</a>
    </div>
    <div class="nav-cta">
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">Prueba gratis</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

{{-- ─── MOBILE MENU ─────────────────────────────────────────────── --}}
<div id="mobile-menu">
  <a href="{{ route('landing') }}" class="{{ $nav === 'home' ? 'active' : '' }}">Inicio</a>
  <a href="{{ route('landing.funcionalidades') }}" class="{{ $nav === 'funcionalidades' ? 'active' : '' }}">Funcionalidades</a>
  <a href="{{ route('landing.precios') }}" class="{{ $nav === 'precios' ? 'active' : '' }}">Precios</a>
  <a href="{{ route('landing.nosotros') }}" class="{{ $nav === 'nosotros' ? 'active' : '' }}">Nosotros</a>
  <a href="{{ route('landing.contacto') }}" class="{{ $nav === 'contacto' ? 'active' : '' }}">Contacto</a>
  <a href="{{ route('landing.precios') }}" class="btn btn-amber">Prueba gratis 60 días</a>
</div>

<main>
  @yield('content')
</main>

{{-- ─── FOOTER ─────────────────────────────────────────────────────── --}}
<footer id="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="{{ route('landing') }}" class="nav-logo">
          <img src="{{ asset('images/facturoom-logo.png') }}" alt="Facturoom" style="height:36px;width:auto;background:#fff;border-radius:8px;padding:5px 8px">
          <div class="nav-logo-text"><span class="factu">Factu</span><span class="room">room</span></div>
        </a>
        <p>Sistema de facturación electrónica hecho para el comercio peruano. Factura, controla tu inventario y vende, todo conectado a SUNAT.</p>
        <div class="footer-stats">
          <div class="footer-stat-item"><div class="footer-stat-val">50+</div><div class="footer-stat-label">Negocios activos</div></div>
          <div class="footer-stat-item"><div class="footer-stat-val">60</div><div class="footer-stat-label">Días gratis</div></div>
          <div class="footer-stat-item"><div class="footer-stat-val">SUNAT</div><div class="footer-stat-label">Integración oficial</div></div>
        </div>
      </div>
      <div class="footer-col">
        <h4>Producto</h4>
        <a href="{{ route('landing.funcionalidades') }}" class="footer-link">Funcionalidades</a>
        <a href="{{ route('landing.precios') }}" class="footer-link">Precios y planes</a>
        <a href="{{ route('landing.nosotros') }}" class="footer-link">Nosotros</a>
        <a href="{{ route('landing.contacto') }}" class="footer-link">Contacto y FAQ</a>
      </div>
      <div class="footer-col">
        <h4>Hablemos</h4>
        <a href="https://wa.me/51981524571?text=Hola%2C%20quiero%20información%20sobre%20Facturoom" target="_blank" rel="noopener" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
          +51 981 524 571
        </a>
        <a href="mailto:facturoom@gmail.com" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          Escríbenos por correo
        </a>
        <a href="https://www.facebook.com/facturoom" target="_blank" rel="noopener" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8v8.44C19.61 23.08 24 18.09 24 12.07z"/></svg>
          Síguenos en Facebook
        </a>
        <a href="{{ route('landing.precios') }}" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          Empezar prueba gratis
        </a>
      </div>
    </div>
    <div class="footer-bottom">
      <span class="footer-copy">&copy; {{ date('Y') }} Facturoom. Todos los derechos reservados.</span>
      <span class="footer-copy">Hecho para el comercio peruano · Integración oficial SUNAT</span>
    </div>
  </div>
</footer>

<script>
/* ─── NAV SCROLL ─────────────────────────────────────────────────── */
var nav = document.getElementById('nav');
window.addEventListener('scroll', function() {
  nav.classList.toggle('scrolled', window.scrollY > 20);
}, {passive: true});

/* ─── MOBILE MENU ────────────────────────────────────────────────── */
var hamburger = document.getElementById('hamburger');
var mobileMenu = document.getElementById('mobile-menu');
hamburger.addEventListener('click', function() {
  var open = mobileMenu.classList.toggle('open');
  hamburger.classList.toggle('open', open);
  document.body.classList.toggle('menu-open', open);
  document.body.style.overflow = open ? 'hidden' : '';
});
mobileMenu.querySelectorAll('a').forEach(function(a){
  a.addEventListener('click', function(){
    mobileMenu.classList.remove('open');
    hamburger.classList.remove('open');
    document.body.classList.remove('menu-open');
    document.body.style.overflow = '';
  });
});

/* ─── REVEAL ON SCROLL ───────────────────────────────────────────── */
var revealEls = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window) {
  var revealObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        revealObs.unobserve(entry.target);
      }
    });
  }, {threshold: 0.12});
  revealEls.forEach(function(el) { revealObs.observe(el); });
} else {
  revealEls.forEach(function(el) { el.classList.add('in-view'); });
}
</script>
@stack('scripts')
</body>
</html>
