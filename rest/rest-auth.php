<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // GET /aprobar-usuario — aprueba usuario vía token de email
    register_rest_route( VX_REST_NAMESPACE, '/aprobar-usuario', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_aprobar_usuario',
        'permission_callback' => '__return_true',
        'args' => [
            'uid'   => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // GET /rechazar-usuario — rechaza usuario vía token de email
    register_rest_route( VX_REST_NAMESPACE, '/rechazar-usuario', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_rechazar_usuario',
        'permission_callback' => '__return_true',
        'args' => [
            'uid'   => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // GET /activar — valida token, activa cuenta, redirige
    register_rest_route( VX_REST_NAMESPACE, '/activar', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_activar_cuenta',
        'permission_callback' => '__return_true',
        'args' => [
            'uid'   => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // POST /reenviar-token — reenvía el email de confirmación
    register_rest_route( VX_REST_NAMESPACE, '/reenviar-token', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_reenviar_token',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /registrar — crea un usuario nuevo
    register_rest_route( VX_REST_NAMESPACE, '/registrar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_registrar',
        'permission_callback' => '__return_true',
        'args' => [
            'nombre'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'apellido' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'email'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
            'password' => [ 'required' => true ],
            'pais'     => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'empresa'  => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'telefono' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
            'cargo'    => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
            'linkedin' => [ 'required' => false, 'sanitize_callback' => 'sanitize_url' ],
        ],
    ] );

} );

function vx_rest_activar_cuenta( WP_REST_Request $request ): void
{
    $user_id = $request->get_param( 'uid' );
    $token   = $request->get_param( 'token' );

    $user = VX_User::get( $user_id );

    if ( ! $user ) {
        wp_safe_redirect( home_url( '/login/?error=usuario_no_encontrado' ) );
        exit;
    }

    if ( 'activo' === $user->get_estado() ) {
        wp_safe_redirect( $user->is_onboarding_completo()
            ? home_url( '/dashboard/' )
            : home_url( '/onboarding/' ) );
        exit;
    }

    // Fix: cuenta rechazada no puede reactivarse con token — invalidar token e informar
    if ( 'rechazado' === $user->get_estado() ) {
        delete_user_meta( $user_id, VX_User_Meta::TOKEN_CONFIRMACION );
        delete_user_meta( $user_id, VX_User_Meta::TOKEN_EXPIRA );
        wp_safe_redirect( home_url( '/login/?error=cuenta_rechazada' ) );
        exit;
    }

    if ( ! VX_Verification::validate_token( $user_id, $token ) ) {
        wp_safe_redirect( home_url( '/confirmar-correo/?error=token_invalido' ) );
        exit;
    }

    VX_Verification::activate_account( $user_id );

    // Iniciar sesión automáticamente
    wp_set_auth_cookie( $user_id, false );
    wp_set_current_user( $user_id );

    wp_safe_redirect( home_url( '/onboarding/' ) );
    exit;
}

function vx_rest_reenviar_token(): WP_REST_Response
{
    $user_id = get_current_user_id();
    $result  = VX_Verification::resend_token( $user_id );

    return new WP_REST_Response( [ 'success' => $result ], $result ? 200 : 400 );
}

function vx_rest_registrar( WP_REST_Request $request ): WP_REST_Response
{
    $nombre   = $request->get_param( 'nombre' );
    $apellido = $request->get_param( 'apellido' );
    $email    = $request->get_param( 'email' );
    $password = $request->get_param( 'password' );
    $pais     = $request->get_param( 'pais' )     ?? '';
    $empresa  = $request->get_param( 'empresa' )  ?? '';
    $telefono = $request->get_param( 'telefono' ) ?? '';
    $cargo    = $request->get_param( 'cargo' )    ?? '';
    $linkedin = $request->get_param( 'linkedin' ) ?? '';

    if ( empty( trim( $pais ) ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'pais_requerido', 'message' => 'El país es obligatorio.' ], 400 );
    }

    if ( empty( trim( $empresa ) ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'empresa_requerida', 'message' => 'El nombre de empresa es obligatorio.' ], 400 );
    }

    if ( empty( trim( $telefono ) ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'telefono_requerido', 'message' => 'El teléfono es obligatorio.' ], 400 );
    }

    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'email_invalido' ], 400 );
    }

    if ( email_exists( $email ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'email_en_uso' ], 409 );
    }

    if ( strlen( $password ) < 8 ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'password_muy_corta' ], 400 );
    }

    $user_id = wp_create_user( $email, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => $user_id->get_error_code() ], 500 );
    }

    // Establecer rol subscriber
    $user = new WP_User( $user_id );
    $user->set_role( 'subscriber' );

    // Guardar meta iniciales
    update_user_meta( $user_id, VX_User_Meta::NOMBRE,              $nombre );
    update_user_meta( $user_id, VX_User_Meta::APELLIDO,            $apellido );
    update_user_meta( $user_id, VX_User_Meta::PAIS,                $pais );
    update_user_meta( $user_id, VX_User_Meta::ESTADO,              'pendiente' );
    update_user_meta( $user_id, VX_User_Meta::ONBOARDING_COMPLETO, false );
    update_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO,     1 );
    update_user_meta( $user_id, VX_User_Meta::PLAN,                'gratuito' );
    update_user_meta( $user_id, VX_User_Meta::PLAN_ESTADO,         'activo' );

    // Guardar teléfono obligatorio
    update_user_meta( $user_id, VX_User_Meta::TELEFONO, sanitize_text_field( $telefono ) );

    // Fix Gap 3: generar slug desde el registro para que el perfil no quede huérfano
    // si el usuario abandona el onboarding antes del paso 2.
    if ( $nombre && $apellido && class_exists( 'VX_Slug_Helper' ) ) {
        $slug = VX_Slug_Helper::generate( $nombre, $apellido, $user_id );
        update_user_meta( $user_id, VX_User_Meta::PERFIL_SLUG, $slug );
    }

    // Guardar empresa inicial en meta temporal (para el onboarding paso 3)
    if ( $empresa ) {
        update_user_meta( $user_id, 'vx_empresa_inicial', $empresa );
    }

    // Guardar cargo y LinkedIn
    if ( $cargo ) {
        update_user_meta( $user_id, 'vx_cargo_inicial', sanitize_text_field( $cargo ) );
    }
    if ( $linkedin ) {
        update_user_meta( $user_id, VX_User_Meta::LINKEDIN, esc_url_raw( $linkedin ) );
    }

    // Iniciar flujo de verificación
    VX_Verification::start( $user_id, $email );

    // Notificar a joao y marcia de cada nuevo registro
    $token_aprobar  = bin2hex( random_bytes( 32 ) );
    $token_rechazar = bin2hex( random_bytes( 32 ) );
    update_user_meta( $user_id, VX_User_Meta::TOKEN_APROBAR,  $token_aprobar );
    update_user_meta( $user_id, VX_User_Meta::TOKEN_RECHAZAR, $token_rechazar );

    $url_aprobar  = rest_url( VX_REST_NAMESPACE . '/aprobar-usuario' ) . '?uid=' . $user_id . '&token=' . $token_aprobar;
    $url_rechazar = rest_url( VX_REST_NAMESPACE . '/rechazar-usuario' ) . '?uid=' . $user_id . '&token=' . $token_rechazar;

    $admins  = [ 'joao@vitrinexo.com', 'marcia@vitrinexo.com' ];
    $asunto  = '[Vitrinexo] Nuevo registro: ' . $nombre . ' ' . $apellido;
    $cuerpo  = '
