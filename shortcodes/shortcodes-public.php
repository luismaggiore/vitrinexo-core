<?php
// v2
if ( ! defined( 'ABSPATH' ) ) exit;

// [vx_landing] — página de inicio pública
add_shortcode( 'vx_landing', function (): string {
    $logo_url     = get_template_directory_uri() . '/assets/img/vitrinexo.svg';
    $is_logged    = is_user_logged_in();
    $registro_url = $is_logged ? home_url( '/dashboard/' ) : home_url( '/login/?tab=registro' );
    $cta_label    = $is_logged ? 'Ir a mi dashboard' : 'Quiero ser Pionero';
    ob_start();
    ?>

    <!-- ── Hero rediseñado 40/60 ── -->
    <section class="vx-hero-split">
        <div class="vx-hero-split__text">
            <div class="vx-hero-split__inner">
                <img class="vx-hero-split__logo fade-up" width="180" src="<?php echo esc_url( $logo_url ); ?>" alt="Vitrinexo" />
                <h1 class="vx-hero-split__title fade-up">Tu <strong>vitrina</strong> para construir <strong>nexos</strong> de negocio.</h1>
                <p class="vx-hero-split__lead fade-up">Muestra lo que haces y encuentra lo que necesitas. <span class="vx-name">Vitrinexo</span> conecta empresas de servicios con quienes las buscan, sin publicidad y entre pares.</p>
                <div class="vx-hero-split__actions fade-up">
                    <a class="btn-vx btn-primary-vx btn-vx-lg btn rounded-pill" href="<?php echo $is_logged ? esc_url( home_url( '/dashboard/' ) ) : '#afiliado-original'; ?>"><?php echo esc_html( $cta_label ); ?></a>
                    <a class="btn-vx btn-outline-vx btn-vx-lg btn rounded-pill" href="#como-funciona">Cómo funciona <i class="ti ti-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="vx-hero-split__media fade-in">
            <img class="vx-hero-split__img" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/1-principal-la-elegida-por-mi.png" alt="Conexiones empresariales Vitrinexo" />
            <div class="vx-hero-split__overlay"></div>
        </div>
    </section>

    <!-- ── El problema ── -->
    <section class="section-landing" id="el-problema">
        <div class="container">
            <div class="section-landing-head">
                <span class="section-landing-label">El problema</span>
                <h2 class="section-landing-title">Mucho evento, <strong>poco nexo.</strong></h2>
                <p class="section-landing-lead">Inscribirse en asociaciones, ir a diplomados, tomar cafés que nunca se concretan. Caro, lento y siempre con esa incómoda barrera de parecer fuera de lugar al ofrecer servicios de inmediato.</p>
            </div>
            <div class="problem-grid">
                <div class="step-card">
                    <div class="step-card__icon"><i class="ti ti-cash-off"></i></div>
                    <h3 class="step-card__title">Caro e ineficiente</h3>
                    <p class="step-card__desc">Membresías, eventos, viajes. El retorno sobre el tiempo invertido en el networking tradicional es bajo y casi imposible de medir.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__icon"><i class="ti ti-mood-empty"></i></div>
                    <h3 class="step-card__title">A veces incómodo</h3>
                    <p class="step-card__desc">No todo el mundo tiene una personalidad extrovertida y vendedora. El formato tradicional penaliza a quienes prefieren que su trabajo hable por ellos.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__icon"><i class="ti ti-brand-linkedin"></i></div>
                    <h3 class="step-card__title">LinkedIn no convierte</h3>
                    <p class="step-card__desc">Ya nadie responde mensajes de desconocidos porque sabe que le van a querer vender algo. El medio mató al mensaje.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__icon"><i class="ti ti-bulb"></i></div>
                    <h3 class="step-card__title">Vitrinexo cambia el contexto</h3>
                    <p class="step-card__desc">Aquí todos están para hacer negocios, eso lo hace legítimo desde el inicio. No hay que disculparse por ofrecer lo que haces.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Cómo funciona ── -->
    <section class="section-landing" id="como-funciona">
        <div class="container">
            <div class="section-landing-head">
                <span class="section-landing-label">Cómo funciona</span>
                <h2 class="section-landing-title">Dinos qué ofreces. Dinos qué buscas.<br><strong>Vitrinexo conecta los puntos para ti.</strong></h2>
                <p class="section-landing-lead">Cada empresa y persona tiene un perfil con información verificada.</p>
            </div>
            <div class="problem-grid">
                <div class="step-card">
                    <div class="step-card__num">01</div>
                    <div class="step-card__icon"><i class="ti ti-id-badge-2"></i></div>
                    <h3 class="step-card__title">Crea tu ficha</h3>
                    <p class="step-card__desc">Publicas quién eres, qué haces y qué buscas.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__num">02</div>
                    <div class="step-card__icon"><i class="ti ti-shield-check"></i></div>
                    <h3 class="step-card__title">Verificamos tus datos</h3>
                    <p class="step-card__desc">En menos de un día hábil tu perfil estará visible para todos los miembros.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__num">03</div>
                    <div class="step-card__icon"><i class="ti ti-search"></i></div>
                    <h3 class="step-card__title">Vitrinea el directorio</h3>
                    <p class="step-card__desc">Identifica oportunidades y comienza a conectar.</p>
                </div>
                <div class="step-card">
                    <div class="step-card__num">04</div>
                    <div class="step-card__icon"><i class="ti ti-heart-handshake"></i></div>
                    <h3 class="step-card__title">Haz negocios</h3>
                    <p class="step-card__desc">Contacto directo, sin intermediarios ni comisiones.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ── Para quién es ── -->
    <section class="section-landing" id="para-quien">
        <div class="container">
            <div class="audience-block">
                <div class="section-landing-head">
                    <span class="section-landing-label">Para quién es</span>
                    <h2 class="section-landing-title">Hecho para empresas de servicios B2B en <strong>expansión</strong></h2>
                    <p class="section-landing-lead">¿Quieres crecer más allá de tus fronteras?<br><span class="vx-name">Vitrinexo</span> es para ti.</p>
                </div>
                <div class="audience-chips">
                    <?php
                    $rubros = [ 'Marketing y publicidad','Tecnología y software','Consultoría y estrategia','Diseño y creatividad','Legal y ética','Contabilidad y finanzas','Recursos humanos','Logística','Salud y bienestar','Educación y capacitación','Construcción e ingeniería','Producción audiovisual','Traducción y localización' ];
                    foreach ( $rubros as $rubro ) :
                    ?><span class="audience-chip"><?php echo esc_html( $rubro ); ?></span><?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ── El multiverso ── -->
    <section class="section-landing" id="el-multiverso">
        <div class="container">
            <div class="section-landing-head">
                <span class="section-landing-label">El multiverso</span>
                <h2 class="section-landing-title">Una plataforma.<br><strong>Muchas comunidades.</strong></h2>
                <p class="section-landing-lead"><span class="vx-name">Vitrinexo</span> no es una sola comunidad. Hay subcomunidades verticales dentro de la misma plataforma, cada una con su propia afinidad y propósito.</p>
            </div>
            <div class="multiverse-grid">
                <div class="multiverse-card multiverse-card--main">
                    <span class="multiverse-card__label">Plataforma principal</span>
                    <h3 class="multiverse-card__title">Vitrinexo</h3>
                    <p class="multiverse-card__desc">El directorio B2B completo. La vitrina donde cada empresa de servicios se muestra y encuentra aliados, clientes y proveedores.</p>
                </div>
                <div class="multiverse-card multiverse-card--out2b">
                    <span class="multiverse-card__label">Comunidad vertical</span>
                    <h3 class="multiverse-card__title">Vitrinexo <em>LGBTQ+</em></h3>
                    <p class="multiverse-card__desc">Para líderes y ejecutivos LGBTQ+ en el mundo empresarial.</p>
                </div>
                <div class="multiverse-card multiverse-card--woman">
                    <span class="multiverse-card__label">Comunidad vertical</span>
                    <h3 class="multiverse-card__title">Vitrinexo <em>Woman</em></h3>
                    <p class="multiverse-card__desc">Mujeres en posiciones de liderazgo empresarial.</p>
                </div>
                <div class="multiverse-card multiverse-card--senior">
                    <span class="multiverse-card__label">Comunidad vertical</span>
                    <h3 class="multiverse-card__title">Vitrinexo <em>Senior</em></h3>
                    <p class="multiverse-card__desc">Ejecutivos con trayectoria consolidada: experiencia como ventaja competitiva.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── 4Dinner ── -->
    <section class="section-landing section-landing--alt section-landing--simple" id="for-dinner">
        <div class="container">
            <div class="section-landing-head">
                <span class="section-landing-label">Experiencia presencial</span>
                <h2 class="section-landing-title">Vitrinexo <strong>4Dinner</strong></h2>
                <p class="section-landing-lead">Cenas de networking en formato íntimo: 4 personas, 1 mesa, 1 conversación real.<br>Miércoles 8pm hora local, simultáneamente en múltiples ciudades.</p>
            </div>
        </div>
    </section>

    <!-- ── Miembro Pionero ── -->
    <?php if ( ! $is_logged ) : ?>
    <section class="section-landing" id="afiliado-original">
        <div class="container">
            <div class="founder-block">
                <div>
                    <span class="badge-vx badge-founder mb-3 d-inline-flex"><i class="ti ti-star"></i> Miembro Pionero · Gratis 3 meses</span>
                    <h2 class="section-landing-title mb-3">Sé parte desde <strong>el primer día.</strong></h2>
                    <p class="section-landing-lead mb-4">Los 100 Miembros Pioneros acceden a beneficios especiales.</p>
                    <ul class="founder-benefits">
                        <li><i class="ti ti-circle-check"></i><span><strong>3 meses gratis</strong> desde el lanzamiento.</span></li>
                        <li><i class="ti ti-circle-check"></i><span>Distintivo <strong>"Miembro Pionero"</strong> visible en tu ficha para siempre.</span></li>
                        <li><i class="ti ti-circle-check"></i><span>Sin compromiso. Si no te convence, simplemente no renuevas.</span></li>
                    </ul>
                </div>
                <div class="founder-form-card">
                    <div id="founderFormView">
                        <h3>Inscríbete</h3>

                        <!-- Contador de inscritos con barra de progreso -->
                        <div id="founderCounter" style="margin-bottom:16px">
                            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
                                <span style="font-size:13px;color:var(--color-text-muted)">Miembros Pioneros</span>
                                <span id="founderCountText" style="font-size:13px;font-weight:600;color:var(--color-primary)">— / 100</span>
                            </div>
                            <div style="background:#e8f0fe;border-radius:999px;height:6px;overflow:hidden">
                                <div id="founderProgressBar" style="height:100%;width:0%;background:var(--color-primary);border-radius:999px;transition:width .6s ease"></div>
                            </div>
                            <p id="founderCountSub" style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0"></p>
                        </div>

                        <div id="founderFormError" class="alert-vx alert-error mb-2" style="display:none;padding:8px 12px;font-size:13px"></div>
                        <form id="founderForm">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label-vx">Nombre *</label>
                                    <input class="form-control-vx" name="nombre" required autocomplete="given-name" />
                                </div>
                                <div class="col-6">
                                    <label class="form-label-vx">Apellido *</label>
                                    <input class="form-control-vx" name="apellido" required autocomplete="family-name" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">Email *</label>
                                <input type="email" class="form-control-vx" name="email" required autocomplete="email" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">Contraseña *</label>
                                <div style="position:relative">
                                    <input type="password" class="form-control-vx" name="password" required autocomplete="new-password" id="founderPassword" style="padding-right:40px" />
                                    <button type="button" id="founderPwdToggle" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-muted);padding:4px">
                                        <i class="ti ti-eye" id="founderPwdIcon" style="font-size:16px"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">Empresa *</label>
                                <input class="form-control-vx" name="empresa" required autocomplete="organization" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">Cargo *</label>
                                <input class="form-control-vx" name="cargo" required autocomplete="organization-title" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">LinkedIn *</label>
                                <input type="url" class="form-control-vx" name="linkedin" required autocomplete="url" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label-vx">Teléfono celular *</label>
                                <input type="tel" class="form-control-vx" name="telefono" required autocomplete="tel" />
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label-vx">País *</label>
                                    <select class="form-control-vx" name="pais" id="founderPais" required>
                                        <option value="">Detectando...</option>
                                        <?php foreach ( [ 'Chile','México','Colombia','Argentina','Perú','España','Ecuador','Uruguay','Venezuela','Bolivia','Paraguay','Guatemala','Honduras','El Salvador','Nicaragua','Costa Rica','Panamá','Cuba','República Dominicana','Estados Unidos','Otro' ] as $p ) : ?>
                                        <option><?php echo esc_html( $p ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label-vx">Rubro *</label>
                                    <select class="form-control-vx" name="rubro" required>
                                        <option value="">Selecciona</option>
                                        <?php foreach ( [ 'Marketing y publicidad','Tecnología y software','Consultoría y estrategia','Diseño y creatividad','Legal y ética','Contabilidad y finanzas','Recursos humanos','Logística','Salud y bienestar','Educación y capacitación','Construcción e ingeniería','Otro' ] as $r ) : ?>
                                        <option><?php echo esc_html( $r ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn-vx btn-primary-vx btn-vx-md w-100 justify-content-center">
                                <i class="ti ti-arrow-right"></i> Empieza a Vitrinear
                            </button>
                        </form>
                    </div>
                    <div class="founder-form-success" id="founderFormSuccess">
                        <h3>⏳ ¡Tu perfil está siendo validado!</h3>
                        <p>Revisa tu bandeja de entrada.<br>Te escribiremos pronto.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function() {
        // ── Contador de inscritos ──────────────────────────────────────────
        fetch('/wp-json/vitrinexo/v1/stats/inscritos')
            .then(function(r){ return r.json(); })
            .then(function(d){
                document.getElementById('founderCountText').textContent = d.inscritos + ' / ' + d.cupo;
                document.getElementById('founderProgressBar').style.width = d.porcentaje + '%';
                var restante = d.restante;
                document.getElementById('founderCountSub').textContent =
                    restante > 0
                        ? 'Quedan ' + restante + ' cupos gratuitos.'
                        : '¡Cupos agotados! Únete a la lista de espera.';
            })
            .catch(function(){});

        // ── Autodetección de país por IP ──────────────────────────────────
        var paisSelect = document.getElementById('founderPais');
        fetch('https://ipapi.co/json/')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var pais = d.country_name || '';
                // Mapear nombres en inglés a español
                var mapa = {
                    'Chile':'Chile','Mexico':'México','Colombia':'Colombia',
                    'Argentina':'Argentina','Peru':'Perú','Spain':'España',
                    'Ecuador':'Ecuador','Uruguay':'Uruguay','Venezuela':'Venezuela',
                    'Bolivia':'Bolivia','Paraguay':'Paraguay','Guatemala':'Guatemala',
                    'Honduras':'Honduras','El Salvador':'El Salvador','Nicaragua':'Nicaragua',
                    'Costa Rica':'Costa Rica','Panama':'Panamá','Cuba':'Cuba',
                    'Dominican Republic':'República Dominicana','United States':'Estados Unidos'
                };
                var paisEs = mapa[pais] || '';
                if (paisSelect) {
                    if (paisEs) {
                        paisSelect.value = paisEs;
                    }
                    if (!paisSelect.value || paisSelect.value === '') {
                        // Agregar la opción si no existe
                        var opt = document.createElement('option');
                        opt.value = pais; opt.textContent = pais; opt.selected = true;
                        paisSelect.insertBefore(opt, paisSelect.options[1]);
                    }
                    // Primer option vacío → quitar "Detectando..."
                    if (paisSelect.options[0].value === '') {
                        paisSelect.options[0].textContent = 'Selecciona';
                    }
                }
            })
            .catch(function(){
                if (paisSelect && paisSelect.options[0]) {
                    paisSelect.options[0].textContent = 'Selecciona';
                    paisSelect.value = '';
                }
            });

        // ── Toggle contraseña ─────────────────────────────────────────────
        var pwdInput  = document.getElementById('founderPassword');
        var pwdToggle = document.getElementById('founderPwdToggle');
        var pwdIcon   = document.getElementById('founderPwdIcon');
        if (pwdToggle) {
            pwdToggle.addEventListener('click', function(){
                var show = pwdInput.type === 'password';
                pwdInput.type  = show ? 'text' : 'password';
                pwdIcon.className = show ? 'ti ti-eye-off' : 'ti ti-eye';
            });
        }

        // ── Submit ────────────────────────────────────────────────────────
        var form    = document.getElementById('founderForm');
        var view    = document.getElementById('founderFormView');
        var success = document.getElementById('founderFormSuccess');
        var errBox  = document.getElementById('founderFormError');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            var btn  = form.querySelector('[type="submit"]');
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-loader"></i> Enviando...';
            btn.disabled  = true;
            if (errBox) errBox.style.display = 'none';

            var data = {};
            new FormData(form).forEach(function(v, k) { data[k] = v; });

            try {
                var res  = await fetch('/wp-json/vitrinexo/v1/registrar', {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body   : JSON.stringify(data)
                });
                var json = await res.json();
                if (json.success) {
                    view.style.display = 'none';
                    success.classList.add('show');
                } else {
                    var msg = json.message || 'Error al registrar. Escríbenos a hola@vitrinexo.com';
                    if (json.error === 'email_existente') msg = 'Ese email ya está registrado.';
                    btn.innerHTML = orig; btn.disabled = false;
                    if (errBox) { errBox.textContent = msg; errBox.style.display = 'block'; }
                }
            } catch(err) {
                btn.innerHTML = orig; btn.disabled = false;
                if (errBox) { errBox.textContent = 'Error de conexión. Intenta de nuevo.'; errBox.style.display = 'block'; }
            }
        });
    })();
    </script>

    <?php endif; /* !$is_logged */ ?>

    <?php
    return ob_get_clean();
} );

