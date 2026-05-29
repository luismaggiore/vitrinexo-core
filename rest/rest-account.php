<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── AJAX Login (wp_ajax_nopriv) ─────────────────────────────────────────────

add_action( 'wp_ajax_nopriv_vx_ajax_login', 'vx_ajax_login_handler' );
add_action( 'wp_ajax_vx_ajax_login',        'vx_ajax_login_handler' );

function vx_ajax_login_handler(): void
{
    check_ajax_referer( 'vx_ajax_login', 'nonce' );

    $email    = sanitize_email( wp_unslash( $_POST['email']    ?? '' ) );
    $password = wp_unslash( $_POST['password'] ?? '' );

    if ( ! $email || ! $password ) {
        wp_send_json_error( [ 'message' => 'Campos requeridos.' ], 400 );
    }

    $user = get_user_by( 'email', $email );

    if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
        wp_send_json_error( [ 'message' => 'Credenciales incorrectas.' ], 401 );
    }

    $vx_user = VX_User::get( $user->ID );

    if ( ! $vx_user ) {
        wp_send_json_error( [ 'message' => 'Usuario no encontrado.' ], 404 );
    }

    if ( 'pendiente' === $vx_user->get_estado() ) {
        $tipo = $vx_user->get_tipo_verificacion();
        wp_send_json_error( [
            'message'  => 'Cuenta pendiente de verificación.',
            'redirect' => 'automatica' === $tipo
                ? home_url( '/confirmar-correo/' )
                : home_url( '/verificacion-pendiente/' ),
        ], 403 );
    }

    if ( 'rechazado' === $vx_user->get_estado() ) {
        wp_send_json_error( [ 'message' => 'Tu cuenta no fue aprobada.' ], 403 );
    }

    wp_set_auth_cookie( $user->ID, false );
    wp_set_current_user( $user->ID );

    $redirect = $vx_user->is_onboarding_completo()
        ? home_url( '/dashboard/' )
        : home_url( '/onboarding/' );

    wp_send_json_success( [ 'redirect' => $redirect ] );
}

// ─── REST: Guardar perfil propio ──────────────────────────────────────────────

add_action( 'rest_api_init', function () {

    register_rest_route( VX_REST_NAMESPACE, '/perfil/guardar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_perfil_guardar',
        'permission_callback' => 'is_user_logged_in',
    ] );

    register_rest_route( VX_REST_NAMESPACE, '/empresa/crear', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_empresa_crear',
        'permission_callback' => 'is_user_logged_in',
    ] );

} );

function vx_rest_empresa_crear( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $data    = $request->get_json_params() ?: [];

    $nombre = sanitize_text_field( $data['nombre'] ?? '' );
    if ( ! $nombre ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'El nombre de la empresa es requerido.' ], 400 );
    }

    $post_id = wp_insert_post( [
        'post_title'  => $nombre,
        'post_type'   => 'vx_empresa',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ] );

    if ( is_wp_error( $post_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'Error al crear la empresa.' ], 500 );
    }

    update_post_meta( $post_id, 'vx_user_id',              $user_id );
    update_post_meta( $post_id, 'vx_cargo',               sanitize_text_field( $data['cargo']                   ?? '' ) );
    update_post_meta( $post_id, 'vx_descripcion',         sanitize_textarea_field( $data['descripcion']         ?? '' ) );
    update_post_meta( $post_id, 'vx_descripcion_cliente', sanitize_textarea_field( $data['descripcion_cliente'] ?? '' ) );
    update_post_meta( $post_id, 'vx_sector',              sanitize_text_field( $data['sector']                  ?? '' ) );
    update_post_meta( $post_id, 'vx_web',                 esc_url_raw( $data['web']                             ?? '' ) );
    update_post_meta( $post_id, 'vx_linkedin',            esc_url_raw( $data['linkedin']                        ?? '' ) );

    return new WP_REST_Response( [ 'success' => true, 'empresa_id' => $post_id ], 200 );
}

