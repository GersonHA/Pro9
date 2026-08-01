@extends('system.landing.layout')

@section('title', 'Precios y planes | Facturoom')
@section('description', 'Planes de Facturoom desde S/ 29.90 al mes: Basic, Standard, Pro y Ultra. 60 días gratis, sin tarjeta y sin permanencia. Compara qué incluye cada plan.')
@section('og_title', 'Precios de Facturoom — desde S/ 29.90 al mes')
@section('og_description', 'Cuatro planes para cada tamaño de negocio. 60 días gratis, sin tarjeta de crédito.')

@push('styles')
.pricing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:48px}
.toggle-label{font-size:.9rem;font-weight:700;color:var(--body);cursor:pointer}
.toggle-label.active{color:var(--carbon)}
.toggle-wrap{position:relative;width:52px;height:28px}
.toggle-wrap input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{position:absolute;inset:0;background:var(--navy-3);border-radius:99px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;top:4px;left:4px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.3s}
.toggle-wrap input:checked + .toggle-slider{background:var(--amber)}
.toggle-wrap input:checked + .toggle-slider::before{transform:translateX(24px)}
.toggle-badge{display:inline-block;background:rgba(245,158,11,.14);color:var(--amber-d);border:1px solid rgba(245,158,11,.28);border-radius:99px;font-size:.72rem;font-weight:800;padding:3px 10px;letter-spacing:.05em;text-transform:uppercase}
.plans-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;align-items:start}
.plan-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;transition:transform .28s cubic-bezier(.16,1,.3,1),box-shadow .28s,border-color .28s}
.plan-card:hover{transform:translateY(-5px);box-shadow:0 16px 40px rgba(15,23,42,.1)}
.plan-card.popular{border-color:var(--amber);border-width:2px;position:relative}
.plan-card.popular::before{content:'Más popular';position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--amber);color:#fff;font-size:.66rem;font-weight:800;padding:4px 14px;border-radius:99px;white-space:nowrap;letter-spacing:.06em;text-transform:uppercase}
.plan-name{font-size:.74rem;font-weight:800;color:var(--body);letter-spacing:.1em;text-transform:uppercase;margin-bottom:6px}
.plan-tag{font-size:.82rem;color:var(--mid);margin-bottom:16px;min-height:2.4em;line-height:1.3}
.plan-price{font-size:2.3rem;font-weight:900;color:var(--carbon);letter-spacing:-.03em;line-height:1;margin-bottom:4px;font-variant-numeric:tabular-nums}
.plan-price span{font-size:.86rem;font-weight:600;color:var(--mid)}
.plan-period{font-size:.78rem;color:var(--mid);margin-bottom:20px;min-height:1.2em}
.plan-feats{list-style:none;display:flex;flex-direction:column;gap:11px;margin-bottom:24px}
.plan-feats li{display:flex;align-items:flex-start;gap:8px;font-size:.86rem;color:var(--body);line-height:1.45}
.plan-feats li svg{flex-shrink:0;margin-top:1px;color:var(--amber)}
.plan-btn{display:block;text-align:center;padding:12px 20px;border-radius:9px;font-weight:700;font-size:.9rem;transition:transform .22s cubic-bezier(.16,1,.3,1),background .22s,border-color .22s,color .22s;cursor:pointer}
.plan-btn:active{transform:scale(.97)}
.plan-btn-outline{border:1.5px solid var(--border);color:var(--carbon);background:transparent}
.plan-btn-outline:hover{border-color:var(--amber);color:var(--amber)}
.plan-btn-amber{background:var(--amber);color:#fff}
.plan-btn-amber:hover{background:var(--amber-d);transform:translateY(-2px)}
.incl-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:8px}
.incl-item{display:flex;align-items:center;gap:9px;font-size:.88rem;color:var(--body);font-weight:600;background:#fff;border:1px solid var(--border);border-radius:10px;padding:13px 16px}
.incl-item svg{color:var(--amber);flex-shrink:0}
.faq-wrap{max-width:760px;margin:0 auto}
.faq-item{border:1px solid var(--border);border-radius:12px;margin-bottom:12px;overflow:hidden;background:#fff}
.faq-q{width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:20px 22px;font-size:1rem;font-weight:700;color:var(--carbon);display:flex;align-items:center;justify-content:space-between;gap:16px;font-family:'Outfit',sans-serif}
.faq-q .chev{transition:transform .25s;color:var(--amber);flex-shrink:0}
.faq-item.open .chev{transform:rotate(180deg)}
.faq-a{max-height:0;overflow:hidden;transition:max-height .3s ease;color:var(--body);font-size:.94rem;line-height:1.7}
.faq-a div{padding:0 22px 20px}
@media(max-width:900px){.plans-grid{grid-template-columns:1fr 1fr}.incl-grid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.plans-grid,.incl-grid{grid-template-columns:1fr}}
@endpush

@section('content')
@php $nav = 'precios'; @endphp

<section class="page-hero">
  <div class="page-hero-grid"></div>
  <div class="page-hero-glow"></div>
  <div class="container">
    <div class="inner reveal" style="max-width:760px;text-align:center;margin:0 auto">
      <div class="eyebrow" style="justify-content:center;display:inline-flex"><span style="width:7px;height:7px;border-radius:50%;background:var(--amber);display:inline-block"></span> Precios</div>
      <h1>Un plan para cada <span class="accent">tamaño de negocio</span></h1>
      <p class="lead" style="margin:0 auto">Empieza con 60 días gratis, sin tarjeta de crédito y sin permanencia. Cambia de plan cuando tu negocio crezca.</p>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">
    <div class="pricing-toggle reveal">
      <span class="toggle-label active" id="lbl-mensual">Mensual</span>
      <label class="toggle-wrap"><input type="checkbox" id="billing"><span class="toggle-slider"></span></label>
      <span class="toggle-label" id="lbl-anual">Anual</span>
      <span class="toggle-badge">2 meses gratis</span>
    </div>

    <div class="plans-grid">
      @php
        $plans = [
          ['name'=>'Basic','id'=>'basic','popular'=>false,'tag'=>'Para empezar a facturar formal.','feats'=>['<strong>2</strong> usuarios','<strong>1</strong> sucursal','<strong>250</strong> comprobantes/mes','Notas de venta ilimitadas','Productos ilimitados','Módulo de compras','Soporte técnico']],
          ['name'=>'Standard','id'=>'standard','popular'=>true,'tag'=>'El favorito de bodegas y ferreterías.','feats'=>['<strong>5</strong> usuarios','<strong>2</strong> sucursales','<strong>600</strong> comprobantes/mes','Notas de venta ilimitadas','Productos ilimitados','Módulo de compras','Soporte técnico']],
          ['name'=>'Pro','id'=>'pro','popular'=>false,'tag'=>'Para negocios con varias áreas.','feats'=>['<strong>12</strong> usuarios','<strong>5</strong> sucursales','<strong>1,250</strong> comprobantes/mes','Notas de venta ilimitadas','Productos ilimitados','Módulo de compras','Soporte técnico']],
          ['name'=>'Ultra','id'=>'ultra','popular'=>false,'tag'=>'Toda la potencia de Facturoom.','feats'=>['<strong>25</strong> usuarios','<strong>9</strong> sucursales','<strong>5,000</strong> comprobantes/mes','Notas de venta ilimitadas','Productos ilimitados','Módulo de compras','Soporte técnico']],
        ];
        $monthly = ['basic'=>'29.90','standard'=>'49.90','pro'=>'69.90','ultra'=>'99.90'];
      @endphp
      @foreach($plans as $p)
        <div class="plan-card reveal {{ $p['popular'] ? 'popular' : '' }}">
          <div class="plan-name">{{ $p['name'] }}</div>
          <div class="plan-tag">{{ $p['tag'] }}</div>
          <div class="plan-price" id="price-{{ $p['id'] }}">S/ {{ $monthly[$p['id']] }}<span> al mes + IGV</span></div>
          <div class="plan-period" id="period-{{ $p['id'] }}">Facturado mensualmente</div>
          <ul class="plan-feats">
            @foreach($p['feats'] as $f)
              <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <span>{!! $f !!}</span></li>
            @endforeach
          </ul>
          <a href="#" class="plan-btn {{ $p['popular'] ? 'plan-btn-amber' : 'plan-btn-outline' }}" data-plan="{{ strtoupper($p['name']) }}">Empezar gratis</a>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- INCLUIDO EN TODOS --}}
