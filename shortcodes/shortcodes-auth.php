<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// [vx_onboarding] — wizard de 6 pasos (estructura exacta del mockup onboarding.html)
add_shortcode( 'vx_onboarding', function (): string {
    $user_id = get_current_user_id();
    if ( ! $user_id ) return '';
    $state       = VX_Onboarding::get_state( $user_id );
    $paso_actual = (int) $state['paso_actual'];

    $user        = VX_User::get( $user_id );
    $nombre      = $user ? $user->get_nombre() : '';
    $apellido    = $user ? $user->get_apellido() : '';
    $bio         = $user ? $user->get_bio() : '';
    $ciudad      = $user ? $user->get_ciudad() : '';
    $pais        = $user ? $user->get_pais() : '';
    $contacto    = get_user_meta( $user_id, VX_User_Meta::CONTACTO_PREFERIDO, true ) ?: 'email';
    $telefono    = get_user_meta( $user_id, VX_User_Meta::TELEFONO, true );
    $linkedin    = get_user_meta( $user_id, VX_User_Meta::LINKEDIN, true );

    $foto_id     = (int) get_user_meta( $user_id, VX_User_Meta::FOTO, true );
    $foto_url    = $foto_id ? wp_get_attachment_image_url( $foto_id, 'vx-avatar' ) : '';

    $tags_preset = [
        'Marketing digital','Diseño y branding','Desarrollo web','Consultoría',
        'Ventas B2B','CRM','Automatización','Legal','Finanzas','RRHH',
        'Data e inteligencia','Logística','Producción audiovisual','Real estate',
        'Partners tecnológicos','Alianzas comerciales',
    ];

    $api_url = rest_url( VX_REST_NAMESPACE . '/' );
    $nonce   = wp_create_nonce( 'wp_rest' );

    ob_start();
    ?>
<!-- NAV CON PROGRESO -->
<nav class="ob-nav">
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="d-flex align-items-center gap-2 text-decoration-none">
    <img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/vitrinexo.svg' ) ); ?>" width="90" alt="Vitrinexo">
  </a>

  <div class="ob-steps d-none d-md-flex" id="ob-steps-indicator">
    <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
    <div class="ob-step <?php echo $paso_actual > $i ? 'ob-step--done' : ( $paso_actual === $i ? 'ob-step--active' : '' ); ?>" id="dot-<?php echo $i; ?>">
      <div class="ob-step-dot">
        <?php echo $paso_actual > $i ? '<i class="ti ti-check" style="font-size:11px"></i>' : $i; ?>
      </div>
    </div>
    <?php if ( $i < 6 ) : ?>
    <div class="ob-step-line <?php echo $paso_actual > $i ? 'ob-step-line--done' : ''; ?>" id="line-<?php echo $i; ?>"></div>
    <?php endif; ?>
    <?php endfor; ?>
  </div>

  <div class="ob-progress d-md-none">
    <div class="ob-progress-track">
      <div class="ob-progress-fill" id="ob-progress-fill" style="width:<?php echo round( ( $paso_actual - 1 ) / 5 * 100 ); ?>%"></div>
    </div>
    <div class="ob-progress-label" id="ob-progress-label">Paso <?php echo $paso_actual; ?> de 6</div>
  </div>

  <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="dashboard-date text-decoration-none">Completar después</a>
</nav>

<!-- PANELES -->
<div class="flex-grow-1 py-4 px-3">

  <!-- ── PASO 1: Bienvenida ── -->
  <div class="ob-panel <?php echo 1 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-1">
    <div class="ob-card text-center">
      <div class="ob-step-eyebrow">Paso 1 de 6</div>
      <h1 class="ob-title">Hola<?php echo $nombre ? ', ' . esc_html( $nombre ) . '.' : '.'; ?><br>Construyamos tu vitrina.</h1>
      <p class="ob-lead">Te vamos a hacer unas preguntas para que tu perfil quede completo y la red pueda encontrarte. Son 5 pasos cortos — menos de 5 minutos.</p>
      <div class="ob-welcome-list">
        <div class="ob-welcome-item"><div class="ob-welcome-icon ob-welcome-icon--green"><i class="ti ti-user"></i></div>Datos básicos de tu perfil</div>
        <div class="ob-welcome-item"><div class="ob-welcome-icon ob-welcome-icon--cyan"><i class="ti ti-building"></i></div>Tu empresa y tu rol</div>
        <div class="ob-welcome-item"><div class="ob-welcome-icon ob-welcome-icon--pink"><i class="ti ti-arrows-exchange"></i></div>Qué ofreces y qué buscas en la red</div>
        <div class="ob-welcome-item"><div class="ob-welcome-icon ob-welcome-icon--purple"><i class="ti ti-users"></i></div>Comunidades a las que quieres pertenecer</div>
      </div>
      <div class="ob-footer ob-footer--center">
        <button class="btn-vx btn-primary-vx btn-vx-lg" onclick="obGoTo(2)">Empezar <i class="ti ti-arrow-right ms-1"></i></button>
      </div>
    </div>
  </div>

  <!-- ── PASO 2: Datos personales ── -->
  <div class="ob-panel <?php echo 2 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-2">
    <div class="ob-card">
      <div class="ob-step-eyebrow">Paso 2 de 6</div>
      <h2 class="ob-title">Cuéntanos sobre ti</h2>
      <p class="ob-lead">Esta información aparece en tu ficha pública.</p>

      <div class="ob-upload-row">
        <div class="logo-upload-zone" id="foto-zone" onclick="document.getElementById('foto-input').click()" style="cursor:pointer">
          <?php if ( $foto_url ) : ?>
          <img src="<?php echo esc_url( $foto_url ); ?>" alt="Tu foto" id="foto-preview" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
          <?php else : ?>
          <i class="ti ti-user ob-logo-placeholder" id="foto-icon"></i>
          <img src="" alt="Tu foto" id="foto-preview" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:inherit">
          <?php endif; ?>
          <div class="logo-upload-overlay"><i class="ti ti-camera"></i></div>
        </div>
        <input type="file" id="foto-input" accept="image/*" style="display:none">
        <input type="hidden" id="foto-id" value="<?php echo esc_attr( $foto_id ?: '' ); ?>">
        <div>
          <p class="ob-upload-meta-title">Tu foto de perfil</p>
          <p class="ob-upload-meta-hint">Recomendado: foto profesional, fondo neutro</p>
          <button class="btn-vx btn-ghost-vx btn-vx-sm" type="button" onclick="document.getElementById('foto-input').click()">
            <i class="ti ti-upload me-1"></i>Subir foto
          </button>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label-vx">Nombre *</label>
          <input type="text" id="ob2-nombre" class="form-control-vx" value="<?php echo esc_attr( $nombre ); ?>" placeholder="Tu nombre">
        </div>
        <div class="col-md-6">
          <label class="form-label-vx">Apellido *</label>
          <input type="text" id="ob2-apellido" class="form-control-vx" value="<?php echo esc_attr( $apellido ); ?>" placeholder="Tu apellido">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label-vx">Bio profesional <span class="form-hint d-inline">(opcional · máx. 300 caracteres)</span></label>
        <textarea id="ob2-bio" class="form-control-vx" rows="3" maxlength="300" placeholder="Ej: Especialista en marketing B2B con 8 años de experiencia en LATAM..."><?php echo esc_textarea( $bio ); ?></textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label-vx">Ciudad</label>
          <input type="text" id="ob2-ciudad" class="form-control-vx" value="<?php echo esc_attr( $ciudad ); ?>" placeholder="Bogotá">
        </div>
        <div class="col-md-6">
          <label class="form-label-vx">País *</label>
          <input type="text" id="ob2-pais" class="form-control-vx"
                 list="vx-paises-list"
                 value="<?php echo esc_attr( $pais ); ?>"
                 placeholder="Escribe tu país..."
                 autocomplete="off">
          <?php echo vx_paises_datalist(); ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label-vx">Preferencia de contacto</label>
        <select id="ob2-contacto" class="form-control-vx ob-select-sm">
          <option value="email" <?php selected( $contacto, 'email' ); ?>>Email</option>
          <option value="telefono" <?php selected( $contacto, 'telefono' ); ?>>Teléfono</option>
          <option value="linkedin" <?php selected( $contacto, 'linkedin' ); ?>>LinkedIn</option>
        </select>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label-vx">Teléfono <span class="form-hint d-inline">(opcional)</span></label>
          <input type="tel" id="ob2-telefono" class="form-control-vx" value="<?php echo esc_attr( $telefono ); ?>" placeholder="+56 9 1234 5678">
        </div>
        <div class="col-md-6">
          <label class="form-label-vx">LinkedIn <span class="form-hint d-inline">(opcional)</span></label>
          <input type="url" id="ob2-linkedin" class="form-control-vx" value="<?php echo esc_attr( $linkedin ); ?>" placeholder="https://linkedin.com/in/...">
        </div>
      </div>

      <div id="ob2-error" class="alert-vx alert-error d-none mb-3"></div>
      <div class="ob-footer">
        <button class="btn-vx btn-ghost-vx btn-vx-md" onclick="obGoTo(1)"><i class="ti ti-arrow-left me-1"></i> Atrás</button>
        <button class="btn-vx btn-primary-vx btn-vx-md" onclick="obSave2()">Continuar <i class="ti ti-arrow-right ms-1"></i></button>
      </div>
    </div>
  </div>

  <!-- ── PASO 3: Empresa ── -->
  <div class="ob-panel <?php echo 3 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-3">
    <div class="ob-card">
      <div class="ob-step-eyebrow">Paso 3 de 6</div>
      <h2 class="ob-title">Tu empresa</h2>
      <p class="ob-lead">La empresa con la que apareces en el directorio. Puedes agregar más desde tu perfil.</p>

      <div class="ob-upload-row">
        <div class="logo-upload-zone" id="logo-zone" onclick="document.getElementById('logo-input').click()" style="cursor:pointer">
          <i class="ti ti-building ob-logo-placeholder" id="logo-empresa-icon"></i>
          <img src="" alt="Logo empresa" id="logo-empresa-preview" style="display:none;width:100%;height:100%;object-fit:contain;border-radius:inherit">
          <div class="logo-upload-overlay"><i class="ti ti-camera"></i></div>
        </div>
        <input type="file" id="logo-input" accept="image/*" style="display:none">
        <input type="hidden" id="logo-empresa-id" value="">
        <div>
          <p class="ob-upload-meta-title">Logo de la empresa</p>
          <p class="ob-upload-meta-hint">Circular · Fondo blanco recomendado</p>
          <button class="btn-vx btn-ghost-vx btn-vx-sm" type="button" onclick="document.getElementById('logo-input').click()">
            <i class="ti ti-upload me-1"></i>Subir logo
          </button>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label-vx">Nombre de la empresa *</label>
        <input type="text" id="ob3-empresa-nombre" class="form-control-vx" placeholder="Ej: BrandLab Internacional">
      </div>
      <div class="mb-3">
        <label class="form-label-vx">Tu cargo / rol *</label>
        <input type="text" id="ob3-empresa-cargo" class="form-control-vx" placeholder="Ej: Directora de Estrategia">
      </div>
      <div class="mb-3">
        <label class="form-label-vx">Sitio web <span class="form-hint d-inline">(opcional)</span></label>
        <div class="input-group-vx">
          <span class="input-icon input-prefix">https://</span>
          <input type="text" id="ob3-empresa-web" placeholder="tuempresa.com">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label-vx">LinkedIn empresa <span class="form-hint d-inline">(opcional)</span></label>
        <div class="input-group-vx">
          <span class="input-icon"><i class="ti ti-brand-linkedin"></i></span>
          <input type="text" id="ob3-empresa-linkedin" placeholder="linkedin.com/company/tuempresa">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label-vx">Descripción breve <span class="form-hint d-inline">(opcional)</span></label>
        <textarea id="ob3-empresa-desc" class="form-control-vx" rows="2" placeholder="En qué se especializa la empresa, a quién atiende..."></textarea>
      </div>

      <div id="ob3-error" class="alert-vx alert-error d-none mb-3"></div>
      <div class="ob-footer">
        <button class="btn-vx btn-ghost-vx btn-vx-md" onclick="obGoTo(2)"><i class="ti ti-arrow-left me-1"></i> Atrás</button>
        <button class="btn-vx btn-primary-vx btn-vx-md" onclick="obSave3()">Continuar <i class="ti ti-arrow-right ms-1"></i></button>
      </div>
    </div>
  </div>

  <!-- ── PASO 4: Ofrece / Busca ── -->
  <div class="ob-panel <?php echo 4 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-4">
    <div class="ob-card">
      <div class="ob-step-eyebrow">Paso 4 de 6</div>
      <h2 class="ob-title">¿Qué traes y qué buscas?</h2>
      <p class="ob-lead">Estos tags son la base de tus matches. Selecciona los que mejor te representen — puedes editarlos después.</p>

      <div class="mb-4 pb-4 ob-section-divider">
        <div class="ob-tag-section-label">
          <div class="ob-tag-dot ob-tag-dot--offer"></div>
          <label class="form-label-vx mb-0">¿Qué ofreces? <span class="form-hint d-inline">(hasta 5)</span></label>
        </div>
        <div class="tag-selector mb-3" id="tags-offer">
          <?php foreach ( $tags_preset as $tag ) : ?>
          <span class="tag-option" data-type="offer" onclick="obToggleTag(this,'offer')"><?php echo esc_html( $tag ); ?></span>
          <?php endforeach; ?>
        </div>
        <div class="input-group-vx ob-input-sm">
          <span class="input-icon"><i class="ti ti-plus"></i></span>
          <input type="text" id="ob4-offer-custom" placeholder="Agregar otro..." onkeydown="obAddCustomTag(event,'offer')">
        </div>
      </div>

      <div class="mb-3">
        <div class="ob-tag-section-label">
          <div class="ob-tag-dot ob-tag-dot--seek"></div>
          <label class="form-label-vx mb-0">¿Qué buscas? <span class="form-hint d-inline">(hasta 5)</span></label>
        </div>
        <div class="tag-selector mb-3" id="tags-seek">
          <?php foreach ( $tags_preset as $tag ) : ?>
          <span class="tag-option" data-type="seek" onclick="obToggleTag(this,'seek')"><?php echo esc_html( $tag ); ?></span>
          <?php endforeach; ?>
        </div>
        <div class="input-group-vx ob-input-sm">
          <span class="input-icon"><i class="ti ti-plus"></i></span>
          <input type="text" id="ob4-seek-custom" placeholder="Agregar otro..." onkeydown="obAddCustomTag(event,'seek')">
        </div>
      </div>

      <div class="ob-footer">
        <button class="btn-vx btn-ghost-vx btn-vx-md" onclick="obGoTo(3)"><i class="ti ti-arrow-left me-1"></i> Atrás</button>
        <button class="btn-vx btn-primary-vx btn-vx-md" onclick="obSave4()">Continuar <i class="ti ti-arrow-right ms-1"></i></button>
      </div>
    </div>
  </div>

  <!-- ── PASO 5: Comunidades ── -->
  <div class="ob-panel <?php echo 5 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-5">
    <div class="ob-card">
      <div class="ob-step-eyebrow">Paso 5 de 6</div>
      <h2 class="ob-title">¿A qué comunidades perteneces?</h2>
      <p class="ob-lead">Opcionales. Puedes activarlas o desactivarlas en cualquier momento desde tu perfil.</p>

      <div class="community-toggle" id="com-out2b" onclick="obToggleCom(this)">
        <div class="community-toggle__icon community-toggle__icon--out2b"><i class="ti ti-rainbow ob-community-icon-i"></i></div>
        <div>
          <div class="community-toggle__title">Out2B</div>
          <div class="community-toggle__desc">Comunidad LGBTQ+ en el mundo empresarial</div>
        </div>
        <i class="ti ti-circle community-toggle__check"></i>
      </div>

      <div class="community-toggle" id="com-woman" onclick="obToggleCom(this)">
        <div class="community-toggle__icon community-toggle__icon--woman"><i class="ti ti-gender-female ob-community-icon-i"></i></div>
        <div>
          <div class="community-toggle__title">Woman</div>
          <div class="community-toggle__desc">Mujeres en posiciones de liderazgo empresarial</div>
        </div>
        <i class="ti ti-circle community-toggle__check"></i>
      </div>

      <div class="community-toggle" id="com-senior" onclick="obToggleCom(this)">
        <div class="community-toggle__icon community-toggle__icon--senior"><i class="ti ti-award ob-community-icon-i"></i></div>
        <div>
          <div class="community-toggle__title">Senior</div>
          <div class="community-toggle__desc">Ejecutivos con trayectoria consolidada — requiere verificación</div>
        </div>
        <i class="ti ti-circle community-toggle__check"></i>
      </div>

      <div class="ob-footer">
        <button class="btn-vx btn-ghost-vx btn-vx-md" onclick="obGoTo(4)"><i class="ti ti-arrow-left me-1"></i> Atrás</button>
        <button class="btn-vx btn-primary-vx btn-vx-md" onclick="obSave5()">Continuar <i class="ti ti-arrow-right ms-1"></i></button>
      </div>
    </div>
  </div>

  <!-- ── PASO 6: Listo ── -->
  <div class="ob-panel <?php echo 6 === $paso_actual ? 'ob-panel--active' : ''; ?>" id="panel-6">
    <div class="ob-card text-center">
      <div class="ob-success-icon mx-auto mb-3"><i class="ti ti-check ob-success-check"></i></div>
      <div class="ob-step-eyebrow">Tu vitrina está lista</div>
      <h2 class="ob-title">Ya estás en la red.<br><strong class="text-primary-color">Encuentra tus próximos nexos.</strong></h2>
      <p class="ob-lead">Tu perfil ya aparece en el directorio y el sistema está calculando tus matches. Puedes completar más detalles en cualquier momento desde tu perfil.</p>
      <div class="ob-success-actions">
        <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-lg">Ir a mi dashboard <i class="ti ti-arrow-right ms-1"></i></a>
        <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-md">Explorar el directorio</a>
      </div>
    </div>
  </div>

</div><!-- /.flex-grow-1 -->

<script>
(function(){
  var API   = <?php echo wp_json_encode( $api_url ); ?>;
  var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
  var TOTAL = 6;
  var current = <?php echo $paso_actual; ?>;

  // ── Navegación ──────────────────────────────────────────────────────────────
  window.obGoTo = function(step) {
    var prev = document.getElementById('panel-' + current);
    if (prev) prev.classList.remove('ob-panel--active');
    current = step;
    var next = document.getElementById('panel-' + current);
    if (next) next.classList.add('ob-panel--active');
    obUpdateIndicators();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  function obUpdateIndicators() {
    var pct = Math.round(((current - 1) / (TOTAL - 1)) * 100);
    var fill = document.getElementById('ob-progress-fill');
    var lbl  = document.getElementById('ob-progress-label');
    if (fill) fill.style.width = pct + '%';
    if (lbl)  lbl.textContent = 'Paso ' + current + ' de ' + TOTAL;
    for (var i = 1; i <= TOTAL; i++) {
      var dot = document.getElementById('dot-' + i);
      if (!dot) continue;
      dot.classList.remove('ob-step--done', 'ob-step--active');
      if (i < current)      dot.classList.add('ob-step--done');
      else if (i === current) dot.classList.add('ob-step--active');
      var dotEl = dot.querySelector('.ob-step-dot');
      if (dotEl) dotEl.innerHTML = i < current ? '<i class="ti ti-check" style="font-size:11px"></i>' : i;
      var line = document.getElementById('line-' + i);
      if (line) line.classList.toggle('ob-step-line--done', i < current);
    }
  }

  // ── Tags ─────────────────────────────────────────────────────────────────────
  window.obToggleTag = function(el, type) {
    var cls = type === 'offer' ? 'tag-option--selected-offer' : 'tag-option--selected-seek';
    if (el.classList.contains(cls)) { el.classList.remove(cls); return; }
    var container = document.getElementById('tags-' + type);
    if (container && container.querySelectorAll('.' + cls).length < 5) el.classList.add(cls);
  };

  window.obAddCustomTag = function(e, type) {
    if (e.key !== 'Enter' && e.key !== ',') return;
    e.preventDefault();
    var val = e.target.value.trim();
    if (!val) return;
    var container = document.getElementById('tags-' + type);
    var cls = type === 'offer' ? 'tag-option--selected-offer' : 'tag-option--selected-seek';
    if (!container || container.querySelectorAll('.' + cls).length >= 5) return;
    // Check not duplicate
    var existing = Array.from(container.querySelectorAll('.tag-option')).map(function(t){ return t.textContent.toLowerCase(); });
    if (existing.includes(val.toLowerCase())) { e.target.value = ''; return; }
    var span = document.createElement('span');
    span.className = 'tag-option ' + cls;
    span.dataset.type = type;
    span.setAttribute('onclick', "obToggleTag(this,'" + type + "')");
    span.textContent = val;
    container.appendChild(span);
    e.target.value = '';
  };

  function obGetSelectedTags(type) {
    var cls = type === 'offer' ? 'tag-option--selected-offer' : 'tag-option--selected-seek';
    var container = document.getElementById('tags-' + type);
    if (!container) return [];
    return Array.from(container.querySelectorAll('.' + cls)).map(function(el){ return el.textContent.trim(); });
  }

  // ── Comunidades ───────────────────────────────────────────────────────────────
  window.obToggleCom = function(el) {
    var on = el.classList.toggle('community-toggle--selected');
    var icon = el.querySelector('.community-toggle__check');
    if (icon) { icon.classList.toggle('ti-circle-check', on); icon.classList.toggle('ti-circle', !on); }
  };

  // ── Upload foto ───────────────────────────────────────────────────────────────
  document.getElementById('foto-input').addEventListener('change', function() {
    obUpload(this, 'foto', function(url, id) {
      var prev = document.getElementById('foto-preview');
      var icon = document.getElementById('foto-icon');
      if (prev) { prev.src = url; prev.style.display = ''; }
      if (icon) icon.style.display = 'none';
      var hid = document.getElementById('foto-id');
      if (hid) hid.value = id;
    });
  });

  document.getElementById('logo-input').addEventListener('change', function() {
    obUpload(this, 'logo', function(url, id) {
      var prev = document.getElementById('logo-empresa-preview');
      var icon = document.getElementById('logo-empresa-icon');
      if (prev) { prev.src = url; prev.style.display = ''; }
      if (icon) icon.style.display = 'none';
      var hid = document.getElementById('logo-empresa-id');
      if (hid) hid.value = id;
    });
  });

  function obUpload(input, tipo, cb) {
    var file = input.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('file', file);
    fd.append('tipo', tipo);
    fetch(API + 'upload', { method:'POST', headers:{'X-WP-Nonce': NONCE}, body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){ if (d.success && d.url) cb(d.url, d.attachment_id); });
  }

  // ── API save ──────────────────────────────────────────────────────────────────
  function obSave(paso, datos, onSuccess) {
    fetch(API + 'onboarding/paso', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
      body: JSON.stringify({ paso: paso, datos: datos, partial: false }),
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success) { onSuccess(); }
      else {
        var errEl = document.getElementById('ob' + paso + '-error');
        var msg = obErrorMsg(d.errors || []);
        if (errEl) { errEl.textContent = msg; errEl.classList.remove('d-none'); }
        else alert(msg);
      }
    })
    .catch(function(){ alert('Error de red. Intenta de nuevo.'); });
  }

  function obErrorMsg(errors) {
    var map = {
      nombre_requerido: 'El nombre es obligatorio.',
      apellido_requerido: 'El apellido es obligatorio.',
      pais_requerido: 'El país es obligatorio.',
      empresa_nombre_requerido: 'El nombre de la empresa es obligatorio.',
      empresa_cargo_requerido: 'Tu cargo en la empresa es obligatorio.',
    };
    return errors.map(function(e){ return map[e] || e; }).join(' ') || 'Error al guardar.';
  }

  // ── Guardado por paso ─────────────────────────────────────────────────────────
  window.obSave2 = function() {
    var errEl = document.getElementById('ob2-error');
    if (errEl) errEl.classList.add('d-none');
    obSave(2, {
      nombre:             document.getElementById('ob2-nombre').value.trim(),
      apellido:           document.getElementById('ob2-apellido').value.trim(),
      bio:                document.getElementById('ob2-bio').value.trim(),
      ciudad:             document.getElementById('ob2-ciudad').value.trim(),
      pais:               document.getElementById('ob2-pais').value,
      contacto_preferido: document.getElementById('ob2-contacto').value,
      telefono:           document.getElementById('ob2-telefono').value.trim(),
      linkedin:           document.getElementById('ob2-linkedin').value.trim(),
      foto_id:            document.getElementById('foto-id').value,
    }, function(){ obGoTo(3); });
  };

  window.obSave3 = function() {
    var errEl = document.getElementById('ob3-error');
    if (errEl) errEl.classList.add('d-none');
    var web = document.getElementById('ob3-empresa-web').value.trim();
    if (web && !web.startsWith('http')) web = 'https://' + web;
    var lin = document.getElementById('ob3-empresa-linkedin').value.trim();
    if (lin && !lin.startsWith('http')) lin = 'https://' + lin;
    obSave(3, {
      empresa_nombre:   document.getElementById('ob3-empresa-nombre').value.trim(),
      empresa_cargo:    document.getElementById('ob3-empresa-cargo').value.trim(),
      empresa_web:      web,
      empresa_linkedin: lin,
      empresa_desc:     document.getElementById('ob3-empresa-desc').value.trim(),
      empresa_logo_id:  document.getElementById('logo-empresa-id').value,
    }, function(){ obGoTo(4); });
  };

  window.obSave4 = function() {
    obSave(4, {
      offer_tags: obGetSelectedTags('offer'),
      seek_tags:  obGetSelectedTags('seek'),
    }, function(){ obGoTo(5); });
  };

  window.obSave5 = function() {
    obSave(5, {
      out2b:  document.getElementById('com-out2b').classList.contains('community-toggle--selected')  ? '1' : '',
      woman:  document.getElementById('com-woman').classList.contains('community-toggle--selected')  ? '1' : '',
      senior: document.getElementById('com-senior').classList.contains('community-toggle--selected') ? '1' : '',
    }, function(){
      // Llamar al endpoint de completar onboarding (activa comunidades, plan, etc.)
      fetch(API + 'onboarding/completar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
      }).then(function(){ obGoTo(6); });
    });
  };

  obUpdateIndicators();
})();
</script>
    <?php
    return ob_get_clean();
} );