function vx_rest_perfil_guardar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $data    = $request->get_json_params() ?: [];

    $personal = [
        'bio'                => [ VX_User_Meta::BIO,                'sanitize_textarea_field' ],
        'ciudad'             => [ VX_User_Meta::CIUDAD,             'sanitize_text_field' ],
        'pais'               => [ VX_User_Meta::PAIS,               'sanitize_text_field' ],
        'contacto_preferido' => [ VX_User_Meta::CONTACTO_PREFERIDO, 'sanitize_text_field' ],
        'offer_texto'        => [ VX_User_Meta::OFFER_TEXTO,        'sanitize_textarea_field' ],
        'seek_texto'         => [ VX_User_Meta::SEEK_TEXTO,         'sanitize_textarea_field' ],
    ];

    foreach ( $personal as $key => [ $meta_key, $sanitizer ] ) {
        if ( array_key_exists( $key, $data ) ) {
            update_user_meta( $user_id, $meta_key, $sanitizer( $data[ $key ] ) );
        }
    }

    if ( array_key_exists( 'offer_tags', $data ) && is_array( $data['offer_tags'] ) ) {
        update_user_meta( $user_id, VX_User_Meta::OFFER_TAGS, array_map( 'sanitize_text_field', $data['offer_tags'] ) );
    }
    if ( array_key_exists( 'seek_tags', $data ) && is_array( $data['seek_tags'] ) ) {
        update_user_meta( $user_id, VX_User_Meta::SEEK_TAGS, array_map( 'sanitize_text_field', $data['seek_tags'] ) );
    }
    if ( array_key_exists( 'comunidad_out2b', $data ) ) {
        update_user_meta( $user_id, VX_User_Meta::COMUNIDAD_OUT2B, $data['comunidad_out2b'] ? '1' : '0' );
    }
    if ( array_key_exists( 'comunidad_senior', $data ) ) {
        update_user_meta( $user_id, VX_User_Meta::COMUNIDAD_SENIOR, $data['comunidad_senior'] ? '1' : '0' );
    }

    if ( ! empty( $data['empresas'] ) && is_array( $data['empresas'] ) ) {
        foreach ( $data['empresas'] as $emp ) {
            $emp_id = absint( $emp['id'] ?? 0 );
            if ( ! $emp_id ) continue;

            $post = get_post( $emp_id );
            if ( ! $post || 'vx_empresa' !== $post->post_type || (int) $post->post_author !== $user_id ) continue;

            if ( isset( $emp['nombre'] ) && $emp['nombre'] !== '' ) {
                wp_update_post( [ 'ID' => $emp_id, 'post_title' => sanitize_text_field( $emp['nombre'] ) ] );
            }
            if ( isset( $emp['cargo'] ) )               update_post_meta( $emp_id, 'vx_cargo',               sanitize_text_field( $emp['cargo'] ) );
            if ( isset( $emp['descripcion'] ) )         update_post_meta( $emp_id, 'vx_descripcion',         sanitize_textarea_field( $emp['descripcion'] ) );
            if ( isset( $emp['descripcion_cliente'] ) ) update_post_meta( $emp_id, 'vx_descripcion_cliente', sanitize_textarea_field( $emp['descripcion_cliente'] ) );
            if ( isset( $emp['sector'] ) )              update_post_meta( $emp_id, 'vx_sector',              sanitize_text_field( $emp['sector'] ) );
            if ( isset( $emp['web'] ) )                 update_post_meta( $emp_id, 'vx_web',                 esc_url_raw( $emp['web'] ) );
            if ( isset( $emp['linkedin'] ) )            update_post_meta( $emp_id, 'vx_linkedin',            esc_url_raw( $emp['linkedin'] ) );
        }
    }

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

// ─── REST: Cambiar contraseña ─────────────────────────────────────────────────

add_action( 'rest_api_init', function () {

    register_rest_route( VX_REST_NAMESPACE, '/cuenta/cambiar-password', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_cambiar_password',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'password_actual' => [ 'required' => true ],
            'password_nuevo'  => [ 'required' => true ],
        ],
    ] );

    register_rest_route( VX_REST_NAMESPACE, '/cuenta/reset-password', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_reset_password',
        'permission_callback' => '__return_true',
        'args' => [
            'key'      => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'login'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_user' ],
            'password' => [ 'required' => true ],
        ],
    ] );

} );

function vx_rest_cambiar_password( WP_REST_Request $request ): WP_REST_Response
{
    $user_id  = get_current_user_id();
    $actual   = $request->get_param( 'password_actual' );
    $nuevo    = $request->get_param( 'password_nuevo' );

    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'usuario_no_encontrado' ], 404 );
    }

    if ( ! wp_check_password( $actual, $user->user_pass, $user_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'password_actual_incorrecta' ], 400 );
    }

    if ( strlen( $nuevo ) < 8 ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'password_muy_corta' ], 400 );
    }

    wp_set_password( $nuevo, $user_id );
    wp_set_auth_cookie( $user_id, false );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_reset_password( WP_REST_Request $request ): WP_REST_Response
{
    $key      = $request->get_param( 'key' );
    $login    = $request->get_param( 'login' );
    $password = $request->get_param( 'password' );

    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'token_invalido' ], 400 );
    }

    if ( strlen( $password ) < 8 ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'password_muy_corta' ], 400 );
    }

    reset_password( $user, $password );

    return new WP_REST_Response( [
        'success'  => true,
        'redirect' => home_url( '/login/?reset=ok' ),
    ], 200 );
}

// ─── AJAX: Reset password request (WP built-in) ──────────────────────────────

add_action( 'wp_ajax_nopriv_vx_reset_password', 'vx_ajax_reset_password_request' );

function vx_ajax_reset_password_request(): void
{
    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

    if ( ! $email ) {
        wp_send_json_error( [ 'message' => 'Email requerido.' ] );
    }

    $user = get_user_by( 'email', $email );

    // Always respond positively to avoid email enumeration
    if ( $user ) {
        retrieve_password( $user->user_login );
    }

    wp_send_json_success();
}