// [vx_landing_4dinner] — landing pública del evento 4Dinner (página de marketing)
add_shortcode( 'vx_landing_4dinner', function (): string {
    $registro_url = home_url( '/login/?tab=registro' );
    $login_url    = home_url( '/login/' );

    // Próximas cenas (preview público — solo ciudad, fecha y cupos, sin datos de asignados)
    $dinners = class_exists( 'VX_Dinner' ) ? VX_Dinner::get_upcoming() : [];

    ob_start();
    ?>
    <!-- HERO 4DINNER -->
    <div class="hero-4dinner">
      <div class="container py-5">
        <div class="row align-items-center g-5">
          <div class="col-12 col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="badge-hero-4dinner">Evento presencial</span>
            </div>
            <h1 style="font-size:clamp(2.2rem,5vw,3.5rem);font-weight:400;letter-spacing:-0.04em;color:#78350f;line-height:1.1;margin-bottom:1rem">
              4 personas.<br>1 mesa.<br><em style="font-style:italic">1 conversación real.</em>
            </h1>
            <p style="font-size:1rem;color:#92400e;line-height:1.7;max-width:480px;margin-bottom:2rem">
              Cada miércoles a las 8pm, cuatro miembros de Vitrinexo se sientan a cenar en una ciudad de Hispanoamérica. Sin agenda formal, sin pitches. Solo personas que ya se conocen por sus fichas y quieren dar el paso a lo presencial.
            </p>
            <div class="d-flex gap-3 flex-wrap">
              <a href="<?php echo esc_url( $registro_url ); ?>" class="btn-vx btn-vx-lg btn-4dinner-cta">
                Quiero participar <i class="ti ti-arrow-right ms-1"></i>
              </a>
              <a href="#como-funciona" class="btn-vx btn-vx-lg btn-4dinner-ghost">
                Cómo funciona
              </a>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="stats-card-hero">
              <div class="row g-3 text-center">
                <div class="col-4">
                  <div class="stat-num-hero">4</div>
                  <div class="stat-label-hero">personas por mesa</div>
                </div>
                <div class="col-4">
                  <div class="stat-num-hero">8pm</div>
                  <div class="stat-label-hero">hora local, miércoles</div>
                </div>
                <div class="col-4">
                  <div class="stat-num-hero">18+</div>
                  <div class="stat-label-hero">ciudades activas</div>
                </div>
              </div>
              <hr style="border-color:#fde68a;margin:1.25rem 0">
              <div class="d-flex flex-column gap-2">
                <div class="d-flex align-items-center gap-2 checklist-item-hero"><i class="ti ti-circle-check checklist-icon-hero"></i> Cada quien paga su consumo — sin costo de acceso</div>
                <div class="d-flex align-items-center gap-2 checklist-item-hero"><i class="ti ti-circle-check checklist-icon-hero"></i> Perfiles mixtos de industria — nada de silos</div>
                <div class="d-flex align-items-center gap-2 checklist-item-hero"><i class="ti ti-circle-check checklist-icon-hero"></i> Simultáneo en múltiples ciudades todos los miércoles</div>
                <div class="d-flex align-items-center gap-2 checklist-item-hero"><i class="ti ti-circle-check checklist-icon-hero"></i> Exclusivo para miembros verificados de Vitrinexo</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <main>
    <div class="container py-5">

      <!-- POR QUÉ FUNCIONA -->
      <div class="row g-5 align-items-center" style="margin-bottom:5rem">
        <div class="col-12 col-lg-5">
          <span class="section-landing-label">La idea detrás</span>
          <h2 style="font-size:clamp(1.6rem,3vw,2.4rem);font-weight:400;letter-spacing:-0.04em;color:var(--color-text-primary);margin:.5rem 0 1rem;line-height:1.2">
            El networking que sí funciona es el que se parece a una cena con amigos
          </h2>
          <p class="text-lead-muted mb-3">Las conferencias, los happy hours y los eventos masivos generan tarjetas de presentación. Las cenas íntimas generan relaciones reales.</p>
          <p class="text-lead-muted mb-3">Con solo 4 personas en la mesa, no hay forma de esconderse ni de hacer networking superficial. La conversación va en serio porque tiene que ir en serio.</p>
          <p class="text-lead-muted">Y como todos son miembros verificados de <span class="vx-name">Vitrinexo</span>, ya se conocen por sus fichas antes de llegar — lo que hace que la primera hora valga por tres.</p>
        </div>
        <div class="col-12 col-lg-7">
          <div class="row g-3">
            <div class="col-6"><div class="card-vx h-100"><div class="card-title-sm">Conexiones que perduran</div><p class="text-body-muted mb-0">Una cena de 4Dinner genera más conexiones de valor que una tarde entera en un evento masivo.</p></div></div>
            <div class="col-6"><div class="card-vx h-100"><div class="card-title-sm">Continental y local</div><p class="text-body-muted mb-0">Simultáneo en 18+ ciudades. Lo que pasa en Santiago pasa también en Bogotá, Lima y México.</p></div></div>
            <div class="col-6"><div class="card-vx h-100"><div class="card-title-sm">Solo verificados</div><p class="text-body-muted mb-0">No hay sorpresas. Todos los comensales tienen ficha verificada en <span class="vx-name">Vitrinexo</span>.</p></div></div>
            <div class="col-6"><div class="card-vx h-100"><div class="card-title-sm">Sin costo de acceso</div><p class="text-body-muted mb-0">Cada persona paga su consumo. <span class="vx-name">Vitrinexo</span> coordina la mesa — gratis.</p></div></div>
          </div>
        </div>
      </div>

      <!-- CÓMO FUNCIONA -->
      <div class="mb-5" id="como-funciona" style="scroll-margin-top:80px">
        <div class="text-center mb-4">
          <span class="section-landing-label">El proceso</span>
          <h2 style="font-size:clamp(1.6rem,3vw,2.2rem);font-weight:400;letter-spacing:-0.04em;color:var(--color-text-primary);margin:.5rem 0">¿Cómo funciona?</h2>
        </div>
        <div class="row g-3 justify-content-center">
          <div class="col-12 col-md-6 col-lg-3">
            <div class="card-vx text-center h-100">
              <div class="step-circle step-circle--green">1</div>
              <div class="card-title-sm">Crea tu cuenta</div>
              <p class="text-sm-muted mb-0" style="line-height:1.6">Regístrate en <span class="vx-name">Vitrinexo</span> y completa tu perfil. Es el punto de partida para todo.</p>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <div class="card-vx text-center h-100">
              <div class="step-circle step-circle--cyan">2</div>
              <div class="card-title-sm">Activa 4Dinner</div>
              <p class="text-sm-muted mb-0" style="line-height:1.6">Desde tu perfil, activa la comunidad 4Dinner e indica tu ciudad y disponibilidad.</p>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <div class="card-vx text-center h-100">
              <div class="step-circle step-circle--purple">3</div>
              <div class="card-title-sm">Te asignamos una mesa</div>
              <p class="text-sm-muted mb-0" style="line-height:1.6">El equipo de <span class="vx-name">Vitrinexo</span> arma la mesa con perfiles complementarios y te confirma el restaurante.</p>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <div class="card-vx text-center h-100">
              <div class="step-circle step-circle--golden">🍽</div>
              <div class="card-title-sm">Cenas el miércoles</div>
              <p class="text-sm-muted mb-0" style="line-height:1.6">Llegas, te sientas y la conversación ocurre sola. Sin moderador, sin agenda.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- PRÓXIMAS CENAS (preview público) -->
      <?php if ( $dinners ) : ?>
      <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <span class="section-landing-label">Agenda</span>
            <h2 style="font-size:1.5rem;font-weight:400;letter-spacing:-0.03em;color:var(--color-text-primary);margin:.25rem 0">Próximas cenas</h2>
          </div>
          <a href="<?php echo esc_url( $registro_url ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm link-primary-color">
            Ver todas al registrarte <i class="ti ti-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="row g-3">
          <?php foreach ( array_slice( $dinners, 0, 3 ) as $dinner ) :
            $fecha_ts  = $dinner->get_fecha();
            $asignados = $dinner->get_asignados();
            $cupos     = max( 0, 4 - count( $asignados ) );
          ?>
          <div class="col-12 col-md-4">
            <div class="card-vx d-flex gap-3 align-items-start">
              <div class="text-center event-date-col--sm">
                <div class="subsection-label" style="margin-bottom:2px;font-size:9px"><?php echo esc_html( strtoupper( date_i18n( 'M', $fecha_ts ) ) ); ?></div>
                <div class="event-day-num ic-success"><?php echo esc_html( date_i18n( 'j', $fecha_ts ) ); ?></div>
                <div class="text-xs-muted"><?php echo esc_html( strtoupper( date_i18n( 'D', $fecha_ts ) ) ); ?></div>
              </div>
              <div>
                <div class="card-title-sm" style="margin-bottom:3px"><?php echo esc_html( $dinner->get_ciudad() ); ?></div>
                <div class="text-sm-muted">
                  <i class="ti ti-users me-1"></i><?php echo esc_html( $cupos . ( $cupos === 1 ? ' cupo libre' : ' cupos libres' ) ); ?> · 8pm
                </div>
                <?php if ( $cupos > 0 ) : ?>
                <span class="badge-vx badge-primary mt-2">Disponible</span>
                <?php else : ?>
                <span class="badge-vx badge-neutral mt-2">Completo</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- CTA FINAL -->
      <div class="text-center py-5">
        <h2 style="font-size:clamp(1.6rem,3vw,2.2rem);font-weight:400;letter-spacing:-0.04em;margin-bottom:1rem">¿Listo para tu primera cena?</h2>
        <p class="text-lead-muted mb-4">Crea tu cuenta en <span class="vx-name">Vitrinexo</span>, activa 4Dinner en tu perfil y el equipo te asigna a la próxima mesa disponible en tu ciudad.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          <a href="<?php echo esc_url( $registro_url ); ?>" class="btn-vx btn-primary-vx btn-vx-lg">
            <i class="ti ti-user-plus me-1"></i>Crear mi cuenta gratis
          </a>
          <a href="<?php echo esc_url( $login_url ); ?>" class="btn-vx btn-ghost-vx btn-vx-lg">
            Ya tengo cuenta
          </a>
        </div>
      </div>

    </div>
    </main>
    <?php
    return ob_get_clean();
} );