// ─── HELPERS ──────────────────────────────────────────────────────────────────

/**
 * Lista completa de países de Latinoamérica + España, ordenada alfabéticamente.
 * Fuente canónica: usada en onboarding, editor-perfil y cualquier select de país.
 *
 * @return string[]
 */
function vx_get_paises_latam(): array {
    return [
        'Argentina', 'Bolivia', 'Brasil', 'Chile', 'Colombia',
        'Costa Rica', 'Cuba', 'Ecuador', 'El Salvador', 'España',
        'Guatemala', 'Honduras', 'México', 'Nicaragua', 'Panamá',
        'Paraguay', 'Perú', 'Puerto Rico', 'República Dominicana',
        'Uruguay', 'Venezuela',
    ];
}

/**
 * Renderiza el <datalist> de países para usar con <input list="vx-paises-list">.
 * Incluido una sola vez por página (evita IDs duplicados con un flag estático).
 */
function vx_paises_datalist(): string {
    static $rendered = false;
    if ( $rendered ) return '';
    $rendered = true;
    $html = '<datalist id="vx-paises-list">';
    foreach ( vx_get_paises_latam() as $p ) {
        $html .= '<option value="' . esc_attr( $p ) . '">';
    }
    $html .= '</datalist>';
    return $html;
}

/**
 * Renderiza una member card con la estructura exacta del mockup.
 *
 * @param array $m         to_card_array() del usuario
 * @param int   $viewer_id ID del usuario que navega (0 = anónimo)
 */
function vx_render_member_card( array $m, int $viewer_id = 0 ): string {
    $uid     = (int) ( $m['id'] ?? 0 );
    $slug    = $m['slug'] ?? '';
    $nombre  = $m['nombre'] ?? '';
    $foto    = $m['foto_url'] ?? '';
    $empresa = $m['empresa'] ?? '';
    $ciudad  = $m['ciudad'] ?? '';
    $pais    = $m['pais_codigo'] ?? $m['pais'] ?? '';

    $perfil_url = home_url( '/perfil/' . $slug . '/' );

    // Badges
    $badges = '';
    if ( ! empty( $m['is_founder'] ) ) {
        $badges .= ' <span class="founder-tooltip" data-tooltip="Miembro fundador" tabindex="0"><i class="founder-tag ti ti-star"></i></span>';
    }
    if ( ! empty( $m['comunidades'] ) && in_array( 'senior', (array) $m['comunidades'], true ) ) {
        $badges .= ' <span class="badge-vx badge-neutral" style="font-size:10px">Senior</span>';
    }

    // Favorito
    $is_fav    = false;
    $fav_style = '';
    if ( $viewer_id && $viewer_id !== $uid ) {
        $vo = VX_User::get( $viewer_id );
        if ( $vo && in_array( $uid, (array) $vo->get_favoritos(), true ) ) {
            $is_fav    = true;
            $fav_style = ' style="color:var(--color-pink-500)"';
        }
    }
    $fav_icon = $is_fav ? 'ti-heart-filled' : 'ti-heart';

    // Tags
    $tags_html = '';
    $has_offer = ! empty( $m['offer_tags'] );
    $has_seek  = ! empty( $m['seek_tags'] );
    if ( $has_offer || $has_seek ) {
        $tags_html .= '<div class="d-flex flex-wrap gap-1 mb-0 p-0">';
        if ( $has_offer ) $tags_html .= '<p class="p-offers">Ofrece</p>';
        if ( $has_seek )  $tags_html .= '<p class="p-seeks">Busca</p>';
        $tags_html .= '</div><div class="d-flex flex-wrap gap-1">';
        foreach ( (array) $m['offer_tags'] as $t ) $tags_html .= '<span class="tag-vx tag-offers">' . esc_html( $t ) . '</span>';
        foreach ( (array) $m['seek_tags']  as $t ) $tags_html .= '<span class="tag-vx tag-seeks">' . esc_html( $t ) . '</span>';
        $tags_html .= '</div>';
    }

    $loc = trim( ( $ciudad ? $ciudad : '' ) . ( $pais ? ' (' . $pais . ')' : '' ) );

    ob_start(); ?>
<div class="card">
  <div class="card-img-container">
    <div class="card-enlaces">
      <a href="<?php echo esc_url( $perfil_url ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm btn-vx-icon-sm" aria-label="Ver perfil"><i class="ti ti-external-link"></i></a>
      <?php if ( $viewer_id && $viewer_id !== $uid ) : ?>
      <button class="btn-vx btn-soft-accent btn-vx-sm btn-vx-icon-sm vx-fav-btn" data-user-id="<?php echo $uid; ?>" data-activo="<?php echo $is_fav ? '1' : '0'; ?>"<?php echo $fav_style; ?> aria-label="Favorito"><i class="ti <?php echo $fav_icon; ?>"></i></button>
      <button class="btn-vx btn-soft-primary btn-vx-sm" data-bs-toggle="modal" data-bs-target="#modalConectar" data-receptor-id="<?php echo $uid; ?>"><i class="ti ti-send"></i> Conectar</button>
      <?php endif; ?>
    </div>
    <div class="card-blur-gradient"></div>
    <img class="card-img-top" src="<?php echo esc_url( $foto ); ?>" alt="<?php echo esc_attr( $nombre ); ?>">
  </div>
  <div class="card-body">
    <div class="info mb-2">
      <h5 class="h6 py-0 my-0"><?php echo esc_html( $nombre ); ?><?php echo $badges; ?></h5>
      <?php if ( $empresa ) : ?><p class="member-company"><?php echo esc_html( $empresa ); ?></p><?php endif; ?>
      <?php if ( $loc ) : ?><p class="member-company"><?php echo esc_html( $loc ); ?></p><?php endif; ?>
    </div>
    <?php echo $tags_html; ?>
  </div>
</div>
<?php
    return ob_get_clean();
}

/**
 * Renderiza el modal Bootstrap #modalConectar para enviar solicitudes.
 */
function vx_modal_conectar_html(): string {
    $nonce    = wp_create_nonce( 'wp_rest' );
    $endpoint = rest_url( VX_REST_NAMESPACE . '/conexiones' );
    ob_start(); ?>
<div class="modal fade" id="modalConectar" tabindex="-1" aria-labelledby="modalConectarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-vx">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold" id="modalConectarLabel"><i class="ti ti-send me-2 ic-primary"></i>Solicitud de conexión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="cta-card__desc mb-3">Cuéntale brevemente por qué quieres conectar con esta persona (mín. 20 caracteres).</p>
        <textarea id="vx-modal-pitch" class="form-control-vx" rows="3" placeholder="Hola, me interesa conectar porque..." maxlength="500"></textarea>
        <div id="vx-modal-msg" class="mt-2 d-none small"></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-vx btn-primary-vx btn-vx-sm" id="vx-modal-enviar"><i class="ti ti-send me-1"></i>Enviar solicitud</button>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  var ep    = <?php echo wp_json_encode( $endpoint ); ?>;
  var nonce = <?php echo wp_json_encode( $nonce ); ?>;
  var mel   = document.getElementById('modalConectar');
  if(!mel) return;
  mel.addEventListener('show.bs.modal',function(e){
    var t=e.relatedTarget;
    if(t){ mel.dataset.receptorId=t.dataset.receptorId||(t.closest('[data-receptor-id]')||{}).dataset?.receptorId||''; }
    document.getElementById('vx-modal-pitch').value='';
    var msg=document.getElementById('vx-modal-msg'); msg.textContent=''; msg.className='mt-2 d-none small';
    document.getElementById('vx-modal-enviar').disabled=false;
  });
  document.getElementById('vx-modal-enviar').addEventListener('click',function(){
    var pitch=document.getElementById('vx-modal-pitch').value.trim();
    var rid=parseInt(mel.dataset.receptorId||'0');
    var msgEl=document.getElementById('vx-modal-msg');
    msgEl.className='mt-2 small d-none';
    if(!pitch||pitch.length<20){msgEl.textContent='Escribe al menos 20 caracteres.';msgEl.className='mt-2 small text-danger';return;}
    this.disabled=true;
    fetch(ep,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify({receptor_id:rid,pitch:pitch})})
    .then(r=>r.json()).then(d=>{
      if(d.success){msgEl.textContent='¡Solicitud enviada!';msgEl.className='mt-2 small text-success';setTimeout(()=>{var m=bootstrap.Modal.getInstance(mel);if(m)m.hide();location.reload();},1200);}
      else{var e2=d.data?.message||d.error||'Error al enviar.';if(d.error==='conexion_pendiente')e2='Ya tienes una solicitud pendiente con esta persona.';msgEl.textContent=e2;msgEl.className='mt-2 small text-danger';this.disabled=false;}
    }).catch(()=>{msgEl.textContent='Error de red.';msgEl.className='mt-2 small text-danger';this.disabled=false;});
  });
})();
</script>
<?php
    return ob_get_clean();
}

// ─── SHORTCODES ───────────────────────────────────────────────────────────────

