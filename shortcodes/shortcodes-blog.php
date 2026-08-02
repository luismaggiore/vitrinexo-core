<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [vx_blog_nuevo] — formulario para que un miembro escriba un artículo.
 * El artículo queda pendiente de aprobación de un administrador.
 */
add_shortcode( 'vx_blog_nuevo', function (): string {
    if ( ! is_user_logged_in() ) {
        return '<div class="container py-5"><p class="text-muted">Debes iniciar sesión para escribir un artículo. <a href="' . esc_url( home_url( '/login/' ) ) . '">Ingresar</a></p></div>';
    }

    $api   = rest_url( VX_REST_NAMESPACE . '/' );
    $nonce = wp_create_nonce( 'wp_rest' );
    $cats  = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );

    ob_start();
    ?>
    <section class="section-landing">
      <div class="container" style="max-width:720px">
        <div class="section-landing-head">
          <span class="section-landing-label">Blog</span>
          <h1 class="section-landing-title">Escribe un <strong>artículo</strong></h1>
          <p class="section-landing-lead">Comparte tu experiencia con la comunidad. Un administrador lo revisará antes de publicarlo.</p>
        </div>

        <form id="vx-blog-form" class="card-vx" style="padding:24px">
          <div class="mb-3">
            <label class="form-label-vx">Título *</label>
            <input type="text" id="vxb-titulo" class="form-control-vx" maxlength="140" placeholder="Un título claro y atractivo" required>
          </div>

          <div class="mb-3">
            <label class="form-label-vx">Categoría</label>
            <select id="vxb-categoria" class="form-control-vx">
              <option value="">Sin categoría</option>
              <?php foreach ( $cats as $cat ) : ?>
              <option value="<?php echo (int) $cat->term_id; ?>"><?php echo esc_html( $cat->name ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label-vx">Contenido *</label>
            <textarea id="vxb-contenido" class="form-control-vx" rows="12" placeholder="Escribe tu artículo aquí..." required></textarea>
          </div>

          <div id="vxb-msg" class="alert-vx d-none mb-3"></div>
          <button type="submit" class="btn-vx btn-primary-vx btn-vx-md" id="vxb-submit">Enviar para revisión</button>
        </form>

        <div id="vx-blog-success" class="card-vx text-center" style="display:none;padding:32px">
          <div style="font-size:44px">✅</div>
          <h3 style="margin:8px 0">¡Artículo enviado!</h3>
          <p class="cta-card__desc">Un administrador lo revisará y te avisaremos por correo cuando se publique.</p>
          <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="btn-vx btn-ghost-vx btn-vx-sm">Volver al blog</a>
        </div>
      </div>
    </section>

    <script>
    (function(){
      var API = <?php echo wp_json_encode( $api ); ?>;
      var NONCE = <?php echo wp_json_encode( $nonce ); ?>;

      var form = document.getElementById('vx-blog-form');
      form.addEventListener('submit', function(e){
        e.preventDefault();
        var msg = document.getElementById('vxb-msg');
        msg.className = 'alert-vx d-none mb-3';
        var titulo = document.getElementById('vxb-titulo').value.trim();
        var contenido = document.getElementById('vxb-contenido').value.trim();
        if (!titulo || !contenido) { msg.textContent='Completa título y contenido.'; msg.className='alert-vx alert-error mb-3'; return; }

        var btn = document.getElementById('vxb-submit');
        btn.disabled = true; btn.textContent = 'Enviando...';
        fetch(API + 'blog/crear', {
          method:'POST',
          headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
          body: JSON.stringify({ titulo: titulo, contenido: contenido, categoria: document.getElementById('vxb-categoria').value })
        }).then(function(r){ return r.json(); }).then(function(d){
          if (d.success) {
            form.style.display='none';
            document.getElementById('vx-blog-success').style.display='';
          } else {
            msg.textContent = d.message || 'No se pudo enviar el artículo.'; msg.className='alert-vx alert-error mb-3';
            btn.disabled=false; btn.textContent='Enviar para revisión';
          }
        }).catch(function(){ msg.textContent='Error de red.'; msg.className='alert-vx alert-error mb-3'; btn.disabled=false; btn.textContent='Enviar para revisión'; });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
} );

/**
 * [vx_blog_moderacion] — panel de moderación (solo administradores).
 * Lista los artículos pendientes con botones Aprobar / Rechazar.
 */
add_shortcode( 'vx_blog_moderacion', function (): string {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '<div class="container py-5"><p class="text-muted">No tienes acceso a esta sección.</p></div>';
    }

    $api   = rest_url( VX_REST_NAMESPACE . '/' );
    $nonce = wp_create_nonce( 'wp_rest' );

    $pendientes = get_posts( [
        'post_type'   => 'post',
        'post_status' => 'pending',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ] );

    ob_start();
    ?>
    <section class="section-landing">
      <div class="container" style="max-width:820px">
        <div class="section-landing-head">
          <span class="section-landing-label">Moderación</span>
          <h1 class="section-landing-title">Artículos <strong>pendientes</strong></h1>
          <p class="section-landing-lead">Revisa y aprueba o rechaza los artículos enviados por los miembros.</p>
        </div>

        <?php if ( empty( $pendientes ) ) : ?>
        <div class="empty-state-vx"><i class="ti ti-checks empty-state-vx__icon"></i><p class="empty-state-vx__title">No hay artículos pendientes</p></div>
        <?php else : ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ( $pendientes as $p ) :
            $autor = get_userdata( $p->post_author );
          ?>
          <div class="card-vx vx-mod-item" data-id="<?php echo (int) $p->ID; ?>" style="padding:16px;display:flex;gap:14px;align-items:flex-start">
            <div style="flex:1;min-width:0">
              <h3 style="margin:0 0 4px;font-size:16px"><?php echo esc_html( $p->post_title ); ?></h3>
              <p style="margin:0 0 8px;font-size:12px;color:var(--color-text-secondary)">Por <?php echo esc_html( $autor ? $autor->display_name : '—' ); ?> · <?php echo esc_html( get_the_date( '', $p ) ); ?></p>
              <p style="margin:0 0 12px;font-size:13px;color:var(--color-text-secondary)"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $p->post_content ), 30 ) ); ?></p>
              <div class="d-flex gap-2">
                <button class="btn-vx btn-primary-vx btn-vx-sm vxb-aprobar">✓ Aprobar</button>
                <button class="btn-vx btn-ghost-vx btn-vx-sm vxb-rechazar">Rechazar</button>
                <a href="<?php echo esc_url( get_preview_post_link( $p ) ); ?>" target="_blank" rel="noopener" class="btn-vx btn-ghost-vx btn-vx-sm">Ver</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <script>
    (function(){
      var API = <?php echo wp_json_encode( $api ); ?>;
      var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
      function act(item, accion){
        var id = item.dataset.id;
        item.style.opacity = '.5';
        fetch(API + 'blog/' + id + '/' + accion, { method:'POST', headers:{'X-WP-Nonce':NONCE} })
          .then(function(r){ return r.json(); }).then(function(d){
            if (d.success) { item.remove(); }
            else { alert('Error'); item.style.opacity='1'; }
          }).catch(function(){ alert('Error de red'); item.style.opacity='1'; });
      }
      document.querySelectorAll('.vxb-aprobar').forEach(function(b){
        b.addEventListener('click', function(){ act(this.closest('.vx-mod-item'), 'aprobar'); });
      });
      document.querySelectorAll('.vxb-rechazar').forEach(function(b){
        b.addEventListener('click', function(){ if(confirm('¿Rechazar este artículo? Volverá a borrador.')) act(this.closest('.vx-mod-item'), 'rechazar'); });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
} );
