@extends('system.landing.layout')

@section('title', 'Funcionalidades — Módulos del sistema | Facturoom')
@section('description', 'Todas las funcionalidades de Facturoom: facturación CPE a SUNAT, POS y caja, inventario por lotes y kardex, compras, guías GRE, 36 reportes, finanzas, comisiones y control de acceso.')
@section('og_title', 'Funcionalidades de Facturoom — 30+ módulos para tu negocio')
@section('og_description', 'POS, inventario por lotes, facturación electrónica, GRE, compras y 36 tipos de reportes. Todo integrado y conectado a SUNAT.')

@push('styles')
.feat-row{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center;padding:40px 0}
.feat-row.flip .feat-visual{order:-1}
.feat-text .badge{display:inline-block;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--amber);background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.22);border-radius:6px;padding:4px 10px;margin-bottom:14px}
.feat-text h2{font-size:clamp(1.6rem,2.8vw,2.1rem);font-weight:900;color:var(--carbon);letter-spacing:-.02em;line-height:1.15;margin-bottom:14px}
.feat-text p{font-size:1rem;color:var(--body);line-height:1.7;margin-bottom:20px}
.feat-list{list-style:none;display:flex;flex-direction:column;gap:11px}
.feat-list li{display:flex;align-items:flex-start;gap:10px;font-size:.92rem;color:var(--body);line-height:1.5}
.feat-list li svg{flex-shrink:0;margin-top:2px;color:var(--amber)}
.feat-visual{background:var(--navy-2);border:1px solid var(--navy-3);border-radius:18px;padding:28px;min-height:240px;box-shadow:0 24px 50px -24px rgba(15,23,42,.45);position:relative;overflow:hidden}
.feat-visual::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(245,158,11,.5),transparent)}
.fv-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:.86rem}
.fv-row:last-child{border-bottom:none}
.fv-row .k{color:rgba(255,255,255,.72);font-weight:600}
.fv-row .v{color:var(--amber);font-weight:800;font-variant-numeric:tabular-nums}
.fv-row .v.green{color:#34D399}
.fv-title{font-size:.78rem;font-weight:700;color:var(--mid);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px}
.fv-pill{display:inline-flex;align-items:center;gap:6px;font-size:.74rem;font-weight:700;color:#34D399;background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:7px 11px;margin-top:14px}
.feat-divider{height:1px;background:var(--border);margin:0}
.extra-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:48px}
.extra-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;transition:transform .28s cubic-bezier(.16,1,.3,1),box-shadow .28s,border-color .28s}
.extra-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(15,23,42,.1);border-color:rgba(245,158,11,.3)}
.extra-icon{width:42px;height:42px;border-radius:11px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;color:var(--amber);margin-bottom:14px}
.extra-card h3{font-size:1rem;font-weight:800;color:var(--carbon);margin-bottom:7px}
.extra-card p{font-size:.86rem;color:var(--body);line-height:1.55}
@media(max-width:768px){.feat-row{grid-template-columns:1fr;gap:28px;padding:28px 0}.feat-row.flip .feat-visual{order:0}.extra-grid{grid-template-columns:1fr}}
@endpush

@section('content')
@php $nav = 'funcionalidades'; @endphp