// [vx_dashboard] — panel principal del usuario
add_shortcode( 'vx_dashboard', function (): string {
    if ( ! is_user_logged_in() ) return '';
    $user_id = get_current_user_id();
    $user    = VX_User::get( $user_id );
    if ( ! $user ) return '';

    $seeks_raw      = VX_Matches::get_seeks_matches( $user_id, [ 'page' => 1, 'per_page' => 5 ] );
    $offers_raw     = VX_Matches::get_offers_matches( $user_id, [ 'page' => 1, 'per_page' => 5 ] );
    $seeks_matches  = array_map( fn( $u ) => $u->to_card_array(), $seeks_raw['users'] ?? [] );
    $offers_matches = array_map( fn( $u ) => $u->to_card_array(), $offers_raw['users'] ?? [] );
    $conexiones     = VX_Connection::get_accepted( $user_id );
    $pending_recv   = VX_Connection::get_received_by( $user_id, 'pendiente' );
    $favoritos_ids  = $user->get_favoritos();
    $offer_tags     = $user->get_offer_tags();
    $seek_tags      = $user->get_seek_tags();

    $dinners = class_exists( 'VX_Dinner' ) ? VX_Dinner::get_upcoming() : [];
    $dinner  = ! empty( $dinners ) ? $dinners[0] : null;

    $fecha_hoy = date_i18n( 'l, j \de F Y' );

    $nonce_rest = wp_create_nonce( 'wp_rest' );
    $ep_responder = rest_url( VX_REST_NAMESPACE . '/conexiones/responder' );

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <p class="dashboard-date"><?php echo esc_html( $fecha_hoy ); ?></p>
            <h1 class="page-header-vx__title">Bienvenido, <strong><?php echo esc_html( $user->get_nombre() ); ?></strong></h1>
            <p class="page-header-vx__lead">
              <?php
              $nm = count( $seeks_matches ) + count( $offers_matches );
              $np = count( $pending_recv );
              $lead = 'Tienes ' . $nm . ' match' . ( $nm !== 1 ? 'es' : '' ) . ' nuevo' . ( $nm !== 1 ? 's' : '' );
              if ( $np > 0 ) $lead .= ' y ' . $np . ' solicitud' . ( $np !== 1 ? 'es' : '' ) . ' de conexión pendiente' . ( $np !== 1 ? 's' : '' );
              $lead .= '.';
              echo esc_html( $lead );
              ?>
            </p>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-soft-primary btn-vx-sm">
              <i class="ti ti-pencil"></i> Editar perfil
            </a>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-4">

      <!-- STATS RÁPIDAS -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card-vx">
            <div class="stat-num-vx stat-num-vx--primary"><?php echo count( $conexiones ); ?></div>
            <div class="stat-label-vx">Conexiones activas</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <a href="<?php echo esc_url( home_url( '/matches/' ) ); ?>" class="text-decoration-none d-block">
            <div class="stat-card-vx stat-card-vx--link">
              <div class="stat-num-vx stat-num-vx--accent"><?php echo $nm; ?></div>
              <div class="stat-label-vx">Matches nuevos <i class="ti ti-arrow-right stat-arrow"></i></div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="<?php echo esc_url( home_url( '/conexiones/' ) ); ?>" class="text-decoration-none d-block">
            <div class="stat-card-vx stat-card-vx--link">
              <div class="stat-num-vx stat-num-vx--secondary"><?php echo $np; ?></div>
              <div class="stat-label-vx">Solicitudes recibidas <i class="ti ti-arrow-right stat-arrow"></i></div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="<?php echo esc_url( home_url( '/favoritos/' ) ); ?>" class="text-decoration-none d-block">
            <div class="stat-card-vx stat-card-vx--link">
              <div class="stat-num-vx stat-num-vx--success"><?php echo count( $favoritos_ids ); ?></div>
              <div class="stat-label-vx">En favoritos <i class="ti ti-arrow-right stat-arrow"></i></div>
            </div>
          </a>
        </div>
      </div>

      <!-- BANNER 4DINNER -->
      <?php if ( $dinner ) : ?>
      <div class="banner-4dinner mb-5">
        <div class="banner-4dinner__deco banner-4dinner__deco--1"></div>
        <div class="banner-4dinner__deco banner-4dinner__deco--2"></div>
        <div class="banner-4dinner__body">
          <div class="banner-4dinner__icon text-white">🍽</div>
          <div>
            <div class="banner-4dinner__eyebrow">Próximo evento</div>
            <div class="banner-4dinner__title">Habrá un <strong>4Dinner</strong> cerca tuyo</div>
            <div class="banner-4dinner__meta">
              <i class="ti ti-map-pin"></i><?php echo esc_html( $dinner->get_ciudad() ); ?> &nbsp;·&nbsp;
              <i class="ti ti-calendar"></i><?php echo esc_html( date_i18n( 'j \de F', strtotime( $dinner->get_fecha() ) ) ); ?>
            </div>
          </div>
        </div>
        <a href="<?php echo esc_url( home_url( '/4dinner/' ) ); ?>" class="btn-vx btn-vx-sm banner-4dinner__cta">
          Ver detalles <i class="ti ti-arrow-right ms-1"></i>
        </a>
      </div>
      <?php endif; ?>

      <!-- SOLICITUDES RECIBIDAS -->
      <?php if ( $pending_recv ) : ?>
      <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <h2 class="section-title-sm">Solicitudes de conexión</h2>
            <p class="cta-card__desc">Personas que quieren conectar contigo</p>
          </div>
          <a href="<?php echo esc_url( home_url( '/conexiones/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm link-primary-color">
            Ver todas <i class="ti ti-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="d-flex flex-column gap-2">
          <?php foreach ( array_slice( $pending_recv, 0, 3 ) as $conn ) :
            $emisor = VX_User::get( $conn->get_emisor_id() );
            if ( ! $emisor ) continue;
            $emp_activa = $emisor->get_empresa_activa();
          ?>
          <div class="card-vx cta-card--seeks">
            <div class="conn-row">
              <img src="<?php echo esc_url( $emisor->get_foto_url( 'vx-avatar' ) ); ?>" alt="" class="conn-avatar">
              <div class="conn-info">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <a href="<?php echo esc_url( home_url( '/perfil/' . $emisor->get_slug() . '/' ) ); ?>" class="conn-name"><?php echo esc_html( $emisor->get_nombre_completo() ); ?></a>
                  <span class="badge-vx badge-accent"><i class="ti ti-bell me-1"></i>Nueva</span>
                </div>
                <div class="conn-meta"><?php if ( $emp_activa ) echo esc_html( $emp_activa->post_title ) . ' · '; echo esc_html( $emisor->get_ciudad() ); if ( $emisor->get_pais_codigo() ) echo ' (' . esc_html( $emisor->get_pais_codigo() ) . ')'; ?></div>
                <?php if ( $conn->get_pitch() ) : ?>
                <div class="conn-pitch">"<?php echo esc_html( wp_trim_words( $conn->get_pitch(), 20 ) ); ?>"</div>
                <?php endif; ?>
              </div>
              <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn-vx btn-soft-primary btn-vx-sm vx-conn-btn" data-conn-id="<?php echo $conn->get_id(); ?>" data-accion="aceptado"><i class="ti ti-check me-1"></i>Aceptar</button>
                <button class="btn-vx btn-ghost-vx btn-vx-sm vx-conn-btn" data-conn-id="<?php echo $conn->get_id(); ?>" data-accion="rechazado">Rechazar</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- BUSCADOR -->
      <div class="card-vx mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="ti ti-search ic-primary" style="font-size:18px"></i>
          <h2 class="section-title-sm" style="font-weight:500">Explorar directorio</h2>
        </div>
        <div class="search-bar-vx mb-3">
          <i class="ti ti-search" style="color:var(--color-text-secondary);font-size:16px"></i>
          <input type="text" id="vx-dash-search" placeholder="Buscar empresas, rubros, países..." />
          <button class="btn-vx btn-primary-vx btn-vx-sm" onclick="window.location.href='<?php echo esc_url( home_url( '/directorio/' ) ); ?>?s='+encodeURIComponent(document.getElementById('vx-dash-search').value)">
            <i class="ti ti-search"></i> Buscar
          </button>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-world"></i> País</a>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-briefcase"></i> Rubro</a>
          <a href="<?php echo esc_url( add_query_arg( 'fundador', '1', home_url( '/directorio/' ) ) ); ?>" class="btn-vx btn-soft-secondary btn-vx-sm"><i class="ti ti-star"></i> Socios Fundadores</a>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm ms-auto link-primary-color">
            Ver todo el directorio <i class="ti ti-arrow-right ms-1"></i>
          </a>
        </div>
      </div>

      <!-- OFRECEN LO QUE BUSCAS -->
      <?php $seeks_members = $seeks_matches; if ( $seeks_members && $seek_tags ) : ?>
      <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
          <h2 class="section-title-md">Ofrecen lo que buscas</h2>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex flex-wrap gap-1">
              <?php foreach ( array_slice( $seek_tags, 0, 3 ) as $tag ) : ?>
              <span class="tag-vx tag-seeks"><?php echo esc_html( $tag ); ?></span>
              <?php endforeach; ?>
            </div>
            <a href="<?php echo esc_url( add_query_arg( 'tipo', 'busca', home_url( '/matches/' ) ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm">Ver todos <i class="ti ti-arrow-right ms-1"></i></a>
          </div>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2">
          <?php foreach ( array_slice( $seeks_members, 0, 5 ) as $m ) : ?>
          <div class="col"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- BUSCAN LO QUE OFRECES -->
      <?php $offers_members = $offers_matches; if ( $offers_members && $offer_tags ) : ?>
      <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
          <h2 class="section-title-md">Buscan lo que ofreces</h2>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex flex-wrap gap-1">
              <?php foreach ( array_slice( $offer_tags, 0, 3 ) as $tag ) : ?>
              <span class="tag-vx tag-offers"><?php echo esc_html( $tag ); ?></span>
              <?php endforeach; ?>
            </div>
            <a href="<?php echo esc_url( add_query_arg( 'tipo', 'ofrece', home_url( '/matches/' ) ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm">Ver todos <i class="ti ti-arrow-right ms-1"></i></a>
          </div>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2">
          <?php foreach ( array_slice( $offers_members, 0, 5 ) as $m ) : ?>
          <div class="col"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

    </div>
    </main>
    <script>
    (function(){
      var ep    = <?php echo wp_json_encode( $ep_responder ); ?>;
      var nonce = <?php echo wp_json_encode( $nonce_rest ); ?>;
      document.querySelectorAll('.vx-conn-btn').forEach(function(btn){
        btn.addEventListener('click',function(){
          var cid=parseInt(this.dataset.connId);
          var acc=this.dataset.accion;
          this.disabled=true;
          fetch(ep,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify({conexion_id:cid,accion:acc})})
          .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else{ alert(d.error||'Error'); this.disabled=false; } })
          .catch(()=>{ alert('Error de red'); this.disabled=false; });
        });
      });
    })();
    </script>
    <?php echo vx_modal_conectar_html(); ?>
    <?php
    return ob_get_clean();
} );

// [vx_directorio] — directorio de miembros
add_shortcode( 'vx_directorio', function (): string {
    $user_id   = get_current_user_id();
    $pais      = isset( $_GET['pais'] )      ? sanitize_text_field( wp_unslash( $_GET['pais'] ) )      : '';
    $comunidad = isset( $_GET['comunidad'] ) ? sanitize_text_field( wp_unslash( $_GET['comunidad'] ) ) : '';
    $busqueda  = isset( $_GET['s'] )         ? sanitize_text_field( wp_unslash( $_GET['s'] ) )         : '';
    $fundador  = ! empty( $_GET['fundador'] );
    $pagina    = max( 1, (int) ( $_GET['pagina'] ?? 1 ) );

    $result     = VX_Directory::get_members( [ 'pais' => $pais, 'comunidad' => $comunidad, 'fundador' => $fundador, 'page' => $pagina ] );
    $filters    = VX_Directory::get_filters();
    $members    = array_map( fn( $u ) => $u->to_card_array(), $result['users'] ?? [] );
    $pagination = $result['pagination'] ?? [];

    ob_start();
    ?>
    <header class="pt-5">
      <div class="container py-5">
        <h1 class="mt-2 mb-5 main-phrase text-center">
          Encuentra tu siguiente <strong>nexo</strong>
        </h1>

        <!-- Búsqueda + filtros -->
        <form method="get" action="<?php echo esc_url( home_url( '/directorio/' ) ); ?>">
          <div class="search-bar-vx mb-3">
            <i class="ti ti-search" style="color:var(--color-text-secondary);font-size:16px"></i>
            <input type="text" name="s" value="<?php echo esc_attr( $busqueda ); ?>" placeholder="Buscar empresas, rubros, países..." />
            <button type="submit" class="btn-vx btn-primary-vx btn-vx-sm"><i class="ti ti-search"></i> Buscar</button>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            <select name="pais" class="btn-vx btn-ghost-vx btn-vx-sm" onchange="this.form.submit()" style="border:1px solid var(--color-border)">
              <option value=""><i class="ti ti-world"></i> País</option>
              <?php foreach ( $filters['paises'] ?? [] as $p ) : ?>
              <option value="<?php echo esc_attr( $p ); ?>" <?php selected( $pais, $p ); ?>><?php echo esc_html( $p ); ?></option>
              <?php endforeach; ?>
            </select>
            <select name="comunidad" class="btn-vx btn-ghost-vx btn-vx-sm" onchange="this.form.submit()" style="border:1px solid var(--color-border)">
              <option value=""><i class="ti ti-briefcase"></i> Comunidad</option>
              <option value="out2b"  <?php selected( $comunidad, 'out2b' ); ?>>Out2B</option>
              <option value="woman"  <?php selected( $comunidad, 'woman' ); ?>>Woman</option>
              <option value="senior" <?php selected( $comunidad, 'senior' ); ?>>Senior</option>
            </select>
            <label class="btn-vx <?php echo $fundador ? 'btn-soft-secondary' : 'btn-ghost-vx'; ?> btn-vx-sm" style="cursor:pointer">
              <input type="checkbox" name="fundador" value="1" <?php checked( $fundador ); ?> onchange="this.form.submit()" style="display:none">
              <i class="ti ti-star"></i> Socios Fundadores
            </label>
            <?php if ( $pais || $comunidad || $fundador || $busqueda ) : ?>
            <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm ms-auto">
              <i class="ti ti-x"></i> Limpiar filtros
            </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </header>

    <section id="search-result" class="members-section pb-5">
      <div class="container">
        <?php if ( $members ) : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 row-cols-xl-5 g-2 justify-content-center" id="members-root">
          <?php foreach ( $members as $m ) : ?>
          <div class="col masonry-item"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
          <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ( ! empty( $pagination ) && ( $pagination['total_pages'] ?? 1 ) > 1 ) : ?>
        <nav class="d-flex justify-content-center gap-2 mt-5">
          <?php for ( $i = 1; $i <= $pagination['total_pages']; $i++ ) : ?>
          <a href="<?php echo esc_url( add_query_arg( 'pagina', $i ) ); ?>"
             class="btn-vx <?php echo $i === $pagina ? 'btn-primary-vx' : 'btn-ghost-vx'; ?> btn-vx-sm">
            <?php echo $i; ?>
          </a>
          <?php endfor; ?>
        </nav>
        <?php endif; ?>

        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-users-group"></i></div>
          <p class="empty-state-vx__title">No se encontraron miembros</p>
          <p class="empty-state-vx__desc">Intenta con otros filtros o términos de búsqueda.</p>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm mt-3">Ver todos</a>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php echo vx_modal_conectar_html(); ?>
    <?php
    return ob_get_clean();
} );

// [vx_matches] — página de matches (busca + ofrece con tabs)
add_shortcode( 'vx_matches', function (): string {
    $user_id = get_current_user_id();
    $user    = VX_User::get( $user_id );
    if ( ! $user ) return '';

    $seeks_result  = VX_Matches::get_seeks_matches( $user_id, [ 'page' => 1, 'per_page' => 20 ] );
    $offers_result = VX_Matches::get_offers_matches( $user_id, [ 'page' => 1, 'per_page' => 20 ] );

    $seeks_members  = array_map( fn( $u ) => $u->to_card_array(), $seeks_result['users']  ?? [] );
    $offers_members = array_map( fn( $u ) => $u->to_card_array(), $offers_result['users'] ?? [] );
    $offer_tags     = $user->get_offer_tags();
    $seek_tags      = $user->get_seek_tags();

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <h1 class="page-header-vx__title">Mis matches</h1>
            <p class="page-header-vx__lead">Perfiles que coinciden con lo que ofreces y con lo que buscas.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="d-flex flex-wrap gap-1">
              <?php foreach ( array_slice( $offer_tags, 0, 2 ) as $t ) : ?><span class="tag-vx tag-offers"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
              <?php foreach ( array_slice( $seek_tags,  0, 2 ) as $t ) : ?><span class="tag-vx tag-seeks"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
            </div>
            <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm">
              <i class="ti ti-pencil"></i> Editar tags
            </a>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-4">

      <nav class="vx-tabs" id="matches-tabs">
        <button class="vx-tab vx-tab--active" id="tab-seeks" onclick="vxSwitchTab('seeks',this)">
          <i class="ti ti-circle-check"></i> Ofrecen lo que buscas
          <span class="badge-vx badge-neutral"><?php echo count( $seeks_members ); ?></span>
        </button>
        <button class="vx-tab" id="tab-offers" onclick="vxSwitchTab('offers',this)">
          <i class="ti ti-search"></i> Buscan lo que ofreces
          <span class="badge-vx badge-neutral"><?php echo count( $offers_members ); ?></span>
        </button>
      </nav>

      <!-- OFRECEN LO QUE BUSCAS -->
      <div id="panel-seeks">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
          <p class="cta-card__desc">Su oferta coincide con lo que declaraste buscar en tu perfil.</p>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-soft-accent btn-vx-sm">Ver directorio <i class="ti ti-arrow-right ms-1"></i></a>
        </div>
        <?php if ( $seeks_members ) : ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2">
          <?php foreach ( $seeks_members as $m ) : ?>
          <div class="col masonry-item"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-sparkles"></i></div>
          <p class="empty-state-vx__title">Sin matches aún</p>
          <p class="empty-state-vx__desc">Agrega tags a tu perfil para ver quiénes ofrecen lo que buscas.</p>
          <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm mt-3">Editar mi perfil</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- BUSCAN LO QUE OFRECES -->
      <div id="panel-offers" style="display:none">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
          <p class="cta-card__desc">Buscan lo que tú declaraste ofrecer en tu perfil.</p>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-soft-primary btn-vx-sm">Ver directorio <i class="ti ti-arrow-right ms-1"></i></a>
        </div>
        <?php if ( $offers_members ) : ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2">
          <?php foreach ( $offers_members as $m ) : ?>
          <div class="col masonry-item"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-sparkles"></i></div>
          <p class="empty-state-vx__title">Sin matches aún</p>
          <p class="empty-state-vx__desc">Agrega tags de oferta a tu perfil para ver quiénes los buscan.</p>
          <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm mt-3">Editar mi perfil</a>
        </div>
        <?php endif; ?>
      </div>

    </div>
    </main>
    <script>
    function vxSwitchTab(tab, el) {
      document.querySelectorAll('#matches-tabs .vx-tab').forEach(function(b){ b.classList.remove('vx-tab--active'); });
      el.classList.add('vx-tab--active');
      document.getElementById('panel-seeks').style.display  = tab==='seeks'  ? '' : 'none';
      document.getElementById('panel-offers').style.display = tab==='offers' ? '' : 'none';
    }
    </script>
    <?php echo vx_modal_conectar_html(); ?>
    <?php
    return ob_get_clean();
} );

