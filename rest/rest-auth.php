<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

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
            'pais'     => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
            'empresa'  => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
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
        // Ya estaba activo → redirigir a onboarding o dashboard
        wp_safe_redirect( $user->is_onboarding_completo()
            ? home_url( '/dashboard/' )
            : home_url( '/onboarding/' ) );
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
    $pais     = $request->get_param( 'pais' ) ?? '';
    $empresa  = $request->get_param( 'empresa' ) ?? '';

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

    // Guardar empresa inicial en meta temporal (para el onboarding paso 3)
    if ( $empresa ) {
        update_user_meta( $user_id, 'vx_empresa_inicial', $empresa );
    }

    // Iniciar flujo de verificación
    VX_Verification::start( $user_id, $email );

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
