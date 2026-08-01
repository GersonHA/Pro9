@extends('system.landing.layout')

@section('title', 'Contacto y soporte | Facturoom')
@section('description', 'Habla con el equipo de Facturoom. Soporte por WhatsApp +51 981 524 571 y correo. Resolvemos tus dudas sobre facturación electrónica, SUNAT y la prueba gratis de 60 días.')
@section('og_title', 'Contacto — Facturoom')
@section('og_description', 'Escríbenos por WhatsApp o correo. Te ayudamos a empezar con tu facturación electrónica en minutos.')

@push('styles')
.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start}
.channels{display:flex;flex-direction:column;gap:16px}
.channel{display:flex;align-items:center;gap:18px;background:#fff;border:1px solid var(--border);border-radius:14px;padding:20px 22px;transition:transform .25s cubic-bezier(.16,1,.3,1),box-shadow .25s,border-color .25s}
.channel:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(15,23,42,.1);border-color:rgba(245,158,11,.3)}
.channel-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.channel-icon.wa{background:rgba(37,211,102,.12);color:#25D366}
.channel-icon.mail{background:rgba(245,158,11,.1);color:var(--amber)}
.channel-icon.clock{background:rgba(148,163,184,.14);color:var(--mid)}
.channel .t{font-size:1.02rem;font-weight:800;color:var(--carbon);margin-bottom:3px}
.channel .d{font-size:.88rem;color:var(--body)}
.form-card{background:#fff;border:1px solid var(--border);border-radius:18px;padding:32px;box-shadow:0 16px 40px -20px rgba(15,23,42,.18)}
.form-card h2{font-size:1.3rem;font-weight:900;color:var(--carbon);margin-bottom:6px;letter-spacing:-.02em}
.form-card .form-sub{font-size:.9rem;color:var(--body);margin-bottom:24px;line-height:1.55}
.field{display:flex;flex-direction:column;gap:7px;margin-bottom:16px}
.field label{font-size:.8rem;font-weight:700;color:var(--carbon)}
.field input,.field textarea,.field select{font-family:'Outfit',sans-serif;background:var(--paper);border:1px solid var(--border);border-radius:10px;padding:12px 14px;color:var(--carbon);font-size:.94rem;transition:border-color .2s,background .2s}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;border-color:var(--amber);background:#fff;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.field input::placeholder,.field textarea::placeholder{color:var(--mid)}
.field textarea{resize:vertical;min-height:96px}
.form-card .btn{width:100%;justify-content:center;margin-top:6px}
.form-hint{font-size:.78rem;color:var(--mid);text-align:center;margin-top:12px}
@media(max-width:768px){.contact-grid{grid-template-columns:1fr;gap:32px}}
@endpush

@section('content')
@php $nav = 'contacto'; @endphp

<section class="page-hero">
  <div class="page-hero-grid"></div>
  <div class="page-hero-glow"></div>
  <div class="container">
    <div class="inner reveal">
      <div class="eyebrow"><span style="width:7px;height:7px;border-radius:50%;background:var(--amber);display:inline-block"></span> Contacto</div>
      <h1>Estamos a un <span class="accent">mensaje</span> de distancia.</h1>
      <p class="lead">¿Dudas sobre la facturación electrónica, SUNAT o cómo empezar? Escríbenos por WhatsApp o déjanos tus datos y te contactamos. Respondemos en minutos, en horario de oficina.</p>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">
    <div class="contact-grid">
      {{-- CHANNELS --}}
      <div class="reveal">
        <div class="section-eye"><span class="dot"></span> Canales directos</div>
        <h2 class="section-title" style="font-size:1.6rem;margin-bottom:24px">Habla con nosotros</h2>
        <div class="channels">
          <a class="channel" href="https://wa.me/51981524571?text=Hola%2C%20quiero%20información%20sobre%20Facturoom" target="_blank" rel="noopener">
            <div class="channel-icon wa"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg></div>
            <div><div class="t">WhatsApp · +51 981 524 571</div><div class="d">La forma más rápida. Toca para escribirnos ahora.</div></div>
          </a>
          <a class="channel" href="mailto:facturoom@gmail.com">
            <div class="channel-icon mail"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
            <div><div class="t">Correo electrónico</div><div class="d">facturoom@gmail.com</div></div>
          </a>
          <a class="channel" href="https://www.facebook.com/facturoom" target="_blank" rel="noopener">
            <div class="channel-icon" style="background:rgba(24,119,242,.1);color:#1877F2"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8v8.44C19.61 23.08 24 18.09 24 12.07z"/></svg></div>
            <div><div class="t">Facebook</div><div class="d">Síguenos y escríbenos en facebook.com/facturoom</div></div>
          </a>
          <div class="channel">
            <div class="channel-icon clock"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="t">Horario de atención</div><div class="d">Lunes a sábado · 9:00 a. m. – 7:00 p. m.</div></div>
          </div>
        </div>
      </div>

      {{-- FORM → WhatsApp --}}
      <div class="form-card reveal">
        <h2>Déjanos tus datos</h2>
        <p class="form-sub">Completa el formulario y se abrirá WhatsApp con tu mensaje listo para enviar. Sin formularios eternos.</p>
        <form id="contact-form">
          <div class="field">
            <label for="cf-name">Tu nombre</label>
            <input type="text" id="cf-name" placeholder="Ej. María Quispe" required>
          </div>
          <div class="field">
            <label for="cf-biz">Nombre de tu negocio</label>
            <input type="text" id="cf-biz" placeholder="Ej. Ferretería Los Andes">
          </div>
          <div class="field">
            <label for="cf-interest">¿En qué te ayudamos?</label>
            <select id="cf-interest">
              <option value="información general">Información general</option>
              <option value="una demo del sistema">Quiero una demo</option>
              <option value="elegir un plan">Ayuda para elegir un plan</option>
              <option value="soporte técnico">Soporte técnico</option>
            </select>
          </div>
          <div class="field">
            <label for="cf-msg">Mensaje (opcional)</label>
            <textarea id="cf-msg" placeholder="Cuéntanos brevemente sobre tu negocio…"></textarea>
          </div>
          <button type="submit" class="btn btn-amber">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.106 1.523 5.834L0 24l6.336-1.501A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
            Enviar por WhatsApp
          </button>
          <p class="form-hint">Te responderemos lo antes posible en horario de oficina.</p>
        </form>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2 class="reveal">¿Prefieres probarlo tú mismo?</h2>
    <p class="reveal">Activa tu prueba gratis de 60 días y empieza a facturar hoy.</p>
    <div class="actions reveal">
      <a href="{{ route('landing.precios') }}" class="btn btn-amber">Ver planes y precios</a>
      <a href="{{ route('landing.funcionalidades') }}" class="btn btn-ghost">Ver funcionalidades</a>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
/* ─── FORMULARIO → WhatsApp (sin backend) ────────────────────────── */
var form = document.getElementById('contact-form');
if (form) {
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var name = document.getElementById('cf-name').value.trim();
    var biz = document.getElementById('cf-biz').value.trim();
    var interest = document.getElementById('cf-interest').value;
    var msg = document.getElementById('cf-msg').value.trim();

    var text = 'Hola, soy ' + (name || 'un interesado');
    if (biz) text += ' del negocio ' + biz;
    text += '. Quiero ' + interest + '.';
    if (msg) text += ' ' + msg;

    window.open('https://wa.me/51981524571?text=' + encodeURIComponent(text), '_blank');
  });
}
</script>
@endpush
