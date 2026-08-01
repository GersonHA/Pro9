@extends('system.landing.layout')

@section('title', 'Nosotros — Quiénes somos | Facturoom')
@section('description', 'Conoce a Facturoom: el sistema de facturación electrónica hecho en Perú para bodegas, ferreterías, farmacias y pymes. Nuestra misión, valores y por qué confían en nosotros.')
@section('og_title', 'Nosotros — Facturoom, facturación electrónica peruana')
@section('og_description', 'Somos un equipo peruano que construye software de facturación electrónica simple y conectado a SUNAT para el comercio local.')

@push('styles')
.about-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:56px;align-items:center}
.about-text p{font-size:1.02rem;color:var(--body);line-height:1.8;margin-bottom:18px}
.about-text strong{color:var(--carbon);font-weight:700}
.about-card{background:var(--navy-2);border:1px solid var(--navy-3);border-radius:18px;padding:32px;position:relative;overflow:hidden;box-shadow:0 24px 50px -24px rgba(15,23,42,.45)}
.about-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(245,158,11,.4),transparent)}
.about-card-stat{display:flex;align-items:center;justify-content:space-between;padding:18px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.about-card-stat:last-child{border-bottom:none}
.about-card-stat .v{font-size:1.9rem;font-weight:900;color:var(--amber);letter-spacing:-.03em;font-variant-numeric:tabular-nums}
.about-card-stat .l{font-size:.9rem;color:rgba(255,255,255,.62);font-weight:600;text-align:right;max-width:55%}
.values-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:48px}
.value-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;transition:transform .28s cubic-bezier(.16,1,.3,1),box-shadow .28s,border-color .28s}
.value-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(15,23,42,.1);border-color:rgba(245,158,11,.3)}
.value-icon{width:46px;height:46px;border-radius:12px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;color:var(--amber);margin-bottom:16px}
.value-card h3{font-size:1.1rem;font-weight:800;color:var(--carbon);margin-bottom:8px}
.value-card p{font-size:.9rem;color:var(--body);line-height:1.6}
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:48px}
.step{position:relative;padding:28px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);transition:transform .28s cubic-bezier(.16,1,.3,1),box-shadow .28s}
.step:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(15,23,42,.08)}
.step-num{font-size:.85rem;font-weight:900;color:#fff;background:var(--amber);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.step h3{font-size:1.05rem;font-weight:800;color:var(--carbon);margin-bottom:8px}
.step p{font-size:.9rem;color:var(--body);line-height:1.6}
@media(max-width:768px){.about-grid{grid-template-columns:1fr;gap:36px}.values-grid,.steps{grid-template-columns:1fr}}
@endpush

@section('content')
@php $nav = 'nosotros'; @endphp

<section class="page-hero">
  <div class="page-hero-grid"></div>
  <div class="page-hero-glow"></div>
  <div class="container">
    <div class="inner reveal">
      <div class="eyebrow"><span style="width:7px;height:7px;border-radius:50%;background:var(--amber);display:inline-block"></span> Nosotros</div>
      <h1>Hacemos que <span class="accent">facturar</span> deje de ser un dolor de cabeza para el negocio peruano.</h1>
      <p class="lead">Facturoom nació de una idea simple: el comerciante peruano debería poder facturar, controlar su stock y entender su negocio sin pelear con SUNAT ni con hojas de Excel. Por eso construimos un sistema completo, en español y conectado a SUNAT.</p>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">
    <div class="about-grid">
      <div class="about-text reveal">
        <div class="section-eye"><span class="dot"></span> Nuestra historia</div>
        <h2 class="section-title" style="margin-bottom:24px">Software peruano para problemas peruanos</h2>
        <p>Trabajando de cerca con bodegas, ferreterías y distribuidoras descubrimos lo mismo una y otra vez: <strong>las herramientas existentes eran caras, complicadas o pensadas para grandes empresas</strong>. El comercio local terminaba facturando a mano, perdiendo stock y tomando decisiones a ciegas.</p>
        <p>Facturoom es nuestra respuesta. Un sistema que junta <strong>facturación electrónica, POS, inventario por lotes, compras y 36 tipos de reportes</strong> en una sola pantalla, sin instalar nada y a un precio que una pyme realmente puede pagar.</p>
        <p>Hoy más de <strong>50 negocios activos</strong> facturan con Facturoom todos los días, y seguimos mejorando el sistema con cada conversación con nuestros clientes.</p>
      </div>
      <div class="about-card reveal">
        <div class="about-card-stat"><span class="v">50+</span><span class="l">Negocios activos facturando a diario</span></div>
        <div class="about-card-stat"><span class="v">8 seg</span><span class="l">Promedio de respuesta CDR de SUNAT</span></div>
        <div class="about-card-stat"><span class="v">30+</span><span class="l">Módulos integrados en un solo sistema</span></div>
        <div class="about-card-stat"><span class="v">100%</span><span class="l">En español y soporte por WhatsApp</span></div>
      </div>
    </div>
  </div>
</section>

<section class="page-section alt">
  <div class="container">
    <div class="section-header reveal" style="text-align:center;max-width:600px;margin:0 auto 0">
      <div class="section-eye" style="justify-content:center"><span class="dot"></span> Lo que nos mueve</div>
      <h2 class="section-title">Nuestros valores</h2>
      <p class="section-sub" style="margin:0 auto">Tres ideas que guían cada decisión que tomamos sobre el producto.</p>
    </div>
    <div class="values-grid">
      <div class="value-card reveal">
        <div class="value-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg></div>
        <h3>Simple de verdad</h3>
        <p>Si una persona que nunca usó un sistema no puede emitir su primera factura en minutos, lo hicimos mal. La simplicidad no es opcional.</p>
      </div>
      <div class="value-card reveal">
        <div class="value-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h3>Cumplimiento sin estrés</h3>
        <p>SUNAT cambia las reglas; nosotros nos encargamos de que tu sistema siempre esté al día. Tú facturas, nosotros mantenemos la integración.</p>
      </div>
      <div class="value-card reveal">
        <div class="value-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <h3>Cerca del cliente</h3>
        <p>Atendemos por WhatsApp, en español y sin tickets eternos. Cada mejora del sistema sale de una conversación real con un negocio real.</p>
      </div>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">
    <div class="section-header reveal" style="text-align:center;max-width:600px;margin:0 auto 0">
      <div class="section-eye" style="justify-content:center"><span class="dot"></span> Empezar es fácil</div>
      <h2 class="section-title">De cero a tu primera factura en 3 pasos</h2>
    </div>
    <div class="steps">
      <div class="step reveal"><div class="step-num">1</div><h3>Activa tu prueba</h3><p>Escríbenos por WhatsApp y te creamos tu cuenta con 60 días gratis, sin tarjeta de crédito.</p></div>
      <div class="step reveal"><div class="step-num">2</div><h3>Configura tu negocio</h3><p>Cargamos tus datos de RUC, series y productos. Te acompañamos en la puesta en marcha.</p></div>
      <div class="step reveal"><div class="step-num">3</div><h3>Empieza a facturar</h3><p>Emite facturas y boletas a SUNAT en segundos y controla todo tu negocio desde una pantalla.</p></div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2 class="reveal">¿Listo para conocer Facturoom por dentro?</h2>
    <p class="reveal">Prueba el sistema completo 60 días gratis o escríbenos y te hacemos una demo en vivo.</p>
    <div class="actions reveal">
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">Ver planes y precios</a>
      <a href="https://wa.me/51981524571?text=Hola%2C%20quiero%20una%20demo%20de%20Facturoom" target="_blank" rel="noopener" class="btn btn-ghost">Pedir una demo</a>
    </div>
  </div>
</section>
@endsection