// [vx_favoritos] — lista de favoritos
add_shortcode( 'vx_favoritos', function (): string {
    $user_id       = get_current_user_id();
    $viewer        = VX_User::get( $user_id );
    $favoritos_ids = $viewer ? (array) $viewer->get_favoritos() : [];

    $members = [];
    foreach ( $favoritos_ids as $uid ) {
        $u = VX_User::get( (int) $uid );
        if ( $u && $u->is_active() ) {
            $members[] = $u->to_card_array();
        }
    }

    $count = count( $members );

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="section-landing-label" style="margin:0">Mis favoritos</span>
            </div>
            <h1 class="page-header-vx__title">Perfiles guardados</h1>
            <p class="page-header-vx__lead">Empresas que marcaste para retomar cuando quieras.</p>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-vx badge-neutral"><i class="ti ti-bookmark me-1"></i><?php echo $count; ?> guardado<?php echo $count !== 1 ? 's' : ''; ?></span>
            <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-layout-grid"></i> Ir al directorio</a>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-4">
      <?php if ( $members ) : ?>
      <div class="page-section-heading mb-3">
        <h2 class="page-section-heading__title">Todos los guardados</h2>
        <span class="badge-vx badge-neutral"><?php echo $count; ?> perfiles</span>
      </div>
      <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2 mb-5">
        <?php foreach ( $members as $m ) : ?>
        <div class="col masonry-item"><?php echo vx_render_member_card( $m, $user_id ); ?></div>
        <?php endforeach; ?>
      </div>
      <?php else : ?>
      <div class="empty-state-vx py-5 text-center">
        <div class="empty-state-vx__icon"><i class="ti ti-heart"></i></div>
        <p class="empty-state-vx__title">Aún no tienes favoritos</p>
        <p class="empty-state-vx__desc">Guarda perfiles que te interesen para encontrarlos fácilmente.</p>
        <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm mt-3"><i class="ti ti-layout-grid me-1"></i>Ir al directorio</a>
      </div>
      <?php endif; ?>
    </div>
    </main>
    <?php echo vx_modal_conectar_html(); ?>
    <?php
    return ob_get_clean();
} );

// [vx_conexiones] — lista de conexiones
add_shortcode( 'vx_conexiones', function (): string {
    $user_id   = get_current_user_id();
    $recibidas = VX_Connection::get_received_by( $user_id );
    $enviadas  = VX_Connection::get_sent_by( $user_id );
    $aceptadas = VX_Connection::get_accepted( $user_id );

    $nonce_rest  = wp_create_nonce( 'wp_rest' );
    $ep_responder = rest_url( VX_REST_NAMESPACE . '/conexiones/responder' );

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="section-landing-label" style="margin:0">Red</span>
            </div>
            <h1 class="page-header-vx__title">Mis conexiones</h1>
            <p class="page-header-vx__lead">Solicitudes enviadas, recibidas y conexiones concretadas.</p>
          </div>
          <div class="d-flex gap-2">
            <div class="stat-card-vx text-center stat-box-vx">
              <div class="stat-num-vx stat-num-vx--success stat-num-lg"><?php echo count( $aceptadas ); ?></div>
              <div class="stat-label-vx">Concretadas</div>
            </div>
            <div class="stat-card-vx text-center stat-box-vx">
              <div class="stat-num-vx stat-num-vx--accent stat-num-lg"><?php echo count( $recibidas ); ?></div>
              <div class="stat-label-vx">Pendientes</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-4">

      <nav class="vx-tabs" id="conn-tabs">
        <button class="vx-tab vx-tab--active" id="tab-concretadas" onclick="vxConnTab('concretadas',this)">
          <i class="ti ti-circle-check"></i> Concretadas
          <span class="badge-vx badge-primary"><?php echo count( $aceptadas ); ?></span>
        </button>
        <button class="vx-tab" id="tab-enviadas" onclick="vxConnTab('enviadas',this)">
          <i class="ti ti-send"></i> Enviadas
          <span class="badge-vx badge-neutral"><?php echo count( $enviadas ); ?></span>
        </button>
        <button class="vx-tab" id="tab-recibidas" onclick="vxConnTab('recibidas',this)">
          <i class="ti ti-inbox"></i> Recibidas
          <span class="badge-vx badge-accent"><?php echo count( $recibidas ); ?></span>
        </button>
      </nav>

      <!-- ── CONCRETADAS ── -->
      <div id="panel-concretadas">
        <p class="cta-card__desc mb-4">Personas con las que la conexión fue aceptada. Sus datos de contacto están disponibles.</p>
        <?php if ( $aceptadas ) : ?>
        <div class="row g-3">
          <?php foreach ( $aceptadas as $conn ) :
            $other_id   = $conn->get_other_user_id( $user_id );
            $other_user = VX_User::get( $other_id );
            if ( ! $other_user ) continue;
            $yo_contacte = $conn->get_emisor_id() === $user_id;
            $emp_activa  = $other_user->get_empresa_activa();
            $cargo       = get_post_meta( $emp_activa ? $emp_activa->ID : 0, 'vx_cargo', true );
            $role_str    = trim( implode( ' · ', array_filter( [
                $cargo,
                $emp_activa ? $emp_activa->post_title : '',
                trim( ( $other_user->get_ciudad() ? $other_user->get_ciudad() : '' ) . ( $other_user->get_pais_codigo() ? ' (' . $other_user->get_pais_codigo() . ')' : '' ) )
            ] ) ) );
            $contacto_preferido = get_user_meta( $other_id, VX_User_Meta::CONTACTO_PREFERIDO, true ) ?: 'email';
          ?>
          <div class="col-12">
            <div class="contact-reveal">
              <img src="<?php echo esc_url( $other_user->get_foto_url( 'vx-avatar' ) ); ?>" alt="" class="contact-reveal-avatar">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <div class="contact-reveal-name"><?php echo esc_html( $other_user->get_nombre_completo() ); ?></div>
                  <span class="badge-vx badge-neutral" style="font-size:10px">
                    <?php if ( $yo_contacte ) : ?><i class="ti ti-send me-1"></i>Yo contacté<?php else : ?><i class="ti ti-inbox me-1"></i>Me contactó<?php endif; ?>
                  </span>
                </div>
                <?php if ( $role_str ) : ?><div class="contact-reveal-role"><?php echo esc_html( $role_str ); ?></div><?php endif; ?>
                <div class="contact-reveal-links">
                  <?php if ( $other_user->get_email() ) : ?>
                  <a href="mailto:<?php echo esc_attr( $other_user->get_email() ); ?>" class="contact-reveal-link">
                    <i class="ti ti-mail"></i> <?php echo esc_html( $other_user->get_email() ); ?>
                    <?php if ( 'email' === $contacto_preferido ) : ?><span class="contact-preferred-badge">Preferido</span><?php endif; ?>
                  </a>
                  <?php endif; ?>
                  <?php if ( $other_user->get_telefono() ) : ?>
                  <a href="tel:<?php echo esc_attr( $other_user->get_telefono() ); ?>" class="contact-reveal-link">
                    <i class="ti ti-phone"></i> <?php echo esc_html( $other_user->get_telefono() ); ?>
                    <?php if ( 'telefono' === $contacto_preferido ) : ?><span class="contact-preferred-badge">Preferido</span><?php endif; ?>
                  </a>
                  <?php endif; ?>
                  <?php if ( $other_user->get_linkedin() ) : ?>
                  <a href="<?php echo esc_url( $other_user->get_linkedin() ); ?>" class="contact-reveal-link" target="_blank" rel="noopener">
                    <i class="ti ti-brand-linkedin"></i> LinkedIn
                    <?php if ( 'linkedin' === $contacto_preferido ) : ?><span class="contact-preferred-badge">Preferido</span><?php endif; ?>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                <a href="<?php echo esc_url( home_url( '/perfil/' . $other_user->get_slug() . '/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-external-link"></i></a>
                <span class="text-xxs-muted"><?php echo esc_html( date_i18n( 'd M Y', $conn->get_fecha_envio() ) ); ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-network"></i></div>
          <p class="empty-state-vx__title">Aún no tienes conexiones concretadas</p>
          <p class="empty-state-vx__desc">Cuando una solicitud sea aceptada, el perfil aparecerá aquí.</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── ENVIADAS ── -->
      <div id="panel-enviadas" style="display:none">
        <?php if ( $enviadas ) : ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ( $enviadas as $conn ) :
            $other_id   = $conn->get_other_user_id( $user_id );
            $other_user = VX_User::get( $other_id );
            if ( ! $other_user ) continue;
            $emp_activa = $other_user->get_empresa_activa();
            $estado     = $conn->get_estado();
            $badge_label = 'pendiente' === $estado ? '<i class="ti ti-clock me-1"></i>Pendiente' : ucfirst( $estado );
          ?>
          <div class="card-vx">
            <div class="conn-row">
              <img src="<?php echo esc_url( $other_user->get_foto_url( 'vx-avatar' ) ); ?>" alt="" class="conn-avatar">
              <div class="conn-info">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <a href="<?php echo esc_url( home_url( '/perfil/' . $other_user->get_slug() . '/' ) ); ?>" class="conn-name"><?php echo esc_html( $other_user->get_nombre_completo() ); ?></a>
                  <span class="badge-vx badge-neutral"><?php echo $badge_label; ?></span>
                </div>
                <div class="conn-meta"><?php if ( $emp_activa ) echo esc_html( $emp_activa->post_title ) . ' · '; echo esc_html( $other_user->get_ciudad() ); if ( $other_user->get_pais_codigo() ) echo ' (' . esc_html( $other_user->get_pais_codigo() ) . ')'; ?></div>
                <div class="conn-date"><i class="ti ti-calendar"></i> Enviada el <?php echo esc_html( date_i18n( 'j M Y', $conn->get_fecha_envio() ) ); ?></div>
                <?php if ( $conn->get_pitch() ) : ?>
                <div class="conn-pitch">"<?php echo esc_html( wp_trim_words( $conn->get_pitch(), 20 ) ); ?>"</div>
                <?php endif; ?>
              </div>
              <div class="flex-shrink-0">
                <a href="<?php echo esc_url( home_url( '/perfil/' . $other_user->get_slug() . '/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-external-link"></i></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-send"></i></div>
          <p class="empty-state-vx__title">No has enviado solicitudes</p>
          <p class="empty-state-vx__desc">Explora el directorio y conecta con otros miembros.</p>
          <a href="<?php echo esc_url( home_url( '/directorio/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm mt-3">Ir al directorio</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── RECIBIDAS ── -->
      <div id="panel-recibidas" style="display:none">
        <?php if ( $recibidas ) : ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ( $recibidas as $conn ) :
            $emisor    = VX_User::get( $conn->get_emisor_id() );
            if ( ! $emisor ) continue;
            $emp_activa = $emisor->get_empresa_activa();
            $estado     = $conn->get_estado();
          ?>
          <div class="card-vx border-left-accent">
            <div class="conn-row">
              <img src="<?php echo esc_url( $emisor->get_foto_url( 'vx-avatar' ) ); ?>" alt="" class="conn-avatar">
              <div class="conn-info">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <a href="<?php echo esc_url( home_url( '/perfil/' . $emisor->get_slug() . '/' ) ); ?>" class="conn-name"><?php echo esc_html( $emisor->get_nombre_completo() ); ?></a>
                  <?php if ( 'pendiente' === $estado ) : ?>
                  <span class="badge-vx badge-accent"><i class="ti ti-bell me-1"></i>Nueva</span>
                  <?php endif; ?>
                </div>
                <div class="conn-meta"><?php if ( $emp_activa ) echo esc_html( $emp_activa->post_title ) . ' · '; echo esc_html( $emisor->get_ciudad() ); if ( $emisor->get_pais_codigo() ) echo ' (' . esc_html( $emisor->get_pais_codigo() ) . ')'; ?></div>
                <div class="conn-date"><i class="ti ti-calendar"></i> Recibida el <?php echo esc_html( date_i18n( 'j M Y', $conn->get_fecha_envio() ) ); ?></div>
                <?php if ( $conn->get_pitch() ) : ?>
                <div class="conn-pitch">"<?php echo esc_html( wp_trim_words( $conn->get_pitch(), 25 ) ); ?>"</div>
                <?php endif; ?>
              </div>
              <?php if ( 'pendiente' === $estado ) : ?>
              <div class="d-flex gap-2 flex-shrink-0 flex-column">
                <button class="btn-vx btn-soft-primary btn-vx-sm vx-conn-btn" data-conn-id="<?php echo $conn->get_id(); ?>" data-accion="aceptado"><i class="ti ti-check me-1"></i>Aceptar</button>
                <button class="btn-vx btn-ghost-vx btn-vx-sm vx-conn-btn" data-conn-id="<?php echo $conn->get_id(); ?>" data-accion="rechazado">Rechazar</button>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state-vx py-5 text-center">
          <div class="empty-state-vx__icon"><i class="ti ti-inbox"></i></div>
          <p class="empty-state-vx__title">No tienes solicitudes recibidas</p>
          <p class="empty-state-vx__desc">Cuando alguien quiera conectar contigo aparecerá aquí.</p>
        </div>
        <?php endif; ?>
      </div>

    </div>
    </main>
    <script>
    function vxConnTab(tab, el) {
      document.querySelectorAll('#conn-tabs .vx-tab').forEach(function(b){ b.classList.remove('vx-tab--active'); });
      el.classList.add('vx-tab--active');
      ['concretadas','enviadas','recibidas'].forEach(function(p){ document.getElementById('panel-'+p).style.display = p===tab ? '' : 'none'; });
    }
    (function(){
      var ep    = <?php echo wp_json_encode( $ep_responder ); ?>;
      var nonce = <?php echo wp_json_encode( $nonce_rest ); ?>;
      document.querySelectorAll('.vx-conn-btn').forEach(function(btn){
        btn.addEventListener('click',function(){
          var cid=parseInt(this.dataset.connId), acc=this.dataset.accion; this.disabled=true;
          fetch(ep,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify({conexion_id:cid,accion:acc})})
          .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else{ alert(d.error||'Error'); this.disabled=false; } })
          .catch(()=>{ alert('Error de red'); this.disabled=false; });
        });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
} );

// [vx_notificaciones] — lista de notificaciones
add_shortcode( 'vx_notificaciones', function (): string {
    $user_id    = get_current_user_id();
    $pagina     = max( 1, (int) ( $_GET['pagina'] ?? 1 ) );
    $result     = VX_Notification::get_for_user( $user_id, $pagina );
    $notifs     = $result['items']      ?? [];
    $pagination = $result['pagination'] ?? [];

    // Mapa de tipo → clase de icono del mockup
    $icon_map = [
        'conexion_recibida' => [ 'class' => 'notif-icon--accent',   'icon' => 'ti-send' ],
        'conexion_aceptada' => [ 'class' => 'notif-icon--success',  'icon' => 'ti-circle-check' ],
        'match'             => [ 'class' => 'notif-icon--primary',  'icon' => 'ti-sparkles' ],
        'dinner'            => [ 'class' => 'notif-icon--dinner',   'icon' => '' ],
        'default'           => [ 'class' => 'notif-icon--neutral',  'icon' => 'ti-bell' ],
    ];

    $tipo_conn_map = [
        'conexion_recibida' => 'notif-item--connection',
        'conexion_aceptada' => 'notif-item--success',
        'match'             => 'notif-item--match',
        'dinner'            => 'notif-item--dinner',
    ];

    // Agrupar por fecha
    $hoy  = [];
    $ayer = [];
    $semana = [];
    $now  = time();
    foreach ( $notifs as $n ) {
        $ts  = strtotime( $n['fecha'] );
        $diff = $now - $ts;
        if ( $diff < DAY_IN_SECONDS ) {
            $hoy[] = $n;
        } elseif ( $diff < 2 * DAY_IN_SECONDS ) {
            $ayer[] = $n;
        } else {
            $semana[] = $n;
        }
    }

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <h1 class="page-header-vx__title">Notificaciones</h1>
            <p class="page-header-vx__lead">Lo que pasó mientras no estabas.</p>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container container-narrow py-4">

      <?php
      $grupos = array_filter( [
          'Hoy'         => $hoy,
          'Ayer'        => $ayer,
          'Esta semana' => $semana,
      ] );

      if ( $grupos ) :
        foreach ( $grupos as $label => $items ) :
      ?>
      <div class="mb-4">
        <span class="subsection-label mb-2 d-block"><?php echo esc_html( $label ); ?></span>
        <div class="d-flex flex-column gap-2">
          <?php foreach ( $items as $notif ) :
            $tipo      = $notif['tipo'] ?? 'default';
            $icon_cfg  = $icon_map[ $tipo ] ?? $icon_map['default'];
            $tipo_css  = $tipo_conn_map[ $tipo ] ?? '';
            $unread    = ! $notif['leida'];
            $has_link  = ! empty( $notif['link'] );
            $texto     = $notif['texto'] ?? $notif['tipo'] ?? '';
            $time_str  = human_time_diff( strtotime( $notif['fecha'] ), time() ) . ' atrás';

            $classes = 'card-vx notif-item' . ( $tipo_css ? ' ' . $tipo_css : '' ) . ( $unread ? ' notif-item--unread' : '' );
          ?>
          <?php if ( $has_link ) : ?><a href="<?php echo esc_url( $notif['link'] ); ?>" class="text-decoration-none"><?php endif; ?>
          <div class="<?php echo esc_attr( $classes ); ?>">
            <div class="notif-icon <?php echo esc_attr( $icon_cfg['class'] ); ?>">
              <?php if ( 'dinner' === $tipo ) : ?>🍽<?php else : ?><i class="ti <?php echo esc_attr( $icon_cfg['icon'] ); ?>"></i><?php endif; ?>
            </div>
            <div class="notif-body">
              <p class="notif-text"><?php echo esc_html( $texto ); ?></p>
              <span class="notif-time"><?php echo esc_html( $time_str ); ?></span>
            </div>
            <?php if ( $has_link ) : ?><i class="ti ti-chevron-right notif-chevron"></i><?php endif; ?>
          </div>
          <?php if ( $has_link ) : ?></a><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else : ?>
      <div class="empty-state-vx py-5 text-center">
        <div class="empty-state-vx__icon"><i class="ti ti-bell-off"></i></div>
        <p class="empty-state-vx__title">Sin notificaciones</p>
        <p class="empty-state-vx__desc">Cuando haya actividad en tu cuenta, te avisaremos aquí.</p>
      </div>
      <?php endif; ?>

      <!-- Paginación -->
      <?php if ( ! empty( $pagination ) && ( $pagination['total_pages'] ?? 1 ) > 1 ) : ?>
      <nav class="d-flex justify-content-center gap-2 mt-4">
        <?php for ( $i = 1; $i <= $pagination['total_pages']; $i++ ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'pagina', $i ) ); ?>"
           class="btn-vx <?php echo $i === $pagina ? 'btn-primary-vx' : 'btn-ghost-vx'; ?> btn-vx-sm">
          <?php echo $i; ?>
        </a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>

    </div>
    </main>
    <?php
    // Marcar todas como leídas
    VX_Notification::mark_all_read( $user_id );
    return ob_get_clean();
} );