<section class="page-hero">
  <div class="page-hero-grid"></div>
  <div class="page-hero-glow"></div>
  <div class="container">
    <div class="inner reveal">
      <div class="eyebrow"><span style="width:7px;height:7px;border-radius:50%;background:var(--amber);display:inline-block"></span> Funcionalidades</div>
      <h1>Un sistema completo, no <span class="accent">una sola herramienta</span>.</h1>
      <p class="lead">Facturoom reúne todo lo que un negocio peruano necesita para vender, facturar y controlar su operación: 8 módulos principales y más de 30 submódulos integrados entre sí. Esto es lo que puedes hacer.</p>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">

    {{-- FACTURACIÓN --}}
    <div class="feat-row">
      <div class="feat-text reveal">
        <span class="badge">Facturación CPE</span>
        <h2>Factura y boletas a SUNAT en segundos</h2>
        <p>Emite comprobantes electrónicos con CDR confirmado en menos de 8 segundos. Series F y B, numeración automática y XML firmado digitalmente, sin software extra.</p>
        <ul class="feat-list">
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Factura, Boleta, Nota de Crédito, Nota de Débito y GRE</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Envío automático del PDF al correo del cliente</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Anulación con comunicación de baja y notas de crédito</li>
        </ul>
      </div>
      <div class="feat-visual reveal">
        <div class="fv-title">Factura F001-00482</div>
        <div class="fv-row"><span class="k">Cliente</span><span class="v" style="color:#fff;font-weight:600">Ferretería Los Andes</span></div>
        <div class="fv-row"><span class="k">Subtotal</span><span class="v" style="color:#fff;font-weight:600">S/ 718.22</span></div>
        <div class="fv-row"><span class="k">IGV (18%)</span><span class="v" style="color:#fff;font-weight:600">S/ 129.28</span></div>
        <div class="fv-row"><span class="k">Total</span><span class="v">S/ 847.50</span></div>
        <div class="fv-pill"><span style="width:6px;height:6px;border-radius:50%;background:var(--green)"></span> SUNAT · CDR aceptado en 6.2 s</div>
      </div>
    </div>
    <div class="feat-divider"></div>

    {{-- INVENTARIO --}}
    <div class="feat-row flip">
      <div class="feat-text reveal">
        <span class="badge">Inventario + Kardex</span>
        <h2>Stock por lotes, valorizado y con alertas</h2>
        <p>Sabe exactamente qué tienes, cuánto vale y qué está por vencer. Kardex valorizado con FIFO, LIFO o Promedio Ponderado y trazabilidad por lote.</p>
        <ul class="feat-list">
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Alertas de stock mínimo y de lotes por vencer</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Kardex valorizado por método contable</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Control multi-almacén y transferencias</li>
        </ul>
      </div>
      <div class="feat-visual reveal">
        <div class="fv-title">Alertas de inventario</div>
        <div class="fv-row"><span class="k">Cemento Sol 42.5kg</span><span class="v" style="color:#EF4444">Stock: 4</span></div>
        <div class="fv-row"><span class="k">Lote A-203 · vence</span><span class="v">en 9 días</span></div>
        <div class="fv-row"><span class="k">Valorización total</span><span class="v">S/ 38,420</span></div>
        <div class="fv-row"><span class="k">Método</span><span class="v green">Promedio ponderado</span></div>
        <div class="fv-pill"><span style="width:6px;height:6px;border-radius:50%;background:var(--green)"></span> 3 productos requieren reposición</div>
      </div>
    </div>
    <div class="feat-divider"></div>

    {{-- POS + CAJA --}}
    <div class="feat-row">
      <div class="feat-text reveal">
        <span class="badge">POS + Caja</span>
        <h2>Vende rápido y cuadra tu caja sin errores</h2>
        <p>Un punto de venta pensado para el mostrador: lector de código de barras, búsqueda instantánea y cierre de caja auditado por usuario y turno.</p>
        <ul class="feat-list">
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Venta con lector de barras e impresión de ticket</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Apertura y cierre de caja con arqueo automático</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Historial y trazabilidad por cajero y turno</li>
        </ul>
      </div>
      <div class="feat-visual reveal">
        <div class="fv-title">Cierre de caja · Turno mañana</div>
        <div class="fv-row"><span class="k">Efectivo</span><span class="v" style="color:#fff;font-weight:600">S/ 1,420.00</span></div>
        <div class="fv-row"><span class="k">Tarjeta / Yape</span><span class="v" style="color:#fff;font-weight:600">S/ 951.50</span></div>
        <div class="fv-row"><span class="k">Total del día</span><span class="v">S/ 2,371.50</span></div>
        <div class="fv-row"><span class="k">Diferencia</span><span class="v green">S/ 0.00 — cuadrado</span></div>
        <div class="fv-pill"><span style="width:6px;height:6px;border-radius:50%;background:var(--green)"></span> Arqueo cuadrado automáticamente</div>
      </div>
    </div>
    <div class="feat-divider"></div>

    {{-- REPORTES --}}
    <div class="feat-row flip">
      <div class="feat-text reveal">
        <span class="badge">36+ reportes</span>
        <h2>Decisiones con datos, no a ciegas</h2>
        <p>Más de 36 tipos de reportes listos para usar: desde kardex valorizado hasta top clientes, comisiones por vendedor y comparativo de compras vs ventas.</p>
        <ul class="feat-list">
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Ventas por período, producto, cliente y vendedor</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Reporte de caja diario y flujo de efectivo</li>
          <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Exportación a Excel y PDF</li>
        </ul>
      </div>
      <div class="feat-visual reveal">
        <div class="fv-title">Top productos del mes</div>
        <div class="fv-row"><span class="k">1 · Cemento Sol</span><span class="v" style="color:#fff;font-weight:600">S/ 12,840</span></div>
        <div class="fv-row"><span class="k">2 · Fierro 1/2"</span><span class="v" style="color:#fff;font-weight:600">S/ 9,210</span></div>
        <div class="fv-row"><span class="k">3 · Pintura látex</span><span class="v" style="color:#fff;font-weight:600">S/ 6,470</span></div>
        <div class="fv-row"><span class="k">Margen promedio</span><span class="v">23.4%</span></div>
        <div class="fv-pill"><span style="width:6px;height:6px;border-radius:50%;background:var(--green)"></span> 36 reportes disponibles</div>
      </div>
    </div>
  </div>
</section>

{{-- EXTRAS --}}
<section class="page-section alt">
  <div class="container">
    <div class="section-header reveal" style="text-align:center;max-width:600px;margin:0 auto 0">
      <div class="section-eye" style="justify-content:center"><span class="dot"></span> Y mucho más</div>
      <h2 class="section-title">Funcionalidades que completan tu operación</h2>
    </div>
    <div class="extra-grid">
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg></div><h3>Compras + Proveedores</h3><p>Órdenes de compra, recepción de mercadería y cálculo automático de margen por producto.</p></div>
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/></svg></div><h3>Guías de Remisión GRE</h3><p>Traslado con placa, chofer y destino enviado electrónicamente a SUNAT.</p></div>
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M12 15h4M6 15h.01"/></svg></div><h3>Finanzas + Detracciones</h3><p>Cuentas bancarias, percepciones, detracciones y conciliación de comprobantes.</p></div>
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg></div><h3>Vendedores + Comisiones</h3><p>Comisiones configurables por producto o categoría y reporte de rendimiento.</p></div>
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><h3>Control de Acceso</h3><p>Roles y permisos por módulo, con log de auditoría de cada acción del sistema.</p></div>
      <div class="extra-card reveal"><div class="extra-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></div><h3>Cotizaciones</h3><p>Crea cotizaciones, conviértelas en venta y registra pagos a cuenta cuando aplique.</p></div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2 class="reveal">Todo esto, en un solo sistema</h2>
    <p class="reveal">Prueba Facturoom 60 días gratis y descubre cuánto tiempo recuperas cada semana.</p>
    <div class="actions reveal">
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">Ver planes y precios</a>
      <a href="https://wa.me/51981524571?text=Hola%2C%20quiero%20ver%20las%20funcionalidades%20de%20Facturoom" target="_blank" rel="noopener" class="btn btn-ghost">Pedir una demo</a>
    </div>
  </div>
</section>
@endsection