<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px">
  <img src="' . esc_url( get_template_directory_uri() ) . '/assets/img/vitrinexo.svg" alt="Vitrinexo" style="height:28px;margin-bottom:24px">
  <h2 style="color:#1a2335;margin-bottom:4px">Nuevo registro</h2>
  <p style="color:#5e6b7a;margin-top:0">Alguien completó el formulario de inscripción.</p>

  <table style="width:100%;border-collapse:collapse;margin:24px 0">
    <tr><td style="padding:10px 12px;background:#f3f9fd;border-radius:6px 6px 0 0;font-weight:600;color:#3d444e;width:130px">Nombre</td>
        <td style="padding:10px 12px;background:#f3f9fd;border-radius:6px 6px 0 0;color:#3d444e">' . esc_html( $nombre . ' ' . $apellido ) . '</td></tr>
    <tr><td style="padding:10px 12px;border-top:1px solid #d7e4ef;font-weight:600;color:#3d444e">Email</td>
        <td style="padding:10px 12px;border-top:1px solid #d7e4ef;color:#3d444e">' . esc_html( $email ) . '</td></tr>
    <tr><td style="padding:10px 12px;border-top:1px solid #d7e4ef;font-weight:600;color:#3d444e">Empresa</td>
        <td style="padding:10px 12px;border-top:1px solid #d7e4ef;color:#3d444e">' . esc_html( $empresa ) . '</td></tr>
    <tr><td style="padding:10px 12px;border-top:1px solid #d7e4ef;font-weight:600;color:#3d444e">Cargo</td>
        <td style="padding:10px 12px;border-top:1px solid #d7e4ef;color:#3d444e">' . esc_html( $cargo ) . '</td></tr>
    <tr><td style="padding:10px 12px;border-top:1px solid #d7e4ef;font-weight:600;color:#3d444e">País</td>
        <td style="padding:10px 12px;border-top:1px solid #d7e4ef;color:#3d444e">' . esc_html( $pais ) . '</td></tr>
    ' . ( $linkedin ? '<tr><td style="padding:10px 12px;border-top:1px solid #d7e4ef;font-weight:600;color:#3d444e">LinkedIn</td>
        <td style="padding:10px 12px;border-top:1px solid #d7e4ef"><a href="' . esc_url( $linkedin ) . '" style="color:#00aeb8">' . esc_html( $linkedin ) . '</a></td></tr>' : '' ) . '
  </table>

  <div style="display:flex;gap:12px;margin:32px 0">
    <a href="' . esc_url( $url_aprobar ) . '"
       style="display:inline-block;background:#00aeb8;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;font-size:15px;margin-right:12px">
      ✓ Aprobar
    </a>
    <a href="' . esc_url( $url_rechazar ) . '"
       style="display:inline-block;background:#ff4d82;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;font-size:15px">
      ✗ Rechazar
    </a>
  </div>

  <p style="color:#5e6b7a;font-size:13px">Estos botones son de un solo uso. Una vez utilizados no podrán volver a usarse.</p>