// [vx_configuracion] — configuración de cuenta
add_shortcode( 'vx_configuracion', function (): string {
    $user_id   = get_current_user_id();
    $user      = VX_User::get( $user_id );
    if ( ! $user ) return '';
    $wp_user   = get_userdata( $user_id );
    $membresia = VX_Membership::get( $user_id );
    $nonce     = wp_create_nonce( 'wp_rest' );
    $ep_pass   = rest_url( VX_REST_NAMESPACE . '/cuenta/cambiar-password' );

    ob_start();
    ?>
    <div class="page-header-vx">
      <div class="container">
        <div class="page-header-vx__inner">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="section-landing-label" style="margin:0">Cuenta</span>
            </div>
            <h1 class="page-header-vx__title">Configuración</h1>
            <p class="page-header-vx__lead">Ajustes de cuenta, seguridad y plan.</p>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-4">
      <div class="row g-4">

        <!-- SIDEBAR NAV -->
        <div class="col-12 col-lg-3">
          <div class="card-vx p-0 overflow-hidden">
            <nav class="d-flex flex-column">
              <a href="#cuenta"       class="config-nav-item config-nav-item--active" onclick="vxShowSection('cuenta',this)"><i class="ti ti-user"></i> Cuenta</a>
              <a href="#seguridad"    class="config-nav-item" onclick="vxShowSection('seguridad',this)"><i class="ti ti-lock"></i> Seguridad</a>
              <a href="#plan"         class="config-nav-item" onclick="vxShowSection('plan',this)"><i class="ti ti-star"></i> Plan</a>
              <a href="#comunidades"  class="config-nav-item" onclick="vxShowSection('comunidades',this)"><i class="ti ti-users-group"></i> Comunidades</a>
            </nav>
          </div>
        </div>

        <!-- CONTENT -->
        <div class="col-12 col-lg-9">

          <!-- CUENTA -->
          <div id="section-cuenta" class="config-section">
            <div class="card-vx mb-3">
              <h2 class="section-title-sm mb-1">Información de cuenta</h2>
              <p class="cta-card__desc mb-4">Estos datos son de tu cuenta, no de tu perfil público.</p>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label-vx">Nombre</label>
                  <input type="text" class="form-control-vx input-readonly-vx" value="<?php echo esc_attr( $user->get_nombre() ); ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label-vx">Apellido</label>
                  <input type="text" class="form-control-vx input-readonly-vx" value="<?php echo esc_attr( $user->get_apellido() ); ?>" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label-vx">Email de cuenta</label>
                  <div class="input-group-vx">
                    <span class="input-icon"><i class="ti ti-mail"></i></span>
                    <input type="email" value="<?php echo esc_attr( $wp_user->user_email ); ?>" readonly class="input-readonly-vx">
                  </div>
                  <div class="form-hint">El email no se puede cambiar. <a href="mailto:hola@vitrinexo.com" class="link-primary-color">Contactar soporte</a></div>
                </div>
              </div>
              <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-primary-vx btn-vx-sm">
                <i class="ti ti-pencil me-1"></i>Editar perfil público
              </a>
            </div>
          </div>

          <!-- SEGURIDAD -->
          <div id="section-seguridad" class="config-section" style="display:none">
            <div class="card-vx mb-3">
              <h2 class="section-title-sm mb-1">Cambiar contraseña</h2>
              <p class="cta-card__desc mb-4">Usa una contraseña de al menos 8 caracteres con letras y números.</p>
              <div class="d-flex flex-column gap-3 mb-4" style="max-width:400px">
                <div>
                  <label class="form-label-vx">Contraseña actual</label>
                  <div class="input-group-vx">
                    <span class="input-icon"><i class="ti ti-lock"></i></span>
                    <input type="password" id="vx-pass-actual" placeholder="Tu contraseña actual">
                  </div>
                </div>
                <div>
                  <label class="form-label-vx">Nueva contraseña</label>
                  <div class="input-group-vx">
                    <span class="input-icon"><i class="ti ti-lock-plus"></i></span>
                    <input type="password" id="vx-pass-nuevo" placeholder="Mínimo 8 caracteres">
                  </div>
                </div>
              </div>
              <div id="vx-pass-msg" class="mb-3 d-none small"></div>
              <button class="btn-vx btn-primary-vx btn-vx-sm" id="vx-btn-cambiar-pass">Actualizar contraseña</button>
            </div>
          </div>

          <!-- PLAN -->
          <div id="section-plan" class="config-section" style="display:none">
            <div class="card-vx border-left-primary mb-3">
              <h2 class="section-title-sm mb-1">Mi membresía</h2>
              <p class="cta-card__desc mb-4">Tu plan actual y fechas de renovación.</p>
              <div class="row g-3 mb-3">
                <div class="col-sm-4">
                  <div class="text-body-muted" style="font-size:12px;margin-bottom:4px">Plan</div>
                  <div class="fw-semibold"><?php echo esc_html( ucfirst( $membresia->get_plan() ?: 'Básico' ) ); ?></div>
                </div>
                <div class="col-sm-4">
                  <div class="text-body-muted" style="font-size:12px;margin-bottom:4px">Estado</div>
                  <div class="fw-semibold">
                    <?php if ( 'activo' === $membresia->get_plan_estado() ) : ?>
                    <span class="ic-success"><i class="ti ti-circle-check me-1"></i></span>Activo
                    <?php else : ?>
                    <?php echo esc_html( ucfirst( $membresia->get_plan_estado() ?: '—' ) ); ?>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ( $membresia->get_expiry() ) : ?>
                <div class="col-sm-4">
                  <div class="text-body-muted" style="font-size:12px;margin-bottom:4px">Vencimiento</div>
                  <div class="fw-semibold"><?php echo esc_html( date_i18n( 'd/m/Y', $membresia->get_expiry() ) ); ?></div>
                </div>
                <?php endif; ?>
              </div>
              <?php if ( $membresia->is_founder() ) : ?>
              <div class="alert-vx alert-info d-flex align-items-center gap-2 mt-2">
                <i class="ti ti-star ic-success"></i>
                <span>Eres Socio Fundador. Tienes acceso completo de por vida.</span>
              </div>
              <?php else : ?>
              <a href="mailto:hola@vitrinexo.com" class="btn-vx btn-primary-vx btn-vx-sm mt-2">
                <i class="ti ti-star me-1"></i>Mejorar membresía
              </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- COMUNIDADES -->
          <div id="section-comunidades" class="config-section" style="display:none">
            <div class="card-vx">
              <h2 class="section-title-sm mb-1">Mis comunidades</h2>
              <p class="cta-card__desc mb-4">Comunidades a las que perteneces en Vitrinexo.</p>
              <?php
              $coms_map = [
                  'out2b'  => [ 'Out2B',  'ti-rainbow',       'color:#a855f7' ],
                  'woman'  => [ 'Woman',  'ti-gender-female', 'color:#ec4899' ],
                  'senior' => [ 'Senior', 'ti-award',         'color:#d97706' ],
              ];
              foreach ( $coms_map as $id => [ $nombre, $icon, $style ] ) :
                  $activa = $user->is_in_community( $id );
              ?>
              <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="ti <?php echo esc_attr( $icon ); ?>" style="font-size:18px;<?php echo esc_attr( $style ); ?>"></i>
                  <span class="fw-semibold"><?php echo esc_html( $nombre ); ?></span>
                </div>
                <?php if ( $activa ) : ?>
                <span class="badge-vx badge-primary"><i class="ti ti-check me-1"></i>Activo</span>
                <?php else : ?>
                <span class="badge-vx badge-neutral">Inactivo</span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php if ( ! $user->is_in_community( 'senior' ) ) : ?>
              <div class="mt-4 pt-3 border-top">
                <p class="cta-card__desc mb-2">¿Tienes más de 15 años de trayectoria empresarial? Solicita tu verificación Senior.</p>
                <button id="vx-solicitar-senior" class="btn-vx btn-ghost-vx btn-vx-sm">
                  <i class="ti ti-award me-1"></i>Solicitar verificación Senior
                </button>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>
    </main>

    <script>
    function vxShowSection(id, el) {
      document.querySelectorAll('.config-section').forEach(function(s){ s.style.display='none'; });
      document.querySelectorAll('.config-nav-item').forEach(function(a){ a.classList.remove('config-nav-item--active'); });
      document.getElementById('section-'+id).style.display = '';
      el.classList.add('config-nav-item--active');
      return false;
    }
    (function(){
      var ep    = <?php echo wp_json_encode( $ep_pass ); ?>;
      var nonce = <?php echo wp_json_encode( $nonce ); ?>;
      var btn   = document.getElementById('vx-btn-cambiar-pass');
      if(!btn) return;
      btn.addEventListener('click', function(){
        var actual = document.getElementById('vx-pass-actual').value.trim();
        var nuevo  = document.getElementById('vx-pass-nuevo').value.trim();
        var msgEl  = document.getElementById('vx-pass-msg');
        msgEl.className='mb-3 d-none small';
        if(!actual||!nuevo){ msgEl.textContent='Completa ambos campos.'; msgEl.className='mb-3 small text-danger'; return; }
        if(nuevo.length<8){ msgEl.textContent='La nueva contraseña debe tener al menos 8 caracteres.'; msgEl.className='mb-3 small text-danger'; return; }
        this.disabled=true;
        fetch(ep,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify({password_actual:actual,password_nuevo:nuevo})})
        .then(r=>r.json()).then(d=>{
          if(d.success){ msgEl.textContent='¡Contraseña actualizada!'; msgEl.className='mb-3 small text-success'; document.getElementById('vx-pass-actual').value=''; document.getElementById('vx-pass-nuevo').value=''; }
          else{ var e2=d.data?.message||d.error||'Error al actualizar.'; if(d.error==='password_actual_incorrecta') e2='La contraseña actual es incorrecta.'; msgEl.textContent=e2; msgEl.className='mb-3 small text-danger'; }
          this.disabled=false;
        }).catch(()=>{ msgEl.textContent='Error de red.'; msgEl.className='mb-3 small text-danger'; this.disabled=false; });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
} );

// [vx_perfil] — perfil público/privado de un miembro
add_shortcode( 'vx_perfil', function (): string {
    $slug   = get_query_var( 'vx_perfil_slug' );
    $viewer = get_current_user_id();

    if ( ! $slug ) {
        return '<div class="container py-5"><p class="text-muted">Perfil no encontrado.</p></div>';
    }

    $users = get_users( [
        'meta_key'   => VX_User_Meta::PERFIL_SLUG,
        'meta_value' => sanitize_title( $slug ),
        'number'     => 1,
        'fields'     => 'ids',
    ] );

    if ( empty( $users ) ) {
        return '<div class="container py-5"><p class="text-muted">Perfil no encontrado.</p></div>';
    }

    $user_id     = (int) $users[0];
    $user        = VX_User::get( $user_id );
    $is_owner    = $viewer && $viewer === $user_id;
    $is_wp_admin = $viewer && current_user_can( 'manage_options' );

    if ( ! $user ) {
        return '<div class="container py-5"><p class="text-muted">Perfil no encontrado.</p></div>';
    }

    if ( ! $user->is_active() && ! $is_owner && ! $is_wp_admin ) {
        return '<div class="container py-5"><p class="text-muted">Este perfil no está disponible.</p></div>';
    }

    // Disparar visita
    if ( $viewer && $viewer !== $user_id ) {
        do_action( 'vx_profile_visited', $user_id, $viewer );
    }

    // Estado del botón conectar
    $btn_state = 'none';
    if ( $viewer && $viewer !== $user_id ) {
        $btn_state = 'disponible';
        $conexion  = VX_Connection::get_between( $viewer, $user_id );
        if ( $conexion ) {
            $est = $conexion->get_estado();
            if ( 'aceptado' === $est ) {
                $btn_state = 'conectado';
            } elseif ( 'pendiente' === $est ) {
                $btn_state = $conexion->get_emisor_id() === $viewer ? 'enviada' : 'recibida';
            }
        }
    }

    $empresas         = $user->get_empresas();
    $conexion_actual  = ( $viewer && $viewer !== $user_id ) ? VX_Connection::get_between( $viewer, $user_id ) : null;
    $contacto_visible = $conexion_actual && 'aceptado' === $conexion_actual->get_estado();

    // Favorito
    $viewer_obj = $viewer ? VX_User::get( $viewer ) : null;
    $is_fav     = $viewer_obj && in_array( $user_id, (array) $viewer_obj->get_favoritos(), true );

    // Banner: usar banner de la primera empresa
    $empresa_activa = $user->get_empresa_activa();
    $banner_url     = '';
    $banner_id      = $empresa_activa ? get_post_meta( $empresa_activa->ID, 'vx_banner', true ) : '';
    if ( $banner_id ) {
        $banner_url = wp_get_attachment_image_url( $banner_id, 'full' ) ?: '';
    }

    // Cargo del usuario en empresa activa
    $cargo_activo = $empresa_activa ? (string) get_post_meta( $empresa_activa->ID, 'vx_cargo', true ) : '';

    $offer_texto = (string) get_user_meta( $user_id, VX_User_Meta::OFFER_TEXTO, true );
    $seek_texto  = (string) get_user_meta( $user_id, VX_User_Meta::SEEK_TEXTO,  true );

    ob_start();
    ?>
<main>
<div class="container">
  <div class="profile my-5">

    <!-- HEADER -->
    <div class="profile-top">

      <!-- Acciones (favorito + conectar) -->
      <div class="profile-actions">
        <?php if ( $viewer && $viewer !== $user_id ) : ?>
        <button class="btn-vx btn-soft-accent btn-vx-sm btn-vx-icon-sm vx-fav-btn"
                data-user-id="<?php echo $user_id; ?>"
                data-activo="<?php echo $is_fav ? '1' : '0'; ?>"
                <?php if ( $is_fav ) echo 'style="color:var(--color-pink-500)"'; ?>
                aria-label="<?php echo $is_fav ? 'Quitar de favoritos' : 'Guardar en favoritos'; ?>">
          <i class="ti <?php echo $is_fav ? 'ti-heart-filled' : 'ti-heart'; ?>"></i>
        </button>
        <?php endif; ?>

        <?php if ( 'disponible' === $btn_state ) : ?>
        <button class="btn-vx btn-soft-primary btn-vx-sm" data-bs-toggle="modal" data-bs-target="#modalConectar" data-receptor-id="<?php echo $user_id; ?>">
          <i class="ti ti-send"></i> Conectar
        </button>
        <?php elseif ( 'enviada' === $btn_state ) : ?>
        <button class="btn-vx btn-ghost-vx btn-vx-sm" disabled><i class="ti ti-clock-hour-4 me-1"></i>Solicitud enviada</button>
        <?php elseif ( 'recibida' === $btn_state ) : ?>
        <a href="<?php echo esc_url( home_url( '/conexiones/' ) ); ?>" class="btn-vx btn-soft-primary btn-vx-sm"><i class="ti ti-inbox me-1"></i>Ver solicitud</a>
        <?php elseif ( 'conectado' === $btn_state ) : ?>
        <button class="btn-vx btn-ghost-vx btn-vx-sm" disabled><i class="ti ti-circle-check me-1 ic-success"></i>Conectados</button>
        <?php endif; ?>

        <?php if ( $is_owner ) : ?>
        <a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-pencil me-1"></i>Editar perfil</a>
        <?php endif; ?>
      </div>

      <!-- Banner -->
      <div class="banner-profile"
           style="background-image:url('<?php echo esc_url( $banner_url ?: get_template_directory_uri() . '/assets/img/banner-default.jpg' ); ?>');background-size:cover;background-position:center">
      </div>

      <!-- Avatar + info -->
      <div class="profile-row">
        <div class="img-profile">
          <img src="<?php echo esc_url( $user->get_foto_url( 'vx-avatar' ) ); ?>" alt="<?php echo esc_attr( $user->get_nombre_completo() ); ?>">
        </div>
        <div class="profile-info">
          <h1 class="profile-name">
            <?php echo esc_html( $user->get_nombre_completo() ); ?>
            <?php if ( $user->is_founder() ) : ?>
            <span class="founder-tooltip ms-1" data-tooltip="Miembro fundador" tabindex="0"><i class="founder-tag ti ti-star"></i></span>
            <?php endif; ?>
            <?php if ( $user->is_senior_verified() ) : ?>
            <span class="badge-vx badge-neutral ms-1" style="font-size:11px">Senior</span>
            <?php endif; ?>
          </h1>
          <?php if ( $empresa_activa ) : ?>
          <a href="<?php echo esc_url( get_post_meta( $empresa_activa->ID, 'vx_web', true ) ?: '#' ); ?>" class="profile-company" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $empresa_activa->post_title ); ?></a>
          <?php endif; ?>
          <?php if ( $cargo_activo ) : ?>
          <p class="profile-title"><?php echo esc_html( $cargo_activo ); ?></p>
          <?php endif; ?>
          <p class="profile-location">
            <i class="ti ti-map-pin"></i>
            <?php echo esc_html( $user->get_ciudad() ?: $user->get_pais() ); ?>
            <?php if ( $user->get_pais_codigo() ) : ?>
            <span class="profile-country-chip"><?php echo esc_html( $user->get_pais_codigo() ); ?></span>
            <?php endif; ?>
          </p>
        </div>
      </div>
    </div>

    <!-- TAGS ROW (comunidades + offer/seek) -->
    <?php
    $all_tags = array_merge( $user->get_offer_tags(), $user->get_seek_tags() );
    if ( $all_tags ) :
    ?>
    <div class="profile-tags-row">
      <?php foreach ( array_slice( $all_tags, 0, 8 ) as $tag ) : ?>
      <span class="tag-vx"><?php echo esc_html( $tag ); ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- CONTENT -->
    <div class="profile-content">

      <!-- Bio / Sobre mí -->
      <?php if ( $user->get_bio() ) : ?>
      <div class="profile-section">
        <h2 class="profile-section-title">Sobre mí</h2>
        <p><?php echo nl2br( esc_html( $user->get_bio() ) ); ?></p>
      </div>
      <?php endif; ?>

      <!-- Tabs de empresa (si hay más de una, mostrar tabs; si hay una sola, mostrar directamente) -->
      <?php if ( count( $empresas ) > 1 ) : ?>
      <div class="profile-tabs">
        <?php foreach ( $empresas as $idx => $emp ) : ?>
        <button class="profile-tab <?php echo 0 === $idx ? 'profile-tab-active' : ''; ?>"
                onclick="vxSwitchProfileTab(<?php echo $idx; ?>)">
          <?php echo esc_html( $emp->post_title ); ?>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php foreach ( $empresas as $idx => $emp ) :
        $emp_logo_id   = get_post_meta( $emp->ID, 'vx_logo', true );
        $emp_logo_url  = $emp_logo_id ? wp_get_attachment_image_url( $emp_logo_id, 'vx-logo' ) : '';
        $emp_cargo     = (string) get_post_meta( $emp->ID, 'vx_cargo',              true );
        $emp_web       = (string) get_post_meta( $emp->ID, 'vx_web',               true );
        $emp_linkedin  = (string) get_post_meta( $emp->ID, 'vx_linkedin',          true );
        $emp_desc      = (string) get_post_meta( $emp->ID, 'vx_descripcion',       true );
        $emp_cliente   = (string) get_post_meta( $emp->ID, 'vx_descripcion_cliente', true );
        $logo_initial  = strtoupper( mb_substr( $emp->post_title, 0, 1 ) );
      ?>
      <div class="profile-company-block" id="company-block-<?php echo $idx; ?>"
           <?php if ( $idx > 0 ) echo 'style="display:none"'; ?>>
        <div class="profile-company-card">
          <div class="profile-company-logo">
            <?php if ( $emp_logo_url ) : ?>
            <img src="<?php echo esc_url( $emp_logo_url ); ?>" alt="<?php echo esc_attr( $emp->post_title ); ?>" style="width:100%;height:100%;object-fit:contain;border-radius:var(--radius-sm)">
            <?php else : ?>
            <span class="profile-company-logo-initial"><?php echo esc_html( $logo_initial ); ?></span>
            <?php endif; ?>
          </div>
          <div class="profile-company-meta">
            <div class="profile-company-meta-name"><?php echo esc_html( $emp->post_title ); ?></div>
            <?php if ( $emp_cargo ) : ?>
            <div class="profile-company-meta-title"><?php echo esc_html( $emp_cargo ); ?></div>
            <?php endif; ?>
            <div class="profile-company-meta-links">
              <?php if ( $emp_web ) : ?>
              <a href="<?php echo esc_url( $emp_web ); ?>" class="profile-company-link" target="_blank" rel="noopener noreferrer">
                <i class="ti ti-world"></i> <?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $emp_web, '/' ) ) ); ?>
              </a>
              <?php endif; ?>
              <?php if ( $emp_linkedin ) : ?>
              <a href="<?php echo esc_url( $emp_linkedin ); ?>" class="profile-company-link" target="_blank" rel="noopener noreferrer">
                <i class="ti ti-brand-linkedin"></i> LinkedIn
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ( $emp_desc ) : ?>
        <div class="profile-section">
          <h2 class="profile-section-title">Sobre <?php echo esc_html( $emp->post_title ); ?></h2>
          <p><?php echo nl2br( esc_html( $emp_desc ) ); ?></p>
        </div>
        <?php endif; ?>

        <?php if ( $emp_cliente ) : ?>
        <div class="profile-section">
          <h2 class="profile-section-title">Cliente ideal</h2>
          <p><?php echo nl2br( esc_html( $emp_cliente ) ); ?></p>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

    </div><!-- /.profile-content -->

    <!-- OFFER / SEEK PILLS -->
    <?php if ( $user->get_offer_tags() || $offer_texto || $user->get_seek_tags() || $seek_texto ) : ?>
    <div class="profile-duo">
      <div class="profile-pill profile-pill-offer">
        <h3 class="profile-pill-title"><i class="ti ti-circle-check"></i> Qué ofrezco</h3>
        <?php if ( $offer_texto ) : ?><p><?php echo esc_html( $offer_texto ); ?></p><?php endif; ?>
        <?php if ( $user->get_offer_tags() ) : ?>
        <div class="profile-pill-tags">
          <?php foreach ( $user->get_offer_tags() as $tag ) : ?>
          <span class="tag-vx tag-offers"><?php echo esc_html( $tag ); ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="profile-pill profile-pill-seek">
        <h3 class="profile-pill-title"><i class="ti ti-search"></i> Qué busco</h3>
        <?php if ( $seek_texto ) : ?><p><?php echo esc_html( $seek_texto ); ?></p><?php endif; ?>
        <?php if ( $user->get_seek_tags() ) : ?>
        <div class="profile-pill-tags">
          <?php foreach ( $user->get_seek_tags() as $tag ) : ?>
          <span class="tag-vx tag-seeks"><?php echo esc_html( $tag ); ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Datos de contacto (solo si conectados) -->
    <?php if ( $contacto_visible ) : ?>
    <div class="card-vx mt-5">
      <h2 class="section-title-sm mb-3"><i class="ti ti-address-book me-2 ic-success"></i>Datos de contacto</h2>
      <div class="contact-reveal-links">
        <?php if ( $user->get_email() ) : ?>
        <a href="mailto:<?php echo esc_attr( $user->get_email() ); ?>" class="contact-reveal-link">
          <i class="ti ti-mail"></i> <?php echo esc_html( $user->get_email() ); ?>
        </a>
        <?php endif; ?>
        <?php if ( $user->get_telefono() ) : ?>
        <a href="tel:<?php echo esc_attr( $user->get_telefono() ); ?>" class="contact-reveal-link">
          <i class="ti ti-phone"></i> <?php echo esc_html( $user->get_telefono() ); ?>
        </a>
        <?php endif; ?>
        <?php if ( $user->get_linkedin() ) : ?>
        <a href="<?php echo esc_url( $user->get_linkedin() ); ?>" class="contact-reveal-link" target="_blank" rel="noopener">
          <i class="ti ti-brand-linkedin"></i> LinkedIn
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.profile -->
</div><!-- /.container -->
</main>

    <?php if ( count( $empresas ) > 1 ) : ?>
    <script>
    function vxSwitchProfileTab(idx) {
      document.querySelectorAll('.profile-tab').forEach(function(b,i){ b.classList.toggle('profile-tab-active', i===idx); });
      document.querySelectorAll('.profile-company-block').forEach(function(b,i){ b.style.display = i===idx ? '' : 'none'; });
    }
    </script>
    <?php endif; ?>

    <?php if ( 'disponible' === $btn_state && $viewer ) : ?>
    <?php echo vx_modal_conectar_html(); ?>
    <?php endif; ?>

    <?php
    return ob_get_clean();
} );

