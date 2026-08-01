<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- ─── SEO BÁSICO ───────────────────────────────────────────────── -->
  <title>Sistema de Facturación Electrónica para Perú | Facturoom</title>
  <meta name="description" content="Sistema de facturación electrónica para pymes en Perú. Emite facturas, boletas y guías GRE a SUNAT en segundos. POS, inventario y 30+ módulos. 60 días gratis.">
  <meta name="keywords" content="facturación electrónica Perú, sistema de facturación electrónica, facturar por internet Perú, sistema ERP pequeñas empresas, boletas electrónicas Perú, guía de remisión electrónica">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{{ url('/') }}">

  <!-- ─── OPEN GRAPH (WhatsApp, Facebook, LinkedIn) ─────────────────── -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/') }}">
  <meta property="og:title" content="Facturoom — Sistema de Facturación Electrónica para Perú">
  <meta property="og:description" content="Emite facturas, boletas y guías GRE a SUNAT en segundos. POS, inventario, compras y 30+ módulos. Prueba gratis 60 días sin tarjeta de crédito.">
  <meta property="og:image" content="{{ asset('images/facturoom-og-banner.png') }}?v=3">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:alt" content="Facturoom — Facturación electrónica a SUNAT en segundos">
  <meta property="og:locale" content="es_PE">
  <meta property="og:site_name" content="Facturoom">

  <!-- ─── TWITTER CARD ──────────────────────────────────────────────── -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Facturoom — Sistema de Facturación Electrónica para Perú">
  <meta name="twitter:description" content="Emite facturas y boletas a SUNAT en segundos. 30+ módulos, 60 días gratis.">
  <meta name="twitter:image" content="{{ asset('images/facturoom-og-banner.png') }}?v=3">
  <meta name="twitter:image:alt" content="Facturoom — Facturación electrónica a SUNAT en segundos">

  <!-- ─── JSON-LD SCHEMA (SoftwareApplication) ─────────────────────── -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "Facturoom",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "url": "{{ url('/') }}",
    "description": "Sistema de facturación electrónica para pequeñas y medianas empresas en Perú. Emite facturas, boletas, notas de crédito y guías de remisión electrónica (GRE) a SUNAT. Incluye POS, inventario, compras, reportes y más de 30 módulos.",
    "inLanguage": "es-PE",
    "offers": [
      {
        "@type": "Offer",
        "name": "Plan Basic",
        "price": "29.90",
        "priceCurrency": "PEN",
        "priceSpecification": { "@type": "UnitPriceSpecification", "billingDuration": "P1M" }
      },
      {
        "@type": "Offer",
        "name": "Plan Standard",
        "price": "49.90",
        "priceCurrency": "PEN",
        "priceSpecification": { "@type": "UnitPriceSpecification", "billingDuration": "P1M" }
      },
      {
        "@type": "Offer",
        "name": "Plan Pro",
        "price": "69.90",
        "priceCurrency": "PEN",
        "priceSpecification": { "@type": "UnitPriceSpecification", "billingDuration": "P1M" }
      },
      {
        "@type": "Offer",
        "name": "Plan Ultra",
        "price": "99.90",
        "priceCurrency": "PEN",
        "priceSpecification": { "@type": "UnitPriceSpecification", "billingDuration": "P1M" }
      }
    ],
    "featureList": [
      "Facturación electrónica a SUNAT",
      "Boletas de venta electrónicas",
      "Guía de Remisión Electrónica (GRE)",
      "Sistema POS",
      "Inventario y Kardex",
      "Control de compras y proveedores",
      "36 tipos de reportes"
    ],
    "publisher": {
      "@type": "Organization",
      "name": "Facturoom",
      "url": "{{ url('/') }}",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+51-981-524-571",
        "contactType": "customer support",
        "availableLanguage": "Spanish"
      }
    }
  }
  </script>

  <!-- ─── FAVICON ───────────────────────────────────────────────────── -->
  <link rel="icon" type="image/png" href="{{ asset('images/facturoom-logo.png') }}">

  <!-- ─── FUENTES ───────────────────────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap&font-display=swap" rel="stylesheet">
<style>
/* ─── TOKENS ─────────────────────────────────────────────────────── */
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
body{font-family:'Outfit',sans-serif;background:var(--paper);color:var(--carbon);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}

/* ─── KEYFRAMES ───────────────────────────────────────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeRight{from{opacity:0;transform:translateX(-28px)}to{opacity:1;transform:translateX(0)}}
@keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes pulseRing{0%{transform:scale(1);opacity:.6}70%{transform:scale(1.55);opacity:0}100%{transform:scale(1.55);opacity:0}}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@keyframes scaleIn{from{opacity:0;transform:scale(.72)}to{opacity:1;transform:scale(1)}}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}

/* ─── UTILITY ─────────────────────────────────────────────────────── */
.container{max-width:1200px;margin:0 auto;padding:0 24px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:9px;font-family:'Outfit',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;border:none;transition:all .22s cubic-bezier(.16,1,.3,1);letter-spacing:.01em}
.btn:active{transform:scale(.97)}
.btn-amber{background:var(--amber);color:#fff}
.btn-amber:hover{background:var(--amber-d);transform:translateY(-2px)}
.btn-ghost{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.22)}
.btn-ghost:hover{border-color:var(--amber);color:var(--amber);transform:translateY(-2px)}
.btn-ghost-dark{background:transparent;color:var(--carbon);border:1.5px solid var(--border)}
.btn-ghost-dark:hover{border-color:var(--amber);color:var(--amber)}
.section-eye{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--amber);margin-bottom:14px}
.section-eye .dot{width:7px;height:7px;border-radius:50%;background:var(--amber);position:relative}
.section-eye .dot::after{content:'';position:absolute;inset:-3px;border-radius:50%;border:1.5px solid var(--amber);animation:pulseRing 2s infinite}
.in-view{animation:fadeUp .55s cubic-bezier(.16,1,.3,1) both}