// [vx_blog] — listado de entradas del blog
add_shortcode( 'vx_blog', function (): string {
    $paged = max( 1, (int) get_query_var( 'paged' ) );
    $per_page = 7; // 1 destacado + 6 en grid

    $query = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    $gradients = [
        'linear-gradient(135deg,var(--color-purple-600),var(--color-purple-400))',
        'linear-gradient(135deg,var(--color-pink-600),var(--color-pink-400))',
        'linear-gradient(135deg,#f59e0b,#f97316)',
        'linear-gradient(135deg,#0ea5e9,#6366f1)',
        'linear-gradient(135deg,var(--color-green-700),var(--color-cyan-600))',
        'linear-gradient(135deg,#be185d,#ec4899)',
    ];

    ob_start();

    if ( ! $query->have_posts() ) :
    ?>
    <div class="page-header-vx">
        <div class="container">
            <div class="page-header-vx__inner">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="section-landing-label" style="margin:0">Blog</span>
                    </div>
                    <h1 class="page-header-vx__title">Ideas, tendencias y<br><strong>casos B2B</strong></h1>
                    <p class="page-header-vx__lead">Ensayos, entrevistas y data sobre el ecosistema de servicios B2B en Hispanoamérica.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-filter"></i> Categoría</button>
                    <button class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-world"></i> País</button>
                </div>
            </div>
        </div>
    </div>
    <div class="container py-5">
        <div class="empty-state-vx">
            <i class="ti ti-article-off empty-state-vx__icon"></i>
            <h2 class="empty-state-vx__title">Aún no hay artículos</h2>
            <p class="empty-state-vx__desc">Pronto publicaremos ensayos, entrevistas y data sobre el ecosistema B2B de Hispanoamérica.</p>
        </div>
    </div>
    <?php
    else :
        $posts_list = [];
        while ( $query->have_posts() ) {
            $query->the_post();
            $posts_list[] = [
                'id'          => get_the_ID(),
                'title'       => get_the_title(),
                'permalink'   => get_permalink(),
                'excerpt'     => wp_trim_words( get_the_excerpt(), 25 ),
                'date'        => get_the_date( 'j M Y' ),
                'read_time'   => max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ),
                'author_id'   => get_the_author_meta( 'ID' ),
                'author_name' => get_the_author(),
                'avatar'      => get_avatar_url( get_the_author_meta( 'ID' ), [ 'size' => 44 ] ),
                'thumbnail'   => has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : '',
                'categories'  => wp_list_pluck( get_the_category(), 'name' ),
            ];
        }
        wp_reset_postdata();

        $featured   = array_shift( $posts_list );
        $feat_cat   = $featured['categories'][0] ?? 'Blog';
        $feat_style = $featured['thumbnail']
            ? 'background:url(' . esc_url( $featured['thumbnail'] ) . ') center/cover no-repeat'
            : 'background:linear-gradient(135deg,var(--color-cyan-700),var(--color-green-600))';
    ?>
    <!-- Page header -->
    <div class="page-header-vx">
        <div class="container">
            <div class="page-header-vx__inner">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="section-landing-label" style="margin:0">Blog</span>
                    </div>
                    <h1 class="page-header-vx__title">Ideas, tendencias y<br><strong>casos B2B</strong></h1>
                    <p class="page-header-vx__lead">Ensayos, entrevistas y data sobre el ecosistema de servicios B2B en Hispanoamérica.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-filter"></i> Categoría</button>
                    <button class="btn-vx btn-ghost-vx btn-vx-sm"><i class="ti ti-world"></i> País</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">

        <!-- Artículo destacado -->
        <div class="card-vx mb-4 p-0 overflow-hidden">
            <div class="row g-0">
                <div class="col-12 col-md-5">
                    <div style="height:100%;min-height:200px;<?php echo esc_attr( $feat_style ); ?>;display:flex;align-items:flex-end;padding:1.5rem;position:relative;">
                        <span class="badge-vx" style="position:absolute;top:14px;left:14px;background:rgba(255,255,255,0.2);color:#fff">
                            <i class="ti ti-star me-1"></i> Destacado
                        </span>
                        <div>
                            <div style="font-size:11px;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:.12em;font-weight:600;margin-bottom:4px"><?php echo esc_html( $feat_cat ); ?></div>
                            <div style="font-size:1.35rem;font-weight:600;color:#fff;line-height:1.25"><?php echo esc_html( $featured['title'] ); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-7 d-flex flex-column" style="padding:1.5rem">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="<?php echo esc_url( $featured['avatar'] ); ?>" class="avatar-sm" alt="" />
                        <span class="text-sm-muted"><?php echo esc_html( $featured['author_name'] ); ?> · <span><?php echo esc_html( $featured['date'] ); ?></span> · <?php echo esc_html( $featured['read_time'] ); ?> min</span>
                    </div>
                    <p class="text-body-muted" style="line-height:1.7;flex-grow:1;margin-bottom:1rem"><?php echo esc_html( $featured['excerpt'] ); ?></p>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex gap-1 flex-wrap">
                            <?php foreach ( $featured['categories'] as $cat ) : ?>
                            <span class="tag-vx"><?php echo esc_html( $cat ); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url( $featured['permalink'] ); ?>" class="btn-vx btn-soft-primary btn-vx-sm">
                            Leer artículo <i class="ti ti-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ( ! empty( $posts_list ) ) : ?>
        <!-- Grid de artículos recientes -->
        <div class="mb-3">
            <span class="subsection-label">Recientes</span>
            <h2 class="subsection-title">Últimas publicaciones</h2>
        </div>

        <div class="row g-3 mb-5">
            <?php foreach ( $posts_list as $i => $post ) :
                $cat_name  = $post['categories'][0] ?? 'Blog';
                $grad      = $gradients[ $i % count( $gradients ) ];
                $img_style = $post['thumbnail']
                    ? 'background:url(' . esc_url( $post['thumbnail'] ) . ') center/cover no-repeat'
                    : 'background:' . $grad;
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?php echo esc_url( $post['permalink'] ); ?>" class="text-decoration-none d-block h-100">
                    <div class="card-vx p-0 h-100 overflow-hidden">
                        <div class="blog-card-img-wrap" style="<?php echo esc_attr( $img_style ); ?>">
                            <div class="blog-card-cat"><?php echo esc_html( $cat_name ); ?></div>
                        </div>
                        <div class="blog-card-body">
                            <h3 class="blog-card-title"><?php echo esc_html( $post['title'] ); ?></h3>
                            <p class="blog-card-excerpt"><?php echo esc_html( $post['excerpt'] ); ?></p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo esc_url( $post['avatar'] ); ?>" class="avatar-22" alt="" />
                                    <span class="text-xs-muted"><?php echo esc_html( $post['date'] ); ?> · <?php echo esc_html( $post['read_time'] ); ?> min</span>
                                </div>
                                <?php if ( $post['categories'] ) : ?>
                                <span class="tag-vx"><?php echo esc_html( $post['categories'][0] ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Paginación -->
        <?php
        $total_pages = $query->max_num_pages;
        if ( $total_pages > 1 ) : ?>
        <nav aria-label="Navegación del blog" class="mb-5">
            <ul class="pagination">
                <li class="page-item <?php echo $paged <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo esc_url( get_pagenum_link( $paged - 1 ) ); ?>"><i class="ti ti-chevron-left"></i></a>
                </li>
                <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                <li class="page-item <?php echo $p === $paged ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo esc_url( get_pagenum_link( $p ) ); ?>"><?php echo esc_html( $p ); ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $paged >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo esc_url( get_pagenum_link( $paged + 1 ) ); ?>"><i class="ti ti-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
    <?php
    endif;
    return ob_get_clean();
} );