// [vx_editor_perfil] — editor del propio perfil
add_shortcode( 'vx_editor_perfil', function (): string {
    $user_id = get_current_user_id();
    $user    = VX_User::get( $user_id );
    if ( ! $user ) return '';

    $empresas        = $user->get_empresas();
    $nombre_completo = trim( $user->get_nombre() . ' ' . $user->get_apellido() );
    $foto_url        = $user->get_foto_url( 'vx-avatar' );
    $offer_tags      = $user->get_offer_tags() ?: [];
    $seek_tags       = $user->get_seek_tags() ?: [];
    $offer_texto     = (string) get_user_meta( $user_id, VX_User_Meta::OFFER_TEXTO, true );
    $seek_texto      = (string) get_user_meta( $user_id, VX_User_Meta::SEEK_TEXTO,  true );
    $is_out2b        = (bool)   get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_OUT2B, true );
    $is_senior       = (bool)   get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_SENIOR, true );
    $current_pais    = $user->get_pais();
    $paises          = vx_get_paises_latam(); // lista completa LATAM + España

    // Pre-compute sector tags per empresa for JS
    $sector_tags_map = [];
    foreach ( $empresas as $emp ) {
        $sector = (string) get_post_meta( $emp->ID, 'vx_sector', true );
        $sector_tags_map[ (string) $emp->ID ] = $sector
            ? array_values( array_filter( array_map( 'trim', explode( ',', $sector ) ) ) )
            : [];
    }

    ob_start();
    ?>
    <div class="container py-4">

      <!-- Título + Guardar -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h1 class="fw-bold mb-1 page-title-vx">Editar perfil</h1>
          <p class="text-md-muted mb-0">Los cambios se reflejarán en el directorio inmediatamente</p>
        </div>
        <button type="button" class="btn-vx btn-primary-vx btn-vx-md" id="vx-btn-guardar-top">
          <i class="ti ti-check me-1"></i>Guardar cambios
        </button>
      </div>

      <!-- Alertas -->
      <div class="alert-vx alert-success d-flex align-items-center gap-3 mb-4 d-none" id="vx-alert-ok">
        <i class="ti ti-circle-check" style="font-size:20px;flex-shrink:0"></i>
        <span>Cambios guardados correctamente.</span>
        <button type="button" onclick="document.getElementById('vx-alert-ok').classList.add('d-none')" class="btn-alert-close">&times;</button>
      </div>
      <div class="alert-vx alert-error d-flex align-items-center gap-3 mb-4 d-none" id="vx-alert-err">
        <i class="ti ti-alert-circle" style="font-size:20px;flex-shrink:0"></i>
        <span id="vx-alert-err-msg">Error al guardar.</span>
        <button type="button" onclick="document.getElementById('vx-alert-err').classList.add('d-none')" class="btn-alert-close">&times;</button>
      </div>

      <form id="vx-editor-form" novalidate>

        <!-- ========== SECCIÓN PERSONA ========== -->
        <div class="modal-vx profile-editor-card mb-4 p-0 overflow-hidden">
          <div class="card-section-header">
            <h4 class="fw-semibold mb-0" style="font-size:1rem">
              <i class="ti ti-user me-2 ic-success"></i>Información personal
            </h4>
          </div>
          <div class="card-section-body">

            <!-- Foto -->
            <div class="d-flex align-items-center gap-3 mb-4 pb-4 border-bottom-vx">
              <div id="vx-foto-preview" style="flex-shrink:0">
                <img src="<?php echo esc_url( $foto_url ); ?>" alt="Foto"
                     style="width:60px;height:60px;border-radius:var(--radius-sm);object-fit:cover;border:2px solid var(--color-border)">
              </div>
              <div>
                <p class="fw-semibold mb-1" style="font-size:14px"><?php echo esc_html( $nombre_completo ); ?></p>
                <label for="vx-foto-input" class="btn-vx btn-ghost-vx btn-vx-sm" style="cursor:pointer">
                  <i class="ti ti-camera me-1"></i>Cambiar foto
                </label>
                <input type="file" id="vx-foto-input" accept="image/jpeg,image/png,image/webp" class="d-none" data-upload-type="foto">
              </div>
            </div>

            <!-- Nombre (readonly) -->
            <div class="mb-4 pb-4 border-bottom-vx">
              <label class="form-label-vx">Nombre completo</label>
              <input type="text" class="form-control-vx input-readonly-vx" value="<?php echo esc_attr( $nombre_completo ); ?>" readonly>
              <p class="text-sm-muted" style="margin-top:6px">
                <i class="ti ti-info-circle me-1"></i>Para cambiar el nombre
                <a href="mailto:hola@vitrinexo.com" class="link-primary-color">contacta a soporte</a>.
              </p>
            </div>

            <!-- Bio -->
            <div class="mb-4 pb-4 border-bottom-vx">
              <label class="form-label-vx">Bio profesional</label>
              <textarea name="bio" class="form-control-vx" rows="3" maxlength="300"><?php echo esc_textarea( $user->get_bio() ); ?></textarea>
            </div>

            <!-- Ciudad + País -->
            <div class="row g-3 mb-4 pb-4 border-bottom-vx">
              <div class="col-md-6">
                <label class="form-label-vx">Ciudad</label>
                <input type="text" name="ciudad" class="form-control-vx" value="<?php echo esc_attr( $user->get_ciudad() ); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label-vx">País</label>
                <input type="text" name="pais" class="form-control-vx"
                       list="vx-paises-list"
                       value="<?php echo esc_attr( $current_pais ); ?>"
                       placeholder="Escribe tu país..."
                       autocomplete="off">
                <?php echo vx_paises_datalist(); ?>
              </div>
            </div>

            <!-- Tags que ofreces -->
            <div class="mb-4 pb-4 border-bottom-vx">
              <label class="form-label-vx">Tags de lo que ofreces</label>
              <div class="d-flex flex-wrap gap-2 mb-2" id="vx-offer-tags-container"></div>
              <input type="text" id="vx-offer-tag-input" class="form-control-vx mb-3 input-md-vx" placeholder="Agregar tag y Enter...">
              <label class="form-label-vx">Descripción de tu oferta</label>
              <textarea name="offer_texto" class="form-control-vx" rows="2"><?php echo esc_textarea( $offer_texto ); ?></textarea>
            </div>

            <!-- Tags que buscas -->
            <div class="mb-4 pb-4 border-bottom-vx">
              <label class="form-label-vx">Tags de lo que buscas</label>
              <div class="d-flex flex-wrap gap-2 mb-2" id="vx-seek-tags-container"></div>
              <input type="text" id="vx-seek-tag-input" class="form-control-vx mb-3 input-md-vx" placeholder="Agregar tag y Enter...">
              <label class="form-label-vx">Descripción de lo que buscas</label>
              <textarea name="seek_texto" class="form-control-vx" rows="2"><?php echo esc_textarea( $seek_texto ); ?></textarea>
            </div>

            <!-- Preferencia de contacto -->
            <div class="mb-4 pb-4 border-bottom-vx">
              <label class="form-label-vx">Preferencia de contacto</label>
              <select name="contacto_preferido" class="form-control-vx input-md-vx">
                <?php foreach ( [ 'email' => 'Email', 'telefono' => 'Teléfono', 'linkedin' => 'LinkedIn' ] as $val => $lbl ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $user->get_contacto_preferido(), $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Comunidades -->
            <div>
              <h5 class="card-title-sm mb-3">Comunidades</h5>
              <div class="mb-3">
                <label class="d-flex align-items-start gap-3" style="cursor:pointer">
                  <input type="checkbox" name="comunidad_out2b"
                    style="margin-top:3px;accent-color:var(--color-pink-500);width:16px;height:16px;flex-shrink:0"
                    <?php checked( $is_out2b ); ?>>
                  <div>
                    <span class="fw-medium" style="font-size:14px">Soy parte de la comunidad LGBTQ+</span>
                    <p class="text-sm-muted" style="margin:2px 0 0">Aparecer en la comunidad OUT2B</p>
                  </div>
                </label>
              </div>
              <div>
                <label class="d-flex align-items-start gap-3" style="cursor:pointer">
                  <input type="checkbox" name="comunidad_senior"
                    style="margin-top:3px;accent-color:var(--color-green-500);width:16px;height:16px;flex-shrink:0"
                    <?php checked( $is_senior ); ?>>
                  <div>
                    <span class="fw-medium" style="font-size:14px">Soy ejecutivo/a Senior</span>
                    <p class="text-sm-muted" style="margin:2px 0 0">Requiere verificación manual</p>
                  </div>
                </label>
              </div>
            </div>

          </div>
        </div>

      </form>

      <!-- ========== SECCIÓN EMPRESAS ========== -->
      <div class="modal-vx profile-editor-card mb-4 p-0 overflow-hidden">
        <div class="card-section-header">
          <h4 class="fw-semibold mb-0" style="font-size:1rem">
            <i class="ti ti-building me-2 ic-success"></i>Empresa
          </h4>
        </div>
        <div class="card-section-body">

          <?php foreach ( $empresas as $i => $emp ) :
            $emp_id    = (int) $emp->ID;
            $cargo     = (string) get_post_meta( $emp_id, 'vx_cargo',               true );
            $desc      = (string) get_post_meta( $emp_id, 'vx_descripcion',          true );
            $desc_cli  = (string) get_post_meta( $emp_id, 'vx_descripcion_cliente',  true );
            $web       = (string) get_post_meta( $emp_id, 'vx_web',                  true );
            $linkedin  = (string) get_post_meta( $emp_id, 'vx_linkedin',             true );
            $logo_id   = (int)    get_post_meta( $emp_id, 'vx_logo',                 true );
            $banner_id = (int)    get_post_meta( $emp_id, 'vx_banner',               true );
            $logo_url  = $logo_id   ? wp_get_attachment_image_url( $logo_id,   'vx-logo'   ) : '';
            $banner_url = $banner_id ? wp_get_attachment_image_url( $banner_id, 'vx-banner' ) : '';
            $is_last   = ( $i === count( $empresas ) - 1 );
          ?>
          <div class="<?php echo $is_last ? '' : 'mb-4 pb-4 border-bottom-vx'; ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <p class="fw-semibold mb-0" style="font-size:15px"><?php echo esc_html( $emp->post_title ); ?></p>
                <?php if ( $cargo ) : ?>
                <p class="text-body-muted" style="margin:2px 0 0"><?php echo esc_html( $cargo ); ?></p>
                <?php endif; ?>
              </div>
              <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm vx-toggle-btn"
                onclick="vxToggleEmpresa(this, <?php echo $emp_id; ?>)">
                <i class="ti ti-pencil me-1"></i>Editar
              </button>
            </div>

            <!-- Formulario expandible -->
            <div class="empresa-form" id="vx-emp-form-<?php echo $emp_id; ?>" style="display:none">

              <!-- Logo -->
              <div class="mb-4 pb-3 border-bottom-vx">
                <label class="form-label-vx mb-2">Logo de empresa</label>
                <div class="d-flex align-items-center gap-3">
                  <div class="logo-upload-zone"
                       onclick="document.getElementById('vx-logo-input-<?php echo $emp_id; ?>').click()">
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo"
                         id="vx-logo-img-<?php echo $emp_id; ?>"
                         <?php echo $logo_url ? '' : 'style="display:none"'; ?>>
                    <div id="vx-logo-placeholder-<?php echo $emp_id; ?>"
                         <?php echo $logo_url ? 'style="display:none"' : ''; ?>
                         style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--color-text-tertiary)">
                      <i class="ti ti-building" style="font-size:24px"></i>
                    </div>
                    <div class="logo-upload-overlay"><i class="ti ti-camera"></i></div>
                  </div>
                  <input type="file" id="vx-logo-input-<?php echo $emp_id; ?>" accept="image/*" class="d-none"
                    data-upload-type="logo" data-empresa-id="<?php echo $emp_id; ?>"
                    onchange="vxPreviewLogo(this, <?php echo $emp_id; ?>)">
                  <div>
                    <p class="text-body-label" style="margin:0 0 2px">Logo de la empresa</p>
                    <p class="text-sm-muted mb-0">Circular · Fondo blanco recomendado · PNG o JPG</p>
                    <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm mt-2"
                      onclick="document.getElementById('vx-logo-input-<?php echo $emp_id; ?>').click()">
                      <i class="ti ti-upload me-1"></i>Subir logo
                    </button>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-vx">Nombre de la empresa</label>
                <input type="text" name="emp_nombre" class="form-control-vx"
                       value="<?php echo esc_attr( $emp->post_title ); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label-vx">Tu cargo / título</label>
                <input type="text" name="emp_cargo" class="form-control-vx"
                       value="<?php echo esc_attr( $cargo ); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label-vx">Sobre la empresa</label>
                <textarea name="emp_descripcion" class="form-control-vx" rows="3"><?php echo esc_textarea( $desc ); ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label-vx">Cliente ideal</label>
                <textarea name="emp_descripcion_cliente" class="form-control-vx" rows="2"><?php echo esc_textarea( $desc_cli ); ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label-vx">Rubros / industrias</label>
                <div class="d-flex flex-wrap gap-2 mb-2"
                     id="vx-sector-tags-<?php echo $emp_id; ?>"></div>
                <input type="text" class="form-control-vx input-sm-vx"
                       placeholder="Agregar rubro y Enter..."
                       id="vx-sector-input-<?php echo $emp_id; ?>"
                       onkeydown="vxAddSectorTag(event,<?php echo $emp_id; ?>)">
              </div>
              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label-vx">Sitio web</label>
                  <input type="url" name="emp_web" class="form-control-vx"
                         value="<?php echo esc_attr( $web ); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-vx">LinkedIn empresa</label>
                  <input type="url" name="emp_linkedin" class="form-control-vx"
                         value="<?php echo esc_attr( $linkedin ); ?>">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label-vx">Banner de empresa</label>
                <?php if ( $banner_url ) : ?>
                <div id="vx-banner-preview-<?php echo $emp_id; ?>" class="mb-2">
                  <img src="<?php echo esc_url( $banner_url ); ?>"
                       style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm)">
                </div>
                <?php else : ?>
                <div id="vx-banner-preview-<?php echo $emp_id; ?>" class="mb-2" style="display:none"></div>
                <?php endif; ?>
                <div class="banner-upload-zone"
                     onclick="document.getElementById('vx-banner-input-<?php echo $emp_id; ?>').click()">
                  <i class="ti ti-photo-up upload-zone-icon"></i>
                  <p class="text-body-muted mb-0">Arrastra una imagen o
                    <span class="link-primary-color">haz clic para subir</span></p>
                  <p class="text-xs-muted" style="margin:4px 0 0">Recomendado: 1200×300px</p>
                </div>
                <input type="file" id="vx-banner-input-<?php echo $emp_id; ?>" accept="image/*" class="d-none"
                  data-upload-type="banner" data-empresa-id="<?php echo $emp_id; ?>"
                  onchange="vxPreviewBanner(this,<?php echo $emp_id; ?>)">
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn-vx btn-primary-vx btn-vx-sm vx-emp-save-btn"
                  onclick="vxGuardarEmpresa(<?php echo $emp_id; ?>,this)">
                  Guardar empresa
                </button>
                <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm"
                  onclick="vxToggleEmpresa(document.querySelector('[onclick*=\'vxToggleEmpresa\'][onclick*=\'<?php echo $emp_id; ?>\']'),<?php echo $emp_id; ?>)">
                  Cancelar
                </button>
              </div>

            </div><!-- /empresa-form -->
          </div>
          <?php endforeach; ?>

          <?php if ( empty( $empresas ) ) : ?>
          <p class="text-body-muted mb-4">No tienes empresas registradas aún.</p>
          <?php endif; ?>

          <!-- Formulario nueva empresa (oculto hasta clic) -->
          <div id="vx-nueva-empresa-form" class="<?php echo empty( $empresas ) ? '' : 'mt-4 pt-4 border-top-vx'; ?>" style="display:none">
            <h5 class="card-title-sm mb-3">Nueva empresa</h5>

            <!-- Logo -->
            <div class="mb-4 pb-3 border-bottom-vx">
              <label class="form-label-vx mb-2">Logo de empresa</label>
              <div class="d-flex align-items-center gap-3">
                <div class="logo-upload-zone" onclick="document.getElementById('vx-new-logo-input').click()">
                  <img src="" alt="Logo" id="vx-new-logo-img" style="display:none">
                  <div id="vx-new-logo-placeholder"
                       style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--color-text-tertiary)">
                    <i class="ti ti-building" style="font-size:24px"></i>
                  </div>
                  <div class="logo-upload-overlay"><i class="ti ti-camera"></i></div>
                </div>
                <input type="file" id="vx-new-logo-input" accept="image/*" class="d-none"
                  onchange="vxPreviewNewLogo(this)">
                <div>
                  <p class="text-body-label" style="margin:0 0 2px">Logo de la empresa</p>
                  <p class="text-sm-muted mb-0">Circular · Fondo blanco recomendado · PNG o JPG</p>
                  <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm mt-2"
                    onclick="document.getElementById('vx-new-logo-input').click()">
                    <i class="ti ti-upload me-1"></i>Subir logo
                  </button>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label-vx">Nombre de la empresa <span style="color:var(--color-pink-500)">*</span></label>
              <input type="text" name="new_emp_nombre" class="form-control-vx" placeholder="Ej: Mi Empresa S.A.">
            </div>
            <div class="mb-3">
              <label class="form-label-vx">Tu cargo / título</label>
              <input type="text" name="new_emp_cargo" class="form-control-vx" placeholder="Ej: CEO, Directora Comercial">
            </div>
            <div class="mb-3">
              <label class="form-label-vx">Sobre la empresa</label>
              <textarea name="new_emp_descripcion" class="form-control-vx" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label-vx">Cliente ideal</label>
              <textarea name="new_emp_descripcion_cliente" class="form-control-vx" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label-vx">Rubros / industrias</label>
              <div class="d-flex flex-wrap gap-2 mb-2" id="vx-sector-tags-new"></div>
              <input type="text" class="form-control-vx input-sm-vx" placeholder="Agregar rubro y Enter..."
                onkeydown="vxAddSectorTag(event,'new')">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label-vx">Sitio web</label>
                <input type="url" name="new_emp_web" class="form-control-vx" placeholder="https://...">
              </div>
              <div class="col-md-6">
                <label class="form-label-vx">LinkedIn empresa</label>
                <input type="url" name="new_emp_linkedin" class="form-control-vx" placeholder="https://linkedin.com/company/...">
              </div>
            </div>

            <!-- Banner -->
            <div class="mb-3">
              <label class="form-label-vx">Banner de empresa</label>
              <div id="vx-new-banner-preview" class="mb-2" style="display:none"></div>
              <div class="banner-upload-zone" onclick="document.getElementById('vx-new-banner-input').click()">
                <i class="ti ti-photo-up upload-zone-icon"></i>
                <p class="text-body-muted mb-0">Arrastra una imagen o
                  <span class="link-primary-color">haz clic para subir</span></p>
                <p class="text-xs-muted" style="margin:4px 0 0">Recomendado: 1200×300px</p>
              </div>
              <input type="file" id="vx-new-banner-input" accept="image/*" class="d-none"
                onchange="vxPreviewNewBanner(this)">
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="button" class="btn-vx btn-primary-vx btn-vx-sm" id="vx-crear-empresa-btn"
                onclick="vxCrearEmpresa(this)">
                <i class="ti ti-plus me-1"></i>Crear empresa
              </button>
              <button type="button" class="btn-vx btn-ghost-vx btn-vx-sm"
                onclick="vxToggleNuevaEmpresa()">
                Cancelar
              </button>
            </div>
          </div>

          <button type="button" id="vx-agregar-empresa-btn"
            class="btn-vx btn-ghost-vx btn-vx-md btn-vx-dashed <?php echo empty( $empresas ) ? '' : 'mt-4'; ?>"
            onclick="vxToggleNuevaEmpresa()">
            <i class="ti ti-plus me-1"></i>Agregar otra empresa
          </button>

        </div>
      </div>

      <!-- Guardar al fondo -->
      <div class="d-flex justify-content-end mb-5">
        <button type="button" class="btn-vx btn-primary-vx btn-vx-lg" id="vx-btn-guardar-bottom">
          <i class="ti ti-check me-1"></i>Guardar cambios
        </button>
      </div>

    </div>

    <script>
    (function () {
      // ── Tag state ────────────────────────────────────────────────────────────
      window._vxOfferTags   = <?php echo wp_json_encode( array_values( $offer_tags ) ); ?>;
      window._vxSeekTags    = <?php echo wp_json_encode( array_values( $seek_tags ) ); ?>;
      window._vxSectorTags  = <?php echo wp_json_encode( $sector_tags_map ); ?>;

      function vxRenderTagChips( containerId, tags, type, empId ) {
        const c = document.getElementById( containerId );
        if ( ! c ) return;
        c.innerHTML = '';
        tags.forEach( function ( tag ) {
          const span = document.createElement( 'span' );
          span.className = 'tag-vx d-flex align-items-center gap-1'
            + ( type === 'offer' ? ' tag-offers' : type === 'seek' ? ' tag-seeks' : '' );
          span.appendChild( document.createTextNode( tag + ' ' ) );
          const btn = document.createElement( 'button' );
          btn.type      = 'button';
          btn.className = 'btn-tag-remove';
          btn.innerHTML = '&times;';
          btn.addEventListener( 'click', function () {
            if ( type === 'offer' ) {
              window._vxOfferTags = window._vxOfferTags.filter( function (t) { return t !== tag; } );
              vxRenderTagChips( 'vx-offer-tags-container', window._vxOfferTags, 'offer' );
            } else if ( type === 'seek' ) {
              window._vxSeekTags = window._vxSeekTags.filter( function (t) { return t !== tag; } );
              vxRenderTagChips( 'vx-seek-tags-container', window._vxSeekTags, 'seek' );
            } else if ( empId ) {
              window._vxSectorTags[ empId ] = ( window._vxSectorTags[ empId ] || [] ).filter( function (t) { return t !== tag; } );
              vxRenderTagChips( 'vx-sector-tags-' + empId, window._vxSectorTags[ empId ], 'sector', empId );
            }
          } );
          span.appendChild( btn );
          c.appendChild( span );
        } );
      }

      // Init tags
      vxRenderTagChips( 'vx-offer-tags-container', window._vxOfferTags,  'offer' );
      vxRenderTagChips( 'vx-seek-tags-container',  window._vxSeekTags,   'seek' );
      Object.keys( window._vxSectorTags ).forEach( function ( id ) {
        vxRenderTagChips( 'vx-sector-tags-' + id, window._vxSectorTags[ id ], 'sector', id );
      } );

      // Tag input handlers
      function vxBindTagInput( inputId, arr, containerId, type ) {
        const el = document.getElementById( inputId );
        if ( ! el ) return;
        el.addEventListener( 'keydown', function ( e ) {
          if ( [ 'Enter', 'Tab', ',' ].includes( e.key ) ) {
            e.preventDefault();
            const val = el.value.trim().toLowerCase();
            if ( val && ! arr.includes( val ) && arr.length < 10 ) {
              arr.push( val );
              vxRenderTagChips( containerId, arr, type );
            }
            el.value = '';
          }
        } );
      }
      vxBindTagInput( 'vx-offer-tag-input', window._vxOfferTags, 'vx-offer-tags-container', 'offer' );
      vxBindTagInput( 'vx-seek-tag-input',  window._vxSeekTags,  'vx-seek-tags-container',  'seek' );

      // ── Empresa helpers ──────────────────────────────────────────────────────
      window.vxAddSectorTag = function ( e, empId ) {
        if ( [ 'Enter', 'Tab', ',' ].includes( e.key ) ) {
          e.preventDefault();
          const val = e.target.value.trim();
          if ( ! val ) return;
          window._vxSectorTags[ empId ] = window._vxSectorTags[ empId ] || [];
          if ( ! window._vxSectorTags[ empId ].includes( val ) ) {
            window._vxSectorTags[ empId ].push( val );
            vxRenderTagChips( 'vx-sector-tags-' + empId, window._vxSectorTags[ empId ], 'sector', empId );
          }
          e.target.value = '';
        }
      };

      window.vxToggleEmpresa = function ( btn, empId ) {
        const form = document.getElementById( 'vx-emp-form-' + empId );
        if ( ! form ) return;
        const isOpen = form.style.display !== 'none';
        form.style.display = isOpen ? 'none' : 'block';
        btn.innerHTML = isOpen
          ? '<i class="ti ti-pencil me-1"></i>Editar'
          : '<i class="ti ti-x me-1"></i>Cerrar';
      };

      window.vxPreviewLogo = function ( input, empId ) {
        if ( ! input.files || ! input.files[0] ) return;
        const reader = new FileReader();
        reader.onload = function ( e ) {
          const img = document.getElementById( 'vx-logo-img-' + empId );
          const ph  = document.getElementById( 'vx-logo-placeholder-' + empId );
          if ( img ) { img.src = e.target.result; img.style.display = ''; }
          if ( ph )  { ph.style.display = 'none'; }
        };
        reader.readAsDataURL( input.files[0] );
      };

      window.vxPreviewBanner = function ( input, empId ) {
        if ( ! input.files || ! input.files[0] ) return;
        const reader = new FileReader();
        reader.onload = function ( e ) {
          const div = document.getElementById( 'vx-banner-preview-' + empId );
          if ( div ) {
            div.style.display = '';
            div.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm)">';
          }
        };
        reader.readAsDataURL( input.files[0] );
      };

      // ── Save helpers ─────────────────────────────────────────────────────────
      async function vxPost( payload, btnEl ) {
        const orig = btnEl.innerHTML;
        btnEl.disabled = true;
        btnEl.innerHTML = '<i class="ti ti-loader-2 me-1"></i>Guardando...';
        try {
          const res  = await fetch( vx_data.api_url + 'perfil/guardar', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vx_data.nonce },
            body:    JSON.stringify( payload ),
          } );
          const json = await res.json();
          if ( json.success ) {
            const ok = document.getElementById( 'vx-alert-ok' );
            ok.classList.remove( 'd-none' );
            window.scrollTo( { top: 0, behavior: 'smooth' } );
            setTimeout( function () { ok.classList.add( 'd-none' ); }, 4000 );
          } else {
            const err = document.getElementById( 'vx-alert-err' );
            document.getElementById( 'vx-alert-err-msg' ).textContent = json.error || 'Error al guardar.';
            err.classList.remove( 'd-none' );
          }
        } catch ( ex ) {
          const err = document.getElementById( 'vx-alert-err' );
          document.getElementById( 'vx-alert-err-msg' ).textContent = 'Error de conexión.';
          err.classList.remove( 'd-none' );
        }
        btnEl.disabled  = false;
        btnEl.innerHTML = orig;
      }

      function vxCollectPersonal() {
        const f = document.getElementById( 'vx-editor-form' );
        return {
          bio:                 f.querySelector( '[name="bio"]' ).value,
          ciudad:              f.querySelector( '[name="ciudad"]' ).value,
          pais:                f.querySelector( '[name="pais"]' ).value,
          contacto_preferido:  f.querySelector( '[name="contacto_preferido"]' ).value,
          offer_tags:          window._vxOfferTags,
          seek_tags:           window._vxSeekTags,
          offer_texto:         f.querySelector( '[name="offer_texto"]' ).value,
          seek_texto:          f.querySelector( '[name="seek_texto"]' ).value,
          comunidad_out2b:     f.querySelector( '[name="comunidad_out2b"]' ).checked,
          comunidad_senior:    f.querySelector( '[name="comunidad_senior"]' ).checked,
        };
      }

      document.getElementById( 'vx-btn-guardar-top' ).addEventListener( 'click', function () {
        vxPost( vxCollectPersonal(), this );
      } );
      document.getElementById( 'vx-btn-guardar-bottom' ).addEventListener( 'click', function () {
        vxPost( vxCollectPersonal(), this );
      } );

      window.vxGuardarEmpresa = function ( empId, btn ) {
        const form = document.getElementById( 'vx-emp-form-' + empId );
        if ( ! form ) return;
        vxPost( {
          empresas: [ {
            id:                  empId,
            nombre:              form.querySelector( '[name="emp_nombre"]' ).value,
            cargo:               form.querySelector( '[name="emp_cargo"]' ).value,
            descripcion:         form.querySelector( '[name="emp_descripcion"]' ).value,
            descripcion_cliente: form.querySelector( '[name="emp_descripcion_cliente"]' ).value,
            sector:              ( window._vxSectorTags[ empId ] || [] ).join( ',' ),
            web:                 form.querySelector( '[name="emp_web"]' ).value,
            linkedin:            form.querySelector( '[name="emp_linkedin"]' ).value,
          } ],
        }, btn );
      };

      // ── Nueva empresa ────────────────────────────────────────────────────────
      window._vxSectorTags[ 'new' ] = [];

      window.vxToggleNuevaEmpresa = function () {
        const form = document.getElementById( 'vx-nueva-empresa-form' );
        const btn  = document.getElementById( 'vx-agregar-empresa-btn' );
        const isOpen = form.style.display !== 'none';
        form.style.display = isOpen ? 'none' : 'block';
        btn.innerHTML = isOpen
          ? '<i class="ti ti-plus me-1"></i>Agregar otra empresa'
          : '<i class="ti ti-x me-1"></i>Cancelar';
        if ( ! isOpen ) {
          form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
        }
      };

      window.vxPreviewNewLogo = function ( input ) {
        if ( ! input.files || ! input.files[0] ) return;
        const reader = new FileReader();
        reader.onload = function ( e ) {
          const img = document.getElementById( 'vx-new-logo-img' );
          const ph  = document.getElementById( 'vx-new-logo-placeholder' );
          if ( img ) { img.src = e.target.result; img.style.display = ''; }
          if ( ph )  { ph.style.display = 'none'; }
        };
        reader.readAsDataURL( input.files[0] );
      };

      window.vxPreviewNewBanner = function ( input ) {
        if ( ! input.files || ! input.files[0] ) return;
        const reader = new FileReader();
        reader.onload = function ( e ) {
          const div = document.getElementById( 'vx-new-banner-preview' );
          if ( div ) {
            div.style.display = '';
            div.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm)">';
          }
        };
        reader.readAsDataURL( input.files[0] );
      };

      async function vxUploadImagen( file, tipo, empresaId ) {
        const fd = new FormData();
        fd.append( 'file',     file );
        fd.append( 'tipo',     tipo );
        fd.append( 'contexto', empresaId );
        try {
          await fetch( vx_data.api_url + 'upload', {
            method:  'POST',
            headers: { 'X-WP-Nonce': vx_data.nonce },
            body:    fd,
          } );
        } catch ( ex ) {
          console.warn( 'Upload error:', ex );
        }
      }

      window.vxCrearEmpresa = async function ( btn ) {
        const form   = document.getElementById( 'vx-nueva-empresa-form' );
        const nombre = form.querySelector( '[name="new_emp_nombre"]' ).value.trim();

        if ( ! nombre ) {
          const input = form.querySelector( '[name="new_emp_nombre"]' );
          input.focus();
          input.style.borderColor = 'var(--color-pink-500)';
          return;
        }

        const orig    = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = '<i class="ti ti-loader-2 me-1"></i>Creando...';

        try {
          // 1. Crear la empresa y obtener su ID
          const res  = await fetch( vx_data.api_url + 'empresa/crear', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vx_data.nonce },
            body:    JSON.stringify( {
              nombre:              nombre,
              cargo:               form.querySelector( '[name="new_emp_cargo"]' ).value,
              descripcion:         form.querySelector( '[name="new_emp_descripcion"]' ).value,
              descripcion_cliente: form.querySelector( '[name="new_emp_descripcion_cliente"]' ).value,
              sector:              ( window._vxSectorTags[ 'new' ] || [] ).join( ',' ),
              web:                 form.querySelector( '[name="new_emp_web"]' ).value,
              linkedin:            form.querySelector( '[name="new_emp_linkedin"]' ).value,
            } ),
          } );
          const json = await res.json();

          if ( ! json.success ) {
            throw new Error( json.error || 'Error al crear la empresa.' );
          }

          // 2. Subir logo y banner (en paralelo) usando el ID recién creado
          const empId     = json.empresa_id;
          const logoInput   = document.getElementById( 'vx-new-logo-input' );
          const bannerInput = document.getElementById( 'vx-new-banner-input' );
          const uploads = [];

          if ( logoInput && logoInput.files[0] ) {
            uploads.push( vxUploadImagen( logoInput.files[0], 'logo', empId ) );
          }
          if ( bannerInput && bannerInput.files[0] ) {
            uploads.push( vxUploadImagen( bannerInput.files[0], 'banner', empId ) );
          }
          if ( uploads.length ) {
            btn.innerHTML = '<i class="ti ti-loader-2 me-1"></i>Subiendo imágenes...';
            await Promise.all( uploads );
          }

          // 3. Recargar para mostrar la empresa con logo y banner
          window.location.reload();

        } catch ( ex ) {
          btn.disabled  = false;
          btn.innerHTML = orig;
          const errEl = document.getElementById( 'vx-alert-err' );
          document.getElementById( 'vx-alert-err-msg' ).textContent = ex.message || 'Error de conexión.';
          errEl.classList.remove( 'd-none' );
          window.scrollTo( { top: 0, behavior: 'smooth' } );
        }
      };

    }());
    </script>
    <?php
    return ob_get_clean();
} );