/* ─── NAV ─────────────────────────────────────────────────────────── */
#nav{position:fixed;top:0;left:0;right:0;z-index:100;background:#fff;border-bottom:1px solid var(--border);transition:background .3s,backdrop-filter .3s,box-shadow .3s}
#nav.scrolled{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);box-shadow:0 2px 16px rgba(15,23,42,.08)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px}
.nav-logo{display:flex;align-items:center;gap:10px}
.nav-logo-text{display:flex;align-items:baseline;gap:0;font-size:1.35rem;font-weight:800;line-height:1}
.nav-logo-text .factu{color:var(--carbon)}
.nav-logo-text .room{color:var(--amber)}
.nav-links{display:flex;align-items:center;gap:28px}
.nav-links a{color:var(--body);font-size:.9rem;font-weight:600;transition:color .2s}
.nav-links a:hover{color:var(--amber)}
.nav-cta{display:flex;align-items:center;gap:12px}
.hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:6px}
.hamburger span{display:block;width:22px;height:2px;background:var(--carbon);border-radius:2px;transition:.3s}

/* ─── HERO ────────────────────────────────────────────────────────── */
#hero{background:var(--navy);min-height:100dvh;display:flex;align-items:center;padding-top:64px;position:relative;overflow:hidden}
.hero-bg{position:absolute;inset:0;pointer-events:none}
.hero-bg-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(245,158,11,.08) 1px,transparent 1px);background-size:28px 28px}
.hero-bg-glow{position:absolute;top:-80px;right:-80px;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,.12) 0%,transparent 70%)}
.hero-inner{display:grid;grid-template-columns:55fr 45fr;gap:64px;align-items:center;padding:80px 0 96px}
.hero-left{animation:fadeRight .65s cubic-bezier(.16,1,.3,1) both}
.hero-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);border-radius:99px;padding:6px 14px;font-size:.78rem;font-weight:700;color:var(--amber);letter-spacing:.06em;text-transform:uppercase;margin-bottom:22px}
.hero-pill .dot{width:6px;height:6px;border-radius:50%;background:var(--amber);animation:pulseRing 2s infinite}
.hero-h1{font-size:clamp(2.4rem,5vw,3.8rem);font-weight:900;line-height:1.05;color:#fff;margin-bottom:20px;letter-spacing:-.02em}
.hero-h1 .accent{color:var(--amber)}
.hero-sub{font-size:1.05rem;color:var(--mid);line-height:1.7;max-width:480px;margin-bottom:32px}
.hero-sub strong{color:#fff;font-weight:600}
.hero-ctas{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:40px}
.hero-badges{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.hero-badge{display:flex;align-items:center;gap:6px;font-size:.82rem;color:var(--mid);font-weight:600}
.hero-badge svg{color:var(--amber)}

/* ─── DASHBOARD PREVIEW ───────────────────────────────────────────── */
.hero-right{animation:fadeUp .75s .2s cubic-bezier(.16,1,.3,1) both}
.dash-card{background:var(--navy-2);border:1px solid var(--navy-3);border-radius:18px;padding:24px;position:relative;overflow:hidden}
.dash-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(245,158,11,.4),transparent)}
.dash-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.dash-title{font-size:.82rem;font-weight:700;color:var(--mid);text-transform:uppercase;letter-spacing:.08em}
.dash-live{display:flex;align-items:center;gap:5px;font-size:.75rem;font-weight:700;color:var(--green)}
.dash-live-dot{width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulseRing 1.8s infinite}
.dash-main-val{font-size:2.6rem;font-weight:900;color:#fff;letter-spacing:-.03em;margin-bottom:4px;font-variant-numeric:tabular-nums}
.dash-main-sub{font-size:.8rem;color:var(--mid);margin-bottom:22px}
.dash-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.dash-mini{background:rgba(255,255,255,.04);border:1px solid var(--navy-3);border-radius:10px;padding:12px}
.dash-mini-label{font-size:.7rem;color:var(--mid);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px}
.dash-mini-val{font-size:1.25rem;font-weight:800;color:#fff;font-variant-numeric:tabular-nums}
.dash-mini-val.amber{color:var(--amber)}
.dash-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.3);border-radius:8px;padding:8px 12px;font-size:.78rem;font-weight:700;color:var(--green);animation:float 3s ease-in-out infinite}
.dash-badge-dot{width:6px;height:6px;border-radius:50%;background:var(--green)}
.dash-recent{margin-top:18px}
.dash-recent-title{font-size:.72rem;color:var(--mid);font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px}
.dash-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.dash-row:last-child{border-bottom:none}
.dash-row-name{font-size:.82rem;color:rgba(255,255,255,.75);font-weight:600}
.dash-row-amount{font-size:.82rem;color:var(--amber);font-weight:700;font-variant-numeric:tabular-nums}

/* ─── MARQUEE ─────────────────────────────────────────────────────── */
#marquee{background:var(--navy-2);border-top:1px solid var(--navy-3);border-bottom:1px solid var(--navy-3);padding:14px 0;overflow:hidden}
.marquee-track{display:flex;width:max-content;animation:marquee 32s linear infinite}
.marquee-track:hover{animation-play-state:paused}
.marquee-item{display:flex;align-items:center;gap:10px;padding:0 28px;white-space:nowrap;font-size:.82rem;font-weight:700;color:var(--mid);letter-spacing:.04em;text-transform:uppercase}
.marquee-sep{color:var(--amber);font-size:1rem}

/* ─── STATS ROW ───────────────────────────────────────────────────── */
#stats{background:var(--paper-2);padding:64px 0;border-bottom:1px solid var(--border)}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.stat-item{padding:24px 32px;text-align:center;position:relative}
.stat-item+.stat-item::before{content:'';position:absolute;left:0;top:25%;height:50%;width:1px;background:var(--border)}
.stat-num{font-size:2.8rem;font-weight:900;color:var(--amber);letter-spacing:-.04em;line-height:1;margin-bottom:6px;font-variant-numeric:tabular-nums}
.stat-label{font-size:.85rem;font-weight:600;color:var(--body)}
.stat-sub{font-size:.75rem;color:var(--mid);margin-top:2px}

/* ─── PAIN / SOLUTION ─────────────────────────────────────────────── */
#pain{padding:96px 0;background:var(--paper)}
.pain-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start}
.pain-col{padding:32px}
.pain-col-before{background:var(--paper-2);border:1px solid var(--border);border-radius:var(--radius);border-top:3px solid #CBD5E1}
.pain-col-after{background:var(--navy);border-radius:var(--radius);border-top:3px solid var(--amber)}
.pain-label{font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:18px}
.pain-label.before{color:var(--body)}
.pain-label.after{color:var(--amber)}
.pain-title{font-size:1.4rem;font-weight:800;line-height:1.25;margin-bottom:20px}
.pain-title.dark{color:var(--carbon)}
.pain-title.light{color:#fff}
.pain-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;font-size:.9rem;line-height:1.55}
.pain-icon-x{width:18px;height:18px;border-radius:50%;background:rgba(100,116,139,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.pain-icon-check{width:18px;height:18px;border-radius:50%;background:rgba(245,158,11,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.pain-text-before{color:var(--body)}
.pain-text-after{color:rgba(255,255,255,.75)}

/* ─── MODULES ─────────────────────────────────────────────────────── */
#modules{padding:96px 0;background:var(--paper-2)}
.section-header{margin-bottom:56px}
.section-title{font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:900;color:var(--carbon);line-height:1.1;letter-spacing:-.02em;margin-bottom:12px}
.section-sub{font-size:1rem;color:var(--body);max-width:560px;line-height:1.7}
.modules-bento{display:grid;gap:16px}
.modules-row-1{display:grid;grid-template-columns:2fr 1fr;gap:16px}
.modules-row-2{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.modules-row-3{display:grid;grid-template-columns:1fr 2fr;gap:16px}
.mod-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;cursor:default;transition:all .28s cubic-bezier(.16,1,.3,1);position:relative;overflow:hidden}
.mod-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--amber);transform:scaleY(0);transform-origin:bottom;transition:transform .28s cubic-bezier(.16,1,.3,1)}
.mod-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(15,23,42,.1);border-color:rgba(245,158,11,.3)}
.mod-card:hover::before{transform:scaleY(1)}
.mod-card.alt{background:var(--navy-2);border-color:var(--navy-3)}
.mod-card.alt .mod-title{color:#fff}
.mod-card.alt .mod-outcome{color:rgba(255,255,255,.65)}
.mod-icon{width:42px;height:42px;border-radius:10px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.mod-icon svg{color:var(--amber)}
.mod-badge{display:inline-block;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--amber);background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:6px;padding:3px 8px;margin-bottom:10px}
.mod-title{font-size:1.05rem;font-weight:800;color:var(--carbon);margin-bottom:8px;line-height:1.25}
.mod-outcome{font-size:.87rem;color:var(--body);line-height:1.6}
.mod-card.large .mod-title{font-size:1.2rem}

/* ─── DOCS SECTION ────────────────────────────────────────────────── */
#docs{background:var(--navy);padding:96px 0}
#docs .section-title{color:#fff}
#docs .section-sub{color:var(--mid)}
.docs-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.docs-row-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:66.66%;margin:0 auto}
.doc-card{background:var(--navy-2);border:1px solid var(--navy-3);border-radius:var(--radius);padding:24px;transition:all .25s cubic-bezier(.16,1,.3,1);position:relative;overflow:hidden;cursor:default}
.doc-card::after{content:'CDR automático';position:absolute;bottom:-32px;right:12px;background:var(--amber);color:#fff;font-size:.7rem;font-weight:800;padding:4px 10px;border-radius:6px;letter-spacing:.06em;text-transform:uppercase;transition:bottom .25s cubic-bezier(.16,1,.3,1)}
.doc-card:hover{transform:translateY(-3px);border-color:var(--navy-3)}
.doc-card:hover::after{bottom:12px}
.doc-code{font-size:.7rem;font-weight:800;color:var(--amber);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;font-variant-numeric:tabular-nums}
.doc-name{font-size:1rem;font-weight:800;color:#fff;margin-bottom:6px;line-height:1.2}
.doc-desc{font-size:.82rem;color:var(--mid);line-height:1.55}
.docs-footer{text-align:center;margin-top:32px;display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap}
.docs-footer-item{display:flex;align-items:center;gap:7px;font-size:.82rem;font-weight:600;color:var(--mid)}
.docs-footer-item svg{color:var(--amber)}

/* ─── TRIAL ───────────────────────────────────────────────────────── */
#trial{background:var(--navy);border-top:1px solid var(--navy-3);padding:96px 0}
.trial-inner{display:grid;grid-template-columns:auto 1fr;gap:64px;align-items:center}
.trial-big{position:relative}
.trial-big-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);white-space:nowrap;background:var(--amber);color:#fff;font-size:.68rem;font-weight:800;padding:4px 12px;border-radius:99px;letter-spacing:.07em;text-transform:uppercase}
.trial-num{font-size:clamp(7rem,14vw,11rem);font-weight:900;color:var(--amber);line-height:1;letter-spacing:-.06em;font-variant-numeric:tabular-nums;opacity:0;transition:none}
.trial-num.in-view{animation:scaleIn .7s cubic-bezier(.16,1,.3,1) both}
.trial-days{font-size:1.6rem;font-weight:800;color:rgba(255,255,255,.4);margin-top:-8px;text-align:center;letter-spacing:.04em;text-transform:uppercase}
.trial-content .section-title{color:#fff}
.trial-content .section-sub{color:var(--mid)}
.trial-bullets{margin:28px 0 32px;display:flex;flex-direction:column;gap:12px}
.trial-bullet{display:flex;align-items:center;gap:12px;font-size:.93rem;color:rgba(255,255,255,.75);font-weight:600}
.trial-check{width:22px;height:22px;border-radius:50%;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ─── PRICING ─────────────────────────────────────────────────────── */
#pricing{padding:96px 0;background:var(--paper)}
.pricing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:48px}
.toggle-label{font-size:.9rem;font-weight:700;color:var(--body);cursor:pointer}
.toggle-label.active{color:var(--carbon)}
.toggle-wrap{position:relative;width:52px;height:28px}
.toggle-wrap input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{position:absolute;inset:0;background:var(--navy-3);border-radius:99px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;top:4px;left:4px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.3s}
.toggle-wrap input:checked + .toggle-slider{background:var(--amber)}
.toggle-wrap input:checked + .toggle-slider::before{transform:translateX(24px)}
.toggle-badge{display:inline-block;background:rgba(245,158,11,.15);color:var(--amber);border:1px solid rgba(245,158,11,.25);border-radius:99px;font-size:.72rem;font-weight:800;padding:3px 10px;letter-spacing:.06em;text-transform:uppercase}
.plans-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;align-items:start}
.plan-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;transition:all .28s cubic-bezier(.16,1,.3,1)}
.plan-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(15,23,42,.1)}
.plan-card.popular{border-color:var(--amber);border-width:2px;position:relative}
.plan-card.popular::before{content:'Más popular';position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--amber);color:#fff;font-size:.68rem;font-weight:800;padding:4px 14px;border-radius:99px;white-space:nowrap;letter-spacing:.07em;text-transform:uppercase}
.plan-name{font-size:.75rem;font-weight:800;color:var(--body);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
.plan-price{font-size:2.2rem;font-weight:900;color:var(--carbon);letter-spacing:-.03em;line-height:1;margin-bottom:4px;font-variant-numeric:tabular-nums}
.plan-price span{font-size:.9rem;font-weight:600;color:var(--mid)}
.plan-period{font-size:.78rem;color:var(--mid);margin-bottom:20px}
.plan-divider{height:1px;background:var(--border);margin:18px 0}
.plan-feats{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:24px}
.plan-feats li{display:flex;align-items:flex-start;gap:8px;font-size:.85rem;color:var(--body);line-height:1.45}
.plan-feats li svg{flex-shrink:0;margin-top:1px;color:var(--amber)}
.plan-btn{display:block;text-align:center;padding:12px 20px;border-radius:9px;font-weight:700;font-size:.9rem;transition:all .22s cubic-bezier(.16,1,.3,1);font-family:'Outfit',sans-serif;cursor:pointer;text-decoration:none}
.plan-btn:active{transform:scale(.97)}
.plan-btn-outline{border:1.5px solid var(--border);color:var(--carbon);background:transparent}
.plan-btn-outline:hover{border-color:var(--amber);color:var(--amber)}
.plan-btn-amber{background:var(--amber);color:#fff;border:none}
.plan-btn-amber:hover{background:var(--amber-d);transform:translateY(-2px)}

/* ─── TRUST ───────────────────────────────────────────────────────── */
#trust{background:var(--paper-2);padding:64px 0;border-top:1px solid var(--border)}
.trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.trust-item{padding:28px 24px;text-align:center;position:relative}
.trust-item+.trust-item::before{content:'';position:absolute;left:0;top:20%;height:60%;width:1px;background:var(--border)}
.trust-icon{width:44px;height:44px;border-radius:11px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:var(--amber)}
.trust-val{font-size:1.4rem;font-weight:900;color:var(--carbon);margin-bottom:4px}
.trust-label{font-size:.82rem;color:var(--body);font-weight:600}

/* ─── SECTORS ─────────────────────────────────────────────────────── */
#sectors{padding:80px 0;background:var(--paper)}
.sectors-pills{display:flex;flex-wrap:wrap;gap:10px;margin-top:32px;justify-content:center}
.sector-pill{display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid var(--border);border-radius:99px;padding:10px 20px;font-size:.88rem;font-weight:700;color:var(--body);cursor:default;transition:all .22s cubic-bezier(.16,1,.3,1)}
.sector-pill:hover{border-color:var(--amber);color:var(--amber);transform:translateY(-2px);background:var(--amber-p)}
.sector-pill svg{color:var(--mid);transition:color .22s}
.sector-pill:hover svg{color:var(--amber)}

/* ─── CTA FINAL ───────────────────────────────────────────────────── */
#cta-final{display:grid;grid-template-columns:1fr 1fr;min-height:420px}
.cta-left{background:var(--navy);display:flex;align-items:center;justify-content:flex-end;padding:72px 64px 72px 40px}
.cta-right{background:var(--amber);display:flex;align-items:center;justify-content:flex-start;padding:72px 40px 72px 64px}
.cta-l-title{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:900;color:#fff;line-height:1.15;margin-bottom:10px;letter-spacing:-.02em}
.cta-l-sub{font-size:.95rem;color:var(--mid);margin-bottom:28px;line-height:1.6}
.cta-r-title{font-size:clamp(1.4rem,2.5vw,2rem);font-weight:900;color:var(--navy);line-height:1.15;margin-bottom:10px;letter-spacing:-.02em}
.cta-r-sub{font-size:.92rem;color:rgba(15,23,42,.6);margin-bottom:28px;line-height:1.6}
.btn-navy{background:var(--navy);color:#fff;border:2px solid var(--navy)}
.btn-navy:hover{background:var(--navy-2);transform:translateY(-2px)}

/* ─── FOOTER ──────────────────────────────────────────────────────── */
#footer{background:var(--navy);border-top:1px solid var(--navy-3);padding:48px 0 32px}
.footer-inner{display:flex;align-items:flex-start;justify-content:space-between;gap:40px;margin-bottom:36px;flex-wrap:wrap}
.footer-stats{display:flex;align-items:center;gap:0}
.footer-stat-item{padding:0 32px;text-align:center}
.footer-stat-item:first-child{padding-left:0}
.footer-stat-item+.footer-stat-item{border-left:1px solid var(--navy-3)}
.footer-stat-val{font-size:1.8rem;font-weight:900;color:var(--amber);letter-spacing:-.03em;line-height:1;margin-bottom:4px;font-variant-numeric:tabular-nums}
.footer-stat-label{font-size:.75rem;color:rgba(255,255,255,.45);font-weight:600;text-transform:uppercase;letter-spacing:.07em}
.footer-links{display:flex;flex-direction:column;gap:10px}
.footer-link{display:flex;align-items:center;gap:8px;font-size:.88rem;color:var(--mid);font-weight:600;transition:color .2s}
.footer-link:hover{color:var(--amber)}
.footer-bottom{border-top:1px solid var(--navy-3);padding-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.footer-copy{font-size:.78rem;color:rgba(255,255,255,.3)}

/* ─── FLOATING WA ─────────────────────────────────────────────────── */
#wa-float{position:fixed;bottom:24px;right:24px;z-index:200;width:56px;height:56px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(37,211,102,.35);transition:transform .22s cubic-bezier(.16,1,.3,1),box-shadow .22s}
#wa-float::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25D366;animation:pulseRing 2.2s infinite;z-index:-1}
#wa-float:hover{transform:scale(1.1);box-shadow:0 8px 28px rgba(37,211,102,.45)}

/* ─── MOBILE ──────────────────────────────────────────────────────── */
@media(max-width:900px){
  .plans-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:768px){
  .nav-links,.nav-cta .btn-amber{display:none}
  .hamburger{display:flex}
  .hero-inner{grid-template-columns:1fr;gap:40px;padding:60px 0 72px}
  .hero-right{display:none}
  .stats-grid{grid-template-columns:1fr 1fr}
  .stat-item+.stat-item::before{display:none}
  .stat-item:nth-child(2n+1)::before{display:none}
  .pain-grid{grid-template-columns:1fr}
  .modules-row-1,.modules-row-2,.modules-row-3{grid-template-columns:1fr}
  .docs-grid{grid-template-columns:1fr 1fr}
  .docs-row-2{grid-template-columns:1fr;max-width:100%}
  .trial-inner{grid-template-columns:1fr;text-align:center;gap:28px}
  .trial-big-badge{position:static;transform:none;display:inline-block;margin-bottom:8px}
  .plans-grid{grid-template-columns:1fr}
  .trust-grid{grid-template-columns:1fr 1fr}
  .trust-item+.trust-item::before{display:none}
  #cta-final{grid-template-columns:1fr}
  .cta-left,.cta-right{padding:56px 28px;justify-content:center;text-align:center}
  .footer-inner{flex-direction:column;gap:24px}
  .footer-stats{gap:0;justify-content:center}
  .footer-stat-item{padding:0 20px}
  .footer-bottom{flex-direction:column;text-align:center}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .docs-grid{grid-template-columns:1fr}
  .trust-grid{grid-template-columns:1fr}
  .plans-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ─── FLOATING WA ─────────────────────────────────────────────── -->
<a id="wa-float" href="https://wa.me/51981524571?text=Hola%2C%20quiero%20información%20sobre%20Facturoom" target="_blank" rel="noopener" aria-label="Contactar por WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.8 9.8 0 0 1-5.012-1.374l-.36-.213-3.76.89.952-3.659-.234-.376A9.787 9.787 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
  </svg>
</a>

<!-- ─── NAV ─────────────────────────────────────────────────────── -->
<nav id="nav">
  <div class="container nav-inner">
    <a href="#" class="nav-logo">
      <img src="{{ asset('images/facturoom-logo.png') }}" alt="Facturoom" height="40" style="height:40px;width:auto">
      <div class="nav-logo-text"><span class="factu">Factu</span><span class="room">room</span></div>
    </a>
    <div class="nav-links">
      <a href="{{ route('landing.funcionalidades') }}">Funcionalidades</a>
      <a href="{{ route('landing.precios') }}">Precios</a>
      <a href="{{ route('landing.nosotros') }}">Nosotros</a>
      <a href="{{ route('landing.contacto') }}">Contacto</a>
    </div>
    <div class="nav-cta">
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">Prueba gratis</a>
    </div>
    <div class="hamburger" id="hamburger">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- ─── HERO ─────────────────────────────────────────────────────── -->
<section id="hero">
  <div class="hero-bg">
    <div class="hero-bg-grid"></div>
    <div class="hero-bg-glow"></div>
  </div>
  <div class="container">
    <div class="hero-inner">
      <!-- Left -->
      <div class="hero-left">
        <div class="hero-pill">
          <span class="dot"></span>
          Sistema de Facturación Electrónica · Perú
        </div>
        <h1 class="hero-h1">
          <span class="accent">Facturación electrónica</span><br>
          para Perú — vende y<br>
          controla todo desde aquí.
        </h1>
        <p class="hero-sub">
          Sistema completo: POS, inventario por lotes, compras,<br>
          guías GRE y <strong>36 tipos de reportes</strong>. Todo conectado a SUNAT.
        </p>
        <div class="hero-ctas">
          <a href="{{ route('landing.precios') }}" class="btn btn-amber">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            Prueba gratis 60 días
          </a>
          <a href="#modules" class="btn btn-ghost">Ver módulos ↓</a>
        </div>
        <div class="hero-badges">
          <div class="hero-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Sin tarjeta de crédito
          </div>
          <div class="hero-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Sin permanencia
          </div>
          <div class="hero-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Soporte por WhatsApp
          </div>
        </div>
      </div>

      <!-- Right — Dashboard Preview -->
      <div class="hero-right">
        <div class="dash-card">
          <div class="dash-header">
            <span class="dash-title">Resumen del día</span>
            <div class="dash-live">
              <span class="dash-live-dot"></span>
              En vivo
            </div>
          </div>
          <div class="dash-main-val" id="hero-counter">S/ 0.00</div>
          <div class="dash-main-sub">Ventas del día de hoy</div>
          <div class="dash-grid">
            <div class="dash-mini">
              <div class="dash-mini-label">Comprobantes</div>
              <div class="dash-mini-val amber" id="hero-docs">0</div>
            </div>
            <div class="dash-mini">
              <div class="dash-mini-label">Stock alertas</div>
              <div class="dash-mini-val">3</div>
            </div>
            <div class="dash-mini">
              <div class="dash-mini-label">Caja del día</div>
              <div class="dash-mini-val amber">S/ 0</div>
            </div>
          </div>
          <div class="dash-badge">
            <span class="dash-badge-dot"></span>
            SUNAT · CDR recibido
          </div>
          <div class="dash-recent">
            <div class="dash-recent-title">Últimas ventas</div>
            <div class="dash-row">
              <span class="dash-row-name">Ferretería Los Andes</span>
              <span class="dash-row-amount">S/ 847.50</span>
            </div>
            <div class="dash-row">
              <span class="dash-row-name">Bodega San Martín</span>
              <span class="dash-row-amount">S/ 234.00</span>
            </div>
            <div class="dash-row">
              <span class="dash-row-name">Distribuidora Lima</span>
              <span class="dash-row-amount">S/ 1,290.00</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─── MARQUEE ─────────────────────────────────────────────────── -->
<div id="marquee">
  <div class="marquee-track">
    <div class="marquee-item"><span class="marquee-sep">·</span> Facturación Electrónica</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Inventario + Kardex</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> POS + Caja</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Guías GRE</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> 30+ Módulos</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> 36 Tipos de Reportes</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Compras + Proveedores</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Kardex por Lotes</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Control de Acceso</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Finanzas + Detracciones</div>
    <!-- duplicate for seamless loop -->
    <div class="marquee-item"><span class="marquee-sep">·</span> Facturación Electrónica</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Inventario + Kardex</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> POS + Caja</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Guías GRE</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> 30+ Módulos</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> 36 Tipos de Reportes</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Compras + Proveedores</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Kardex por Lotes</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Control de Acceso</div>
    <div class="marquee-item"><span class="marquee-sep">·</span> Finanzas + Detracciones</div>
  </div>
</div>

<!-- ─── STATS ROW ─────────────────────────────────────────────────── -->
<section id="stats">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-num" data-target="30" data-suffix="+">0+</div>
        <div class="stat-label">Módulos disponibles</div>
        <div class="stat-sub">Integrados y conectados</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="8" data-suffix=" seg">0 seg</div>
        <div class="stat-label">Por comprobante a SUNAT</div>
        <div class="stat-sub">CDR automático</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="5" data-suffix="">0</div>
        <div class="stat-label">Tipos de documentos</div>
        <div class="stat-sub">Factura, Boleta, NC, ND, GRE</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="36" data-suffix="+">0+</div>
        <div class="stat-label">Tipos de reportes</div>
        <div class="stat-sub">Kardex, ventas, caja y más</div>
      </div>
    </div>
  </div>
</section>

<!-- ─── PAIN / SOLUTION ───────────────────────────────────────────── -->
<section id="pain">
  <div class="container">
    <div class="section-header" style="text-align:center;max-width:600px;margin:0 auto 56px">
      <div class="section-eye"><span class="dot"></span>El problema</div>
      <h2 class="section-title">Gestionar un negocio no debería ser tan complicado</h2>
    </div>
    <div class="pain-grid">
      <!-- ANTES -->
      <div class="pain-col pain-col-before">
        <div class="pain-label before">Sin Facturoom</div>
        <h3 class="pain-title dark">Un caos de procesos manuales</h3>
        <div class="pain-item">
          <div class="pain-icon-x">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </div>
          <span class="pain-text-before">Facturas en Excel que se pierden y errores que llegan a SUNAT tarde</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-x">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </div>
          <span class="pain-text-before">Inventario contado a mano: nunca sabes qué tienes realmente en stock</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-x">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </div>
          <span class="pain-text-before">Caja cuadrada a ojímetro, sin trazabilidad por usuario ni turno</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-x">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </div>
          <span class="pain-text-before">Reportes en papel que nadie lee y decisiones tomadas a ciegas</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-x">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </div>
          <span class="pain-text-before">Guías de remisión manuales con riesgo de multa en fiscalización</span>
        </div>
      </div>
      <!-- CON FACTUROOM -->
      <div class="pain-col pain-col-after">
        <div class="pain-label after">Con Facturoom</div>
        <h3 class="pain-title light">Control total desde una sola pantalla</h3>
        <div class="pain-item">
          <div class="pain-icon-check">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="pain-text-after">CDR de SUNAT confirmado en 8 segundos, sin intervención manual</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-check">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="pain-text-after">Kardex valorizado FIFO/LIFO/Prom. ponderado con alertas de stock mínimo</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-check">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="pain-text-after">Cierre de caja auditado por usuario con historial y cuadre automático</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-check">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="pain-text-after">36 tipos de reportes: desde kardex valorizado hasta top clientes y comisiones</span>
        </div>
        <div class="pain-item">
          <div class="pain-icon-check">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="pain-text-after">GRE electrónica con placa, chofer y destino enviada a SUNAT en segundos</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─── MODULES ───────────────────────────────────────────────────── -->
<section id="modules">
  <div class="container">
    <div class="section-header">
      <div class="section-eye"><span class="dot"></span>Módulos</div>
      <h2 class="section-title">Todo lo que tu negocio necesita,<br>en un solo sistema</h2>
      <p class="section-sub">8 módulos principales, 30+ submódulos integrados. Cada uno diseñado para el comercio peruano.</p>
    </div>
    <div class="modules-bento">
      <!-- Row 1: 2fr + 1fr -->
      <div class="modules-row-1">
        <div class="mod-card large">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
          </div>
          <div class="mod-badge">Principal</div>
          <div class="mod-title">Facturación CPE + SUNAT</div>
          <div class="mod-outcome">Facturas y boletas a SUNAT con CDR en menos de 8 segundos. Series F y B, numeración automática y XML firmado.</div>
        </div>
        <div class="mod-card">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16M4 11h4M16 11h4"/></svg>
          </div>
          <div class="mod-badge">Inventario</div>
          <div class="mod-title">Inventario + Kardex + Lotes</div>
          <div class="mod-outcome">FIFO, LIFO y Promedio Ponderado. Alertas de stock mínimo, trazabilidad por lote y vencimientos.</div>
        </div>
      </div>

      <!-- Row 2: 1fr + 1fr + 1fr (alt bg) -->
      <div class="modules-row-2">
        <div class="mod-card alt">
          <div class="mod-icon" style="background:rgba(245,158,11,.2)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20M6 15h4M14 15h4"/></svg>
          </div>
          <div class="mod-title">POS + Caja</div>
          <div class="mod-outcome">Venta rápida en mostrador con lector de código de barras. Cierre de caja auditado por usuario y turno.</div>
        </div>
        <div class="mod-card alt">
          <div class="mod-icon" style="background:rgba(245,158,11,.2)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
          </div>
          <div class="mod-title">Compras + Proveedores</div>
          <div class="mod-outcome">Órdenes de compra, recepción de mercadería y cálculo automático de margen de ganancia por producto.</div>
        </div>
        <div class="mod-card alt">
          <div class="mod-icon" style="background:rgba(245,158,11,.2)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8zM1 16h15M17.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM5.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/></svg>
          </div>
          <div class="mod-title">Guías de Remisión GRE</div>
          <div class="mod-outcome">Traslado documentado con placa, chofer y destino enviado electrónicamente a SUNAT.</div>
        </div>
      </div>

      <!-- Row 3: 1fr + 2fr -->
      <div class="modules-row-3">
        <div class="mod-card">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="mod-badge">Seguridad</div>
          <div class="mod-title">Control de Acceso</div>
          <div class="mod-outcome">Roles y permisos por módulo. Log de auditoría completo de cada acción en el sistema.</div>
        </div>
        <div class="mod-card large">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div class="mod-badge">36+ reportes</div>
          <div class="mod-title">Reportes + Análisis de negocio</div>
          <div class="mod-outcome">Kardex valorizado, top clientes y productos, comisiones, caja diaria y 29 reportes más para decidir con datos reales.</div>
        </div>
      </div>

      <!-- Row 4: 1fr + 1fr -->
      <div class="modules-row-1">
        <div class="mod-card">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M12 15h4M6 15h.01"/></svg>
          </div>
          <div class="mod-badge">Finanzas</div>
          <div class="mod-title">Finanzas + Detracciones</div>
          <div class="mod-outcome">Cuentas bancarias, percepciones y detracciones, pagos a proveedores y conciliación de comprobantes tributarios.</div>
        </div>
        <div class="mod-card">
          <div class="mod-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="mod-badge">Multi-usuario</div>
          <div class="mod-title">Vendedores + Comisiones</div>
          <div class="mod-outcome">Gestión de vendedores con comisiones configurables por producto o categoría. Reporte de rendimiento individual.</div>
        </div>
      </div>
    </div>
    <div style="text-align:center;margin-top:40px">
      <a href="{{ route('landing.funcionalidades') }}" class="btn btn-ghost-dark">Ver todas las funcionalidades →</a>
    </div>
  </div>
</section>

<!-- ─── TRIAL ─────────────────────────────────────────────────────── -->
<section id="trial">
  <div class="container">
    <div class="trial-inner">
      <div class="trial-big">
        <div class="trial-big-badge">Días de prueba real</div>
        <div class="trial-num" id="trial-num">60</div>
        <div class="trial-days">Días gratis</div>
      </div>
      <div class="trial-content">
        <div class="section-eye"><span class="dot"></span>Sin riesgo</div>
        <h2 class="section-title">Prueba Facturoom sin<br>comprometer nada</h2>
        <p class="section-sub">60 días para usar el sistema con datos reales, sin límite de comprobantes durante la prueba.</p>
        <div class="trial-bullets">
          <div class="trial-bullet">
            <div class="trial-check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            Sin tarjeta de crédito requerida
          </div>
          <div class="trial-bullet">
            <div class="trial-check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            Acceso completo a todos los módulos
          </div>
          <div class="trial-bullet">
            <div class="trial-check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            Soporte personalizado por WhatsApp
          </div>
          <div class="trial-bullet">
            <div class="trial-check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            Sin permanencia ni contrato
          </div>
        </div>
        <a href="{{ route('landing.precios') }}" class="btn btn-amber">
          Empezar prueba gratis
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ─── PRECIOS (teaser → /precios) ─────────────────────────────── -->
<section id="pricing-cta" style="padding:88px 0;background:var(--paper-2)">
  <div class="container">
    <div style="background:#fff;border:1px solid var(--border);border-radius:18px;padding:clamp(32px,5vw,52px);display:flex;align-items:center;justify-content:space-between;gap:32px;flex-wrap:wrap;box-shadow:0 16px 40px -24px rgba(15,23,42,.18)">
      <div style="max-width:560px">
        <div class="section-eye"><span class="dot"></span>Precios</div>
        <h2 class="section-title" style="margin-bottom:10px">Planes desde <span style="color:var(--amber)">S/ 29.90</span> al mes</h2>
        <p class="section-sub">Cuatro planes para cada tamaño de negocio, con 60 días gratis y sin permanencia. Mira el detalle de cada uno y elige el tuyo.</p>
      </div>
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">
        Ver planes y precios
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ─── SECTORS ───────────────────────────────────────────────────── -->
<section id="sectors">
  <div class="container">
    <div class="section-header" style="text-align:center;max-width:560px;margin:0 auto 16px">
      <div class="section-eye"><span class="dot"></span>Sectores</div>
      <h2 class="section-title">Hecho para el negocio peruano</h2>
      <p class="section-sub">Desde bodegas hasta distribuidoras, Facturoom se adapta a tu sector sin configuraciones complejas.</p>
    </div>
    <div class="sectors-pills">
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
        Bodegas y minimarkets
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Ferreterías
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        Farmacias y boticas
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
        Restaurantes y cafeterías
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/></svg>
        Distribuidoras
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
        Textilerías y confecciones
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        Hoteles y hospedajes
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
        Servicios técnicos
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
        Manufactureras
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Tiendas retail
      </div>
      <div class="sector-pill">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Importadoras
      </div>
    </div>
  </div>
</section>

<!-- ─── CTA FINAL ─────────────────────────────────────────────────── -->
<section id="cta-final">
  <div class="cta-left">
    <div style="max-width:380px">
      <div class="section-eye"><span class="dot"></span>Empieza hoy</div>
      <h2 class="cta-l-title">Lleva el control<br>de tu negocio,<br>desde hoy.</h2>
      <p class="cta-l-sub">Configura tu cuenta y empieza a facturar a SUNAT en minutos.</p>
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        Ver planes
      </a>
    </div>
  </div>
  <div class="cta-right">
    <div style="max-width:380px">
      <h2 class="cta-r-title">¿Tienes dudas?<br>Hablemos por WhatsApp.</h2>
      <p class="cta-r-sub">Nuestro equipo responde en minutos y te ayuda a configurar tu primera factura.</p>
      <a href="https://wa.me/51981524571?text=Hola%2C%20quiero%20información%20sobre%20Facturoom" target="_blank" rel="noopener" class="btn btn-navy">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.8 9.8 0 0 1-5.012-1.374l-.36-.213-3.76.89.952-3.659-.234-.376A9.787 9.787 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
        Escribir por WhatsApp
      </a>
    </div>
  </div>
</section>

<!-- ─── FOOTER ─────────────────────────────────────────────────────── -->
<footer id="footer">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-stats">
        <div class="footer-stat-item">
          <div class="footer-stat-val">50+</div>
          <div class="footer-stat-label">Negocios activos</div>
        </div>
        <div class="footer-stat-item">
          <div class="footer-stat-val">60</div>
          <div class="footer-stat-label">Días gratis</div>
        </div>
        <div class="footer-stat-item">
          <div class="footer-stat-val">SUNAT</div>
          <div class="footer-stat-label">Integración oficial</div>
        </div>
      </div>
      <div class="footer-links">
        <a href="https://wa.me/51981524571" target="_blank" rel="noopener" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.8 9.8 0 0 1-5.012-1.374l-.36-.213-3.76.89.952-3.659-.234-.376A9.787 9.787 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
          +51 981 524 571
        </a>
        <a href="mailto:facturoom@gmail.com" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          Escríbenos
        </a>
        <a href="https://www.facebook.com/facturoom" target="_blank" rel="noopener" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8v8.44C19.61 23.08 24 18.09 24 12.07z"/></svg>
          Facebook
        </a>
        <a href="{{ route('landing.precios') }}" class="footer-link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          Ver precios
        </a>
      </div>
    </div>
    <div class="footer-bottom">
      <span class="footer-copy">&copy; {{ date('Y') }} Facturoom. Todos los derechos reservados.</span>
      <span class="footer-copy">Hecho para el comercio peruano</span>
    </div>
  </div>
</footer>

<script>
/* ─── NAV SCROLL ─────────────────────────────────────────────────── */
var nav = document.getElementById('nav');
window.addEventListener('scroll', function() {
  if (window.scrollY > 20) {
    nav.classList.add('scrolled');
  } else {
    nav.classList.remove('scrolled');
  }
}, {passive: true});

/* ─── HERO COUNTER ANIMATION ─────────────────────────────────────── */
(function() {
  var heroCounter = document.getElementById('hero-counter');
  var heroDocs = document.getElementById('hero-docs');
  if (!heroCounter) return;

  var start = null;
  var duration = 1800;
  var targetSales = 2371.50;
  var targetDocs = 47;

  function animateHero(ts) {
    if (!start) start = ts;
    var progress = Math.min((ts - start) / duration, 1);
    var ease = 1 - Math.pow(1 - progress, 3);

    var currentSales = targetSales * ease;
    heroCounter.textContent = 'S/ ' + currentSales.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    var currentDocs = Math.floor(targetDocs * ease);
    heroDocs.textContent = currentDocs;

    if (progress < 1) {
      requestAnimationFrame(animateHero);
    }
  }

  setTimeout(function() {
    requestAnimationFrame(animateHero);
  }, 600);
})();

/* ─── STATS COUNTER (IntersectionObserver) ───────────────────────── */
var statNums = document.querySelectorAll('.stat-num[data-target]');
var statsObserver = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (!entry.isIntersecting) return;
    statsObserver.unobserve(entry.target);

    var el = entry.target;
    var target = parseInt(el.dataset.target, 10);
    var suffix = el.dataset.suffix || '';
    var start = null;
    var duration = 1200;

    function animate(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3);
      var current = Math.floor(target * ease);
      el.textContent = current + suffix;
      if (progress < 1) requestAnimationFrame(animate);
    }
    requestAnimationFrame(animate);
  });
}, {threshold: 0.5});

statNums.forEach(function(el) { statsObserver.observe(el); });

/* ─── TRIAL 60 SCALE-IN ──────────────────────────────────────────── */
var trialNum = document.getElementById('trial-num');
if (trialNum) {
  var trialObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        trialObserver.unobserve(entry.target);
      }
    });
  }, {threshold: 0.4});
  trialObserver.observe(trialNum);
}