<section class="page-section alt">
  <div class="container">
    <div class="section-header reveal" style="text-align:center;max-width:600px;margin:0 auto 0">
      <div class="section-eye" style="justify-content:center"><span class="dot"></span> En todos los planes</div>
      <h2 class="section-title">Lo que siempre está incluido</h2>
      <p class="section-sub" style="margin:0 auto">Sin importar el plan que elijas, esto viene de fábrica.</p>
    </div>
    <div class="incl-grid">
      @foreach([
        'Facturación electrónica a SUNAT','CDR automático en segundos','Boletas, NC, ND y GRE','Soporte por WhatsApp',
        '60 días de prueba gratis','Sin permanencia ni contrato','Actualizaciones automáticas','Acceso desde cualquier navegador',
      ] as $i)
        <div class="incl-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> {{ $i }}</div>
      @endforeach
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="page-section">
  <div class="container">
    <div class="section-header reveal" style="text-align:center;max-width:600px;margin:0 auto 44px">
      <div class="section-eye" style="justify-content:center"><span class="dot"></span> Preguntas frecuentes</div>
      <h2 class="section-title">Antes de empezar, resolvamos dudas</h2>
    </div>
    <div class="faq-wrap">
      @php
        $faqs = [
          ['q'=>'¿Los 60 días de prueba son realmente gratis?','a'=>'Sí. Tienes 60 días con acceso completo a todos los módulos, sin pedirte tarjeta de crédito y sin compromiso. Si al terminar no deseas continuar, simplemente no pagas.'],
          ['q'=>'¿Necesito instalar algo en mi computadora?','a'=>'No. Facturoom funciona 100% en la nube desde cualquier navegador (computadora, laptop o tablet). Solo necesitas internet.'],
          ['q'=>'¿Está conectado oficialmente a SUNAT?','a'=>'Sí. Facturoom emite comprobantes electrónicos válidos y recibe el CDR de SUNAT automáticamente, normalmente en menos de 8 segundos.'],
          ['q'=>'¿Puedo cambiar de plan después?','a'=>'Claro. Puedes subir o bajar de plan cuando tu negocio lo necesite. Escríbenos por WhatsApp y hacemos el cambio sin perder tu información.'],
          ['q'=>'¿Los precios incluyen IGV?','a'=>'Los precios mostrados son mensuales y se les agrega el IGV correspondiente. En el plan anual obtienes 2 meses gratis frente al pago mensual.'],
          ['q'=>'¿Cómo recibo soporte?','a'=>'Te atendemos por WhatsApp en español, sin tickets eternos. En los planes superiores el soporte es prioritario e incluye acompañamiento de inicio.'],
        ];
      @endphp
      @foreach($faqs as $f)
        <div class="faq-item reveal">
          <button class="faq-q" type="button">{{ $f['q'] }}
            <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-a"><div>{{ $f['a'] }}</div></div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2 class="reveal">¿Aún con dudas sobre qué plan elegir?</h2>
    <p class="reveal">Cuéntanos cómo es tu negocio por WhatsApp y te recomendamos el plan ideal.</p>
    <div class="actions reveal">
      <a href="https://wa.me/51981524571?text=Hola%2C%20qu%C3%A9%20plan%20de%20Facturoom%20me%20conviene" target="_blank" rel="noopener" class="btn btn-amber">Hablar por WhatsApp</a>
      <a href="{{ route('landing.funcionalidades') }}" class="btn btn-ghost">Ver funcionalidades</a>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