// [vx_4dinner] — página de 4Dinners para miembros
add_shortcode( 'vx_4dinner', function (): string {
    $user_id = get_current_user_id();
    $dinners = VX_Dinner::get_upcoming();
    $pasados = VX_Dinner::get_past();

    $gradients = [
        'linear-gradient(135deg,var(--color-green-600),var(--color-green-400))',
        'linear-gradient(135deg,var(--color-purple-600),var(--color-purple-400))',
        'linear-gradient(135deg,#f59e0b,#f97316)',
        'linear-gradient(135deg,#0ea5e9,#6366f1)',
    ];

    ob_start();
    ?>
    <!-- PAGE HEADER -->
    <div class="page-header-vx page-header-vx--4dinner">
        <div class="container">
            <div class="page-header-vx__inner">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge-vx badge-4dinner-light">
                            <i class="ti ti-tools-kitchen-2 me-1"></i> Eventos presenciales
                        </span>
                    </div>
                    <h1 class="page-header-vx__title" style="color:#fff">4Dinner</h1>
                    <p class="page-header-vx__lead" style="color:rgba(255,255,255,.85)">4 personas. 1 mesa. Una conversación que importa.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="#proximos" class="btn-vx btn-vx-sm btn-4dinner-ghost">
                        <i class="ti ti-calendar-event"></i> Próximos eventos
                    </a>
                    <a href="#form-interes" class="btn-vx btn-vx-sm btn-4dinner-cta">
                        Quiero participar <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">

        <!-- ABOUT STRIP -->
        <div class="card-vx mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-7">
                    <p class="text-md-muted" style="line-height:1.7;margin:0">
                        Cada miércoles a las 8pm, 4 miembros de la red Vitrinexo se sientan a cenar en una ciudad distinta. Sin agenda, sin pitches formales. Solo conversación real entre personas que ya se conocen por sus fichas. Cada quien paga su consumo.
                    </p>
                </div>
                <div class="col-12 col-md-5">
                    <div class="row g-2">
                        <div class="col-4 text-center">
                            <div class="stat-num-vx stat-num-vx--success stat-num-vx--md">4</div>
                            <div class="stat-label-vx">personas por mesa</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stat-num-vx stat-num-vx--primary stat-num-vx--md">8pm</div>
                            <div class="stat-label-vx">hora local, miércoles</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stat-num-vx stat-num-vx--secondary stat-num-vx--md">18+</div>
                            <div class="stat-label-vx">ciudades activas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRÓXIMOS EVENTOS -->
        <section id="proximos" class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <span class="subsection-label">Agenda</span>
                    <h2 class="subsection-title">Próximos eventos</h2>
                </div>
                <?php if ( $dinners ) : ?>
                <span class="badge-vx badge-primary"><?php echo count( $dinners ); ?> ciudad<?php echo count( $dinners ) !== 1 ? 'es' : ''; ?> en agenda</span>
                <?php endif; ?>
            </div>

            <?php if ( $dinners ) : ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ( $dinners as $dinner ) :
                    $asignados   = $dinner->get_asignados();
                    $interesados = $dinner->get_interesados();
                    $user_asig   = in_array( $user_id, $asignados, true );
                    $user_inter  = in_array( $user_id, $interesados, true );
                    $cupos_disp  = max( 0, 4 - count( $asignados ) );
                    $completo    = $cupos_disp === 0 && ! $user_asig;
                    $fecha_ts    = strtotime( $dinner->get_fecha() );
                ?>
                <div class="card-vx d-flex gap-4 align-items-start flex-wrap" <?php echo $completo ? 'style="opacity:.7"' : ''; ?>>
                    <div class="text-center event-date-col">
                        <div class="subsection-label" style="margin-bottom:2px"><?php echo esc_html( strtoupper( date_i18n( 'M', $fecha_ts ) ) ); ?></div>
                        <div class="stat-num-vx <?php echo $completo ? '' : 'stat-num-vx--success'; ?> event-date-day" <?php echo $completo ? 'style="color:var(--color-ice-700)"' : ''; ?>><?php echo esc_html( date_i18n( 'j', $fecha_ts ) ); ?></div>
                        <div class="text-xs-muted"><?php echo esc_html( strtoupper( date_i18n( 'D', $fecha_ts ) ) ); ?></div>
                    </div>
                    <div class="event-card-body">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <h3 class="fw-semibold" style="font-size:15px;margin:0">4Dinner <?php echo esc_html( $dinner->get_ciudad() ); ?></h3>
                            <?php if ( $user_asig ) : ?>
                                <span class="badge-vx badge-success">Confirmado</span>
                            <?php elseif ( $completo ) : ?>
                                <span class="badge-vx badge-neutral">Completo</span>
                            <?php else : ?>
                                <span class="badge-vx badge-primary">Cupos disponibles</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mb-2 text-sm-muted">
                            <span><i class="ti ti-map-pin me-1 <?php echo $completo ? '' : 'ic-success'; ?>"></i><?php echo esc_html( $dinner->get_ciudad() . ', ' . $dinner->get_pais() ); ?></span>
                            <span><i class="ti ti-clock me-1 <?php echo $completo ? '' : 'ic-success'; ?>"></i>8:00 pm</span>
                            <span><i class="ti ti-users me-1 <?php echo $completo ? '' : 'ic-success'; ?>"></i>
                                <?php echo $completo ? '4/4 · Completo' : esc_html( $cupos_disp . ( $cupos_disp === 1 ? ' cupo libre' : ' cupos libres' ) ); ?>
                            </span>
                        </div>
                        <?php if ( $dinner->get_restaurante() ) : ?>
                        <p class="text-body-muted mb-3"><?php echo esc_html( $dinner->get_restaurante() ); ?></p>
                        <?php endif; ?>
                        <?php if ( count( $asignados ) > 0 ) : ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-group">
                                <?php foreach ( array_slice( $asignados, 0, 4 ) as $uid ) : ?>
                                <div class="av"><img src="<?php echo esc_url( get_avatar_url( $uid, [ 'size' => 40 ] ) ); ?>" alt=""></div>
                                <?php endforeach; ?>
                            </div>
                            <span class="text-sm-muted">
                                <?php echo count( $asignados ); ?> confirmado<?php echo count( $asignados ) !== 1 ? 's' : ''; ?>
                                <?php echo $cupos_disp > 0 ? ' · ' . $cupos_disp . ( $cupos_disp === 1 ? ' cupo libre' : ' cupos libres' ) : ''; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="event-card-cta">
                        <?php if ( $user_asig ) : ?>
                            <span class="text-sm-muted" style="font-size:13px">Recibirás los detalles<br>por email</span>
                        <?php elseif ( $completo ) : ?>
                            <button class="btn-vx btn-ghost-vx btn-vx-sm vx-dinner-interes-btn <?php echo $user_inter ? 'vx-dinner-interes-btn--active' : ''; ?>"
                                    data-dinner-id="<?php echo esc_attr( $dinner->get_id() ); ?>" data-activo="<?php echo $user_inter ? '1' : '0'; ?>">
                                <i class="ti ti-list"></i> <?php echo $user_inter ? 'En lista de espera' : 'Lista de espera'; ?>
                            </button>
                        <?php else : ?>
                            <button class="btn-vx btn-primary-vx btn-vx-sm vx-dinner-interes-btn <?php echo $user_inter ? 'vx-dinner-interes-btn--active' : ''; ?>"
                                    data-dinner-id="<?php echo esc_attr( $dinner->get_id() ); ?>" data-activo="<?php echo $user_inter ? '1' : '0'; ?>">
                                <i class="ti ti-send"></i> <?php echo $user_inter ? 'Quitar interés' : 'Quiero ir'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="empty-state-vx py-4">
                <i class="ti ti-restaurant empty-state-vx__icon"></i>
                <h2 class="empty-state-vx__title">No hay eventos próximos</h2>
                <p class="empty-state-vx__desc">Cuando se creen nuevos eventos 4Dinner recibirás una notificación.</p>
            </div>
            <?php endif; ?>
        </section>

        <!-- FORMULARIO DE INTERÉS -->
        <section id="form-interes" class="mb-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="card-vx">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box icon-box--sm icon-box--green" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:var(--color-green-100);border-radius:10px;flex-shrink:0">
                                <i class="ti ti-tools-kitchen-2" style="font-size:22px;color:var(--color-green-600)"></i>
                            </div>
                            <div>
                                <h2 class="subsection-title" style="margin:0">Quiero participar en un 4Dinner</h2>
                                <p class="text-body-muted" style="margin:0">Te avisamos cuando haya un evento cerca tuyo</p>
                            </div>
                        </div>
                        <div class="alert-vx alert-info mb-4">
                            <i class="ti ti-info-circle"></i>
                            <span>No es Google Form — te asignamos directamente a la próxima mesa disponible en tu ciudad.</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label-vx">Tu nombre</label>
                                <div class="input-group-vx">
                                    <span class="input-icon"><i class="ti ti-user"></i></span>
                                    <input type="text" placeholder="Felipe Muñoz" id="dinner-nombre">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label-vx">Ciudad donde te gustaría cenar</label>
                                <select class="form-control-vx" id="dinner-ciudad">
                                    <option value="">Selecciona una ciudad...</option>
                                    <?php foreach ( [ 'Bogotá, Colombia','Santiago, Chile','Ciudad de México, México','Buenos Aires, Argentina','Lima, Perú','Montevideo, Uruguay','Madrid, España','Otra' ] as $c ) : ?>
                                    <option><?php echo esc_html( $c ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label-vx">¿Fechas a evitar?</label>
                                <textarea class="form-control-vx" rows="2" placeholder="Ej: Prefiero junio, evito la última semana del mes..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label-vx">¿Algún rubro o perfil con quien te interese cenar?</label>
                                <textarea class="form-control-vx" rows="2" placeholder="Ej: Me interesa conocer gente del sector legal o fintech..."></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn-vx btn-primary-vx btn-vx-md w-100"><i class="ti ti-send"></i> Enviar mi interés</button>
                                <p class="text-xs-muted text-center mt-2">El equipo te confirma por email en 24–48 horas hábiles.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- EVENTOS PASADOS -->
        <?php if ( $pasados ) : ?>
        <section class="mb-5">
            <div class="mb-3">
                <span class="subsection-label">Historia</span>
                <h2 class="subsection-title">Eventos pasados</h2>
            </div>
            <div class="row g-3">
                <?php foreach ( array_slice( $pasados, 0, 4 ) as $i => $dinner ) :
                    $grad     = $gradients[ $i % 4 ];
                    $asig     = $dinner->get_asignados();
                    $fecha_ts = strtotime( $dinner->get_fecha() );
                ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card-vx p-0 overflow-hidden">
                        <div class="event-past-header" style="background:<?php echo esc_attr( $grad ); ?>">
                            <div>
                                <div class="event-past-header__month"><?php echo esc_html( date_i18n( 'F Y', $fecha_ts ) ); ?></div>
                                <div class="event-past-header__city"><?php echo esc_html( $dinner->get_ciudad() ); ?></div>
                            </div>
                            <span class="badge-overlay">Realizado</span>
                        </div>
                        <div class="event-past-card-body">
                            <?php if ( $asig ) : ?>
                            <div class="avatar-group mb-2">
                                <?php foreach ( array_slice( $asig, 0, 4 ) as $uid ) : ?>
                                <div class="av"><img src="<?php echo esc_url( get_avatar_url( $uid, [ 'size' => 40 ] ) ); ?>" alt=""></div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ( $dinner->get_restaurante() ) : ?>
                            <div class="text-xs-muted"><i class="ti ti-map-pin me-1 ic-success"></i><?php echo esc_html( $dinner->get_restaurante() . ', ' . $dinner->get_ciudad() ); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
} );

// [vx_comunidad] — página de comunidad (out2b, woman, senior)
add_shortcode( 'vx_comunidad', function ( $atts ): string {
    $atts = shortcode_atts( [ 'slug' => '' ], $atts );
    $slug = sanitize_key( $atts['slug'] );

    if ( ! in_array( $slug, [ 'out2b', 'woman', 'senior' ], true ) ) return '';

    $user_id  = get_current_user_id();
    $user     = VX_User::get( $user_id );
    $is_member = $user && $user->is_in_community( $slug );

    $pagina   = max( 1, (int) ( $_GET['pagina'] ?? 1 ) );
    $result   = VX_Community::get_members( $slug, [ 'pagina' => $pagina ] );
    $members  = $result['members'] ?? [];
    $pagination = $result['pagination'] ?? [];

    $meta = [
        'out2b'  => [ 'Out2B', 'Red de empresarios hispanoamericanos con vocación internacional.' ],
        'woman'  => [ 'Woman', 'Comunidad de empresarias hispanoamericanas.' ],
        'senior' => [ 'Senior', 'Empresarios con más de 15 años de trayectoria verificada.' ],
    ];

    [ $nombre, $descripcion ] = $meta[ $slug ];

    ob_start();
    ?>
    <div class="vx-comunidad vx-comunidad--<?php echo esc_attr( $slug ); ?>">
        <div class="container py-4">

            <div class="vx-comunidad__header mb-4">
                <div>
                    <h1 class="vx-page-title"><?php echo esc_html( $nombre ); ?></h1>
                    <p class="text-muted"><?php echo esc_html( $descripcion ); ?></p>
                </div>
                <?php if ( ! $is_member ) : ?>
                    <button id="vx-join-community" class="btn-vx btn-vx--primary" data-community="<?php echo esc_attr( $slug ); ?>">
                        Unirme
                    </button>
                <?php else : ?>
                    <span class="badge-vx badge-vx--success">Eres miembro</span>
                <?php endif; ?>
            </div>

            <?php if ( $members ) : ?>
                <div class="row g-4">
                    <?php foreach ( $members as $member ) : ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <?php get_template_part( 'partials/card-member', null, [ 'member' => $member, 'context' => 'comunidad' ] ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( ! empty( $pagination ) && $pagination['total_pages'] > 1 ) : ?>
                    <?php get_template_part( 'partials/pagination', null, [ 'pagination' => $pagination ] ); ?>
                <?php endif; ?>
            <?php else : ?>
                <?php get_template_part( 'partials/empty-state', null, [ 'tipo' => 'comunidad' ] ); ?>
            <?php endif; ?>

        </div>
    </div>
    <?php
    return ob_get_clean();
} );