</div>';

    $headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: Vitrinexo <hola@vitrinexo.com>' ];
    foreach ( $admins as $admin ) {
        wp_mail( $admin, $asunto, $cuerpo, $headers );
    }

    $tipo_verificacion = VX_Domain_Helper::is_institutional( $email ) ? 'automatica' : 'manual';

    return new WP_REST_Response( [
        'success'            => true,
        'user_id'            => $user_id,
        'tipo_verificacion'  => $tipo_verificacion,
        'redirect'           => 'automatica' === $tipo_verificacion
            ? home_url( '/confirmar-correo/' )
            : home_url( '/verificacion-pendiente/' ),
    ], 201 );
}

function vx_rest_aprobar_usuario( WP_REST_Request $request ): void
{
    $user_id = $request->get_param( 'uid' );
    $token   = $request->get_param( 'token' );
    $stored  = get_user_meta( $user_id, VX_User_Meta::TOKEN_APROBAR, true );

    if ( ! $stored || ! hash_equals( $stored, $token ) ) {
        wp_die( '<h2>Enlace inválido o ya utilizado.</h2>' );
    }

    delete_user_meta( $user_id, VX_User_Meta::TOKEN_APROBAR );
    delete_user_meta( $user_id, VX_User_Meta::TOKEN_RECHAZAR );

    $user = VX_User::get( $user_id );
    if ( ! $user ) wp_die( '<h2>Usuario no encontrado.</h2>' );

    if ( 'activo' === $user->get_estado() ) {
        wp_die( '<h2>Este usuario ya estaba aprobado.</h2>' );
    }

    VX_Verification::approve_manual( $user_id );

    wp_die( '<h2 style="font-family:sans-serif;color:#00aeb8">✓ Usuario aprobado</h2><p style="font-family:sans-serif">' . esc_html( $user->get_nombre_completo() ) . ' (' . esc_html( $user->get_email() ) . ') ha sido aprobado y recibirá el email de activación.</p>' );
}

function vx_rest_rechazar_usuario( WP_REST_Request $request ): void
{
    $user_id = $request->get_param( 'uid' );
    $token   = $request->get_param( 'token' );
    $stored  = get_user_meta( $user_id, VX_User_Meta::TOKEN_RECHAZAR, true );

    if ( ! $stored || ! hash_equals( $stored, $token ) ) {
        wp_die( '<h2>Enlace inválido o ya utilizado.</h2>' );
    }

    delete_user_meta( $user_id, VX_User_Meta::TOKEN_APROBAR );
    delete_user_meta( $user_id, VX_User_Meta::TOKEN_RECHAZAR );

    $user = VX_User::get( $user_id );
    if ( ! $user ) wp_die( '<h2>Usuario no encontrado.</h2>' );

    if ( 'rechazado' === $user->get_estado() ) {
        wp_die( '<h2>Este usuario ya estaba rechazado.</h2>' );
    }

    update_user_meta( $user_id, VX_User_Meta::ESTADO, 'rechazado' );

    // Notificar al usuario
    VX_Mailer::send(
        $user->get_email(),
        'Tu solicitud en Vitrinexo',
        'rechazo',
        [ 'nombre' => $user->get_nombre() ]
    );

    wp_die( '<h2 style="font-family:sans-serif;color:#ff4d82">✗ Usuario rechazado</h2><p style="font-family:sans-serif">' . esc_html( $user->get_nombre_completo() ) . ' (' . esc_html( $user->get_email() ) . ') ha sido rechazado.</p>' );
}