/* ─── PRICING TOGGLE + WA DEEP-LINKS ─────────────────────────────── */
var PRICES = {
  monthly: { basic: '29.90', standard: '49.90', pro: '69.90', ultra: '99.90' },
  annual:  { basic: '299.00', standard: '499.00', pro: '699.00', ultra: '999.00' }
};
var currentPeriod = 'mensual';
function updateWALinks() {
  document.querySelectorAll('[data-plan]').forEach(function(btn) {
    var msg = encodeURIComponent('Hola, estoy interesado en el plan ' + currentPeriod + ' ' + btn.dataset.plan);
    btn.href = 'https://wa.me/51981524571?text=' + msg;
  });
}
function updatePrices(isAnnual) {
  var prices = isAnnual ? PRICES.annual : PRICES.monthly;
  var suffix = isAnnual ? '<span>/año</span>' : '<span> al mes + IGV</span>';
  var periodText = isAnnual ? 'Precio total anual (2 meses gratis)' : 'Facturado mensualmente';
  ['basic','standard','pro','ultra'].forEach(function(id){
    document.getElementById('price-' + id).innerHTML = 'S/ ' + prices[id] + suffix;
    document.getElementById('period-' + id).textContent = periodText;
  });
  document.getElementById('lbl-mensual').classList.toggle('active', !isAnnual);
  document.getElementById('lbl-anual').classList.toggle('active', isAnnual);
}
var billing = document.getElementById('billing');
if (billing) {
  billing.addEventListener('change', function() {
    currentPeriod = this.checked ? 'anual' : 'mensual';
    updatePrices(this.checked);
    updateWALinks();
  });
}
updateWALinks();

/* ─── FAQ ACCORDION ──────────────────────────────────────────────── */
document.querySelectorAll('.faq-item').forEach(function(item) {
  var q = item.querySelector('.faq-q');
  var a = item.querySelector('.faq-a');
  q.addEventListener('click', function() {
    var open = item.classList.toggle('open');
    a.style.maxHeight = open ? a.scrollHeight + 'px' : null;
  });
});
</script>
@endpush