/* ─── SECTION FADE-IN ────────────────────────────────────────────── */
var revealEls = document.querySelectorAll('#stats, #pain, #modules, #pricing-cta, #sectors, .pain-col, .mod-card, .sector-pill, .stat-item');
var revealObs = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) {
      entry.target.classList.add('in-view');
      revealObs.unobserve(entry.target);
    }
  });
}, {threshold: 0.12});
revealEls.forEach(function(el, i) {
  el.style.animationDelay = (i * 0.05) + 's';
  revealObs.observe(el);
});

/* ─── PRICING TOGGLE + WA LINKS ──────────────────────────────────── */
var hamburger = document.getElementById('hamburger');
hamburger.addEventListener('click', function() {
  var wa = document.getElementById('wa-float');
  var msg = encodeURIComponent('Hola, quiero información sobre Facturoom');
  window.open('https://wa.me/51981524571?text=' + msg, '_blank');
});

/* ─── DASH MINI CAJA COUNTER ─────────────────────────────────────── */
(function() {
  var cajaEl = document.querySelector('.dash-mini:nth-child(3) .dash-mini-val');
  if (!cajaEl) return;
  var start = null;
  var target = 2371;
  var dur = 2000;
  function animate(ts) {
    if (!start) start = ts;
    var p = Math.min((ts - start) / dur, 1);
    var ease = 1 - Math.pow(1 - p, 3);
    cajaEl.textContent = 'S/ ' + Math.floor(target * ease).toLocaleString('es-PE');
    if (p < 1) requestAnimationFrame(animate);
  }
  setTimeout(function() { requestAnimationFrame(animate); }, 900);
})();
</script>
</body>
</html>
