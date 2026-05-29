<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// [vx_login] — formulario de login + registro con tabs
add_shortcode( 'vx_login', function (): string {
    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/dashboard/' ) );
        exit;
    }

    $tab   = isset( $_GET['tab'] ) && 'registro' === $_GET['tab'] ? 'registro' : 'login';
    $error = isset( $_GET['error'] ) ? sanitize_key( $_GET['error'] ) : '';

    ob_start();
    ?>
    <div class="vx-auth-page">
        <div class="container-sm py-5">
            <div class="vx-auth-card">

                <div class="vx-auth-card__logo mb-4">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/vitrinexo.svg' ); ?>" alt="Vitrinexo" height="40">
                </div>

                <!-- Tabs -->
                <div class="vx-tabs mb-4">
                    <button class="vx-tab <?php echo 'login' === $tab ? 'vx-tab--active' : ''; ?>" data-tab="login">Iniciar sesión</button>
                    <button class="vx-tab <?php echo 'registro' === $tab ? 'vx-tab--active' : ''; ?>" data-tab="registro">Crear cuenta</button>
                </div>

                <?php if ( $error ) : ?>
                    <div class="vx-alert vx-alert--error mb-3">
                        <?php
                        $error_messages = [
                            'credenciales_invalidas' => 'Email o contraseña incorrectos.',
                            'usuario_no_encontrado'  => 'No encontramos ese usuario.',
                            'token_invalido'         => 'El enlace de activación es inválido o expiró.',
                            'cuenta_pendiente'       => 'Tu cuenta está pendiente de verificación.',
                        ];
                        echo esc_html( $error_messages[ $error ] ?? 'Ha ocurrido un error.' );
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Panel Login -->
                <div class="vx-tab-panel <?php echo 'login' === $tab ? 'vx-tab-panel--active' : ''; ?>" data-panel="login">
                    <form id="vx-login-form" class="vx-form" novalidate>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="login-email">Correo electrónico</label>
                            <input type="email" id="login-email" name="email" class="vx-form__input" required autocomplete="email">
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="login-password">
                                Contraseña
                                <a href="<?php echo esc_url( home_url( '/recuperar-contrasena/' ) ); ?>" class="vx-form__label-link">¿Olvidaste tu contraseña?</a>
                            </label>
                            <input type="password" id="login-password" name="password" class="vx-form__input" required autocomplete="current-password">
                        </div>
                        <div id="vx-login-error" class="vx-alert vx-alert--error d-none"></div>
                        <button type="submit" class="btn-vx btn-vx--primary w-100 mt-3">Iniciar sesión</button>
                    </form>
                </div>

                <!-- Panel Registro -->
                <div class="vx-tab-panel <?php echo 'registro' === $tab ? 'vx-tab-panel--active' : ''; ?>" data-panel="registro">
                    <form id="vx-registro-form" class="vx-form" novalidate>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="vx-form__group">
                                    <label class="vx-form__label" for="reg-nombre">Nombre</label>
                                    <input type="text" id="reg-nombre" name="nombre" class="vx-form__input" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="vx-form__group">
                                    <label class="vx-form__label" for="reg-apellido">Apellido</label>
                                    <input type="text" id="reg-apellido" name="apellido" class="vx-form__input" required>
                                </div>
                            </div>
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="reg-email">Correo electrónico</label>
                            <input type="email" id="reg-email" name="email" class="vx-form__input" required autocomplete="email">
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="reg-password">Contraseña <small class="text-muted">(mínimo 8 caracteres)</small></label>
                            <input type="password" id="reg-password" name="password" class="vx-form__input" required minlength="8">
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="reg-pais">País <small class="text-muted">(opcional)</small></label>
                            <input type="text" id="reg-pais" name="pais" class="vx-form__input">
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="reg-empresa">Empresa <small class="text-muted">(opcional)</small></label>
                            <input type="text" id="reg-empresa" name="empresa" class="vx-form__input">
                        </div>
                        <div id="vx-registro-error" class="vx-alert vx-alert--error d-none"></div>
                        <button type="submit" class="btn-vx btn-vx--primary w-100 mt-3">Crear cuenta</button>
                        <p class="vx-form__terms mt-2 text-center">
                            Al registrarte aceptas nuestros <a href="#">Términos de uso</a> y <a href="#">Política de privacidad</a>.
                        </p>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

// [vx_confirmar_correo] — pantalla de "revisa tu correo"
add_shortcode( 'vx_confirmar_correo', function (): string {
    $user_id = get_current_user_id();
    $email   = $user_id ? wp_get_current_user()->user_email : '';

    ob_start();
    ?>
    <div class="vx-flow-page">
        <div class="container-sm py-5">
            <div class="vx-flow-card text-center">
                <div class="vx-flow-card__icon mb-4">
                    <i class="ti ti-mail-check"></i>
                </div>
                <h1 class="vx-flow-card__title">Revisa tu correo</h1>
                <p class="vx-flow-card__desc">
                    Enviamos un enlace de activación a <strong><?php echo esc_html( $email ); ?></strong>.<br>
                    Haz clic en el enlace para activar tu cuenta.
                </p>
                <p class="text-muted mt-3">¿No recibiste el correo?</p>
                <?php if ( $user_id ) : ?>
                    <button id="vx-reenviar-token" class="btn-vx btn-vx--ghost">Reenviar correo</button>
                    <div id="vx-reenviar-msg" class="vx-alert mt-3 d-none"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

// [vx_verificacion_pendiente] — pantalla de espera de verificación manual
add_shortcode( 'vx_verificacion_pendiente', function (): string {
    ob_start();
    ?>
    <div class="vx-flow-page">
        <div class="container-sm py-5">
            <div class="vx-flow-card text-center">
                <div class="vx-flow-card__icon mb-4">
                    <i class="ti ti-clock-hour-4"></i>
                </div>
                <h1 class="vx-flow-card__title">Verificación en proceso</h1>
                <p class="vx-flow-card__desc">
                    Tu solicitud fue recibida y está siendo revisada por nuestro equipo.<br>
                    Te notificaremos por correo cuando sea aprobada.
                </p>
                <p class="text-muted mt-3">El proceso toma entre 24 y 72 horas hábiles.</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

// [vx_recuperar_contrasena] — solicitar reseteo
add_shortcode( 'vx_recuperar_contrasena', function (): string {
    ob_start();
    ?>
    <div class="vx-auth-page">
        <div class="container-sm py-5">
            <div class="vx-auth-card">
                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="vx-back-link mb-4 d-block">
                    <i class="ti ti-arrow-left"></i> Volver al login
                </a>
                <h1 class="vx-auth-card__title">Recuperar contraseña</h1>
                <p class="text-muted mb-4">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
                <form id="vx-recuperar-form" class="vx-form" novalidate>
                    <div class="vx-form__group">
                        <label class="vx-form__label" for="rec-email">Correo electrónico</label>
                        <input type="email" id="rec-email" name="email" class="vx-form__input" required>
                    </div>
                    <div id="vx-recuperar-msg" class="vx-alert d-none mt-2"></div>
                    <button type="submit" class="btn-vx btn-vx--primary w-100 mt-3">Enviar enlace</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

// [vx_nueva_contrasena] — ingresar nueva contraseña con el token de WP
add_shortcode( 'vx_nueva_contrasena', function (): string {
    $key    = isset( $_GET['key'] )   ? sanitize_text_field( wp_unslash( $_GET['key'] ) )   : '';
    $login  = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';
    $result = check_password_reset_key( $key, $login );

    ob_start();
    if ( is_wp_error( $result ) ) {
        ?>
        <div class="container-sm py-5 text-center">
            <div class="vx-alert vx-alert--error">El enlace de recuperación es inválido o ha expirado. <a href="<?php echo esc_url( home_url( '/recuperar-contrasena/' ) ); ?>">Solicitar uno nuevo</a>.</div>
        </div>
        <?php
    } else {
        ?>
        <div class="vx-auth-page">
            <div class="container-sm py-5">
                <div class="vx-auth-card">
                    <h1 class="vx-auth-card__title">Nueva contraseña</h1>
                    <form id="vx-nueva-pass-form" class="vx-form" novalidate>
                        <input type="hidden" name="key"   value="<?php echo esc_attr( $key ); ?>">
                        <input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="new-pass">Nueva contraseña</label>
                            <input type="password" id="new-pass" name="password" class="vx-form__input" required minlength="8">
                        </div>
                        <div class="vx-form__group">
                            <label class="vx-form__label" for="new-pass-confirm">Confirmar contraseña</label>
                            <input type="password" id="new-pass-confirm" name="password_confirm" class="vx-form__input" required minlength="8">
                        </div>
                        <div id="vx-nueva-pass-msg" class="vx-alert d-none mt-2"></div>
                        <button type="submit" class="btn-vx btn-vx--primary w-100 mt-3">Guardar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    return ob_get_clean();
} );

// [vx_conexion_aceptada] — confirmación de aceptación
add_shortcode( 'vx_conexion_aceptada', function (): string {
    ob_start();
    ?>
    <div class="vx-flow-page">
        <div class="container-sm py-5">
            <div class="vx-flow-card text-center">
                <div class="vx-flow-card__icon vx-flow-card__icon--success mb-4">
                    <i class="ti ti-circle-check"></i>
                </div>
                <h1 class="vx-flow-card__title">¡Conexión aceptada!</h1>
                <p class="vx-flow-card__desc">Se notificó al otro miembro. Ahora pueden ver sus datos de contacto mutuamente.</p>
                <a href="<?php echo esc_url( home_url( '/conexiones/' ) ); ?>" class="btn-vx btn-vx--primary mt-4">Ver mis conexiones</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

// [vx_conexion_rechazada] — confirmación de rechazo (discreta)
add_shortcode( 'vx_conexion_rechazada', function (): string {
    ob_start();
    ?>
    <div class="vx-flow-page">
        <div class="container-sm py-5">
            <div class="vx-flow-card text-center">
                <div class="vx-flow-card__icon mb-4">
                    <i class="ti ti-check"></i>
                </div>
                <h1 class="vx-flow-card__title">Respuesta registrada</h1>
                <p class="vx-flow-card__desc">Tu respuesta fue registrada correctamente.</p>
                <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="btn-vx btn-vx--ghost mt-4">Ir al inicio</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );
